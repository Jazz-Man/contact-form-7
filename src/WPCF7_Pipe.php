<?php

namespace JazzMan\ContactForm7;

/**
 * Class representing a pair of pipe.
 */
class WPCF7_Pipe {
	public ?string $before = '';

	public ?string $after = '';

	public function __construct($text) {
		$text = (string) $text;

		$pipe_pos = strpos($text, '|');

		if (false === $pipe_pos) {
			$this->before = $this->after = trim($text);
		} else {
			$this->before = trim(substr($text, 0, $pipe_pos));
			$this->after = trim(substr($text, $pipe_pos + 1));
		}
	}
}
