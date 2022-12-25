<?php

use JazzMan\ContactForm7\Admin\WPCF7_TagGenerator;

function wpcf7_current_action() {
    if (isset($_REQUEST['action']) && -1 != $_REQUEST['action']) {
        return $_REQUEST['action'];
    }

    if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2']) {
        return $_REQUEST['action2'];
    }

    return false;
}

function wpcf7_admin_has_edit_cap(): bool {
    return current_user_can('wpcf7_edit_contact_forms');
}

function wpcf7_add_tag_generator($name, $title, $elm_id, $callback, $options = []): bool {
    $tag_generator = WPCF7_TagGenerator::get_instance();

    return $tag_generator->add($name, $title, $callback, $options);
}
