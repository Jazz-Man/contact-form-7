<?php

namespace JazzMan\ContactForm7\Integration;


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
