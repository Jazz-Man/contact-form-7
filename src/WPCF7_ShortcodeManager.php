<?php

namespace JazzMan\ContactForm7;


/**
 * @deprecated
 */
class WPCF7_ShortcodeManager {
	private static WPCF7_FormTagsManager $form_tags_manager;

	private function __construct() {
	}

	public static function get_instance(): WPCF7_ShortcodeManager {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::get_instance'
		);

		self::$form_tags_manager = WPCF7_FormTagsManager::get_instance();

		return new self();
	}

	public function get_scanned_tags() {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::get_scanned_tags'
		);

		return self::$form_tags_manager->get_scanned_tags();
	}

	public function add_shortcode($tag, $callback, $has_name = false) {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::add'
		);

		return self::$form_tags_manager->add($tag, $callback, $has_name);
	}

	public function remove_shortcode($tag) {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::remove'
		);

		return self::$form_tags_manager->remove($tag);
	}

	public function normalize_shortcode($content) {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::normalize'
		);

		return self::$form_tags_manager->normalize($content);
	}

	public function do_shortcode($content, $exec = true) {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::replace_all'
		);

		if ($exec) {
			return self::$form_tags_manager->replace_all($content);
		}

		return self::$form_tags_manager->scan($content);
	}

	public function scan_shortcode($content) {
		wpcf7_deprecated_function(
			__METHOD__,
			'4.6',
			'WPCF7_FormTagsManager::scan'
		);

		return self::$form_tags_manager->scan($content);
	}
}
