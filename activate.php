<?php
$result = get_data("show tables like 'oauth_%'");
if (count($result) === 0) {
    run_sql_script(__DIR__ . '/sql/oauth_tables.sql');
}

$result = get_data("show tables like 'elgg_push_notifications_%'");
if (count($result) === 0) {
    run_sql_script(__DIR__ . '/sql/push_tables.sql');
}