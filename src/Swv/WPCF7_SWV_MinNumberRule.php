<?php

namespace JazzMan\ContactForm7\Swv;

class WPCF7_SWV_MinNumberRule extends WPCF7_SWV_Rule {
    public const rule_name = 'minnumber';

    public function matches($context) {
        if (false === parent::matches($context)) {
            return false;
        }

        if (empty($context['text'])) {
            return false;
        }

        return true;
    }

    public function validate($context) {
        $field = $this->get_property('field');
        $input = $_POST[$field] ?? '';
        $input = \wpcf7_array_flatten($input);
        $input = \wpcf7_exclude_blank($input);

        $threshold = $this->get_property('threshold');

        if (!\wpcf7_is_number($threshold)) {
            return true;
        }

        foreach ($input as $i) {
            if (\wpcf7_is_number($i) && (float) $i < (float) $threshold) {
                return new \WP_Error(
                    'wpcf7_invalid_minnumber',
                    $this->get_property('error')
                );
            }
        }

        return true;
    }

    public function to_array() {
        return ['rule' => self::rule_name] + (array) $this->properties;
    }
}
