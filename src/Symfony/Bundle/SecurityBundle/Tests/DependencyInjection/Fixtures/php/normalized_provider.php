<?php

$container->loadFromExtension('security', array(
    'providers' => array(
        'with-dash' => array(
            'memory' => array(),
        ),
    ),
    'firewalls' => array(
        'main' => array(
            'provider' => 'with-dash',
            'form_login' => true,
        ),
    ),
));
