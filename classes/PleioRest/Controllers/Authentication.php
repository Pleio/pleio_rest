<?php
namespace PleioRest\Controllers;

class Authentication {

    public function authorize($request, $response, $args) {
        global $CONFIG;
        if ($CONFIG->pleio) {
            return $resopnse->wihStatus(404)->write(json_encode([
                "pretty_message" => "Could not find endpoint"
            ]));
        }

        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();

        $authRequest = \OAuth2\Request::createFromGlobals();
        $authResponse = new \OAuth2\Response();

        if (!$server->validateAuthorizeRequest($authRequest, $authResponse)) {
            $authResponse->send();
        }

        if (elgg_is_logged_in()) {
            $server->handleAuthorizeRequest($authRequest, $authResponse, true, elgg_get_logged_in_user_guid());

            $status = $authResponse->getStatusCode();
            if ($status == 302) {
                $response = $response->withStatus(302)->withHeader("Location", $authResponse->getHttpHeader("Location"));
            } else {
                $response = $response->write(json_encode($authResponse->getParameters(), JSON_PRETTY_PRINT));
            }
        } else {
            $response = $response->withStatus(403)->write(json_encode([
                "pretty_message" => "Not logged in"
            ]));
        }

        return $response;
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
            return $resopnse->wihStatus(404)->write(json_encode([
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