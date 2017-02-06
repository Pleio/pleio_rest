<?php
$plugin = $vars["entity"];

echo "<div>";
echo elgg_view("input/dropdown", array(
    "name" => "params[is_master]",
    "value" => $plugin->is_master,
    "options_values" => [
        "no" => "Slave",
        "yes" => "Master"
    ]));
echo elgg_echo("pleio_rest:master_slave");
echo "</div>";