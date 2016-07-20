CREATE TABLE IF NOT EXISTS elgg_push_notifications_count (id bigint(20) NOT NULL, user_guid bigint(20) NOT NULL, site_guid bigint(20) NOT NULL, container_guid bigint(20) NOT NULL, count int(11) NOT NULL);
ALTER TABLE `elgg_push_notifications_count` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `user_guid` (`user_guid`,`container_guid`);
ALTER TABLE `elgg_push_notifications_count` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS elgg_push_notifications_subscriptions (id bigint(20) NOT NULL, user_guid bigint(20) NOT NULL, client_id varchar(100) NOT NULL, service enum('gcm','apns', 'wns') NOT NULL, device_id varchar(100) NOT NULL, token varchar(100) NOT NULL);
ALTER TABLE `elgg_push_notifications_subscriptions` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `token` (`service`, `token`);
ALTER TABLE `elgg_push_notifications_subscriptions` MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;