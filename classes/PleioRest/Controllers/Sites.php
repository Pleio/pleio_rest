<?php
namespace PleioRest\Controllers;

class Sites {

    public function __construct() {
        $this->handler = new \PleioRest\Services\PushNotificationHandler();
    }

    /**
     * @SWG\Get(
     *     path="/api/sites/mine",
     *     security={{"oauth2": {"scope"}}},
     *     tags={"sites"},
     *     summary="Retrieve a list of sites the current user is member of and that have the API enabled.",
     *     description="Retrieve a list of sites the current user is member of and that have the API enabled.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Offset the results by",
     *         default=0,
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit the results by",
     *         default=20,
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation.",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Site")
     *         ),
     *     )
     * )
     */
    public function getMine($request, $response, $args) {
        $params = $request->getQueryParams();
        $current_user = elgg_get_logged_in_user_entity();

        $entities = array();
        foreach (subsite_manager_get_user_subsites($current_user->guid) as $entity) {
            if (pleio_rest_subsite_plugin_enabled($entity)) {
                $entities[] = $this->parseSite($entity);
            }
        }

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => count($entities),
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Get(
     *     path="/api/sites",
     *     security={{"oauth2": {"scope"}}},
     *     tags={"sites"},
     *     summary="Retrieve a list of all sites.",
     *     description="Retrieve a list of all sites.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Offset the results by",
     *         default=0,
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit the results by",
     *         default=20,
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation.",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Site")
     *         ),
     *     )
     * )
     */
    public function getAll($request, $response, $args) {
        $params = $request->getQueryParams();
        $limit = (int) $params['limit'];
        $offset = (int) $params['offset'];

        if (!$limit | $limit < 0 | $limit > 50) {
            $limit = 20;
        }

        $dbprefix = elgg_get_config("dbprefix");
        $options = array(
            'type' => 'site',
            'offset' => $offset,
            'limit' => $limit,
            'joins' => array("JOIN {$dbprefix}sites_entity ge ON e.guid = ge.guid"),
            'order_by' => 'ge.name'
        );

        $entities = array();
        foreach (elgg_get_entities($options) as $entity) {
            $entities[] = $this->parseSite($entity);
        }

        $options['count'] = true;
        $total = elgg_get_entities($options);

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Definition(
     *  definition="Site",
     *  required={"guid","name", "url", "membership"},
     *  @SWG\Property(property="guid", type="integer"),
     *  @SWG\Property(property="name", type="string"),
     *  @SWG\Property(property="url", type="string"),
     *  @SWG\Property(property="membership", type="string", description="Can be open or closed."),
     *  @SWG\Property(property="icon_url", type="string"),
     *  @SWG\Property(property="time_created", type="string", description="In ISO-8601 format.")
     * )
     */
    private function parseSite(\ElggSite $site) {
        $user = elgg_get_logged_in_user_entity();

        return array(
            'guid' => $site->guid,
            'name' => $site->name,
            'url' => $site->url,
            'membership' => $site instanceof Subsite ? $site->getMembership() : "open",
            'icon_url' => $site->getIconURL(),
            'groups_unread_count' => $this->handler->getUnreadGroupsCount($user),
            'time_created' => date('c', $site->time_created) // ISO-8601
        );
    }
}