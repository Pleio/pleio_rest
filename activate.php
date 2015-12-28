<?php
$result = get_data("show tables like 'oauth_%'");

if (count($result) === 0) {
    run_sql_script(__DIR__ . '/sql/create_tables.sql');
}