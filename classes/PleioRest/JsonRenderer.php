<?php
namespace PleioRest;

class JsonRenderer {
    protected $templatePath;

    public function render(ResponseInterface $response, $template, $data) {
        $response->getBody->write(json_encode($data));
        return $response;
    }
}