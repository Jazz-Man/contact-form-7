<?php

namespace JazzMan\ContactForm7\Swv;

/**
 * The base class of SWV composite rules.
 */
abstract class WPCF7_SWV_CompositeRule extends WPCF7_SWV_Rule {
    protected $rules = [];

    /**
     * Adds a sub-rule to this composite rule.
     *
     * @param WPCF7_SWV_Rule $rule sub-rule to be added
     */
    public function add_rule($rule): void {
        if ($rule instanceof WPCF7_SWV_Rule) {
            $this->rules[] = $rule;
        }
    }

    /**
     * Returns an iterator of sub-rules.
     */
    public function rules() {
        foreach ($this->rules as $rule) {
            yield $rule;
        }
    }

    /**
     * Returns true if this rule matches the given context.
     *
     * @param array $context context
     */
    public function matches($context) {
        return true;
    }

    /**
     * Validates with this rule's logic.
     *
     * @param array $context context
     */
    public function validate($context) {
        foreach ($this->rules() as $rule) {
            if ($rule->matches($context)) {
                $result = $rule->validate($context);

                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        return true;
    }

    /**
     * Converts the properties to an array.
     *
     * @return array array of properties
     */
    public function to_array() {
        $rules_arrays = array_map(
            fn ($rule) => $rule->to_array(),
            $this->rules
        );

        return array_merge(
            parent::to_array(),
            [
                'rules' => $rules_arrays,
            ]
        );
    }
}
