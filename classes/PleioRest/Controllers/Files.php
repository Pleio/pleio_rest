<?php
namespace PleioRest\Controllers;

class Files {
    /**
     * @SWG\Get(
     *     path="/api/groups/{guid}/files",
     *     security={{"oauth2": {"all"}}},
     *     tags={"files"},
     *     summary="Find files and folders in a specific group.",
     *     description="Find files and folders from a specific group.",
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
     *         name="container_guid",
     *         in="query",
     *         description="Fetch the elements of a subfolder with this guid",
     *         default=0,
     *         required=false,
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
     *             @SWG\Items(ref="#/definitions/FileFolder")
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
        $container_guid = (int) $params['container_guid'];

        if (!$limit | $limit < 0 | $limit > 50) {
            $limit = 20;
        }

        $dbprefix = elgg_get_config("dbprefix");
        $parent_guid = get_metastring_id('parent_guid');

        $options = array(
            'type' => 'object',
            'subtypes' => array('file', 'folder'),
            'offset' => $offset,
            'limit' => $limit,
            'container_guid' => $group->guid,
            'joins' => array(
                "LEFT JOIN {$dbprefix}objects_entity oe ON e.guid = oe.guid",
                "LEFT JOIN {$dbprefix}metadata md ON e.guid = md.entity_guid AND md.name_id = {$parent_guid}",
                "LEFT JOIN {$dbprefix}metastrings msv ON md.value_id = msv.id",
                "LEFT JOIN {$dbprefix}entity_relationships r ON e.guid = r.guid_two AND r.relationship = 'folder_of'"
            ),
            'order_by' => 'e.subtype DESC, oe.title'
        );

        $file = get_subtype_id('object', 'file');
        $folder = get_subtype_id('object', 'folder');

        if (!$container_guid) {
            $options['wheres'] = "(e.subtype = '{$file}' AND r.guid_one IS NULL) OR (e.subtype = '{$folder}' AND msv.string = '0')";
        } else {
            $options['wheres'] = "(e.subtype = '{$file}' AND r.guid_one = '{$container_guid}') OR (e.subtype = '{$folder}' AND msv.string = '{$container_guid}')";
        }

        $entities = array();
        foreach (elgg_get_entities($options) as $entity) {
            $entities[] = $this->parseObject($entity);
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
     *  definition="FileFolder",
     *  required={"guid","subtype","title"},
     *  @SWG\Property(property="guid", type="integer"),
     *  @SWG\Property(property="subtype", type="string", enum={"file", "folder"}),
     *  @SWG\Property(property="title", type="string"),
     *  @SWG\Property(property="time_created", type="string")
     * )
     */
    private function parseObject(\ElggObject $object) {
        $subtype = $object->getSubtype();

        $data = array(
            'guid' => $object->guid,
            'subtype' => $subtype,
            'title' => html_entity_decode($object->title, ENT_QUOTES),
            'time_created' => date('c', $object->time_created)
        );

        if ($subtype == "file") {
            $data['url'] = elgg_normalize_url('file/download/' . $object->guid);
        } else {
            $data['url'] = $object->getURL();
        }

        return $data;
    }
}