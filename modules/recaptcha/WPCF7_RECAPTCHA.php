<?php

use JazzMan\ContactForm7\Integration\WPCF7_Service;
use JazzMan\ContactForm7\WPCF7;
use JazzMan\ContactForm7\WPCF7_Submission;



class WPCF7_RECAPTCHA extends WPCF7_Service {
    private static WPCF7_RECAPTCHA $instance;

    private $sitekeys;

    private $last_score;

    private function __construct() {
        $this->sitekeys = WPCF7::get_option('recaptcha');
    }

    public static function get_instance(): self {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get_title() {
        return __('reCAPTCHA', 'contact-form-7');
    }

    public function is_active() {
        $sitekey = $this->get_sitekey();
        $secret = $this->get_secret($sitekey);

        return $sitekey && $secret;
    }

    public function get_categories(): ?array {
        return ['spam_protection'];
    }

    public function link(): void {
        echo wpcf7_link(
            'https://www.google.com/recaptcha/intro/index.html',
            'google.com/recaptcha'
        );
    }

    public function get_global_sitekey() {
        static $sitekey = '';

        if ($sitekey) {
            return $sitekey;
        }

        if (defined('WPCF7_RECAPTCHA_SITEKEY')) {
            $sitekey = WPCF7_RECAPTCHA_SITEKEY;
        }

        $sitekey = apply_filters('wpcf7_recaptcha_sitekey', $sitekey);

        return $sitekey;
    }

    public function get_global_secret() {
        static $secret = '';

        if ($secret) {
            return $secret;
        }

        if (defined('WPCF7_RECAPTCHA_SECRET')) {
            $secret = WPCF7_RECAPTCHA_SECRET;
        }

        $secret = apply_filters('wpcf7_recaptcha_secret', $secret);

        return $secret;
    }

    public function get_sitekey() {
        if ($this->get_global_sitekey() && $this->get_global_secret()) {
            return $this->get_global_sitekey();
        }

        if (empty($this->sitekeys)
        || !is_array($this->sitekeys)) {
            return false;
        }

        $sitekeys = array_keys($this->sitekeys);

        return $sitekeys[0];
    }

    public function get_secret($sitekey) {
        if ($this->get_global_sitekey() && $this->get_global_secret()) {
            return $this->get_global_secret();
        }

        $sitekeys = (array) $this->sitekeys;

        if (isset($sitekeys[$sitekey])) {
            return $sitekeys[$sitekey];
        }

        return false;
    }

    public function verify($token) {
        $is_human = false;

        if (empty($token) || !$this->is_active()) {
            return $is_human;
        }

        $endpoint = 'https://www.google.com/recaptcha/api/siteverify';

        $sitekey = $this->get_sitekey();
        $secret = $this->get_secret($sitekey);

        $request = [
            'body' => [
                'secret' => $secret,
                'response' => $token,
            ],
        ];

        $response = wp_remote_post(sanitize_url($endpoint), $request);

        if (200 != wp_remote_retrieve_response_code($response)) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }

            return $is_human;
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        $this->last_score = $score = $response_body['score']
            ?? 0;

        $threshold = $this->get_threshold();
        $is_human = $threshold < $score;

        $is_human = apply_filters(
            'wpcf7_recaptcha_verify_response',
            $is_human,
            $response_body
        );

        if ($submission = WPCF7_Submission::get_instance()) {
            $submission->push('recaptcha', [
                'version' => '3.0',
                'threshold' => $threshold,
                'response' => $response_body,
            ]);
        }

        return $is_human;
    }

    public function get_threshold() {
        return apply_filters('wpcf7_recaptcha_threshold', 0.50);
    }

    public function get_last_score() {
        return $this->last_score;
    }

    public function load($action = ''): void {
        if ('setup' == $action && 'POST' == $_SERVER['REQUEST_METHOD']) {
            check_admin_referer('wpcf7-recaptcha-setup');

            if (!empty($_POST['reset'])) {
                $this->reset_data();
                $redirect_to = $this->menu_page_url('action=setup');
            } else {
                $sitekey = isset($_POST['sitekey']) ? trim($_POST['sitekey']) : '';
                $secret = isset($_POST['secret']) ? trim($_POST['secret']) : '';

                if ($sitekey && $secret) {
                    $this->sitekeys = [$sitekey => $secret];
                    $this->save_data();

                    $redirect_to = $this->menu_page_url([
                        'message' => 'success',
                    ]);
                } else {
                    $redirect_to = $this->menu_page_url([
                        'action' => 'setup',
                        'message' => 'invalid',
                    ]);
                }
            }

            if (WPCF7::get_option('recaptcha_v2_v3_warning')) {
                WPCF7::update_option('recaptcha_v2_v3_warning', false);
            }

            wp_safe_redirect($redirect_to);

            exit;
        }
    }

