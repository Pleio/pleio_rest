<?php
namespace PleioRest\Controllers;

class Groups {

    /**
     * @SWG\Get(
     *     path="/api/groups/mine",
     *     tags={"groups"},
     *     summary="Retrieve a list of groups the current user is member of.",
     *     description="Retrieve a list of groups the current user is member of.",
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
     *             @SWG\Items(ref="#/definitions/Group")
     *         ),
     *     )
     * )
     */
    public function getMine($request, $response, $args) {
        $params = $request->getQueryParams();
        $limit = (int) $params['limit'];
        $offset = (int) $params['offset'];

        if (!$limit | $limit < 0 | $limit > 50) {
            $limit = 20;
        }

        $current_user = elgg_get_logged_in_user_entity();

        $dbprefix = elgg_get_config("dbprefix");
        $options = array(
            'type' => 'group',
            'limit' => $limit,
            'offset' => $offset,
            'joins' => array("JOIN {$dbprefix}groups_entity ge ON e.guid = ge.guid"),
            'order_by' => 'ge.name',
            'relationship' => 'member',
            'relationship_guid' => $current_user->guid
        );

        $entities = array();
        foreach (elgg_get_entities_from_relationship($options) as $entity) {
            $entities[] = $this->parseGroup($entity);
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
     * @SWG\Get(
     *     path="/api/groups",
     *     tags={"groups"},
     *     summary="Retrieve a list of all the groups on the site.",
     *     description="Retrieve a list of all the groups on the site.",
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
     *             @SWG\Items(ref="#/definitions/Group")
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
            'type' => 'group',
            'offset' => $offset,
            'limit' => $limit,
            'joins' => array("JOIN {$dbprefix}groups_entity ge ON e.guid = ge.guid"),
            'order_by' => 'ge.name'
        );

        $entities = array();
        foreach (elgg_get_entities($options) as $entity) {
            $entities[] = $this->parseGroup($entity);
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
     *  definition="Group",
     *  required={"guid","name", "membership"},
     *  @SWG\Property(property="guid", type="integer"),
     *  @SWG\Property(property="name", type="string"),
     *  @SWG\Property(property="description", type="string"),
     *  @SWG\Property(property="membership", type="string", description="Can be open or closed."),
     *  @SWG\Property(property="time_created", type="string")
     * )
     */
    private function parseGroup(\ElggGroup $group) {
        return array(
            'guid' => $group->guid,
            'name' => $group->name,
            'description' => $group->description,
            'membership' => $group->membership === 2 ? "open" : "closed",
            'time_created' => date('c', $group->time_created) // ISO-8601
        );
    }
}