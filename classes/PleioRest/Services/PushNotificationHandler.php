<?php
namespace PleioRest\Services;

class PushNotificationHandler {
    public function __construct() {
        $this->dbprefix = elgg_get_config('dbprefix');
        $this->factory = new \PleioRest\Services\ServiceFactory();
    }

    // @todo: move to asynchronous RabbitMQ task
    public function fanOutNotifications($river) {
        foreach ($this->getSubscribers($river) as $subscriber) {
            $this->incrementNotificationCount($subscriber, $river);
            $this->sendNotification($subscriber);
        }
    }

    public function sendNotification($user) {
        $unreadGroupsCount = $this->getUnreadGroupsCount($user);
        if (!$unreadGroupsCount) {
            return true;
        }

        $subscriptions = $this->getSubscriptions($user);
        foreach ($subscriptions as $subscription) {
            $service = $this->factory->getService($subscription);
            if ($service) {
                $service->push($subscription, array(
                    'count' => $unreadGroupsCount
                ));

                // @todo: housekeeping, purge old tokens
            }
        }
    }

    public function getSubscribers($river) {
        $container = $river->getObjectEntity()->getContainerEntity();

        $batch = new \ElggBatch('elgg_get_entities_from_relationship', array(
            'relationship' => 'member',
            'relationship_guid' => $container->guid,
            'inverse_relationship' => true,
            'type' => 'user'
        ));

        foreach ($batch as $user) {
            if ($user === $river->getSubjectEntity()) {
                //continue;
            }
            if (!$this->isRegistered($user)) {
                continue;
            }

            yield $user;
        }
    }

    public function isRegistered($user) {
        return get_data_row("SELECT user_guid FROM {$this->dbprefix}push_notifications_subscriptions WHERE user_guid = {$user->guid}");
    }

    public function getSubscriptions($user) {
        return get_data("SELECT * FROM {$this->dbprefix}push_notifications_subscriptions WHERE user_guid = {$user->guid}");
    }

    public function addSubscription(\ElggUser $user, $client_id, $service, $token) {
        //@todo: verify stoken

        $client_id = mysql_real_escape_string($client_id);
        $service = mysql_real_escape_string($service);
        $token = mysql_real_escape_string($token);
        return insert_data("INSERT INTO {$this->dbprefix}push_notifications_subscriptions (user_guid, client_id, service, token) VALUES (\"{$user->guid}\", \"{$client_id}\", \"{$service}\", \"{$token}\")");
    }

    public function incrementNotificationCount($user, $river) {
        $container = $river->getObjectEntity()->getContainerEntity();
        return insert_data("INSERT INTO {$this->dbprefix}push_notifications_count (user_guid, site_guid, container_guid, count) VALUES ({$user->guid}, {$container->site_guid}, {$container->guid}, 1) ON DUPLICATE KEY UPDATE count=count+1");
    }

    public function getContainerUnreadCount(\ElggUser $user, \ElggEntity $container) {
        $row = get_data_row("SELECT * FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid} AND container_guid = {$container->guid}");
        if (!$row) {
            return 0;
        } else{
            return (int) $row->count;
        }
    }

    // @todo: make site sensitive
    public function getUnreadGroupsCount(\ElggUser $user, \ElggSite $site = null) {
        if ($site) {
            $row = get_data_row("SELECT COUNT(*) AS count FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid} AND site_guid = {$site->guid}");
        } else {
            $row = get_data_row("SELECT COUNT(*) AS count FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid}");
        }

        return (int) $row->count;
    }

    public function markContainerAsRead(\ElggUser $user, \ElggEntity $container) {
        return delete_data("DELETE FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid} AND container_guid = {$container->guid}");
    }
}