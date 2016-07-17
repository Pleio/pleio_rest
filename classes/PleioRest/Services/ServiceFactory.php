<?php
namespace PleioRest\Services;

class ServiceFactory {
    public function __construct() {
        $services = array();

        $clients = get_data("SELECT * FROM oauth_clients WHERE gcm_key IS NOT NULL OR apns_cert IS NOT NULL");
        foreach ($clients as $client) {
            $services[$client->client_id] = array();

            if ($client->gcm_key) {
                $services[$client->client_id]['gcm'] = new GcmPush($client->gcm_key);
            }
            if ($client->apns_cert) {
                $services[$client->client_id]['apns'] = new ApnsPush($client->apns_cert);
            }

            $services[$client->client_id]['mpns'] = new MpnsPush(null);
        }

        $this->services = $services;
    }

    public function getService($subscription) {
        $services = $this->services;
        if (!isset($services[$subscription->client_id])) {
            return false;
        }

        if (!isset($services[$subscription->client_id][$subscription->service])) {
            return false;
        }

        return $services[$subscription->client_id][$subscription->service];
    }
}