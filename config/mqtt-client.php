<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default MQTT Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => 'default',

    /*
    |--------------------------------------------------------------------------
    | MQTT Connections
    |--------------------------------------------------------------------------
    */
    'connections' => [

        'default' => [
            'host' => env('MQTT_HOST', '127.0.0.1'),
            'port' => env('MQTT_PORT', 1883),
            'client_id' => env('MQTT_CLIENT_ID', null),
            'auth' => [
                'username' => env('MQTT_AUTH_USERNAME', null),
                'password' => env('MQTT_AUTH_PASSWORD', null),
            ],
            'settings' => [
                'connect_timeout' => env('MQTT_CONNECT_TIMEOUT', 60),
                'keep_alive_interval' => env('MQTT_KEEP_ALIVE_INTERVAL', 60),
            ],
        ],

    ],

];
