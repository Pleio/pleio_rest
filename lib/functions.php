<?php
function pleio_rest_subsite_plugin_enabled(Subsite $site) {
    $db_prefix = elgg_get_config('dbprefix');

    $options = array(
        'type' => 'object',
        'subtype' => 'plugin',
        'relationship_guid' => $site->guid,
        'relationship' => 'active_plugin',
        'inverse_relationship' => true,
        'site_guid' => $site->guid,
        'joins' => array("JOIN {$db_prefix}objects_entity oe on oe.guid = e.guid"),
        'selects' => array("oe.title"),
        'wheres' => array("oe.title = \"pleio_rest\"")
    );

    $plugin_enabled = elgg_get_entities_from_relationship($options);
    if (count($plugin_enabled) == 1) {
        return true;
    }

    return false;
}

?>