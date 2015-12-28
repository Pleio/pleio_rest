<?php
namespace PleioRest\Controllers;

class Activities {

    public function __construct() {
        $currentUser = elgg_get_logged_in_user_entity();

        $this->latestViews = unserialize($currentUser->getPrivateSetting('river_latest_view'));
        if (!$this->latestViews) {
            $this->latestViews = array();
        }
    }

    /**
     * @SWG\Get(
     *     path="/api/activities/group/{guid}",
     *     tags={"activities"},
     *     summary="Find activities in a specific group.",
     *     description="Find the most recent activities from a specific group.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="guid",
     *         in="path",
     *         description="The guid of the specific group",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
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
     *             @SWG\Items(ref="#/definitions/Activity")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Could not find the requested group.",
     *     )
     * )
     */
    public function getGroup($request, $response, $args) {
        $guid = (int) $args['guid'];
        $group = get_entity($guid);

        if (!$group | !$group instanceof \ElggGroup) {
            return $response->withStatus(404);
        }

        $params = $request->getQueryParams();
        $limit = (int) $params['limit'];
        $offset = (int) $params['offset'];

        if (!$limit | $limit < 0 | $limit > 50) {
            $limit = 20;
        }

        $dbprefix = elgg_get_config("dbprefix");
        $options = array(
            'offset' => $offset,
            'limit' => $limit,
            'joins' => array("JOIN {$dbprefix}entities e1 ON e1.guid = rv.object_guid"),
            'wheres' => array("(e1.container_guid = $group->guid)"),
        );

        $entities = array();
        foreach (elgg_get_river($options) as $entity) {
            $entities[] = $this->parseActivity($entity);
        }

        $options['count'] = true;
        $total = elgg_get_river($options);

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Get(
     *     path="/api/activities",
     *     tags={"activities"},
     *     summary="Find activities of the whole site.",
     *     description="Find the most recent activities of the whole site.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Offset the results by",
     *         default=0,
     *         required=false,
     *         type="integer",
     *         format="int64",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="multi"
     *     ),
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit the results by",
     *         default=20,
     *         required=false,
     *         type="integer",
     *         format="int64",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="multi"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation.",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Activity")
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
            'offset' => $offset,
            'limit' => $limit
        );

        $entities = array();
        foreach (elgg_get_river($options) as $entity) {
            $entities[] = $this->parseActivity($entity);
        }

        $options['count'] = true;
        $total = elgg_get_river($options);

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }


    /**
     * @SWG\Post(
     *     path="/api/activities/group/{guid}/mark_seen",
     *     tags={"activities"},
     *     summary="Mark the activities in the specific group as seen.",
     *     description="Mark the activities in the specific group as seen till the most recent activity.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="guid",
     *         in="path",
     *         description="The guid of the specific group",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Could not find the requested group."
     *     )
     * )
     */

    /**
     * @SWG\Post(
     *     path="/api/activities/mark_seen",
     *     tags={"activities"},
     *     summary="Mark the activities as seen.",
     *     description="Mark the activities as seen till the most recent activity.",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     )
     * )
     */
    public function markSeen($request, $response, $args) {
        $guid = (int) $args['guid'];

        if ($guid) {
            $entity = get_entity($guid);
            if (!$entity instanceof \ElggGroup) {
                return $response->withStatus(404);
            }
        } else {
            $entity = elgg_get_site_entity();
        }

        $this->latestViews[$entity->guid] = time();

        $currentUser = elgg_get_logged_in_user_entity();
        if (!$currentUser->setPrivateSetting('river_latest_view', serialize($this->latestViews))) {
            return $response->withStatus(500);
        }
    }

    /**
     * @SWG\Definition(
     *  definition="Activity",
     *  required={"id","subject"},
     *  @SWG\Property(property="id", type="integer"),
     *  @SWG\Property(property="subject", type="string")
     * )
     */
    private function parseActivity(\ElggRiverItem $activity) {
        $subject = get_entity($activity->subject_guid);
        $object = get_entity($activity->object_guid);

        return array(
            'id' => $activity->id,
            'subject' => array(
                'guid' => $activity->subject_guid,
                'name' => $subject->name
            ),
            'action_type' => $activity->action_type,
            'object' => array(
                'guid' => $activity->object_guid,
                'type' => $object->getSubtype(),
                'title' => $object->title
            ),
            'time_created' => date('c', $activity->posted), // ISO-8601,
            'is_unseen' => $this->isUnseen($activity)
        );
    }

    private function isUnseen(\ElggRiverItem $activity) {
        $container = $activity->getObjectEntity()->getContainerEntity();

        if (array_key_exists($container->guid, $this->latestViews)) {
            if ($activity->posted > $this->latestViews[$container->guid]) {
                return true;
            } else {
                return false;
            }
        } else {
            return false; // tracking is not enabled yet as there is no time marker for this container.
        }
    }
}