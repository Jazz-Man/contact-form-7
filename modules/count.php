<?php
/**
 ** A base module for [count], Twitter-like character count.
 */

/* form_tag handler */

use JazzMan\ContactForm7\WPCF7_FormTag;

add_action('wpcf7_init', 'wpcf7_add_form_tag_count', 10, 0);

function wpcf7_add_form_tag_count(): void {
    wpcf7_add_form_tag(
        'count',
        'wpcf7_count_form_tag_handler',
        [
            'name-attr' => true,
            'zero-controls-container' => true,
            'not-for-mail' => true,
        ]
    );
}

function wpcf7_count_form_tag_handler(WPCF7_FormTag $tag): string {
    if (empty($tag->name)) {
        return '';
    }

    $targets = wpcf7_scan_form_tags(['name' => $tag->name]);
    $maxlength = $minlength = null;

    while ($targets) {
        $target = array_shift($targets);

        if ('count' != $target->type) {
            $maxlength = $target->get_maxlength_option();
            $minlength = $target->get_minlength_option();

            break;
        }
    }

    if ($maxlength && $minlength
    && $maxlength < $minlength) {
        $maxlength = $minlength = null;
    }

    if ($tag->has_option('down')) {
        $value = (int) $maxlength;
        $class = 'wpcf7-character-count down';
    } else {
        $value = '0';
        $class = 'wpcf7-character-count up';
    }

    $atts = [];

    $atts['id'] = $tag->get_id_option();
    $atts['class'] = $tag->get_class_option($class);
    $atts['data-target-name'] = $tag->name;
    $atts['data-starting-value'] = $value;
    $atts['data-current-value'] = $value;
    $atts['data-maximum-value'] = $maxlength;
    $atts['data-minimum-value'] = $minlength;

    return sprintf(
        '<span %1$s>%2$s</span>',
        wpcf7_format_atts($atts),
        $value
    );
}
