<?php
namespace PleioRest;

use \OAuth2\Storage\UserCredentialsInterface;

class UserCredentialsStorage implements UserCredentialsInterface {
    private $tokenCache = [];

    public function checkUserCredentials($login, $password) {
        if (elgg_is_active_plugin("pleio")) {
            return $this->loginThroughPleio($login, $password);
        }

        return $this->loginThroughElgg($login, $password);
    }

    public function getUserDetails($login) {
        if (elgg_is_active_plugin("pleio")) {
            $user = $this->getUserPleio($login);
        } else {
            $user = $this->getUserElgg($login);
        }

        if ($user) {
            return [
                'user_id' => $user->guid,
                'scope' => false
            ];
        }

        return false;
    }

    public function loginThroughPleio($login, $password) {
        global $CONFIG;

        $provider = new \ModPleio\Provider([
            "clientId" => $CONFIG->pleio->client,
            "clientSecret" => $CONFIG->pleio->secret,
            "url" => $CONFIG->pleio->url,
        ]);

        try {
            // Try to get an access token using the resource owner password credentials grant.
            $accessToken = $provider->getAccessToken("password", [
                "username" => $login,
                "password" => $password
            ]);

            if ($accessToken) {
                $this->tokenCache[$login] = $accessToken;
                return true;
            }

            return false;
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            elgg_log("Could not login through Pleio " . $e->getMessage(), "ERROR");
            return false;
        }
    }

    public function getUserPleio($login) {
        global $CONFIG;

        $token = $this->tokenCache[$login];
        if (!$token) {
            return false;
        }

        $provider = new \ModPleio\Provider([
            "clientId" => $CONFIG->pleio->client,
            "clientSecret" => $CONFIG->pleio->secret,
            "url" => $CONFIG->pleio->url,
        ]);

        $resourceOwner = $provider->getResourceOwner($token);
        if (!$resourceOwner) {
            return false;
        }

        return get_user_by_pleio_guid_or_email($resourceOwner->getGuid(), $resourceOwner->getEmail());
    }

    public function loginThroughElgg($login, $password) {
        $user = $this->getUserElgg($login);

        // a typesafe comparisson is very important as elgg_authenticate returns true or string.
        if ($user && elgg_authenticate($user->username, $password) === true) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserElgg($login) {
        if (strpos($login, '@') !== false) {
            $users = get_user_by_email($login);
            $user = $users[0];
        } else {
            $user = get_user_by_username($login);
        }

        return $user;
    }
}