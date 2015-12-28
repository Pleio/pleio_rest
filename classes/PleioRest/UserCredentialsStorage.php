<?php
namespace PleioRest;

use \OAuth2\Storage\UserCredentialsInterface;

class UserCredentialsStorage implements UserCredentialsInterface {

    public function checkUserCredentials($login, $password) {
        $user = $this->getUser($login);

        // a typesafe comparisson is very important as elgg_authenticate returns true or string.
        if ($user && elgg_authenticate($user->username, $password) === true) {
            return true;
        } else {
            return false;
        }
    }

    public function getUser($login) {
        if (strpos($login, '@') !== false) {
            $users = get_user_by_email($login);
            $user = $users[0];
        } else {
            $user = get_user_by_username($login);
        }

        return $user;
    }

    public function getUserDetails($login) {
        $user = $this->getUser($login);

        if ($user) {
            return array(
                'user_id' => $user->guid,
                'scope' => false
            );
        } else {
            return false;
        }
    }
}