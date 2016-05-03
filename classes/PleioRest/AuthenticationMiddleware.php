<?php
namespace PleioRest;

class AuthenticationMiddleware {
    public function __invoke($request, $response, $next) {
        $params = $request->getQueryParams();

        if (
            $params['handler'] === "oauth" |
            ($params['handler'] === "api" && !isset($params['page'])) |
            ($params['handler'] === "api" && $params['page'] === "doc") |
            ($params['handler'] === "api" && $params['page'] === "doc/swagger") |
            ($params['handler'] === "api" && $params['page'] === "users/me/login_token")
        ) {
            $response = $next($request, $response);
            return $response;
        }

        $factory = new AuthenticationServerFactory();
        $server = $factory->getServer();

        if (!$server->verifyResourceRequest(\OAuth2\Request::createFromGlobals())) {
            $response = $response->withStatus(403);
            $response = $response->withHeader('Content-type', 'application/json');
            return $response->write(json_encode(array(
                'status' => 403,
                'error' => 'invalid_access_token',
                'pretty_error' => 'You did not supply an OAuth access token or the token is invalid.'
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $token = $server->getAccessTokenData(\OAuth2\Request::createFromGlobals());
        $user = get_user($token['user_id']);

        if (!$user) {
            $response = $response->withStatus(403);
            $response = $response->withHeader('Content-type', 'application/json');
            return $response->write(json_encode(array(
                'status' => 403,
                'error' => 'invalid_access_token',
                'pretty_error' => 'You did not supply an OAuth access token or the token is invalid.'
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if (!login($user)) {
            $response = $response->withStatus(403);
            $response = $response->withHeader('Content-type', 'application/json');
            return $response->write(json_encode(array(
                'status' => 403,
                'error' => 'could_not_login',
                'pretty_error' => 'Could not login the user associated with this token. Probably the account is banned.'
            ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $response = $next($request, $response);
        return $response;
    }
}