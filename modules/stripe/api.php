<?php

/**
 * Class for the Stripe API.
 *
 * @see https://stripe.com/docs/api
 */
class WPCF7_Stripe_API {
    public const api_version = '2022-08-01';

    public const partner_id = 'pp_partner_HHbvqLh1AaO7Am';

    public const app_name = 'WordPress Contact Form 7';

    public const app_url = 'https://contactform7.com/stripe-integration/';

    private string $secret;

    /**
     * Constructor.
     *
     * @param string $secret secret key
     */
    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    /**
     * Creates a Payment Intent.
     *
     * @see https://stripe.com/docs/api/payment_intents/create
     *
     * @param array|string $args Optional. Arguments to control behavior.
     *
     * @return array|bool an associative array if 200 OK, false otherwise
     */
    public function create_payment_intent($args = '') {
        $args = wp_parse_args($args, [
            'amount' => 0,
            'currency' => '',
            'receipt_email' => '',
        ]);

        if (!is_email($args['receipt_email'])) {
            unset($args['receipt_email']);
        }

        $endpoint = 'https://api.stripe.com/v1/payment_intents';

        $request = [
            'headers' => $this->default_headers(),
            'body' => $args,
        ];

        $response = wp_remote_post(sanitize_url($endpoint), $request);

        if (200 != wp_remote_retrieve_response_code($response)) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }

            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        return json_decode($response_body, true);
    }

    /**
     * Retrieve a Payment Intent.
     *
     * @see https://stripe.com/docs/api/payment_intents/retrieve
     *
     * @param string $id payment Intent identifier
     *
     * @return array|bool an associative array if 200 OK, false otherwise
     */
    public function retrieve_payment_intent($id) {
        $endpoint = sprintf(
            'https://api.stripe.com/v1/payment_intents/%s',
            urlencode($id)
        );

        $request = [
            'headers' => $this->default_headers(),
        ];

        $response = wp_remote_get(sanitize_url($endpoint), $request);

        if (200 != wp_remote_retrieve_response_code($response)) {
            if (WP_DEBUG) {
                $this->log($endpoint, $request, $response);
            }

            return false;
        }

        $response_body = wp_remote_retrieve_body($response);

        return json_decode($response_body, true);
    }

    /**
     * Sends a debug information for a remote request to the PHP error log.
     *
     * @param string         $url      URL to retrieve
     * @param array          $request  request arguments
     * @param array|WP_Error $response the response or WP_Error on failure
     */
    private function log(string $url, $request, $response): void {
        wpcf7_log_remote_request($url, $request, $response);
    }

    /**
     * Returns default set of HTTP request headers used for Stripe API.
     *
     * @see https://stripe.com/docs/building-plugins#setappinfo
     *
     * @return array an associative array of headers
     */
    private function default_headers(): array {
        $app_info = [
            'name' => self::app_name,
            'partner_id' => self::partner_id,
            'url' => self::app_url,
            'version' => WPCF7_VERSION,
        ];

        $ua = [
            'lang' => 'php',
            'lang_version' => PHP_VERSION,
            'application' => $app_info,
        ];

        return [
            'Authorization' => sprintf('Bearer %s', $this->secret),
            'Stripe-Version' => self::api_version,
            'X-Stripe-Client-User-Agent' => json_encode($ua),
            'User-Agent' => sprintf(
                '%1$s/%2$s (%3$s)',
                self::app_name,
                WPCF7_VERSION,
                self::app_url
            ),
        ];
    }
}
