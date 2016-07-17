<?php
namespace PleioRest\Services;

class MpnsPush implements PushInterface {
    public function __construct($key = null) {
        $this->key = $key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/xml',
            'Accept: application/*',
            'X-NotificationClass: 0',
            'X-WindowsPhone-Target: toast'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->ch = $ch;
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function push($subscription, $message) {
        $msg = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<wp:Notification xmlns:wp=\"WPNotification\">" .
                "<wp:Toast>" .
                "<wp:Text1>" . htmlspecialchars($message['title']) . "</wp:Text1>" .
                "<wp:Text2>" . $message['count'] . " nieuwe berichten</wp:Text2>" .
                "</wp:Toast>" .
                "</wp:Notification>";

        curl_setopt($this->ch, CURLOPT_URL, $subscription->token);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $msg);

        echo "[MPNS] Sending " . $message['title'] . " (" . $message['count'] . ") to " . $subscription->token . PHP_EOL;

        $result = curl_exec($this->ch);

        echo "[MPNS] Received " . $result . PHP_EOL;
    }
}