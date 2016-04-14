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
        include($CONFIG->path . 'version.php');

        preg_match("/(.*?)(?:\.|$)/", gethostname(), $matches);
        $host = $matches[1];

        $info = array(
            'name' => 'Pleio REST API',
            'server' => $host,
            'version' => array(
                'friendly' => $release,
                'unfriendly' => $version
            )
        );

        $buildFile = $CONFIG->path . 'REVISION';
        if (file_exists($buildFile)) {
            $info['build'] = str_replace(PHP_EOL, '', file_get_contents($buildFile));
        }

        $info['help'] = 'For more information check out the Swagger documentation on /api/doc';

        $response = $response->withHeader('Content-type', 'application/json');
        return $response->write(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}