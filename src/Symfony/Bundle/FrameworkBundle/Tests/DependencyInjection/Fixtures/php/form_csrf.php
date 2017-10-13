<?php

$container->loadFromExtension('framework', array(
    'session' => array(
        'storage_id' => 'session.storage.native',
        'handler_id' => 'session.handler.native_file',
    ),
    'csrf_protection' => array('enabled' => true),
    'form' => array(
        'csrf_protection' => array(
            'enabled' => true,
            'field_name' => '_csrf',
        ),
    ),
));
