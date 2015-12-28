<?php
namespace PleioRest;

class AuthenticationMiddleware {
    public function __invoke($request, $response, $next) {
        $params = $request->getQueryParams();


        if (
            $params['handler'] === "oauth" |
            ($params['handler'] === "api" && !isset($params['page'])) |
            ($params['handler'] === "api" && $params['page'] === "doc")
        ) {
            $response = $next($request, $response);
            return $response;
        }

        $factory = new AuthenticationServerFactory();
        $server = $factory->getServer();

        if ($server->verifyResourceRequest(\OAuth2\Request::createFromGlobals())) {
            $response = $next($request, $response);
            return $response;
        } else {
            $response = $response->withStatus(403);
            return $response;
        }
    }
}