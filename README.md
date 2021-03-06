Pleio REST API
==============
This plugin provides an OAuth2.0 protected REST endpoint for third-party applications to an Elgg instance.

Installation
------------
Use the normal procedures to install and activate the Elgg plugin. During activation the plugin will create a the following new tables: oauth_access_tokens, oauth_authorization_codes, oauth_clients, oauth_jwt, oauth_refresh_tokens, oauth_scopes, oauth_users, elgg_push_notifications_count and elgg_push_notifications_subscriptions.

Configuration
-------------
To create a new OAuth2.0 client application insert a row into oauth_client:

    INSERT INTO oauth_clients ('client_id', 'client_secret', 'gcm_key', 'apns_cert') VALUES ('[my-client-id]', '[supersecret]', '[google-api-key]', '[apple-certificate-and-private-key]');

The client_id and client_secret have to be configured in the OAuth2.0 client as well. Optionally iOS and Android push notifications of new river objects to group members can be configured by setting [google-api-key] to the Google Cloud Messaging (GCM) API key and [apple-certificate-and-private-key] to the Apple Push certificate and private key. To generate the Apple certificate and private key export them from your Apple Keychain and use the following commands:

    openssl x509 -in pleioapp-development.cer -inform der -out pleioapp-development.crt
    openssl pkcs12 -nocerts -nodes -in pleioapp-development.p12 -out pleioapp-development.key
    Enter Import Password:
    MAC verified OK

    cat pleioapp-development.crt pleioapp-development.key

Paste the output into the apns_cert field of oauth_clients.

To receive notifications, applications register to the API by calling the /api/user/register_push endpoint.

Version information and documentation
-------------------------------------
Version information can be found at https://[your-elgg-instance]/api. To view the most recent API documentation check out https://[your-elgg-instance]/api/doc with the [Swagger API browser](petstore.swagger.io/).

Regenerate API documentation
----------------------------
To regenerate the Documentation is automatically generated by [swagger-php](https://github.com/zircote/swagger-php). Use

    composer global require zircote/swagger-php

to install this package globally. Then run

    ~/.composer/vendor/bin/swagger

inside this plugin folder to generate swagger.json.

REST calls
----------
Authorization is done by using OAuth2 access tokens. Request a new access token like:

    curl  --data "client_id=pleioapp&client_secret=as389sfj3lkjsf3&username=admin&password=adminadmin&grant_type=password" http://www.pleio.dev/oauth/v2/token

Then perform authenticated requests like this:

    curl -v -H "Authorization: Bearer <access_token>" https://www.pleio.dev/api/groups