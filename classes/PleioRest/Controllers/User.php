<?php
namespace PleioRest\Controllers;

class User {

    /**
     * @SWG\Post(
     *     path="/api/users/me/register_push",
     *     security={{"oauth2": {"all"}}},
     *     tags={"user"},
     *     summary="Register application for push notifications.",
     *     description="Register an application to receive push notifications of activities.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="token",
     *         in="query",
     *         description="The token received from Google Cloud Messaging (GCM) or Apple Push Notification Gateway Service (APNS).",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="service",
     *         in="query",
     *         description="The service (gcm or apns)",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     )
     * )
     */
    public function registerPush($request, $response, $args) {
        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();
        $accessData = $server->getAccessTokenData(\OAuth2\Request::createFromGlobals());

        $vars = $request->getParsedBody();
        
        $device_id = $vars['device_id'];
        $token = $vars['token'];
        $service = $vars['service'];

        if (!in_array($service, array('gcm', 'apns'))) {
            return $response->withStatus(404);
        }

        $handler = new \PleioRest\Services\PushNotificationHandler;
        $handler->addSubscription(elgg_get_logged_in_user_entity(), $accessData['client_id'], $service, $device_id, $token);
    }
}