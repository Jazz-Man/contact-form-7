<?php
/**
 * Schema-Woven Validation API.
 */
require_once WPCF7_PLUGIN_DIR.'/includes/swv/schema-holder.php';

require_once WPCF7_PLUGIN_DIR.'/includes/swv/script-loader.php';

/**
 * Returns an associative array of SWV rules.
 */
function wpcf7_swv_available_rules() {
    $rules = [
        'required' => 'WPCF7_SWV_RequiredRule',
        'requiredfile' => 'WPCF7_SWV_RequiredFileRule',
        'email' => 'WPCF7_SWV_EmailRule',
        'url' => 'WPCF7_SWV_URLRule',
        'tel' => 'WPCF7_SWV_TelRule',
        'number' => 'WPCF7_SWV_NumberRule',
        'date' => 'WPCF7_SWV_DateRule',
        'file' => 'WPCF7_SWV_FileRule',
        'enum' => 'WPCF7_SWV_EnumRule',
        'minitems' => 'WPCF7_SWV_MinItemsRule',
        'maxitems' => 'WPCF7_SWV_MaxItemsRule',
        'minlength' => 'WPCF7_SWV_MinLengthRule',
        'maxlength' => 'WPCF7_SWV_MaxLengthRule',
        'minnumber' => 'WPCF7_SWV_MinNumberRule',
        'maxnumber' => 'WPCF7_SWV_MaxNumberRule',
        'mindate' => 'WPCF7_SWV_MinDateRule',
        'maxdate' => 'WPCF7_SWV_MaxDateRule',
        'minfilesize' => 'WPCF7_SWV_MinFileSizeRule',
        'maxfilesize' => 'WPCF7_SWV_MaxFileSizeRule',
    ];

    return apply_filters('wpcf7_swv_available_rules', $rules);
}

add_action('wpcf7_init', 'wpcf7_swv_load_rules', 10, 0);

/**
 * Loads SWV fules.
 */
function wpcf7_swv_load_rules(): void {
    $rules = wpcf7_swv_available_rules();

    foreach (array_keys($rules) as $rule) {
        $file = sprintf('%s.php', $rule);
        $path = path_join(WPCF7_PLUGIN_DIR.'/includes/swv/rules', $file);

        if (file_exists($path)) {
            include_once $path;
        }
    }
}

/**
 * Creates an SWV rule object.
 *
 * @param string       $rule_name  rule name
 * @param array|string $properties Optional. Rule properties.
 *
 * @return null|WPCF7_SWV_Rule the rule object, or null if it failed
 */
function wpcf7_swv_create_rule($rule_name, $properties = '') {
    $rules = wpcf7_swv_available_rules();

    if (isset($rules[$rule_name])) {
        return new $rules[$rule_name]($properties);
    }
}

/**
 * Returns an associative array of JSON Schema for Contact Form 7 SWV.
 */
function wpcf7_swv_get_meta_schema() {
    return [
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'title' => 'Contact Form 7 SWV',
        'description' => 'Contact Form 7 SWV meta-schema',
        'type' => 'object',
        'properties' => [
            'version' => [
                'type' => 'string',
            ],
            'locale' => [
                'type' => 'string',
            ],
            'rules' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'rule' => [
                            'type' => 'string',
                            'enum' => array_keys(wpcf7_swv_available_rules()),
                        ],
                        'field' => [
                            'type' => 'string',
                            'pattern' => '^[A-Za-z][-A-Za-z0-9_:]*$',
                        ],
                        'error' => [
                            'type' => 'string',
                        ],
                        'accept' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'threshold' => [
                            'type' => 'string',
                        ],
                    ],
                    'required' => ['rule'],
                ],
            ],
        ],
    ];
}

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
            if ($field && !in_array($field, (array) $context['field'], true)) {
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
