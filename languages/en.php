<?php

$english = array(
    'pleio_rest:master_slave' => "This setting allows you to configure the plugin as master or as slave. When it is master, the plugin issues it's own access tokens. When it is slave, the plugin uses a different OAuth2.0 endpoint at $CONFIG->pleio_url to issue the tokens. This is useful when using the Pleioapp, one app that views multiple sites."
);

add_translation("en", $english);