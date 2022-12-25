<?php
/**
 * Sendinblue module main file.
 *
 * @see https://contactform7.com/sendinblue-integration/
 */

use JazzMan\ContactForm7\WPCF7_ContactForm;
use JazzMan\ContactForm7\WPCF7_Submission;

wpcf7_include_module_file('sendinblue/service.php');
wpcf7_include_module_file('sendinblue/contact-form-properties.php');
wpcf7_include_module_file('sendinblue/doi.php');

add_action('wpcf7_init', 'wpcf7_sendinblue_register_service', 10, 0);

/**
 * Registers the Sendinblue service.
 */
function wpcf7_sendinblue_register_service(): void {
    $integration = WPCF7_Integration::get_instance();

    $integration->add_service(
        'sendinblue',
        WPCF7_Sendinblue::get_instance()
    );
}

add_action('wpcf7_submit', 'wpcf7_sendinblue_submit', 10, 2);

/**
 * Callback to the wpcf7_submit action hook. Creates a contact
 * based on the submission.
 */
function wpcf7_sendinblue_submit(WPCF7_ContactForm $contact_form, array $result): void {
    if ($contact_form->in_demo_mode()) {
        return;
    }

    $service = WPCF7_Sendinblue::get_instance();

    if (!$service->is_active()) {
        return;
    }

    if (empty($result['posted_data_hash'])) {
        return;
    }

    if (empty($result['status'])
    || !in_array($result['status'], ['mail_sent', 'mail_failed'], true)) {
        return;
    }

    $submission = WPCF7_Submission::get_instance();

    $consented = true;

    foreach ($contact_form->scan_form_tags('feature=name-attr') as $tag) {
        if ($tag->has_option('consent_for:sendinblue')
        && null == $submission->get_posted_data($tag->name)) {
            $consented = false;

            break;
        }
    }

    if (!$consented) {
        return;
    }

    $prop = wp_parse_args(
        $contact_form->prop('sendinblue'),
        [
            'enable_contact_list' => false,
            'contact_lists' => [],
            'enable_transactional_email' => false,
            'email_template' => 0,
        ]
    );

    if (!$prop['enable_contact_list']) {
        return;
    }

    $attributes = wpcf7_sendinblue_collect_parameters();

    $params = [
        'contact' => [],
        'email' => [],
    ];

    if (!empty($attributes['EMAIL']) || !empty($attributes['SMS'])) {
        $params['contact'] = apply_filters(
            'wpcf7_sendinblue_contact_parameters',
            [
                'email' => $attributes['EMAIL'],
                'attributes' => (object) $attributes,
                'listIds' => (array) $prop['contact_lists'],
                'updateEnabled' => false,
            ]
        );
    }

    if ($prop['enable_transactional_email'] && $prop['email_template']) {
        $first_name = isset($attributes['FIRSTNAME'])
            ? trim($attributes['FIRSTNAME'])
            : '';

        $last_name = isset($attributes['LASTNAME'])
            ? trim($attributes['LASTNAME'])
            : '';

        if ($first_name || $last_name) {
            $email_to_name = sprintf(
                /* translators: 1: first name, 2: last name */
                _x('%1$s %2$s', 'personal name', 'contact-form-7'),
                $first_name,
                $last_name
            );
        } else {
            $email_to_name = '';
        }

        $params['email'] = apply_filters(
            'wpcf7_sendinblue_email_parameters',
            [
                'templateId' => absint($prop['email_template']),
                'to' => [
                    [
                        'name' => $email_to_name,
                        'email' => $attributes['EMAIL'],
                    ],
                ],
                'params' => (object) $attributes,
                'tags' => ['Contact Form 7'],
            ]
        );
    }

    if (is_email($attributes['EMAIL'])) {
        $token = null;

        do_action_ref_array('wpcf7_doi', [
            'wpcf7_sendinblue',
            [
                'email_to' => $attributes['EMAIL'],
                'properties' => $params,
            ],
            &$token,
        ]);

        if (isset($token)) {
            return;
        }
    }

    if (!empty($params['contact'])) {
        $contact_id = $service->create_contact($params['contact']);

        if ($contact_id && !empty($params['email'])) {
            $service->send_email($params['email']);
        }
    }
}

/**
 * Collects parameters for Sendinblue contact data based on submission.
 *
 * @return array sendinblue contact parameters
 */
function wpcf7_sendinblue_collect_parameters(): array {
    $params = [];

    $submission = WPCF7_Submission::get_instance();

    foreach ((array) $submission->get_posted_data() as $name => $val) {
        $name = strtoupper($name);

        if ('YOUR-' == substr($name, 0, 5)) {
            $name = substr($name, 5);
        }

        if ($val) {
            $params += [
                $name => $val,
            ];
        }
    }

    if (isset($params['SMS'])) {
        $sms = implode(' ', (array) $params['SMS']);
        $sms = trim($sms);

        $plus = '+' == substr($sms, 0, 1) ? '+' : '';
        $sms = preg_replace('/[^0-9]/', '', $sms);

        if (6 < strlen($sms) && strlen($sms) < 18) {
            $params['SMS'] = $plus.$sms;
        } else { // Invalid phone number
            unset($params['SMS']);
        }
    }

    if (isset($params['NAME'])) {
        $your_name = implode(' ', (array) $params['NAME']);
        $your_name = explode(' ', $your_name);

        if (!isset($params['LASTNAME'])) {
            $params['LASTNAME'] = implode(
                ' ',
                array_slice($your_name, 1)
            );
        }

        if (!isset($params['FIRSTNAME'])) {
            $params['FIRSTNAME'] = implode(
                ' ',
                array_slice($your_name, 0, 1)
            );
        }
    }

    return apply_filters(
        'wpcf7_sendinblue_collect_parameters',
        $params
    );
}