    public function admin_notice($message = ''): void {
        if ('invalid' == $message) {
            echo sprintf(
                '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
                esc_html(__('Error', 'contact-form-7')),
                esc_html(__('Invalid key values.', 'contact-form-7'))
            );
        }

        if ('success' == $message) {
            echo sprintf(
                '<div class="notice notice-success"><p>%s</p></div>',
                esc_html(__('Settings saved.', 'contact-form-7'))
            );
        }
    }

    public function display($action = ''): void {
        echo sprintf(
            '<p>%s</p>',
            esc_html(__('reCAPTCHA protects you against spam and other types of automated abuse. With Contact Form 7&#8217;s reCAPTCHA integration module, you can block abusive form submissions by spam bots.', 'contact-form-7'))
        );

        echo sprintf(
            '<p><strong>%s</strong></p>',
            wpcf7_link(
                __('https://contactform7.com/recaptcha/', 'contact-form-7'),
                __('reCAPTCHA (v3)', 'contact-form-7')
            )
        );

        if ($this->is_active()) {
            echo sprintf(
                '<p class="dashicons-before dashicons-yes">%s</p>',
                esc_html(__('reCAPTCHA is active on this site.', 'contact-form-7'))
            );
        }

        if ('setup' == $action) {
            $this->display_setup();
        } else {
            echo sprintf(
                '<p><a href="%1$s" class="button">%2$s</a></p>',
                esc_url($this->menu_page_url('action=setup')),
                esc_html(__('Setup Integration', 'contact-form-7'))
            );
        }
    }

    protected function log($url, $request, $response): void {
        wpcf7_log_remote_request($url, $request, $response);
    }

    protected function menu_page_url($args = '') {
        $args = wp_parse_args($args, []);

        $url = menu_page_url('wpcf7-integration', false);
        $url = add_query_arg(['service' => 'recaptcha'], $url);

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    protected function save_data(): void {
        WPCF7::update_option('recaptcha', $this->sitekeys);
    }

    protected function reset_data(): void {
        $this->sitekeys = null;
        $this->save_data();
    }

    private function display_setup(): void {
        $sitekey = $this->is_active() ? $this->get_sitekey() : '';
        $secret = $this->is_active() ? $this->get_secret($sitekey) : '';

        ?>
<form method="post" action="<?php echo esc_url($this->menu_page_url('action=setup')); ?>">
<?php wp_nonce_field('wpcf7-recaptcha-setup'); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="sitekey"><?php echo esc_html(__('Site Key', 'contact-form-7')); ?></label></th>
	<td><?php
                if ($this->is_active()) {
                    echo esc_html($sitekey);
                    echo sprintf(
                        '<input type="hidden" value="%1$s" id="sitekey" name="sitekey" />',
                        esc_attr($sitekey)
                    );
                } else {
                    echo sprintf(
                        '<input type="text" aria-required="true" value="%1$s" id="sitekey" name="sitekey" class="regular-text code" />',
                        esc_attr($sitekey)
                    );
                }
        ?></td>
</tr>
<tr>
	<th scope="row"><label for="secret"><?php echo esc_html(__('Secret Key', 'contact-form-7')); ?></label></th>
	<td><?php
            if ($this->is_active()) {
                echo esc_html(wpcf7_mask_password($secret, 4, 4));
                echo sprintf(
                    '<input type="hidden" value="%1$s" id="secret" name="secret" />',
                    esc_attr($secret)
                );
            } else {
                echo sprintf(
                    '<input type="text" aria-required="true" value="%1$s" id="secret" name="secret" class="regular-text code" />',
                    esc_attr($secret)
                );
            }
        ?></td>
</tr>
</tbody>
</table>
<?php
            if ($this->is_active()) {
                if ($this->get_global_sitekey() && $this->get_global_secret()) {
                    // nothing
                } else {
                    submit_button(
                        _x('Remove Keys', 'API keys', 'contact-form-7'),
                        'small',
                        'reset'
                    );
                }
            } else {
                submit_button(__('Save Changes', 'contact-form-7'));
            }
        ?>
</form>
<?php
    }
}
