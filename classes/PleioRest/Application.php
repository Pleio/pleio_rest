<?php
namespace PleioRest;

/**
 * @SWG\Info(
 *   version="1.0.0",
 *   title="Pleio REST API",
 *   description="This document describes the Pleio REST API. The results of the API (so the contents) are limited to the site that is requested except for the site calls. When requesting an access token, the same token can be used for all the sites.",
 *   termsOfService="https://www.pleio.nl",
 *   @SWG\Contact(
 *     email="helpdesk@pleio.nl"
 *   ),
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
 *   name="version",
 *   description="Retrieve API version information.",
 * )
 */

use \PleioRest\AuthenticationMiddleware as AuthenticationMiddleware;
use \PleioRest\JsonRenderer as JsonRenderer;

class Application {
    public function run() {
        $app = new \Slim\App(['settings' => ['displayErrorDetails' => true]]);

        $app->add(new AuthenticationMiddleware());

        $app->post('/oauth/v2/token', 'PleioRest\Controllers\Authentication::getToken');

        $app->get('/api', 'PleioRest\Controllers\Version::getVersion');
        $app->get('/api/doc', 'PleioRest\Controllers\Documentation::getDocumentation');

        $app->get('/api/activities', 'PleioRest\Controllers\Activities:getAll');
        $app->get('/api/activities/mark_seen', 'PleioRest\Controllers\Activities:markSeen');
        $app->get('/api/activities/group/{guid}', 'PleioRest\Controllers\Activities:getGroup');
        $app->get('/api/activities/group/{guid}/mark_seen', 'PleioRest\Controllers\Activities:markSeen');

        $app->get('/api/sites', 'PleioRest\Controllers\Sites:getAll');
        $app->get('/api/sites/mine', 'PleioRest\Controllers\Sites:getMine');

        $app->get('/api/groups', 'PleioRest\Controllers\Groups:getAll');
        $app->get('/api/groups/mine', 'PleioRest\Controllers\Groups:getMine');

        $app->run();
    }
}
