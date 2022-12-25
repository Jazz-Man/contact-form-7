<?php
/**
 * Integration API.
 *
 * @see https://contactform7.com/integration-with-external-apis/
 */
class WPCF7_Integration {
    private static WPCF7_Integration $instance;

    private array $services = [];

    private array $categories = [];

    private function __construct() {
    }

    /**
     * Returns initially supported service categories.
     *
     * @return array service categories
     */
    public static function get_builtin_categories(): array {
        return [
            'spam_protection' => __('Spam protection', 'contact-form-7'),
            'email_marketing' => __('Email marketing', 'contact-form-7'),
            'payments' => __('Payments', 'contact-form-7'),
        ];
    }

    /**
     * Returns the singleton instance of this class.
     *
     * @return WPCF7_Integration the instance
     */
    public static function get_instance(): self {
        if (empty(self::$instance)) {
            self::$instance = new self();
            self::$instance->categories = self::get_builtin_categories();
        }

        return self::$instance;
    }

    /**
     * Adds a service to the services list.
     */
    public function add_service(string $name, WPCF7_Service $service) {
        $name = sanitize_key($name);

        if (empty($name)
        || isset($this->services[$name])) {
            return false;
        }

        $this->services[$name] = $service;
    }

    /**
     * Adds a service category to the categories list.
     *
     * @param mixed $title
     */
    public function add_category(string $name, $title) {
        $name = sanitize_key($name);

        if (empty($name)
        || isset($this->categories[$name])) {
            return false;
        }

        $this->categories[$name] = $title;
    }

    /**
     * Returns true if a service with the name exists in the services list.
     *
     * @param string $name the name of service to search
     */
    public function service_exists(string $name = ''): bool {
        if ('' == $name) {
            return (bool) count($this->services);
        }

        return isset($this->services[$name]);
    }

    /**
     * Returns a service object with the name.
     *
     * @param string $name the name of service
     *
     * @return null|WPCF7_Service the service object if it exists,
     *                            false otherwise
     */
    public function get_service(string $name): ?WPCF7_Service {
        if ($this->service_exists($name)) {
            return $this->services[$name];
        }

        return null;
    }

    /**
     * Prints services list.
     *
     * @param mixed $args
     */
    public function list_services($args = ''): void {
        $args = wp_parse_args($args, [
            'include' => [],
        ]);

        $singular = false;
        $services = (array) $this->services;

        if (!empty($args['include'])) {
            $services = array_intersect_key(
                $services,
                array_flip((array) $args['include'])
            );

            if (1 == count($services)) {
                $singular = true;
            }
        }

        if (empty($services)) {
            return;
        }

        $action = wpcf7_current_action();

        foreach ($services as $name => $service) {
            $cats = array_intersect_key(
                $this->categories,
                array_flip($service->get_categories())
            );
            ?>
<div class="card<?php echo $service->is_active() ? ' active' : ''; ?>" id="<?php echo esc_attr($name); ?>">
<?php $service->icon(); ?>
<h2 class="title"><?php echo esc_html($service->get_title()); ?></h2>
<div class="infobox">
<?php echo esc_html(implode(', ', $cats)); ?>
</div>
<br class="clear" />

<div class="inside">
<?php
                        if ($singular) {
                            $service->display($action);
                        } else {
                            $service->display();
                        }
            ?>
</div>
</div>
<?php
        }
    }
}

/**
 * Abstract class for services.
 *
 * Only instances of this class's subclasses are allowed to be
 * listed on the Integration page.
 */
abstract class WPCF7_Service {
    abstract public function get_title();

    abstract public function is_active();

    public function get_categories(): ?array {
        return [];
    }

    public function icon(): ?string {
        return '';
    }

    public function link() {
        return '';
    }

    public function load(string $action = ''): void {
    }

    public function display(string $action = ''): void {
    }

    public function admin_notice(string $message = ''): void {
    }
}

/**
 * Class for services that use OAuth.
 *
 * While this is not an abstract class, subclassing this class for
 * your aim is advised.
 */
class WPCF7_Service_OAuth2 extends WPCF7_Service {
    protected $client_id = '';

    protected $client_secret = '';

    protected $access_token = '';

    protected $refresh_token = '';

