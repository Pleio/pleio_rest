<?php

$english = array(
    'pleio_rest:email:account_created:subject' => "Welcome to Pleio!",
    'pleio_rest:email:account_created:body' => "Dear %s,

    Welcome to Pleio! You logged in for the first time using your organization account. By using your organisation account you can use Pleio without configuring an additional password.

    Would you like to be able to login using a password as well, for example when you are not in the office? Use this link to configure the password:

    %s
    ",
    'pleio_rest:email:account_linked:subject' => "Organisation account linked",
    'pleio_rest:email:account_linked:body' => "Dear %s,

    You recently logged in to Pleio using your organisation account (%s). Because you already registered on Pleio we linked your account.

    You are now able to login to Pleio using either your organisation account or password."
);

add_translation("en", $english);