<?php
/**
 * Schema-Woven Validation API.
 */

use JazzMan\ContactForm7\Swv\WPCF7_SWV_DateRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_EmailRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_EnumRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_FileRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MaxDateRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MaxFileSizeRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MaxItemsRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MaxLengthRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MaxNumberRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MinDateRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MinFileSizeRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MinItemsRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MinLengthRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_MinNumberRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_NumberRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_RequiredFileRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_RequiredRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_Rule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_TelRule;
use JazzMan\ContactForm7\Swv\WPCF7_SWV_URLRule;

require_once WPCF7_PLUGIN_DIR.'/includes/swv/script-loader.php';

/**
 * Returns an associative array of SWV rules.
 */
function wpcf7_swv_available_rules() {
    $rules = [
        'required' => WPCF7_SWV_RequiredRule::class,
        'requiredfile' => WPCF7_SWV_RequiredFileRule::class,
        'email' => WPCF7_SWV_EmailRule::class,
        'url' => WPCF7_SWV_URLRule::class,
        'tel' => WPCF7_SWV_TelRule::class,
        'number' => WPCF7_SWV_NumberRule::class,
        'date' => WPCF7_SWV_DateRule::class,
        'file' => WPCF7_SWV_FileRule::class,
        'enum' => WPCF7_SWV_EnumRule::class,
        'minitems' => WPCF7_SWV_MinItemsRule::class,
        'maxitems' => WPCF7_SWV_MaxItemsRule::class,
        'minlength' => WPCF7_SWV_MinLengthRule::class,
        'maxlength' => WPCF7_SWV_MaxLengthRule::class,
        'minnumber' => WPCF7_SWV_MinNumberRule::class,
        'maxnumber' => WPCF7_SWV_MaxNumberRule::class,
        'mindate' => WPCF7_SWV_MinDateRule::class,
        'maxdate' => WPCF7_SWV_MaxDateRule::class,
        'minfilesize' => WPCF7_SWV_MinFileSizeRule::class,
        'maxfilesize' => WPCF7_SWV_MaxFileSizeRule::class,
    ];

    return apply_filters('wpcf7_swv_available_rules', $rules);
}

/**
 * Creates an SWV rule object.
 *
 * @param string       $rule_name  rule name
 * @param array|string $properties Optional. Rule properties.
 *
 * @return null|WPCF7_SWV_Rule the rule object, or null if it failed
 */
function wpcf7_swv_create_rule(string $rule_name, $properties = ''): ?WPCF7_SWV_Rule {
    $rules = wpcf7_swv_available_rules();

    if (isset($rules[$rule_name])) {
        return new $rules[$rule_name]($properties);
    }
	return null;
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
