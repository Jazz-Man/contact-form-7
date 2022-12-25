<?php

namespace JazzMan\ContactForm7;

class WPCF7 {
	/**
	 * Loads modules from the modules directory.
	 */
	public static function load_modules(): void {
		self::load_module('acceptance');
		self::load_module('akismet');
		self::load_module('checkbox');
		self::load_module('constant-contact');
		self::load_module('count');
		self::load_module('date');
		self::load_module('disallowed-list');
		self::load_module('doi-helper');
		self::load_module('file');
		self::load_module('flamingo');
		self::load_module('hidden');
		self::load_module('listo');
		self::load_module('number');
		self::load_module('quiz');
		self::load_module('really-simple-captcha');
		self::load_module('recaptcha');
		self::load_module('reflection');
		self::load_module('response');
		self::load_module('select');
		self::load_module('sendinblue');
		self::load_module('stripe');
		self::load_module('submit');
		self::load_module('text');
		self::load_module('textarea');
	}

	/**
	 * Retrieves a named entry from the option array of Contact Form 7.
	 *
	 * @param string $name          array item key
	 * @param mixed  $default_value Optional. Default value to return if the entry
	 *                              does not exist. Default false.
	 *
	 * @return mixed Array value tied to the $name key. If nothing found,
	 *               the $default_value value will be returned.
	 */
	public static function get_option($name, $default_value = false) {
		$option = get_option('wpcf7');

		if (false === $option) {
			return $default_value;
		}

		if (isset($option[$name])) {
			return $option[$name];
		}

		return $default_value;
	}

	/**
	 * Update an entry value on the option array of Contact Form 7.
	 *
	 * @param string $name  array item key
	 * @param mixed  $value option value
	 */
	public static function update_option(string $name, $value): void {
		$option = get_option('wpcf7');
		$option = (false === $option) ? [] : (array) $option;
		$option = array_merge($option, [$name => $value]);
		update_option('wpcf7', $option);
	}

	/**
	 * Loads the specified module.
	 *
	 * @param string $mod name of module
	 *
	 * @return bool true on success, false on failure
	 */
	protected static function load_module(string $mod) {
		return false
		       || wpcf7_include_module_file($mod.'/'.$mod.'.php')
		       || wpcf7_include_module_file($mod.'.php');
	}
}
