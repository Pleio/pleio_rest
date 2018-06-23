<?php
namespace PleioRest\Controllers;

class Files {
    /**
     * @SWG\Get(
     *     path="/api/groups/{group_guid}/files",
     *     security={{"oauth2": {"all"}}},
     *     tags={"files"},
     *     summary="Find files and folders in a specific group.",
     *     description="Find files and folders from a specific group.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="group_guid",
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
        $dbprefix = elgg_get_config('dbprefix');

        if (!$limit | $limit < 0 | $limit > 100) {
            $limit = 20;
        }

        $options = array(
            'type' => 'object',
            'subtype' => 'folder',
            'container_guid' => $group->guid,
            'limit' => $limit,
            'offset' => $offset,
            'metadata_name_value_pairs' => array(
                'name' => 'parent_guid',
                'value' => $container_guid ? $container_guid : 0
            ),
            'joins' => array(
                "LEFT JOIN {$dbprefix}objects_entity oe ON e.guid = oe.guid"
            ),
            'order_by' => 'oe.title'
        );

        $folders = elgg_get_entities_from_metadata($options);
        if (!$folders) {
            $folders = array();
        }

        $options['count'] = true;
        $total = elgg_get_entities_from_metadata($options);

        $options = array(
            'type' => 'object',
            'subtype' => 'file',
            'container_guid' => $group->guid,
            'limit' => $limit - count($folders),
            'offset' => max(0, $offset - $total),
            'joins' => array(
                "LEFT JOIN {$dbprefix}objects_entity oe ON e.guid = oe.guid",
                "LEFT JOIN {$dbprefix}entity_relationships r ON e.guid = r.guid_two AND r.relationship = 'folder_of'"
            ),
            'order_by' => 'oe.title'
        );

        if ($container_guid) {
            $options['wheres'] = "r.guid_one = '{$container_guid}'";
        } else {
            $options['wheres'] = "r.guid_one IS NULL";
        }

        if ($limit - count($folders) > 0) {
            $files = elgg_get_entities($options);
            if (!$files) {
                $files = array();
            }
        } else {
            $files = array();
        }

        $options['count'] = true;
        $total += elgg_get_entities_from_metadata($options);

        $entities = array();
        foreach (array_merge($folders, $files) as $entity) {
            $entities[] = $this->parseObject($entity);
        }

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode(array(
            'total' => $total,
            'entities' => $entities
        ), JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Post(
     *     path="/api/groups/{group_guid}/files",
     *     security={{"oauth2": {"all"}}},
     *     tags={"files"},
     *     summary="Create a new file or folder in a specific group.",
     *     description="Create a new file or folder in a specific group.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="group_guid",
     *         in="path",
     *         description="The guid of the specific group",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="container_guid",
     *         in="query",
     *         description="Create the new element in a subfolder with this guid",
     *         default=0,
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation.",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Could not find the requested group or subfolder.",
     *     ),
     *     @SWG\Response(
     *         response=403,
     *         description="Could not create file or folder due to restricted permissions.",
     *     )
     * )
     */
    function postGroup($request, $response, $args) {

    }

    /**
     * @SWG\Delete(
     *     path="/api/groups/{group_guid}/files",
     *     security={{"oauth2": {"all"}}},
     *     tags={"files"},
     *     summary="Remove a specific file or folder in a group.",
     *     description="Remove a specific file or folder in a group.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="group_guid",
     *         in="path",
     *         description="The guid of the specific group",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="guid",
     *         in="query",
     *         description="The guid of the object to be deleted",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation.",
     *     ),
     *     @SWG\Response(
     *         response=404,
     *         description="Could not find the group or item.",
     *     ),
     *     @SWG\Response(
     *         response=403,
     *         description="Could not delete file or folder due to restricted permissions.",
     *     )
     * )
     */

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