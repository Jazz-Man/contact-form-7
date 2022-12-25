<?php
/**
 * All the functions and classes in this file are deprecated.
 * You should not use them. The functions and classes will be
 * removed in a later version.
 *
 * @param mixed $tag
 * @param mixed $callback
 * @param mixed $has_name
 */

use JazzMan\ContactForm7\WPCF7_FormTag;

/**
 * @deprecated
 */
function wpcf7_add_shortcode($tag, $callback, $has_name = false) {
    wpcf7_deprecated_function(__FUNCTION__, '4.6', 'wpcf7_add_form_tag');

    return wpcf7_add_form_tag($tag, $callback, $has_name);
}

/**
 * @deprecated
 */
function wpcf7_remove_shortcode($tag) {
    wpcf7_deprecated_function(__FUNCTION__, '4.6', 'wpcf7_remove_form_tag');

    return wpcf7_remove_form_tag($tag);
}

/**
 * @deprecated
 *
 * @return null|array|string
 */
function wpcf7_do_shortcode($content) {
    wpcf7_deprecated_function(
        __FUNCTION__,
        '4.6',
        'wpcf7_replace_all_form_tags'
    );

    return wpcf7_replace_all_form_tags($content);
}

/**
 * @deprecated
 *
 * @return array
 */
function wpcf7_scan_shortcode($cond = null) {
    wpcf7_deprecated_function(__FUNCTION__, '4.6', 'wpcf7_scan_form_tags');

    return wpcf7_scan_form_tags($cond);
}

/**
 * @deprecated
 */
class WPCF7_Shortcode extends WPCF7_FormTag {
    public function __construct($tag) {
        wpcf7_deprecated_function('WPCF7_Shortcode', '4.6', 'JazzMan\ContactForm7\WPCF7_FormTag' );

        parent::__construct($tag);
    }
}
