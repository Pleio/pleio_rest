<?php
namespace PleioRest\Services;

class PushNotificationHandler {
    public function __construct() {
        $this->dbprefix = elgg_get_config('dbprefix');
        $this->factory = new \PleioRest\Services\ServiceFactory();
    }

    public function fanOutNotifications($river) {
        foreach ($this->getSubscribers($river) as $subscriber) {
            $this->incrementNotificationCount($subscriber, $river);
            $this->sendNotification($subscriber, $river);
        }
    }

    public function sendNotification($user, $river) {
        $unreadGroupsCount = $this->getUnreadGroupsCount($user);
        if (!$unreadGroupsCount) {
            return true;
        }

        $subscriptions = $this->getSubscriptions($user);
        foreach ($subscriptions as $subscription) {
            $service = $this->factory->getService($subscription);

            if ($service) {
                $response = $service->push($subscription, array(
                    'title' => $this->generateTitle($river),
                    'river' => $river,
                    'count' => $unreadGroupsCount
                ));

                // @todo: housekeeping, purge old tokens
            }
        }
    }

    public function generateTitle($river) {
        $subject = $river->getSubjectEntity();
        $object = $river->getObjectEntity();
        if ($object) {
            $container = $object->getContainerEntity();
            if ($object->title) {
                $objectTitle = html_entity_decode($object->title, ENT_QUOTES);
            } else {
                $objectTitle = elgg_get_excerpt(html_entity_decode($object->description, ENT_QUOTES), 60);
            }
        }

        switch($river->action_type) {
            case "create":
                $title = $subject->name . " heeft " . $objectTitle . " geplaatst in " . $container->name;
                break;
            case "update":
                $title = $subject->name . " heeft " . $objectTitle . " vernieuwd in " . $container->name;
                break;
            case "join":
                $title = $subject->name . " is lid geworden van " . $objectTitle;
                break;
            case "reply":
                $title = $subject->name . " heeft gereageerd op " . $objectTitle;
                break;
            default:
                $title = "";
                break;
        }

        return $title;
    }

    public function getSubscribers($river) {
        $container = $river->getObjectEntity()->getContainerEntity();

        $members = $this->getMembers($container);
        foreach ($members as $member) {
                if ($this->isRegistered($member->guid)) {
                    yield $member;
                }
        }
    }

    public function isRegistered($user_guid) {
        return get_data_row("SELECT user_guid FROM {$this->dbprefix}push_notifications_subscriptions WHERE user_guid = {$user_guid}");
    }

    public function getMembers(\ElggGroup $group) {
        return get_data("SELECT guid_one AS guid FROM {$this->dbprefix}entity_relationships WHERE relationship = 'member' AND guid_two = {$group->guid}");
    }

    public function getSubscriptions($user) {
        return get_data("SELECT * FROM {$this->dbprefix}push_notifications_subscriptions WHERE user_guid = {$user->guid}");
    }

    public function addSubscription(\ElggUser $user, $client_id, $service, $device_id, $token) {
        $client_id = sanitize_string($client_id);
        $service = sanitize_string($service);
        $device_id = sanitize_string($device_id);
        $token = sanitize_string($token);

        // remove old tokens from the user with the specific device
        delete_data("DELETE FROM {$this->dbprefix}push_notifications_subscriptions WHERE client_id = \"{$client_id}\" AND service = \"{$service}\" AND device_id = \"{$device_id}\" AND user_guid = \"{$user->guid}\"");

        // remove subscription of the specific token (user change)
        delete_data("DELETE FROM {$this->dbprefix}push_notifications_subscriptions WHERE client_id = \"{$client_id}\" AND service = \"{$service}\" AND token = \"{$token}\"");

        return insert_data("INSERT INTO {$this->dbprefix}push_notifications_subscriptions (user_guid, client_id, service, device_id, token) VALUES (\"{$user->guid}\", \"{$client_id}\", \"{$service}\", \"{$device_id}\", \"{$token}\")");
    }

    public function removeSubscription(\ElggUser $user, $client_id, $service, $device_id) {
        $client_id = sanitize_string($client_id);
        $service = sanitize_string($service);
        $device_id = sanitize_string($device_id);
        return delete_data("DELETE FROM {$this->dbprefix}push_notifications_subscriptions WHERE user_guid = \"{$user->guid}\" AND client_id = \"{$client_id}\" AND service = \"{$service}\" AND device_id = \"{$device_id}\"");
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

    public function getUnreadGroupsCount($user, $site = null) {
        if ($site) {
            $row = get_data_row("SELECT SUM(count) AS count FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid} AND site_guid = {$site->guid}");
        } else {
            $row = get_data_row("SELECT SUM(count) AS count FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid} GROUP BY user_guid");
        }

        return (int) $row->count;
    }

    public function markContainerAsRead(\ElggUser $user, \ElggEntity $container) {
        return delete_data("DELETE FROM {$this->dbprefix}push_notifications_count WHERE user_guid = {$user->guid} AND container_guid = {$container->guid}");
    }
}