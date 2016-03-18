<?php

function pleio_rest_created_river_event_handler($event, $object_type, $river) {
    $container = $river->getObjectEntity()->getContainerEntity();
    $dbprefix = elgg_get_config('dbprefix');

    if (!$container instanceof ElggGroup) {
        return null;
    }

    if (class_exists('PleioAsyncTaskhandler')) {
        $taskHandler = new PleioAsyncTaskhandler();
        $taskHandler->schedule('pleio_rest_created_river_async', array($river->id));
    } else {
        $notHandler = new \PleioRest\Services\PushNotificationHandler();
        $notHandler->fanOutNotifications($river);
    }
}

function pleio_rest_created_river_async($river_id) {
    $ia = elgg_set_ignore_access(true);

    $river = elgg_get_river(array('id' => $river_id));
    if (count($river) === 1) {
        $river = $river[0];
        $notHandler = new \PleioRest\Services\PushNotificationHandler();
        $notHandler->fanOutNotifications($river);
    }

    elgg_set_ignore_access($ia);
}