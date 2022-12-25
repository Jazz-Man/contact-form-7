<?php
/**
 * Controller for front-end requests, scripts, and styles.
 */

use JazzMan\ContactForm7\WPCF7_Submission;

add_action(
    'parse_request',
    'wpcf7_control_init',
    20,
    0
);

/**
 * Handles a submission in non-Ajax mode.
 */
function wpcf7_control_init(): void {
    if (WPCF7_Submission::is_restful()) {
        return;
    }

    if (isset($_POST['_wpcf7'])) {
        $contact_form = wpcf7_contact_form((int) $_POST['_wpcf7']);

        if ($contact_form) {
            $contact_form->submit();
        }
    }
}

/**
 * Registers main scripts and styles.
 */
add_action(
    'wp_enqueue_scripts',
    function (): void {
        $assets = [];
        $asset_file = wpcf7_plugin_path('includes/js/index.asset.php');

        if (file_exists($asset_file)) {
            $assets = include $asset_file;
        }

        $assets = wp_parse_args($assets, [
            'dependencies' => [],
            'version' => WPCF7_VERSION,
        ]);

        wp_register_script(
            'contact-form-7',
            wpcf7_plugin_url('includes/js/index.js'),
            array_merge(
                $assets['dependencies'],
                ['swv']
            ),
            $assets['version'],
            true
        );

        wp_register_script(
            'contact-form-7-html5-fallback',
            wpcf7_plugin_url('includes/js/html5-fallback.js'),
            ['jquery-ui-datepicker'],
            WPCF7_VERSION,
            true
        );

        if (wpcf7_load_js()) {
            wpcf7_enqueue_scripts();
        }

        wp_register_style(
            'contact-form-7',
            wpcf7_plugin_url('includes/css/styles.css'),
            [],
            WPCF7_VERSION,
            'all'
        );

        wp_register_style(
            'contact-form-7-rtl',
            wpcf7_plugin_url('includes/css/styles-rtl.css'),
            ['contact-form-7'],
            WPCF7_VERSION,
            'all'
        );

        wp_register_style(
            'jquery-ui-smoothness',
            wpcf7_plugin_url(
                'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css'
            ),
            [],
            '1.12.1',
            'screen'
        );

        if (wpcf7_load_css()) {
            wpcf7_enqueue_styles();
        }
    },
    10,
    0
);

/**
 * Enqueues scripts.
 */
function wpcf7_enqueue_scripts(): void {
    wp_enqueue_script('contact-form-7');

    $wpcf7 = [
        'api' => [
            'root' => sanitize_url(get_rest_url()),
            'namespace' => 'contact-form-7/v1',
        ],
    ];

    if (defined('WP_CACHE') && WP_CACHE) {
        $wpcf7['cached'] = 1;
    }

    wp_localize_script('contact-form-7', 'wpcf7', $wpcf7);

    do_action('wpcf7_enqueue_scripts');
}

/**
 * Returns true if the main script is enqueued.
 */
function wpcf7_script_is(): bool {
    return wp_script_is('contact-form-7');
}

/**
 * Enqueues styles.
 */
function wpcf7_enqueue_styles(): void {
    wp_enqueue_style('contact-form-7');

    if (wpcf7_is_rtl()) {
        wp_enqueue_style('contact-form-7-rtl');
    }

    do_action('wpcf7_enqueue_styles');
}

/**
 * Returns true if the main stylesheet is enqueued.
 */
function wpcf7_style_is(): bool {
    return wp_style_is('contact-form-7');
}

add_action(
    'wp_enqueue_scripts',
    'wpcf7_html5_fallback',
    20,
    0
);

/**
 * Enqueues scripts and styles for the HTML5 fallback.
 */
function wpcf7_html5_fallback(): void {
    if (!wpcf7_support_html5_fallback()) {
        return;
    }

    if (wpcf7_script_is()) {
        wp_enqueue_script('contact-form-7-html5-fallback');
    }

    if (wpcf7_style_is()) {
        wp_enqueue_style('jquery-ui-smoothness');
    }
}
