<?php

namespace JazzMan\ContactForm7;

class WPCF7_MailTaggedText {
	private bool $html = false;

	private $callback;

	private string $content = '';

	private array $replaced_tags = [];

	public function __construct(string $content, $args = '') {
		$args = wp_parse_args($args, [
			'html' => false,
			'callback' => null,
		]);

		$this->html = (bool) $args['html'];

		if (null !== $args['callback']
		    && is_callable($args['callback'])) {
			$this->callback = $args['callback'];
		} elseif ($this->html) {
			$this->callback = [$this, 'replace_tags_callback_html'];
		} else {
			$this->callback = [$this, 'replace_tags_callback'];
		}

		$this->content = $content;
	}

	public function get_replaced_tags(): array {
		return $this->replaced_tags;
	}

	public function replace_tags() {
		$regex = '/(\[?)\[[\t ]*'
		         .'([a-zA-Z_][0-9a-zA-Z:._-]*)' // [2] = name
		         .'((?:[\t ]+"[^"]*"|[\t ]+\'[^\']*\')*)' // [3] = values
		         .'[\t ]*\](\]?)/';

		return preg_replace_callback($regex, $this->callback, $this->content);
	}

	public function format($original, $format): array {
		$original = (array) $original;

		foreach ($original as $key => $value) {
			if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
				$datetime = date_create($value, wp_timezone());

				if (false !== $datetime) {
					$original[$key] = wp_date($format, $datetime->getTimestamp());
				}
			}
		}

		return $original;
	}

	private function replace_tags_callback_html($matches) {
		return $this->replace_tags_callback($matches, true);
	}

	private function replace_tags_callback(array $matches, bool $html = false) {
		// allow [[foo]] syntax for escaping a tag
		if ('[' == $matches[1]
		    && ']' == $matches[4]) {
			return substr($matches[0], 1, -1);
		}

		$tag = $matches[0];
		$tagname = $matches[2];
		$values = $matches[3];

		$mail_tag = new WPCF7_MailTag($tag, $tagname, $values);
		$field_name = $mail_tag->field_name();

		$submission = WPCF7_Submission::get_instance();
		$submitted = $submission
			? $submission->get_posted_data($field_name)
			: null;

		if ($mail_tag->get_option('do_not_heat')) {
			$submitted = isset($_POST[$field_name])
				? wp_unslash($_POST[$field_name])
				: '';
		}

		$replaced = $submitted;

		if (null !== $replaced) {
			if ($format = $mail_tag->get_option('format')) {
				$replaced = $this->format($replaced, $format);
			}

			$replaced = wpcf7_flat_join($replaced, [
				'separator' => wp_get_list_item_separator(),
			]);

			if ($html) {
				$replaced = esc_html($replaced);
				$replaced = wptexturize($replaced);
			}
		}

		if ($form_tag = $mail_tag->corresponding_form_tag()) {
			$type = $form_tag->type;

			$replaced = apply_filters(
				"wpcf7_mail_tag_replaced_{$type}",
				$replaced,
				$submitted,
				$html,
				$mail_tag
			);
		}

		$replaced = apply_filters(
			'wpcf7_mail_tag_replaced',
			$replaced,
			$submitted,
			$html,
			$mail_tag
		);

		if (null !== $replaced) {
			$replaced = trim($replaced);

			$this->replaced_tags[$tag] = $replaced;

			return $replaced;
		}

		$special = apply_filters(
			'wpcf7_special_mail_tags',
			null,
			$mail_tag->tag_name(),
			$html,
			$mail_tag
		);

		if (null !== $special) {
			$this->replaced_tags[$tag] = $special;

			return $special;
		}

		return $tag;
	}
}