    protected $authorization_endpoint = 'https://example.com/authorization';

    protected $token_endpoint = 'https://example.com/token';

    public function get_title(): string {
        return '';
    }

    public function is_active(): bool {
        return !empty($this->refresh_token);
    }

    public function load($action = ''): void {
        if ('auth_redirect' == $action) {
            $code = $_GET['code'] ?? '';

            if ($code) {
                $this->request_token($code);
            }

            if (!empty($this->access_token)) {
                $message = 'success';
            } else {
                $message = 'failed';
            }

            wp_safe_redirect($this->menu_page_url(
                [
                    'action' => 'setup',
                    'message' => $message,
                ]
            ));

            exit;
        }
    }

    protected function save_data(): void {
    }

    protected function reset_data(): void {
    }

    protected function get_redirect_uri() {
        return admin_url();
    }

    protected function menu_page_url($args = ''): string {
        return menu_page_url('wpcf7-integration', false);
    }

    protected function authorize($scope = ''): void {
        $endpoint = add_query_arg(
            [
                'response_type' => 'code',
                'client_id' => $this->client_id,
                'redirect_uri' => urlencode($this->get_redirect_uri()),
                'scope' => $scope,
            ],
            $this->authorization_endpoint
        );

        if (wp_redirect(sanitize_url($endpoint))) {
            exit;
        }
    }

    protected function get_http_authorization_header(string $scheme = 'basic') {
        $scheme = strtolower(trim($scheme));

        switch ($scheme) {
            case 'bearer':
                return sprintf('Bearer %s', $this->access_token);

            case 'basic':
            default:
                return sprintf(
                    'Basic %s',
                    base64_encode($this->client_id.':'.$this->client_secret)
                );
        }
    }

    protected function request_token($authorization_code) {
        $endpoint = add_query_arg(
            [
                'code' => $authorization_code,
                'redirect_uri' => urlencode($this->get_redirect_uri()),
                'grant_type' => 'authorization_code',
            ],
            $this->token_endpoint
        );

        $request = [
            'headers' => [
                'Authorization' => $this->get_http_authorization_header('basic'),
            ],
        ];

        $response = wp_remote_post(sanitize_url($endpoint), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (WP_DEBUG && 400 <= $response_code) {
            $this->log($endpoint, $request, $response);
        }

        if (401 == $response_code) { // Unauthorized
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            if (isset($response_body['access_token'])) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            if (isset($response_body['refresh_token'])) {
                $this->refresh_token = $response_body['refresh_token'];
            } else {
                $this->refresh_token = null;
            }
        }

        $this->save_data();

        return $response;
    }

    protected function refresh_token() {
        $endpoint = add_query_arg(
            [
                'refresh_token' => $this->refresh_token,
                'grant_type' => 'refresh_token',
            ],
            $this->token_endpoint
        );

        $request = [
            'headers' => [
                'Authorization' => $this->get_http_authorization_header('basic'),
            ],
        ];

        $response = wp_remote_post(sanitize_url($endpoint), $request);
        $response_code = (int) wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body, true);

        if (WP_DEBUG && 400 <= $response_code) {
            $this->log($endpoint, $request, $response);
        }

        if (401 == $response_code) { // Unauthorized
            $this->access_token = null;
            $this->refresh_token = null;
        } else {
            if (isset($response_body['access_token'])) {
                $this->access_token = $response_body['access_token'];
            } else {
                $this->access_token = null;
            }

            if (isset($response_body['refresh_token'])) {
                $this->refresh_token = $response_body['refresh_token'];
            }
        }

        $this->save_data();

        return $response;
    }

    protected function remote_request( string $url, $request = []) {
        static $refreshed = false;

        $request = wp_parse_args($request, []);

        $request['headers'] = array_merge(
            $request['headers'],
            [
                'Authorization' => $this->get_http_authorization_header('bearer'),
            ]
        );

        $response = wp_remote_request(sanitize_url($url), $request);

        if (401 === wp_remote_retrieve_response_code($response)
        && !$refreshed) {
            $this->refresh_token();
            $refreshed = true;

            $response = $this->remote_request($url, $request);
        }

        return $response;
    }

    protected function log( string $url, $request, $response): void {
        wpcf7_log_remote_request($url, $request, $response);
    }
}
