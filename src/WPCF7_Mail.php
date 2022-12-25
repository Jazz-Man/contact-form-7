<?php

namespace JazzMan\ContactForm7;

use JazzMan\ContactForm7\WPCF7_Submission;

class WPCF7_Mail {
	private static WPCF7_Mail $current;

	private string $name = '';

	private string $locale = '';

	private array $template = [];

	private bool $use_html = false;

	private bool $exclude_blank = false;

	private function __construct(string $name, $template) {
		$this->name = trim($name);
		$this->use_html = !empty($template['use_html']);
		$this->exclude_blank = !empty($template['exclude_blank']);

		$this->template = wp_parse_args($template, [
			'subject' => '',
			'sender' => '',
			'body' => '',
			'recipient' => '',
			'additional_headers' => '',
			'attachments' => '',
		]);

		if ($submission = WPCF7_Submission::get_instance()) {
			$contact_form = $submission->get_contact_form();
			$this->locale = $contact_form->locale();
		}
	}

	public static function get_current(): WPCF7_Mail {
		return self::$current;
	}

	public static function send($template, string $name = '') {
		self::$current = new self($name, $template);

		return self::$current->compose();
	}

	public function name(): string {
		return $this->name;
	}

	public function get(string $component, bool $replace_tags = false) {
		$use_html = ($this->use_html && 'body' == $component);
		$exclude_blank = ($this->exclude_blank && 'body' == $component);

		$template = $this->template;
		$component = $template[$component] ?? '';

		if ($replace_tags) {
			$component = $this->replace_tags($component, [
				'html' => $use_html,
				'exclude_blank' => $exclude_blank,
			]);

			if ($use_html
			    && !preg_match('%<html[>\s].*</html>%is', $component)) {
				$component = $this->htmlize($component);
			}
		}

		return $component;
	}

	public function replace_tags($content, $args = '') {
		if (true === $args) {
			$args = ['html' => true];
		}

		$args = wp_parse_args($args, [
			'html' => false,
			'exclude_blank' => false,
		]);

		return wpcf7_mail_replace_tags($content, $args);
	}

	private function htmlize(string $body): string {
		if ($this->locale) {
			$lang_atts = sprintf(
				' %s',
				wpcf7_format_atts([
					'dir' => wpcf7_is_rtl($this->locale) ? 'rtl' : 'ltr',
					'lang' => str_replace('_', '-', $this->locale),
				])
			);
		} else {
			$lang_atts = '';
		}

		$header = apply_filters(
			'wpcf7_mail_html_header',
			'<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml"'.$lang_atts.'>
<head>
<title>'.esc_html($this->get('subject', true)).'</title>
</head>
<body>
',
			$this
		);

		$footer = apply_filters(
			'wpcf7_mail_html_footer',
			'</body>
</html>',
			$this
		);

		return $header.wpcf7_autop($body).$footer;
	}

	private function compose(bool $send = true) {
		$components = [
			'subject' => $this->get('subject', true),
			'sender' => $this->get('sender', true),
			'body' => $this->get('body', true),
			'recipient' => $this->get('recipient', true),
			'additional_headers' => $this->get('additional_headers', true),
			'attachments' => $this->attachments(),
		];

		$components = apply_filters(
			'wpcf7_mail_components',
			$components,
			wpcf7_get_current_contact_form(),
			$this
		);

		if (!$send) {
			return $components;
		}

		$subject = wpcf7_strip_newline($components['subject']);
		$sender = wpcf7_strip_newline($components['sender']);
		$recipient = wpcf7_strip_newline($components['recipient']);
		$body = $components['body'];
		$additional_headers = trim($components['additional_headers']);

		$headers = "From: {$sender}\n";

		if ($this->use_html) {
			$headers .= "Content-Type: text/html\n";
			$headers .= "X-WPCF7-Content-Type: text/html\n";
		} else {
			$headers .= "X-WPCF7-Content-Type: text/plain\n";
		}

		if ($additional_headers) {
			$headers .= $additional_headers."\n";
		}

		$attachments = array_filter(
			(array) $components['attachments'],
			function ($attachment) {
				$path = path_join(WP_CONTENT_DIR, $attachment);

				if (!wpcf7_is_file_path_in_content_dir($path)) {
					if (WP_DEBUG) {
						trigger_error(
							sprintf(
							/* translators: %s: Attachment file path. */
								__('Failed to attach a file. %s is not in the allowed directory.', 'contact-form-7'),
								$path
							),
							E_USER_NOTICE
						);
					}

					return false;
				}

				if (!is_readable($path) || !is_file($path)) {
					if (WP_DEBUG) {
						trigger_error(
							sprintf(
							/* translators: %s: Attachment file path. */
								__('Failed to attach a file. %s is not a readable file.', 'contact-form-7'),
								$path
							),
							E_USER_NOTICE
						);
					}

					return false;
				}

				static $total_size = [];

				if (!isset($total_size[$this->name])) {
					$total_size[$this->name] = 0;
				}

				$file_size = (int) @filesize($path);

				if (25 * MB_IN_BYTES < $total_size[$this->name] + $file_size) {
					if (WP_DEBUG) {
						trigger_error(
							__('Failed to attach a file. The total file size exceeds the limit of 25 megabytes.', 'contact-form-7'),
							E_USER_NOTICE
						);
					}

					return false;
				}

				$total_size[$this->name] += $file_size;

				return true;
			}
		);

		return wp_mail($recipient, $subject, $body, $headers, $attachments);
	}

	private function attachments(?string $template = null): array {
		if (!$template) {
			$template = $this->get('attachments');
		}

		$attachments = [];

		if ($submission = WPCF7_Submission::get_instance()) {
			$uploaded_files = $submission->uploaded_files();

			foreach ((array) $uploaded_files as $name => $paths) {
				if (false !== strpos($template, "[{$name}]")) {
					$attachments = array_merge($attachments, (array) $paths);
				}
			}
		}

		foreach (explode("\n", $template) as $line) {
			$line = trim($line);

			if ('' === $line || '[' == substr($line, 0, 1)) {
				continue;
			}

			$attachments[] = path_join(WP_CONTENT_DIR, $line);
		}

		if ($submission = WPCF7_Submission::get_instance()) {
			$attachments = array_merge(
				$attachments,
				(array) $submission->extra_attachments($this->name)
			);
		}

		return $attachments;
	}
}
