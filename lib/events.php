<?php

function pleio_rest_created_river_event_handler($event, $object_type, $river) {
    $container = $river->getObjectEntity()->getContainerEntity();
    $dbprefix = elgg_get_config('dbprefix');

    if (!$container instanceof ElggGroup) {
        return null;
    }

    $handler = new \PleioRest\Services\PushNotificationHandler();
    $handler->fanOutNotifications($river);
}