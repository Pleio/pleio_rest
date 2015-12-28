<?php
namespace PleioRest\Controllers;

class Authentication {

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
        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();

        $authRequest = \OAuth2\Request::createFromGlobals();
        $authResponse = $server->handleTokenRequest($authRequest);

        $response = $response->withStatus($authResponse->getStatusCode());
        $response->write(json_encode($authResponse->getParameters(), JSON_PRETTY_PRINT));

        return $response;
    }
}