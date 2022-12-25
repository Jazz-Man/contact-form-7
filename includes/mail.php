<?php

use JazzMan\ContactForm7\WPCF7_MailTaggedText;

/**
 * @param array|string $content
 * @param array|string $args
 *
 * @return array|string
 */
function wpcf7_mail_replace_tags($content, $args = '') {
    $args = wp_parse_args($args, [
        'html' => false,
        'exclude_blank' => false,
    ]);

    if (is_array($content)) {
        foreach ($content as $key => $value) {
            $content[$key] = wpcf7_mail_replace_tags($value, $args);
        }

        return $content;
    }

    $content = explode("\n", $content);

    foreach ($content as $num => $line) {
        $line = new WPCF7_MailTaggedText($line, $args);
        $replaced = $line->replace_tags();

        if ($args['exclude_blank']) {
            $replaced_tags = $line->get_replaced_tags();

            if (empty($replaced_tags)
            || array_filter($replaced_tags, 'strlen')) {
                $content[$num] = $replaced;
            } else {
                unset($content[$num]); // Remove a line.
            }
        } else {
            $content[$num] = $replaced;
        }
    }

    return implode("\n", $content);
}

add_action('phpmailer_init', 'wpcf7_phpmailer_init', 10, 1);

function wpcf7_phpmailer_init(PHPMailer $phpmailer): void {
    $custom_headers = $phpmailer->getCustomHeaders();
    $phpmailer->clearCustomHeaders();
    $wpcf7_content_type = false;

    foreach ((array) $custom_headers as $custom_header) {
        $name = $custom_header[0];
        $value = $custom_header[1];

        if ('X-WPCF7-Content-Type' === $name) {
            $wpcf7_content_type = trim($value);
        } else {
            try {
                $phpmailer->addCustomHeader($name, $value);
            } catch (\PHPMailer\PHPMailer\Exception $e) {
            }
        }
    }

    if ('text/html' === $wpcf7_content_type) {
        try {
            $phpmailer->msgHTML($phpmailer->Body);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
        }
    } elseif ('text/plain' === $wpcf7_content_type) {
        $phpmailer->AltBody = '';
    }
}
