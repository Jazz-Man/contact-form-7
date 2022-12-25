<?php

use JazzMan\ContactForm7\Integration\WPCF7_Service;
use JazzMan\ContactForm7\WPCF7;

class WPCF7_Sendinblue extends WPCF7_Service {
    use WPCF7_Sendinblue_API;

    private static WPCF7_Sendinblue $instance;

    private $api_key;

    private function __construct() {
        $option = WPCF7::get_option('sendinblue');

        if (isset($option['api_key'])) {
            $this->api_key = $option['api_key'];
        }
    }

    public static function get_instance(): self {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get_title() {
        return __('Sendinblue', 'contact-form-7');
    }

    public function is_active() {
        return (bool) $this->get_api_key();
    }

    public function get_api_key() {
        return $this->api_key;
    }

    public function get_categories(): ?array {
        return ['email_marketing'];
    }

    public function link(): void {
        echo wpcf7_link(
            'https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=1031580-b1bb1d',
            'sendinblue.com'
        );
    }

    public function load($action = ''): void {
        if ('setup' == $action && 'POST' == $_SERVER['REQUEST_METHOD']) {
            check_admin_referer('wpcf7-sendinblue-setup');

            if (!empty($_POST['reset'])) {
                $this->reset_data();
                $redirect_to = $this->menu_page_url('action=setup');
            } else {
                $this->api_key = isset($_POST['api_key'])
                    ? trim($_POST['api_key'])
                    : '';

                $confirmed = $this->confirm_key();

                if (true === $confirmed) {
                    $redirect_to = $this->menu_page_url([
                        'message' => 'success',
                    ]);

                    $this->save_data();
                } elseif (false === $confirmed) {
                    $redirect_to = $this->menu_page_url([
                        'action' => 'setup',
                        'message' => 'unauthorized',
                    ]);
                } else {
                    $redirect_to = $this->menu_page_url([
                        'action' => 'setup',
                        'message' => 'invalid',
                    ]);
                }
            }

            wp_safe_redirect($redirect_to);

            exit;
        }
    }

    public function admin_notice($message = ''): void {
        if ('unauthorized' == $message) {
            echo sprintf(
                '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>',
                esc_html(__('Error', 'contact-form-7')),
                esc_html(__('You have not been authenticated. Make sure the provided API key is correct.', 'contact-form-7'))
            );
        }

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
            esc_html(__('Store and organize your contacts while protecting user privacy on Sendinblue, the leading CRM & email marketing platform in Europe. Sendinblue offers unlimited contacts and advanced marketing features.', 'contact-form-7'))
        );

        echo sprintf(
            '<p><strong>%s</strong></p>',
            wpcf7_link(
                __('https://contactform7.com/sendinblue-integration/', 'contact-form-7'),
                __('Sendinblue integration', 'contact-form-7')
            )
        );

        if ($this->is_active()) {
            echo sprintf(
                '<p class="dashicons-before dashicons-yes">%s</p>',
                esc_html(__('Sendinblue is active on this site.', 'contact-form-7'))
            );
        }

        if ('setup' == $action) {
            $this->display_setup();
        } else {
            echo sprintf(
                '<p><a href="%1$s" class="button">%2$s</a></p>',
                esc_url($this->menu_page_url('action=setup')),
                esc_html(__('Setup integration', 'contact-form-7'))
            );
        }
    }

    protected function log($url, $request, $response): void {
        wpcf7_log_remote_request($url, $request, $response);
    }

