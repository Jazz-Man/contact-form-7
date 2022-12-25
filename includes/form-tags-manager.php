<?php

use JazzMan\ContactForm7\WPCF7_ContactForm;
use JazzMan\ContactForm7\WPCF7_FormTag;
use JazzMan\ContactForm7\WPCF7_FormTagsManager;

/**
 * Wrapper function of WPCF7_FormTagsManager::add().
 *
 * @param mixed $tag_types
 * @param mixed $features
 */
function wpcf7_add_form_tag($tag_types, callable $callback, $features = '') {
    $manager = WPCF7_FormTagsManager::get_instance();

    return $manager->add($tag_types, $callback, $features);
}

/**
 * Wrapper function of WPCF7_FormTagsManager::remove().
 *
 * @param mixed $tag_type
 */
function wpcf7_remove_form_tag($tag_type) {
    $manager = WPCF7_FormTagsManager::get_instance();

    return $manager->remove($tag_type);
}

/**
 * Wrapper function of WPCF7_FormTagsManager::replace_all().
 *
 * @param mixed $content
 */
function wpcf7_replace_all_form_tags($content) {
    $manager = WPCF7_FormTagsManager::get_instance();

    return $manager->replace_all($content);
}

/**
 * Wrapper function of JazzMan\ContactForm7\WPCF7_ContactForm::scan_form_tags().
 *
 * @param mixed $cond
 *
 * @return WPCF7_FormTag[]
 */
function wpcf7_scan_form_tags($cond = null): array {
    $contact_form = WPCF7_ContactForm::get_current();

    if ($contact_form) {
        return $contact_form->scan_form_tags($cond);
    }

    return [];
}

/**
 * Wrapper function of WPCF7_FormTagsManager::tag_type_supports().
 *
 * @param mixed $tag_type
 * @param mixed $feature
 */
function wpcf7_form_tag_supports($tag_type, $feature) {
    $manager = WPCF7_FormTagsManager::get_instance();

    return $manager->tag_type_supports($tag_type, $feature);
}
