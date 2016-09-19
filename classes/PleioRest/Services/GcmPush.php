<?php
namespace PleioRest\Services;

class GcmPush implements PushInterface {
    public function __construct($key) {
        $this->key = $key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: key=' . $key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->ch = $ch;
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function push($subscription, $message) {
        $river = $message['river'];
        $object = $river->getObjectEntity();
        if ($object) {
            $site = get_entity($river->site_guid);
            $group = $river->getContainerEntity();
        }

        $fields = array(
            'to' => $subscription->token,
            'notification' => [
                'body' => $message['title']
            ],
            'data' => [
                'count' => $message['count'],
                'site_guid' => $site ? $site->guid : 0,
                'group_guid' => $group ? $group->guid : 0
            ]
        );

        echo "[GCM] Sending " . $message['title'] . " (" . $message['count'] . ") to " . $subscription->token . PHP_EOL;

        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($this->ch);

        echo "[GCM] Received " . $result . PHP_EOL;
    }
}