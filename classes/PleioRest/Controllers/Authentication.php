<?php
namespace PleioRest\Controllers;

class Authentication {

    public function authorize($request, $response, $args) {
        global $CONFIG;
        if ($CONFIG->pleio) {
            return $response->wihStatus(404)->write(json_encode([
                "pretty_message" => "Could not find endpoint"
            ]));
        }

        $idp = get_input("idp");
        $is_master = elgg_get_plugin_setting("is_master", "pleio_rest");

        if ($is_master !== "yes") {
            throw new Exception("Could not authorize this user, as the server is not a master.");
        }

        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();

        $authRequest = \OAuth2\Request::createFromGlobals();
        $authResponse = new \OAuth2\Response();

        if (!$server->validateAuthorizeRequest($authRequest, $authResponse)) {
            $authResponse->send();
        }

        if (!elgg_is_logged_in()) {
            if ($idp) {
                $user = Authentication::handleSAMLAuthorization();
            } else {
                $uri = $request->getUri();
                $scheme = $uri->getScheme();
                $host = $uri->getHost();
                forward("/login?returnto=" . urlencode("${scheme}://${host}" . $_SERVER["REQUEST_URI"]));
            }
        } else {
            $user = elgg_get_logged_in_user_entity();
        }

        if ($user) {
            $server->handleAuthorizeRequest($authRequest, $authResponse, true, elgg_get_logged_in_user_guid());

            $status = $authResponse->getStatusCode();
            if ($status == 302) {
                $response = $response->withStatus(302)->withHeader("Location", $authResponse->getHttpHeader("Location"));
            } else {
                $response = $response->write(json_encode($authResponse->getParameters(), JSON_PRETTY_PRINT));
            }
        } else {
            $response = $response->withStatus(302)->withHeader("Location", "/");
        }

        return $response;
    }

    public function handleSAMLAuthorization() {
        $source = get_input("idp");
        $label = simplesaml_get_source_label($source);
        if (!simplesaml_is_enabled_source($source)) {
            register_error("Invalid SAML source provided.");
            return false;
        }

        $saml_auth = new \SimpleSAML_Auth_Simple($source);
        $saml_auth->requireAuth();
        $attributes = $saml_auth->getAttributes();

        $requiredAttributes = Authentication::getRequiredAttributes($attributes);
        if (!$requiredAttributes) {
            register_error("Could not find all the required SAML attributes.");
            return false;
        }

        $user = simplesaml_find_user($source, $attributes);

        if (!$user) {
            $user = Authentication::findUserByEmail($requiredAttributes["email"]);
            if ($user) {
                simplesaml_link_user($user, $source, $requiredAttributes["externalId"]);
            }
        }

        if (!$user) {
            $user = Authentication::registerUserBySAML($requiredAttributes);
            simplesaml_link_user($user, $source, $requiredAttributes["externalId"]);
        }

        if (!elgg_get_user_validation_status($user->guid)) {
            elgg_set_user_validation_status($user->guid, true, "simplesaml");
        }

        try {
            login($user, false);
        } catch (\LoginException $e) {
            register_error($e->getMessage());
            return false;
        }

        return $user;
    }

    public function findUserByEmail($email) {
        $hidden = access_show_hidden_entities(true);
        $users = get_user_by_email($email);
        access_show_hidden_entities($hidden);

        if ($users) {
            $user = $users[0];
            return $user;
        }

        return false;
    }

    public function getRequiredAttributes($attributes) {
        $name = Authentication::extractSAMLParameter("elgg:firstname", $attributes);
        $email = Authentication::extractSAMLParameter("elgg:email", $attributes);
        $externalId = Authentication::extractSAMLParameter("elgg:external_id", $attributes);

        if ($name && $email && $externalId) {
            return [
                "name" => $name,
                "email" => $email,
                "externalId" => $externalId
            ];
        }

        return false;
    }

    public function registerUserBySAML($attributes) {
        $username = simplesaml_generate_username_from_email($attributes["email"]);
        $password = generate_random_cleartext_password();

        try {
            $user_guid = register_user($username, $password, $attributes["name"], $attributes["email"]);
            $new_user = get_user($user_guid);

            elgg_set_user_validation_status($new_user->guid, true, "simplesaml");

            $params = array(
                "user" => $new_user,
                "password" => $password,
                "friend_guid" => "",
                "invitecode" => ""
            );

            if (!elgg_trigger_plugin_hook("register", "user", $params, TRUE)) {
                $ia = elgg_set_ignore_access(true);
                $new_user->delete();
                elgg_set_ignore_access($ia);
                throw new RegistrationException(elgg_echo("registerbad"));
            }

            return $new_user;
        } catch (\RegistrationException $e) {
            register_error($e->getMessage());
            return false;
        }
    }

    public function extractSAMLParameter($key, $attributes) {
        $item = elgg_extract($key, $attributes, "");
        if (is_array($item)) {
            $item = $item[0];
        }

        return $item;
    }

    /**
     * @SWG\Post(
     *     path="/oauth/v2/token",
     *     tags={"authentication"},
     *     summary="Request a new access token.",
     *     description="Request a new access token for the specific user.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="username",
     *         in="query",
     *         description="The username of the specific user.",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="query",
     *         description="The password of the specific user.",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="client_id",
     *         in="query",
     *         description="The id of the client application.",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="client_secret",
     *         in="query",
     *         description="The secret of the client application.",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     ),
     * )
     */
    public function getToken($request, $response, $args) {
        global $CONFIG;
        if ($CONFIG->pleio) {
            return $response->wihStatus(404)->write(json_encode([
                "pretty_message" => "Could not find endpoint"
            ]));
        }

        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();

        $authRequest = \OAuth2\Request::createFromGlobals();
        $authResponse = $server->handleTokenRequest($authRequest);

        $response = $response->withStatus($authResponse->getStatusCode());
        $response->write(json_encode($authResponse->getParameters(), JSON_PRETTY_PRINT));

        return $response;
    }
}