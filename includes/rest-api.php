<?php

use JazzMan\ContactForm7\RestApi\WPCF7_REST_Controller;

add_action(
    'rest_api_init',
    function (): void {
        $controller = new WPCF7_REST_Controller();
        $controller->register_routes();
    },
    10,
    0
);
