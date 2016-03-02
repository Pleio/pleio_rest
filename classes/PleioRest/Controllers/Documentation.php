<?php
namespace PleioRest\Controllers;

class Documentation {

    public function getDocumentation($request, $response, $args) {
        return $response->write(elgg_view("pleio_rest/swagger"));
    }

    public function getSwagger($request, $response, $args) {
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');

        $swaggerFile = dirname(__FILE__) . '/../../../swagger.json';

        if (file_exists($swaggerFile)) {
            return $response->write(file_get_contents($swaggerFile));
        } else {
            $response = $response->withStatus(500);
            return $response->write(json_encode(array(
                'error' => 'Could not find swagger.json on the server'
            ), JSON_PRETTY_PRINT));
        }
    }
}