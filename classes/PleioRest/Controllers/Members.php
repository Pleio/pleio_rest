<?php
namespace PleioRest\Controllers;

class Members {
    /**
     * @SWG\Get(
     *     path="/api/groups/{guid}/members",
     *     security={{"oauth2": {"all"}}},
     *     tags={"members"},
     *     summary="Find members in a specific group.",
     *     description="Find the members from a specific group.",
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

        if (!$limit | $limit < 0 | $limit > 100) {
            $limit = 20;
        }

        $db_prefix = elgg_get_config('dbprefix');
        $options = array(
            'relationship' => 'member',
            'relationship_guid' => $group->guid,
            'inverse_relationship' => true,
            'type' => 'user',
            'limit' => $limit,
            'offset' => $offset,
            'joins' => array(
                "JOIN {$db_prefix}users_entity oe ON e.guid = oe.guid"
            ),
            'order_by' => 'oe.name'
        );

        $entities = array();
        foreach (elgg_get_entities_from_relationship($options) as $entity) {
            $entities[] = $this->parseUser($entity);
        }

        $options['count'] = true;
        $total = elgg_get_entities_from_relationship($options);

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Definition(
     *  definition="User",
     *  required={"guid","name"},
     *  @SWG\Property(property="guid", type="integer"),
     *  @SWG\Property(property="name", type="string"),
     *  @SWG\Property(property="icon_url", type="string"),
     *  @SWG\Property(property="url", type="string")
     * )
     */
    private function parseUser(\ElggUser $user) {
        return array(
            'guid' => $user->guid,
            'name' => html_entity_decode($user->name),
            'icon_url' => $user->getIconURL(),
            'url' => $user->getURL()
        );
    }
}