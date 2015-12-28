<?php
namespace PleioRest\Controllers;

class Version {

    /**
     * @SWG\Get(
     *     path="/api",
     *     tags={"version"},
     *     @SWG\Response(response="200", description="Retrieve API version information.")
     * )
     */
    public function getVersion($request, $response, $args) {
        global $CONFIG;
        include($CONFIG->path . "version.php");

        $info = array(
            'name' => "Pleio REST API",
            'version' => array(
                'unfriendly' => $version,
                'friendly' => $release
            ),
            'help' => 'For more information check out the Swagger documentation on /api/doc'
        );

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}