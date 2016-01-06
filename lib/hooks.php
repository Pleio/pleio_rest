<?php
function pleio_rest_public_pages($hook, $type, $return_value, $params) {
	// API endpoint handles it's own authentication, do not let it block by walled garden.
	$return_value[] = 'api';
	$return_value[] = 'api.*';
	$return_value[] = 'oauth/v2/.*';
	return $return_value;
}
?>
