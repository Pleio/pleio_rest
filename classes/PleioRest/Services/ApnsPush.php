<?php
namespace PleioRest\Services;

class ApnsPush implements PushInterface {
    public function __construct($certificate) {
        $this->certificate = $certificate;

        $this->ctx = stream_context_create();

        $tmp_file = tempnam(sys_get_temp_dir(), "apns");
        file_put_contents($tmp_file, $certificate);
        register_shutdown_function("unlink", $tmp_file);

        stream_context_set_option($this->ctx, 'ssl' ,'local_cert', $tmp_file);
        $this->fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $this->ctx);
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
        fwrite($this->fp, $msg);

        return $response = $this->parseResponse();
    }

    public function parseResponse() {
        $error = fread($this->fp, 6);

        if (!$error) {
            return true;
        }

        $error = unpack('Ccommand/Cstatus_code/Nindentifier', $error);
        if ($error == '0') {
            return 'No errors';
        } elseif ($error == '1') {
            return 'Processing error';
        } elseif ($error == '2') {
            return 'Missing device token';
        } elseif ($error == '3') {
            return 'Missing topic';
        } elseif ($error == '4') {
            return 'Missing payload';
        } elseif ($error == '5') {
            return 'Invalid token size';
        } elseif ($error == '6') {
            return 'Invalid topic size';
        } elseif ($error == '7') {
            return 'Invalid payload size';
        } elseif ($error == '8') {
            return 'Invalid token';
        } elseif ($error == '255') {
            return 'Unknown';
        }
    }
}