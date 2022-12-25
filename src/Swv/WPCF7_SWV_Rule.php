<?php

namespace JazzMan\ContactForm7\Swv;

/**
 * The base class of SWV rules.
 */
abstract class WPCF7_SWV_Rule {
    protected $properties = [];

    public function __construct($properties = '') {
        $this->properties = wp_parse_args($properties, []);
    }

    /**
     * Returns true if this rule matches the given context.
     *
     * @param array $context context
     */
    public function matches(array $context) {
        $field = $this->get_property('field');

        if (!empty($context['field'])) {
            if ($field && !\in_array($field, (array) $context['field'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates with this rule's logic.
     *
     * @param array $context context
     */
    public function validate($context) {
        return true;
    }

    /**
     * Converts the properties to an array.
     *
     * @return array array of properties
     */
    public function to_array() {
        return (array) $this->properties;
    }

    /**
     * Returns the property value specified by the given property name.
     *
     * @param string $name property name
     *
     * @return mixed property value
     */
    public function get_property(string $name) {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }
    }
}
