<?php
namespace PleioRest\Services;

interface PushInterface {
    public function __construct($key);
    public function push($subscription, $message);
}