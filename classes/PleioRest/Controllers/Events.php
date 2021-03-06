<?php
namespace PleioRest\Controllers;

class Events {
    /**
     * @SWG\Get(
     *     path="/api/groups/{guid}/events",
     *     security={{"oauth2": {"all"}}},
     *     tags={"events"},
     *     summary="Find events in a specific group.",
     *     description="Find the most recent events from a specific group.",
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
     *             @SWG\Items(ref="#/definitions/Event")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Could not find the requested event.",
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
            'type' => 'object',
            'subtype' => 'event',
            'offset' => $offset,
            'limit' => $limit,
            'container_guid' => $group->guid,
            'metadata_name_value_pairs' => array(
                array('name' => 'start_day', 'operand' => '>=', 'value' => mktime(0, 0, 1))
            ),
            'order_by_metadata' => array(
                'name' => 'start_day',
                'direction' => 'ASC',
                'as' => 'integer'
            )
        );

        $entities = array();
        foreach (elgg_get_entities_from_metadata($options) as $entity) {
            $entities[] = $this->parseEvent($entity);
        }

        $options['count'] = true;
        $total = elgg_get_entities_from_metadata($options);

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Definition(
     *  definition="Event",
     *  required={"guid","title"},
     *  @SWG\Property(property="guid", type="integer"),
     *  @SWG\Property(property="title", type="string"),
     *  @SWG\Property(property="description", type="string"),
     *  @SWG\Property(property="start_time", type="string"),
     *  @SWG\Property(property="end_time", type="string"),
     *  @SWG\Property(property="url", type="string"),
     *  @SWG\Property(property="icon_url", type="string"),
     *  @SWG\Property(property="time_created", type="string")
     * )
     */
    private function parseEvent(\ElggObject $event) {
        $start_time = mktime(
            date("H", $event->start_time),
            date("i", $event->start_time),
            0,
            date("n", $event->start_day),
            date("j", $event->start_day),
            date("Y", $event->start_day)
        );

        if ($event->end_ts) {
            $end_time = date('c', $event->end_ts);
        } else {
            $end_time = null;
        }

        return array(
            'guid' => $event->guid,
            'title' => html_entity_decode($event->title, ENT_QUOTES),
            'description' => html_entity_decode($event->description, ENT_QUOTES),
            'start_time' => date('c', $start_time), // ISO-8601
            'end_time' => $end_time,
            'url' => $event->getURL(),
            'icon_url' => $event->getIconURL(),
            'time_created' => date('c', $event->time_created)
        );
    }
}