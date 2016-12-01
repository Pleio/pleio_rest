CREATE TABLE IF NOT EXISTS elgg_push_notifications_count (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_guid` bigint(20) NOT NULL,
    `site_guid` bigint(20) NOT NULL,
    `container_guid` bigint(20) NOT NULL,
    `count` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_guid` (`user_guid`,`container_guid`)
);

CREATE TABLE IF NOT EXISTS elgg_push_notifications_subscriptions (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `user_guid` bigint(20) NOT NULL,
    `client_id` varchar(100) NOT NULL,
    `service` enum('gcm','apns', 'wns') NOT NULL,
    `device_id` varchar(100) NOT NULL,
    `token` varchar(512) NOT NULL,
    PRIMARY KEY (`id`)
);