<?php
require_once(dirname(__FILE__) . "/lib/events.php");

require_once(dirname(__FILE__) . "/../../vendor/autoload.php");
spl_autoload_register('pleio_rest_autoloader');
function pleio_rest_autoloader($class) {
    $filename = "classes/" . str_replace('\\', '/', $class) . '.php';
    if (file_exists(dirname(__FILE__) . "/" . $filename)) {
        include($filename);
    }
}

function pleio_rest_init() {
    elgg_register_page_handler("api", "pleio_rest_page_handler");
    elgg_register_page_handler("oauth", "pleio_rest_page_handler");

    elgg_register_event_handler("created", "river", "pleio_rest_created_river_event_handler");
}

elgg_register_event_handler('init', 'system', 'pleio_rest_init');

function pleio_rest_page_handler($url) {
    $app = new PleioRest\Application();
    $app->run();

    return true;
}