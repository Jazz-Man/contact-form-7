<?php

class WPCF7_SWV_NumberRule extends WPCF7_SWV_Rule {
    public const rule_name = 'number';

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
        $input = wpcf7_array_flatten($input);
        $input = wpcf7_exclude_blank($input);

        foreach ($input as $i) {
            if (!wpcf7_is_number($i)) {
                return new WP_Error(
                    'wpcf7_invalid_number',
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
