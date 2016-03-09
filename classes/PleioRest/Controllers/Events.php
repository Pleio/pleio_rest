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
            'container_guid' => $group->guid
        );

        $entities = array();
        foreach (elgg_get_entities($options) as $entity) {
            $entities[] = $this->parseEvent($entity);
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
     *  definition="Event",
     *  required={"guid","title"},
     *  @SWG\Property(property="guid", type="integer"),
     *  @SWG\Property(property="title", type="string"),
     *  @SWG\Property(property="description", type="string"),
     *  @SWG\Property(property="from", type="string"),
     *  @SWG\Property(property="to", type="string"),
     *  @SWG\Property(property="time_created", type="string")
     * )
     */
    private function parseEvent(\ElggObject $event) {
        return array(
            'guid' => $event->guid,
            'title' => $event->title,
            'description' => $event->description,
            'from' => $event->from,
            'to' => $event->to,
            'time_created' => date('c', $event->time_created) // ISO-8601,
        );
    }
}