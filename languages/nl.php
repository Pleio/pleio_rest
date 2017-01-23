<?php

$dutch = array(
    'pleio_rest:master_slave' => "Met deze instelling kan de plugin als een master of slave ingesteld worden. Wanneer de plugin een master is, dan deelt de plugin zelf access tokens uit. Wanneer de plugin geen master is, dan wordt een ander OAuth2.0 endpoint op $CONFIG->pleio_url gebruikt om tokens uit te delen. Dit kan handig zijn wanneer de Pleioapp wordt gebruikt om meerdere sites in één app te bekijken."
);

add_translation("nl", $dutch);