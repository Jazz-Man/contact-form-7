<?php

namespace JazzMan\ContactForm7;

/**
 * The singleton instance of this class manages the collection of form-tags.
 */
class WPCF7_FormTagsManager {
	private static WPCF7_FormTagsManager $instance;

	private array $tag_types = [];

	private $scanned_tags; // Tags scanned at the last time of scan()

	private $placeholders = [];

	private function __construct() {
	}

	/**
	 * Returns the singleton instance.
	 *
	 * @return WPCF7_FormTagsManager the singleton manager
	 */
	public static function get_instance(): self {
		if (empty(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns scanned form-tags.
	 *
	 * @return array array of JazzMan\ContactForm7\WPCF7_FormTag objects
	 */
	public function get_scanned_tags() {
		return $this->scanned_tags;
	}

	/**
	 * Registers form-tag types to the manager.
	 *
	 * @param array|string $tag_types the name of the form-tag type or
	 *                                an array of the names
	 * @param callable     $callback  the callback to generates a form control HTML
	 *                                for a form-tag in this type
	 * @param array|string $features  Optional. Features a form-tag
	 *                                in this type supports.
	 */
	public function add($tag_types, callable $callback, $features = ''): void {
		if (!is_callable($callback)) {
			return;
		}

		if (true === $features) { // for back-compat
			$features = ['name-attr' => true];
		}

		$features = wp_parse_args($features, []);

		$tag_types = array_filter(array_unique((array) $tag_types));

		foreach ($tag_types as $tag_type) {
			$tag_type = $this->sanitize_tag_type($tag_type);

			if (!$this->tag_type_exists($tag_type)) {
				$this->tag_types[$tag_type] = [
					'function' => $callback,
					'features' => $features,
				];
			}
		}
	}

	/**
	 * Returns true if the given tag type exists.
	 */
	public function tag_type_exists(string $tag_type): bool {
		return isset($this->tag_types[$tag_type]);
	}

	/**
	 * Returns true if the tag type supports the features.
	 *
	 * @param string       $tag_type the name of the form-tag type
	 * @param array|string $features the feature to check or an array of features
	 *
	 * @return bool true if the form-tag type supports at least one of
	 *              the given features, false otherwise
	 */
	public function tag_type_supports(string $tag_type, $features): bool {
		$features = array_filter((array) $features);

		if (isset($this->tag_types[$tag_type]['features'])) {
			return (bool) array_intersect(
				array_keys(array_filter($this->tag_types[$tag_type]['features'])),
				$features
			);
		}

		return false;
	}

	/**
	 * Returns form-tag types that support the given features.
	 *
	 * @param array|string $features Optional. The feature to check or
	 *                               an array of features. Default empty array.
	 * @param bool         $invert   Optional. If this value is true, returns form-tag
	 *                               types that do not support the given features. Default false.
	 *
	 * @return array An array of form-tag types. If the $features param is empty,
	 *               returns all form-tag types that have been registered.
	 */
	public function collect_tag_types($features = [], bool $invert = false) {
		$tag_types = array_keys($this->tag_types);

		if (empty($features)) {
			return $tag_types;
		}

		$output = [];

		foreach ($tag_types as $tag_type) {
			if (!$invert && $this->tag_type_supports($tag_type, $features)
			    || $invert && !$this->tag_type_supports($tag_type, $features)) {
				$output[] = $tag_type;
			}
		}

		return $output;
	}

	/**
	 * Deregisters the form-tag type.
	 */
	public function remove(string $tag_type): void {
		unset($this->tag_types[$tag_type]);
	}

	/**
	 * Normalizes the text content that includes form-tags.
	 */
	public function normalize(string $content) {
		if (empty($this->tag_types)) {
			return $content;
		}

		return preg_replace_callback(
			'/'.$this->tag_regex().'/s',
			[$this, 'normalize_callback'],
			$content
		);
	}

	/**
	 * Replace all form-tags in the given text with placeholders.
	 */
	public function replace_with_placeholders(string $content) {
		if (empty($this->tag_types)) {
			return $content;
		}

		$this->placeholders = [];

		$callback = function (array $matches) {
			// Allow [[foo]] syntax for escaping a tag.
			if ('[' === $matches[1] && ']' === $matches[6]) {
				return $matches[0];
			}

			$tag = $matches[0];
			$tag_type = $matches[2];

			$block_or_hidden = $this->tag_type_supports(
				$tag_type,
				['display-block', 'display-hidden']
			);

			if ($block_or_hidden) {
				$placeholder_tag_name = WPCF7_HTMLFormatter::placeholder_block;
			} else {
				$placeholder_tag_name = WPCF7_HTMLFormatter::placeholder_inline;
			}

			$placeholder = sprintf(
				'<%1$s id="%2$s" />',
				$placeholder_tag_name,
				sha1($tag)
			);

			[ $placeholder ] =
				WPCF7_HTMLFormatter::normalize_start_tag($placeholder);

			$this->placeholders[$placeholder] = $tag;

			return $placeholder;
		};

		return preg_replace_callback(
			'/'.$this->tag_regex().'/s',
			$callback,
			$content
		);
	}

	/**
	 * Replace placeholders in the given text with original form-tags.
	 */
	public function restore_from_placeholders(string $content) {
		return str_replace(
			array_keys($this->placeholders),
			array_values($this->placeholders),
			$content
		);
	}

	/**
	 * Replaces all form-tags in the text content.
	 *
	 * @param string $content the text content including form-tags
	 *
	 * @return string the result of replacements
	 */
	public function replace_all(string $content) {
		return $this->scan($content, true);
	}

	/**
	 * Scans form-tags in the text content.
	 *
	 * @param string $content the text content including form-tags
	 * @param bool   $replace Optional. Whether scanned form-tags will be
	 *                        replaced. Default false.
	 *
	 * @return array|string An array of scanned form-tags if $replace is false.
	 *                      Otherwise text that scanned form-tags are replaced.
	 */
	public function scan(string $content, bool $replace = false) {
		$this->scanned_tags = [];

		if (empty($this->tag_types)) {
			if ($replace) {
				return $content;
			}

			return $this->scanned_tags;
		}

		if ($replace) {
			return preg_replace_callback(
				'/'.$this->tag_regex().'/s',
				[$this, 'replace_callback'],
				$content
			);
		}
		preg_replace_callback(
			'/'.$this->tag_regex().'/s',
			[$this, 'scan_callback'],
			$content
		);

		return $this->scanned_tags;
	}

	/**
	 * Filters form-tags based on a condition array argument.
	 *
	 * @param array|string $input The original form-tags collection.
	 *                            If it is a string, scans form-tags from it.
	 * @param array|string        $cond  the conditions that filtering will be based on
	 *
	 * @return \JazzMan\ContactForm7\WPCF7_FormTag[] the filtered form-tags collection
	 */
	public function filter($input, $cond): array {
		if (is_array($input)) {
			$tags = $input;
		} elseif (is_string($input)) {
			$tags = $this->scan($input);
		} else {
			$tags = $this->scanned_tags;
		}

		$cond = wp_parse_args($cond, [
			'type' => [],
			'basetype' => [],
			'name' => [],
			'feature' => [],
		]);

		$cond = array_map(fn ($c) => array_filter(array_map('trim', (array) $c)), $cond);

		$tags = array_filter(
			(array) $tags,
			function ($tag) use ($cond) {
				$tag = new WPCF7_FormTag($tag);

				if ($cond['type']
				    && !in_array($tag->type, $cond['type'], true)) {
					return false;
				}

				if ($cond['basetype']
				    && !in_array($tag->basetype, $cond['basetype'], true)) {
					return false;
				}

				if ($cond['name']
				    && !in_array($tag->name, $cond['name'], true)) {
					return false;
				}

				foreach ($cond['feature'] as $feature) {
					if ('!' === substr($feature, 0, 1)) { // Negation
						$feature = trim(substr($feature, 1));

						if ($this->tag_type_supports($tag->type, $feature)) {
							return false;
						}
					} else {
						if (!$this->tag_type_supports($tag->type, $feature)) {
							return false;
						}
					}
				}

				return true;
			}
		);

		return array_values($tags);
	}

	/**
	 * Sanitizes the form-tag type name.
	 */
	private function sanitize_tag_type(string $tag_type): string {
		$tag_type = preg_replace('/[^a-zA-Z0-9_*]+/', '_', $tag_type);
		$tag_type = rtrim($tag_type, '_');

		return strtolower($tag_type);
	}

	/**
	 * The callback function used within normalize().
	 */
	private function normalize_callback(array $matches) {
		// allow [[foo]] syntax for escaping a tag
		if ('[' == $matches[1]
		    && ']' == $matches[6]) {
			return $matches[0];
		}

		$tag = $matches[2];

		$attr = trim(preg_replace('/[\r\n\t ]+/', ' ', $matches[3]));
		$attr = strtr($attr, ['<' => '&lt;', '>' => '&gt;']);

		$content = trim($matches[5]);
		$content = str_replace("\n", '<WPPreserveNewline />', $content);

		return $matches[1].'['.$tag
		       .($attr ? ' '.$attr : '')
		       .($matches[4] ? ' '.$matches[4] : '')
		       .']'
		       .($content ? $content.'[/'.$tag.']' : '')
		       .$matches[6];
	}

	/**
	 * Returns the regular expression for a form-tag.
	 */
	private function tag_regex(): string {
		$tagnames = array_keys($this->tag_types);
		$tagregexp = implode('|', array_map('preg_quote', $tagnames));

		return '(\[?)'
		       .'\[('.$tagregexp.')(?:[\r\n\t ](.*?))?(?:[\r\n\t ](\/))?\]'
		       .'(?:([^[]*?)\[\/\2\])?'
		       .'(\]?)';
	}

	/**
	 * The callback function for the form-tag replacement.
	 */
	private function replace_callback(array $matches) {
		return $this->scan_callback($matches, true);
	}

	/**
	 * The callback function for the form-tag scanning.
	 */
	private function scan_callback(array $matches, bool $replace = false) {
		// allow [[foo]] syntax for escaping a tag
		if ('[' == $matches[1]
		    && ']' == $matches[6]) {
			return substr($matches[0], 1, -1);
		}

		$tag_type = $matches[2];
		$tag_basetype = trim($tag_type, '*');
		$attr = $this->parse_atts($matches[3]);

		$scanned_tag = [
			'type' => $tag_type,
			'basetype' => $tag_basetype,
			'raw_name' => '',
			'name' => '',
			'options' => [],
			'raw_values' => [],
			'values' => [],
			'pipes' => null,
			'labels' => [],
			'attr' => '',
			'content' => '',
		];

		if ($this->tag_type_supports($tag_type, 'singular')) {
			$tags_in_same_basetype = $this->filter(
				$this->scanned_tags,
				['basetype' => $tag_basetype]
			);

			if ($tags_in_same_basetype) {
				// Another tag in the same base type already exists. Ignore this one.
				return $matches[0];
			}
		}

		if (is_array($attr)) {
			if (is_array($attr['options'])) {
				if ($this->tag_type_supports($tag_type, 'name-attr')
				    && !empty($attr['options'])) {
					$scanned_tag['raw_name'] = array_shift($attr['options']);

					if (!wpcf7_is_name($scanned_tag['raw_name'])) {
						return $matches[0]; // Invalid name is used. Ignore this tag.
					}

					$scanned_tag['name'] = strtr($scanned_tag['raw_name'], '.', '_');
				}

				$scanned_tag['options'] = (array) $attr['options'];
			}

			$scanned_tag['raw_values'] = (array) $attr['values'];

			if (WPCF7_USE_PIPE) {
				$pipes = new WPCF7_Pipes($scanned_tag['raw_values']);
				$scanned_tag['values'] = $pipes->collect_befores();
				$scanned_tag['pipes'] = $pipes;
			} else {
				$scanned_tag['values'] = $scanned_tag['raw_values'];
			}

			$scanned_tag['labels'] = $scanned_tag['values'];
		} else {
			$scanned_tag['attr'] = $attr;
		}

		$scanned_tag['values'] = array_map('trim', $scanned_tag['values']);
		$scanned_tag['labels'] = array_map('trim', $scanned_tag['labels']);

		$content = trim($matches[5]);
		$content = preg_replace("/<br[\r\n\t ]*\\/?>$/m", '', $content);
		$scanned_tag['content'] = $content;

		$scanned_tag = apply_filters('wpcf7_form_tag', $scanned_tag, $replace);

		$scanned_tag = new WPCF7_FormTag($scanned_tag);

		$this->scanned_tags[] = $scanned_tag;

		if ($replace) {
			$callback = $this->tag_types[$tag_type]['function'];

			return $matches[1].call_user_func($callback, $scanned_tag).$matches[6];
		}

		return $matches[0];
	}

	/**
	 * Parses the attributes of a form-tag to extract the name,
	 * options, and values.
	 *
	 * @param string $text attributes of a form-tag
	 *
	 * @return array|string an associative array of the options and values
	 *                      if the input is in the correct syntax,
	 *                      otherwise the input text itself
	 */
	private function parse_atts(string $text) {
		$atts = ['options' => [], 'values' => []];
		$text = preg_replace('/[\\x{00a0}\\x{200b}]+/u', ' ', $text);
		$text = trim($text);

		$pattern = '%^([-+*=0-9a-zA-Z:.!?#$&@_/|\%\r\n\t ]*?)((?:[\r\n\t ]*"[^"]*"|[\r\n\t ]*\'[^\']*\')*)$%';

		if (preg_match($pattern, $text, $matches)) {
			if (!empty($matches[1])) {
				$atts['options'] = preg_split('/[\r\n\t ]+/', trim($matches[1]));
			}

			if (!empty($matches[2])) {
				preg_match_all('/"[^"]*"|\'[^\']*\'/', $matches[2], $matched_values);
				$atts['values'] = wpcf7_strip_quote_deep($matched_values[0]);
			}
		} else {
			$atts = $text;
		}

		return $atts;
	}
}
