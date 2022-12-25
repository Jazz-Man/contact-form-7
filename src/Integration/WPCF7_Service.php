<?php

namespace JazzMan\ContactForm7\Integration;

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
