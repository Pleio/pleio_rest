<?php
namespace PleioRest\Services;

class WnsPush implements PushInterface {
    public function __construct($credentials) {
        $this->clientId = $credentials['clientId'];
        $this->clientSecret = $credentials['clientSecret'];
        $this->clientTokenExpiry = 0;
    }

    public function requestAccessToken() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://login.live.com/accesstoken.srf");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
            'scope' => 'notify.windows.com'
        )));

        echo "[WNS] Requesting new access token for push server " . PHP_EOL;
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result) {
            $result = json_decode($result);
            if ($result->access_token) {
                echo "[WNS] Retrieved access token for push server " . PHP_EOL;
                $this->clientToken = $result->access_token;
                $this->clientTokenExpiry = time() + $result->expires_in - 500;
                return;
            }
        }

        echo "[WNS] Could not retrieve access token for push server, received " . print_r($result, true) . PHP_EOL;
        $this->clientToken = false;
    }

    public function push($subscription, $message) {
        $url = parse_url($subscription->token);
        if (!preg_match('/.*notify.windows.com$/', $url['host'])) {
            echo "[WNS] Invalid subscription URL provided (" . $subscription->token . ")" . PHP_EOL;
            return;
        }

        if ($this->clientTokenExpiry < time()) {
            $this->requestAccessToken();
        }

        if (!$this->clientToken) {
            return;
        }

        $msg = "<?xml version=\"1.0\" encoding=\"utf-16\"?>" .
                "<toast launch=\"\">" .
                    "<visual lang=\"nl-NL\">" .
                        "<binding template=\"ToastText01\">" .
                            "<text id=\"1\">" . htmlspecialchars($message['title']) . "</text>" .
                        "</binding>" .
                    "</visual>" .
                "</toast>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $subscription->token);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/xml',
            'Content-Length: ' . strlen($msg),
            'Authorization: Bearer ' . $this->clientToken,
            'X-WNS-Type: wns/toast'
        ));

        echo "[WNS] Sending " . $message['title'] . " (" . $message['count'] . ") to " . $subscription->token . PHP_EOL;
        $result = curl_exec($ch);
        echo "[WNS] Received " . print_r($result, true) . " with HTTP code " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;

        curl_close($ch);
    }
}