    protected function menu_page_url($args = '') {
        $args = wp_parse_args($args, []);

        $url = menu_page_url('wpcf7-integration', false);
        $url = add_query_arg(['service' => 'sendinblue'], $url);

        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    protected function save_data(): void {
        WPCF7::update_option('sendinblue', [
            'api_key' => $this->api_key,
        ]);
    }

    protected function reset_data(): void {
        $this->api_key = null;
        $this->save_data();
    }

    private function display_setup(): void {
        $api_key = $this->get_api_key();

        ?>
<form method="post" action="<?php echo esc_url($this->menu_page_url('action=setup')); ?>">
<?php wp_nonce_field('wpcf7-sendinblue-setup'); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="publishable"><?php echo esc_html(__('API key', 'contact-form-7')); ?></label></th>
	<td><?php
                if ($this->is_active()) {
                    echo esc_html(wpcf7_mask_password($api_key, 4, 8));
                    echo sprintf(
                        '<input type="hidden" value="%s" id="api_key" name="api_key" />',
                        esc_attr($api_key)
                    );
                } else {
                    echo sprintf(
                        '<input type="text" aria-required="true" value="%s" id="api_key" name="api_key" class="regular-text code" />',
                        esc_attr($api_key)
                    );
                }
        ?></td>
</tr>
</tbody>
</table>
<?php
            if ($this->is_active()) {
                submit_button(
                    _x('Remove key', 'API keys', 'contact-form-7'),
                    'small',
                    'reset'
                );
            } else {
                submit_button(__('Save changes', 'contact-form-7'));
            }
        ?>
</form>
<?php
    }
}

/**
 * Trait for the Sendinblue API (v3).
 *
 * @see https://developers.sendinblue.com/reference
 */
trait WPCF7_Sendinblue_API {
    public function confirm_key() {
        $endpoint = 'https://api.sendinblue.com/v3/account';

        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'API-Key' => $this->get_api_key(),
            ],
        ];

        $response = wp_remote_get($endpoint, $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

        if (200 === $response_code) { // 200 OK
            return true;
        }

        if (401 === $response_code) { // 401 Unauthorized
            return false;
        }

        if (400 <= $response_code) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }
        }
    }

    public function get_lists() {
        $endpoint = add_query_arg(
            [
                'limit' => 50,
                'offset' => 0,
            ],
            'https://api.sendinblue.com/v3/contacts/lists'
        );

        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'API-Key' => $this->get_api_key(),
            ],
        ];

        $response = wp_remote_get($endpoint, $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

        if (200 === $response_code) { // 200 OK
            $response_body = wp_remote_retrieve_body($response);
            $response_body = json_decode($response_body, true);

            if (empty($response_body['lists'])) {
                return [];
            }

            return (array) $response_body['lists'];
        } elseif (400 <= $response_code) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }
        }
    }

    public function get_templates() {
        $endpoint = add_query_arg(
            [
                'templateStatus' => 'true',
                'limit' => 100,
                'offset' => 0,
            ],
            'https://api.sendinblue.com/v3/smtp/templates'
        );

        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'API-Key' => $this->get_api_key(),
            ],
        ];

        $response = wp_remote_get($endpoint, $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

        if (200 === $response_code) { // 200 OK
            $response_body = wp_remote_retrieve_body($response);
            $response_body = json_decode($response_body, true);

            if (empty($response_body['templates'])) {
                return [];
            }

            return (array) $response_body['templates'];
        } elseif (400 <= $response_code) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }
        }
    }

    public function create_contact($properties) {
        $endpoint = 'https://api.sendinblue.com/v3/contacts';

        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'API-Key' => $this->get_api_key(),
            ],
            'body' => json_encode($properties),
        ];

        $response = wp_remote_post($endpoint, $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

        if (in_array($response_code, [201, 204], true)) {
            return wp_remote_retrieve_body($response);
        }

        if (400 <= $response_code) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }
        }

        return false;
    }

    public function send_email($properties) {
        $endpoint = 'https://api.sendinblue.com/v3/smtp/email';

        $request = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
                'API-Key' => $this->get_api_key(),
            ],
            'body' => json_encode($properties),
        ];

        $response = wp_remote_post($endpoint, $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);

        if (201 === $response_code) { // 201 Transactional email sent
            return wp_remote_retrieve_body($response);
        }

        if (400 <= $response_code) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }
        }

        return false;
    }
}
