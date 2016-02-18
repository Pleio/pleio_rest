<?php
namespace PleioRest\Services;

class ApnsPush implements PushInterface {
    public function __construct($certificate) {
        global $CONFIG;

        $this->certificate = $certificate;

        $this->ctx = stream_context_create();

        $tmp_file = tempnam(sys_get_temp_dir(), "apns");
        file_put_contents($tmp_file, $certificate);
        register_shutdown_function("unlink", $tmp_file);

        stream_context_set_option($this->ctx, 'ssl' ,'local_cert', $tmp_file);

        if ($CONFIG->env == "test") {
            $host = "gateway.sandbox.push.apple.com";
        } else {
            $host = "gateway.push.apple.com";
        }

        $this->fp = stream_socket_client("ssl://" . $host . ":2195", $err, $errstr, 60, STREAM_CLIENT_CONNECT, $this->ctx);
    }

    public function push($subscription, $message) {
        $body = array();

        $body['aps'] = array();
        $body['aps']['alert'] = 'This is a push message.';
        $body['aps']['badge'] = $message['count'];
        $body['aps']['sound'] = 'default';

        $expiry = time() + (3600*24*90); // keep alive for 90 days
        $payload = json_encode($body);

        $msg = chr(0) . pack('n', 32) . pack('H*', $subscription->token) . pack('n', strlen($payload)) . $payload;
        $response = fwrite($this->fp, $msg);

        return $response;
    }
}