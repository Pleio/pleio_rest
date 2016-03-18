<?php
namespace PleioRest\Controllers;

class Activities {
    /**
     * @SWG\Get(
     *     path="/api/groups/{guid}/activities",
     *     security={{"oauth2": {"all"}}},
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
        $currentUser = elgg_get_logged_in_user_entity();
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

        $handler = new \PleioRest\Services\PushNotificationHandler();

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'number_unread' => $handler->getContainerUnreadCount($currentUser, $group),
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Post(
     *     path="/api/groups/{guid}/activities/mark_read",
     *     security={{"oauth2": {"all"}}},
     *     tags={"activities"},
     *     summary="Mark the activities in the specific group as read.",
     *     description="Mark the activities in the specific group as read till the most recent activity.",
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
    public function markRead($request, $response, $args) {
        $currentUser = elgg_get_logged_in_user_entity();

        $guid = (int) $args['guid'];
        if (!$guid) {
            return $response->withStatus(404);
        }

        $container = get_entity($guid);
        if (!$container instanceof \ElggGroup) {
            return $response->withStatus(404);
        }

        $handler = new \PleioRest\Services\PushNotificationHandler();
        $handler->markContainerAsRead($currentUser, $container);
    }

    /**
     * @SWG\Definition(
     *  definition="Activity",
     *  required={"id","subject"},
     *  @SWG\Property(property="id", type="integer"),
     *  @SWG\Property(property="subject", type="string"),
     *  @SWG\Property(property="action_type", type="string"),
     *  @SWG\Property(property="object", type="string"),
     *  @SWG\Property(property="time_created", type="string")
     * )
     */
    private function parseActivity(\ElggRiverItem $activity) {
        $subject = get_entity($activity->subject_guid);
        $object = get_entity($activity->object_guid);

        if ($object instanceof \ElggWire) {
            $objectTitle = html_entity_decode($object->description);
        } else {
            $objectTitle = html_entity_decode($object->title);
        }

        return array(
            'id' => $activity->id,
            'subject' => array(
                'guid' => $activity->subject_guid,
                'name' => html_entity_decode($subject->name),
                'icon_url' => $subject->getIconURL()
            ),
            'action_type' => $activity->action_type,
            'object' => array(
                'guid' => $activity->object_guid,
                'type' => $object->getSubtype(),
                'title' => $objectTitle,
                'url' => $object->getURL()
            ),
            'time_created' => date('c', $activity->posted) // ISO-8601,
        );
    }
}