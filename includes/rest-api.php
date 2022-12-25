<?php

add_action(
    'rest_api_init',
    function (): void {
        $controller = new WPCF7_REST_Controller();
        $controller->register_routes();
    },
    10,
    0
);

class WPCF7_REST_Controller {
    public const route_namespace = 'contact-form-7/v1';

    public function register_routes(): void {
        register_rest_route(
            self::route_namespace,
            '/contact-forms',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_contact_forms'],
                    'permission_callback' => function () {
                        if (current_user_can('wpcf7_read_contact_forms')) {
                            return true;
                        }

                        return new WP_Error(
                            'wpcf7_forbidden',
                            __('You are not allowed to access contact forms.', 'contact-form-7'),
                            ['status' => 403]
                        );
                    },
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_contact_form'],
                    'permission_callback' => function () {
                        if (current_user_can('wpcf7_edit_contact_forms')) {
                            return true;
                        }

                        return new WP_Error(
                            'wpcf7_forbidden',
                            __('You are not allowed to create a contact form.', 'contact-form-7'),
                            ['status' => 403]
                        );
                    },
                ],
            ]
        );

        register_rest_route(
            self::route_namespace,
            '/contact-forms/(?P<id>\d+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_contact_form'],
                    'permission_callback' => function (WP_REST_Request $request) {
                        $id = (int) $request->get_param('id');

                        if (current_user_can('wpcf7_edit_contact_form', $id)) {
                            return true;
                        }

                        return new WP_Error(
                            'wpcf7_forbidden',
                            __('You are not allowed to access the requested contact form.', 'contact-form-7'),
                            ['status' => 403]
                        );
                    },
                ],
                [
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'update_contact_form'],
                    'permission_callback' => function (WP_REST_Request $request) {
                        $id = (int) $request->get_param('id');

                        if (current_user_can('wpcf7_edit_contact_form', $id)) {
                            return true;
                        }

                        return new WP_Error(
                            'wpcf7_forbidden',
                            __('You are not allowed to access the requested contact form.', 'contact-form-7'),
                            ['status' => 403]
                        );
                    },
                ],
                [
                    'methods' => WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_contact_form'],
                    'permission_callback' => function (WP_REST_Request $request) {
                        $id = (int) $request->get_param('id');

                        if (current_user_can('wpcf7_delete_contact_form', $id)) {
                            return true;
                        }

                        return new WP_Error(
                            'wpcf7_forbidden',
                            __('You are not allowed to access the requested contact form.', 'contact-form-7'),
                            ['status' => 403]
                        );
                    },
                ],
            ]
        );

        register_rest_route(
            self::route_namespace,
            '/contact-forms/(?P<id>\d+)/feedback',
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_feedback'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::route_namespace,
            '/contact-forms/(?P<id>\d+)/feedback/schema',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_schema'],
                    'permission_callback' => '__return_true',
                ],
                'schema' => 'wpcf7_swv_get_meta_schema',
            ]
        );

        register_rest_route(
            self::route_namespace,
            '/contact-forms/(?P<id>\d+)/refill',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_refill'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function get_contact_forms(WP_REST_Request $request) {
        $args = [];

        $per_page = $request->get_param('per_page');

        if (null !== $per_page) {
            $args['posts_per_page'] = (int) $per_page;
        }

        $offset = $request->get_param('offset');

        if (null !== $offset) {
            $args['offset'] = (int) $offset;
        }

        $order = $request->get_param('order');

        if (null !== $order) {
            $args['order'] = (string) $order;
        }

        $orderby = $request->get_param('orderby');

        if (null !== $orderby) {
            $args['orderby'] = (string) $orderby;
        }

        $search = $request->get_param('search');

        if (null !== $search) {
            $args['s'] = (string) $search;
        }

        $items = WPCF7_ContactForm::find($args);

        $response = [];

        foreach ($items as $item) {
            $response[] = [
                'id' => $item->id(),
                'slug' => $item->name(),
                'title' => $item->title(),
                'locale' => $item->locale(),
            ];
        }

        return rest_ensure_response($response);
    }

    public function create_contact_form(WP_REST_Request $request) {
        $id = (int) $request->get_param('id');

        if ($id) {
            return new WP_Error(
                'wpcf7_post_exists',
                __('Cannot create existing contact form.', 'contact-form-7'),
                ['status' => 400]
            );
        }

        $args = $request->get_params();
        $args['id'] = -1; // Create
        $context = $request->get_param('context');
        $item = wpcf7_save_contact_form($args, $context);

        if (!$item) {
            return new WP_Error(
                'wpcf7_cannot_save',
                __('There was an error saving the contact form.', 'contact-form-7'),
                ['status' => 500]
            );
        }

        $response = [
            'id' => $item->id(),
            'slug' => $item->name(),
            'title' => $item->title(),
            'locale' => $item->locale(),
            'properties' => $this->get_properties($item),
            'config_errors' => [],
        ];

        if (wpcf7_validate_configuration()) {
            $config_validator = new WPCF7_ConfigValidator($item);
            $config_validator->validate();

            $response['config_errors'] = $config_validator->collect_error_messages();

            if ('save' == $context) {
                $config_validator->save();
            }
        }

        return rest_ensure_response($response);
    }

    public function get_contact_form(WP_REST_Request $request) {
        $id = (int) $request->get_param('id');
        $item = wpcf7_contact_form($id);

        if (!$item) {
            return new WP_Error(
                'wpcf7_not_found',
                __('The requested contact form was not found.', 'contact-form-7'),
                ['status' => 404]
            );
        }

        $response = [
            'id' => $item->id(),
            'slug' => $item->name(),
            'title' => $item->title(),
            'locale' => $item->locale(),
            'properties' => $this->get_properties($item),
        ];

        return rest_ensure_response($response);
    }

    public function update_contact_form(WP_REST_Request $request) {
        $id = (int) $request->get_param('id');
        $item = wpcf7_contact_form($id);

        if (!$item) {
            return new WP_Error(
                'wpcf7_not_found',
                __('The requested contact form was not found.', 'contact-form-7'),
                ['status' => 404]
            );
        }

        $args = $request->get_params();
        $context = $request->get_param('context');
        $item = wpcf7_save_contact_form($args, $context);

        if (!$item) {
            return new WP_Error(
                'wpcf7_cannot_save',
                __('There was an error saving the contact form.', 'contact-form-7'),
                ['status' => 500]
            );
        }

        $response = [
            'id' => $item->id(),
            'slug' => $item->name(),
            'title' => $item->title(),
            'locale' => $item->locale(),
            'properties' => $this->get_properties($item),
            'config_errors' => [],
        ];

        if (wpcf7_validate_configuration()) {
            $config_validator = new WPCF7_ConfigValidator($item);
            $config_validator->validate();

            $response['config_errors'] = $config_validator->collect_error_messages();

            if ('save' == $context) {
                $config_validator->save();
            }
        }

        return rest_ensure_response($response);
    }

    public function delete_contact_form(WP_REST_Request $request) {
        $id = (int) $request->get_param('id');
        $item = wpcf7_contact_form($id);

        if (!$item) {
            return new WP_Error(
                'wpcf7_not_found',
                __('The requested contact form was not found.', 'contact-form-7'),
                ['status' => 404]
            );
        }

        $result = $item->delete();

        if (!$result) {
            return new WP_Error(
                'wpcf7_cannot_delete',
                __('There was an error deleting the contact form.', 'contact-form-7'),
                ['status' => 500]
            );
        }

        $response = ['deleted' => true];

        return rest_ensure_response($response);
    }

    public function create_feedback(WP_REST_Request $request) {
        $content_type = $request->get_header('Content-Type');

        if (!str_starts_with($content_type, 'multipart/form-data')) {
            return new WP_Error(
                'wpcf7_unsupported_media_type',
                __('The request payload format is not supported.', 'contact-form-7'),
                ['status' => 415]
            );
        }

        $url_params = $request->get_url_params();

        $item = null;

        if (!empty($url_params['id'])) {
            $item = wpcf7_contact_form($url_params['id']);
        }

        if (!$item) {
            return new WP_Error(
                'wpcf7_not_found',
                __('The requested contact form was not found.', 'contact-form-7'),
                ['status' => 404]
            );
        }

        $unit_tag = wpcf7_sanitize_unit_tag(
            $request->get_param('_wpcf7_unit_tag')
        );

        $result = $item->submit();

        $response = array_merge($result, [
            'into' => sprintf('#%s', $unit_tag),
            'invalid_fields' => [],
        ]);

        if (!empty($result['invalid_fields'])) {
            $invalid_fields = [];

            foreach ((array) $result['invalid_fields'] as $name => $field) {
                if (!wpcf7_is_name($name)) {
                    continue;
                }

                $name = strtr($name, '.', '_');

                $invalid_fields[] = [
                    'field' => $name,
                    'message' => $field['reason'],
                    'idref' => $field['idref'],
                    'error_id' => sprintf(
                        '%1$s-ve-%2$s',
                        $unit_tag,
                        $name
                    ),
                ];
            }

            $response['invalid_fields'] = $invalid_fields;
        }

        $response = wpcf7_apply_filters_deprecated(
            'wpcf7_ajax_json_echo',
            [$response, $result],
            '5.2',
            'wpcf7_feedback_response'
        );

        $response = apply_filters('wpcf7_feedback_response', $response, $result);

        return rest_ensure_response($response);
    }

    public function get_schema(WP_REST_Request $request) {
        $url_params = $request->get_url_params();

        $item = null;

        if (!empty($url_params['id'])) {
            $item = wpcf7_contact_form($url_params['id']);
        }

        if (!$item) {
            return new WP_Error(
                'wpcf7_not_found',
                __('The requested contact form was not found.', 'contact-form-7'),
                ['status' => 404]
            );
        }

        $schema = $item->get_schema();

        $response = isset($schema) ? $schema->to_array() : [];

        return rest_ensure_response($response);
    }

    public function get_refill(WP_REST_Request $request) {
        $id = (int) $request->get_param('id');
        $item = wpcf7_contact_form($id);

        if (!$item) {
            return new WP_Error(
                'wpcf7_not_found',
                __('The requested contact form was not found.', 'contact-form-7'),
                ['status' => 404]
            );
        }

        $response = wpcf7_apply_filters_deprecated(
            'wpcf7_ajax_onload',
            [[]],
            '5.2',
            'wpcf7_refill_response'
        );

        $response = apply_filters('wpcf7_refill_response', []);

        return rest_ensure_response($response);
    }

    private function get_properties(WPCF7_ContactForm $contact_form): array {
        $properties = $contact_form->get_properties();

        $properties['form'] = [
            'content' => (string) $properties['form'],
            'fields' => array_map(
                function (WPCF7_FormTag $form_tag) {
                    return [
                        'type' => $form_tag->type,
                        'basetype' => $form_tag->basetype,
                        'name' => $form_tag->name,
                        'options' => $form_tag->options,
                        'raw_values' => $form_tag->raw_values,
                        'labels' => $form_tag->labels,
                        'values' => $form_tag->values,
                        'pipes' => $form_tag->pipes instanceof WPCF7_Pipes
                            ? $form_tag->pipes->to_array()
                            : $form_tag->pipes,
                        'content' => $form_tag->content,
                    ];
                },
                $contact_form->scan_form_tags()
            ),
        ];

        $properties['additional_settings'] = [
            'content' => (string) $properties['additional_settings'],
            'settings' => array_filter(array_map(
                function ($setting) {
                    $pattern = '/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/';

                    if (preg_match($pattern, $setting, $matches)) {
                        $name = trim($matches[1]);
                        $value = trim($matches[2]);

                        if (in_array($value, ['on', 'true'], true)) {
                            $value = true;
                        } elseif (in_array($value, ['off', 'false'], true)) {
                            $value = false;
                        }

                        return [$name, $value];
                    }

                    return false;
                },
                explode("\n", $properties['additional_settings'])
            )),
        ];

        return $properties;
    }

    private function get_argument_schema() {
        return [
            'id' => [
                'description' => __('Unique identifier for the contact form.', 'contact-form-7'),
                'type' => 'integer',
                'required' => true,
            ],
        ];
    }
}
