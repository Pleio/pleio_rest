<?php
namespace PleioRest;

/**
 * @SWG\Swagger(
 *   schemes={"https"},
 *   host="www.pleio.nl"
 * )
 * @SWG\Info(
 *   version="1.0.0",
 *   title="Pleio REST API",
 *   description="This document describes the Pleio REST API. The results of the API (so the contents) are limited to the site that is requested except for the site calls. When requesting an access token, the same token can be used for all the sites.",
 *   termsOfService="https://www.pleio.nl",
 *   @SWG\Contact(
 *     email="helpdesk@pleio.nl"
 *   ),
 * )
 * @SWG\SecurityScheme(
 *   securityDefinition="oauth2",
 *   type="oauth2",
 *   tokenUrl="https://www.pleio.nl/oauth/v2/token",
 *   flow="password",
 *   scopes={"all": "Perform all actions"}
 * )
 * @SWG\Tag(
 *   name="activities",
 *   description="Retrieve the latest activities."
 * )
 * @SWG\Tag(
 *   name="authentication",
 *   description="Request a token to access protected resources."
 * )
 * @SWG\Tag(
 *   name="groups",
 *   description="Retrieve a list of groups."
 * )
 * @SWG\Tag(
 *   name="sites",
 *   description="Retrieve a list of sites.",
 * )
 * @SWG\Tag(
 *   name="events",
 *   description="Retrieve a list of events."
 * )
 * @SWG\Tag(
 *   name="members",
 *   description="Retrieve a list of members."
 * )
 * @SWG\Tag(
 *   name="files",
 *   description="Retrieve a list of files and folders."
 * )
 * @SWG\Tag(
 *   name="version",
 *   description="Retrieve API version information.",
 * )
 * @SWG\Tag(
 *   name="user",
 *   description="Perform user-based actions.",
 * )
 */

use \PleioRest\AuthenticationMiddleware as AuthenticationMiddleware;
use \PleioRest\JsonRenderer as JsonRenderer;

class Application {
    public function run() {
        $configuration = [
            'settings' => [
                'displayErrorDetails' => true,
            ],
        ];

        $c = new \Slim\Container($configuration);
        $c['notFoundHandler'] = function($c) {
            return function ($request, $response) use ($c) {
                return $c['response']->withStatus(404)
                                     ->withHeader('Content-type', 'application/json')
                                     ->write(json_encode(array(
                                        'status' => 404,
                                        'error' => 'not_found',
                                        'pretty_error' => 'Could not find the specified endpoint.'
                                    ), JSON_PRETTY_PRINT));
            };
        };

        /*$c['errorHandler'] = function($c) {
            return function ($request, $response) use ($c) {
                return $c['response']->withStatus(500)
                                     ->withHeader('Content-type', 'application/json')
                                     ->write(json_encode(array(
                                        'status' => 404,
                                        'error' => 'internal_error',
                                        'pretty_error' => 'An internal error has occured, please contact the site administrator.'
                                    ), JSON_PRETTY_PRINT));
            };
        };*/

        $app = new \Slim\App($c);
        $app->add(new AuthenticationMiddleware());

        $app->get('/oauth/v2/authorize', 'PleioRest\Controllers\Authentication::authorize');
        $app->post('/oauth/v2/token', 'PleioRest\Controllers\Authentication::getToken');

        $app->get('/api/users/me', 'PleioRest\Controllers\User:me');
        $app->post('/api/users/me/register_push', 'PleioRest\Controllers\User:registerPush');
        $app->post('/api/users/me/deregister_push', 'PleioRest\Controllers\User:deregisterPush');
        $app->post('/api/users/me/generate_token', 'PleioRest\Controllers\User:generateToken');
        $app->get('/api/users/me/login_token', 'PleioRest\Controllers\User:loginToken');

        $app->get('/api', 'PleioRest\Controllers\Version:getVersion');
        $app->get('/api/doc', 'PleioRest\Controllers\Documentation:getDocumentation');
        $app->get('/api/doc/swagger', 'PleioRest\Controllers\Documentation:getSwagger');

        $app->get('/api/sites', 'PleioRest\Controllers\Sites:getAll');
        $app->get('/api/sites/mine', 'PleioRest\Controllers\Sites:getMine');

        $app->get('/api/groups', 'PleioRest\Controllers\Groups:getAll');
        $app->get('/api/groups/mine', 'PleioRest\Controllers\Groups:getMine');

        $app->get('/api/groups/{guid}/activities', 'PleioRest\Controllers\Activities:getGroup');
        $app->post('/api/groups/{guid}/activities/mark_read', 'PleioRest\Controllers\Activities:markRead');

        $app->get('/api/groups/{guid}/events', 'PleioRest\Controllers\Events:getGroup');
        $app->get('/api/groups/{guid}/members', 'PleioRest\Controllers\Members:getGroup');
        $app->get('/api/groups/{guid}/files', 'PleioRest\Controllers\Files:getGroup');

        $app->run();
    }
}
