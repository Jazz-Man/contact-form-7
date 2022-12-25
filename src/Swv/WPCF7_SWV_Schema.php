<?php

namespace JazzMan\ContactForm7\Swv;

/**
 * The schema class as a composite rule.
 */
class WPCF7_SWV_Schema extends WPCF7_SWV_CompositeRule {
    public const version = 'Contact Form 7 SWV Schema 2022-10';

    public function __construct($properties = '') {
        $this->properties = wp_parse_args($properties, [
            'version' => self::version,
        ]);
    }
}
