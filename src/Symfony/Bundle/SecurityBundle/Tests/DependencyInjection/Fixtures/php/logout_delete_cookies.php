<?php

$container->loadFromExtension('security', array(
    'providers' => array(
        'default' => array('id' => 'foo'),
    ),
    'firewalls' => array(
        'logout_delete_cookies' => array(
            'anonymous' => true,
            'logout' => array(
                'delete_cookies' => array('cookie-name' => true)
            )
        ),
    ),
));
