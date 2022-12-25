<?php

namespace JazzMan\ContactForm7;

class WPCF7_ContactFormTemplate {
	public static function get_default($prop = 'form') {
		if ('form' == $prop) {
			$template = self::form();
		} elseif ('mail' == $prop) {
			$template = self::mail();
		} elseif ('mail_2' == $prop) {
			$template = self::mail_2();
		} elseif ('messages' == $prop) {
			$template = self::messages();
		} else {
			$template = null;
		}

		return apply_filters('wpcf7_default_template', $template, $prop);
	}

	public static function form() {
		$template = sprintf(
			'
<label> %2$s
    [text* your-name autocomplete:name] </label>

<label> %3$s
    [email* your-email autocomplete:email] </label>

<label> %4$s
    [text* your-subject] </label>

<label> %5$s %1$s
    [textarea your-message] </label>

[submit "%6$s"]',
			__('(optional)', 'contact-form-7'),
			__('Your name', 'contact-form-7'),
			__('Your email', 'contact-form-7'),
			__('Subject', 'contact-form-7'),
			__('Your message', 'contact-form-7'),
			__('Submit', 'contact-form-7')
		);

		return trim($template);
	}

	public static function mail(): array {
		return [
			'subject' => sprintf(
			/* translators: 1: blog name, 2: [your-subject] */
				_x('%1$s "%2$s"', 'mail subject', 'contact-form-7'),
				'[_site_title]',
				'[your-subject]'
			),
			'sender' => sprintf(
				'%s <%s>',
				'[_site_title]',
				self::from_email()
			),
			'body' => sprintf(
			          /* translators: %s: [your-name] <[your-email]> */
				          __('From: %s', 'contact-form-7'),
				          '[your-name] <[your-email]>'
			          )."\n"
			          .sprintf(
			          /* translators: %s: [your-subject] */
				          __('Subject: %s', 'contact-form-7'),
				          '[your-subject]'
			          )."\n\n"
			          .__('Message Body:', 'contact-form-7')
			          ."\n".'[your-message]'."\n\n"
			          .'-- '."\n"
			          .sprintf(
			          /* translators: 1: blog name, 2: blog URL */
				          __('This e-mail was sent from a contact form on %1$s (%2$s)', 'contact-form-7'),
				          '[_site_title]',
				          '[_site_url]'
			          ),
			'recipient' => '[_site_admin_email]',
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0,
		];
	}

	public static function mail_2(): array {
		return [
			'active' => false,
			'subject' => sprintf(
			/* translators: 1: blog name, 2: [your-subject] */
				_x('%1$s "%2$s"', 'mail subject', 'contact-form-7'),
				'[_site_title]',
				'[your-subject]'
			),
			'sender' => sprintf(
				'%s <%s>',
				'[_site_title]',
				self::from_email()
			),
			'body' => __('Message Body:', 'contact-form-7')
			          ."\n".'[your-message]'."\n\n"
			          .'-- '."\n"
			          .sprintf(
			          /* translators: 1: blog name, 2: blog URL */
				          __('This e-mail was sent from a contact form on %1$s (%2$s)', 'contact-form-7'),
				          '[_site_title]',
				          '[_site_url]'
			          ),
			'recipient' => '[your-email]',
			'additional_headers' => sprintf(
				'Reply-To: %s',
				'[_site_admin_email]'
			),
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0,
		];
	}

	public static function from_email() {
		$admin_email = get_option('admin_email');

		if (wpcf7_is_localhost()) {
			return $admin_email;
		}

		$sitename = wp_parse_url(network_home_url(), PHP_URL_HOST);
		$sitename = strtolower($sitename);

		if ('www.' === substr($sitename, 0, 4)) {
			$sitename = substr($sitename, 4);
		}

		if (strpbrk($admin_email, '@') === '@'.$sitename) {
			return $admin_email;
		}

		return 'wordpress@'.$sitename;
	}

	public static function messages(): array {
		$messages = [];

		foreach (wpcf7_messages() as $key => $arr) {
			$messages[$key] = $arr['default'];
		}

		return $messages;
	}
}
