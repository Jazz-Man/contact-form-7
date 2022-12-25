<?php

function wpcf7_messages() {
    $messages = [
        'mail_sent_ok' => [
            'description' => __("Sender's message was sent successfully", 'contact-form-7'),
            'default' => __('Thank you for your message. It has been sent.', 'contact-form-7'),
        ],

        'mail_sent_ng' => [
            'description' => __("Sender's message failed to send", 'contact-form-7'),
            'default' => __('There was an error trying to send your message. Please try again later.', 'contact-form-7'),
        ],

        'validation_error' => [
            'description' => __('Validation errors occurred', 'contact-form-7'),
            'default' => __('One or more fields have an error. Please check and try again.', 'contact-form-7'),
        ],

        'spam' => [
            'description' => __('Submission was referred to as spam', 'contact-form-7'),
            'default' => __('There was an error trying to send your message. Please try again later.', 'contact-form-7'),
        ],

        'accept_terms' => [
            'description' => __('There are terms that the sender must accept', 'contact-form-7'),
            'default' => __('You must accept the terms and conditions before sending your message.', 'contact-form-7'),
        ],

        'invalid_required' => [
            'description' => __('There is a field that the sender must fill in', 'contact-form-7'),
            'default' => __('Please fill out this field.', 'contact-form-7'),
        ],

        'invalid_too_long' => [
            'description' => __('There is a field with input that is longer than the maximum allowed length', 'contact-form-7'),
            'default' => __('This field has a too long input.', 'contact-form-7'),
        ],

        'invalid_too_short' => [
            'description' => __('There is a field with input that is shorter than the minimum allowed length', 'contact-form-7'),
            'default' => __('This field has a too short input.', 'contact-form-7'),
        ],
    ];

    return apply_filters('wpcf7_messages', $messages);
}
