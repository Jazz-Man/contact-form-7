<?php
/**
 * The Akismet integration module.
 *
 * @see https://akismet.com/development/api/
 */

use JazzMan\ContactForm7\Integration\WPCF7_Integration;
use JazzMan\ContactForm7\WPCF7_Submission;

wpcf7_include_module_file('akismet/WPCF7_Akismet.php');

add_action(
    'wpcf7_init',
    'wpcf7_akismet_register_service',
    30,
    0
);

/**
 * Registers the Akismet service.
 */
function wpcf7_akismet_register_service(): void {
    $integration = WPCF7_Integration::get_instance();

    $integration->add_service(
        'akismet',
        WPCF7_Akismet::get_instance()
    );
}

add_filter('wpcf7_spam', 'wpcf7_akismet', 10, 2);

function wpcf7_akismet(bool $spam, WPCF7_Submission $submission) {
    if ($spam) {
        return $spam;
    }

    if (!wpcf7_akismet_is_available()) {
        return false;
    }

    if (!$params = wpcf7_akismet_submitted_params()) {
        return false;
    }

    $c = [];

    $c['comment_author'] = $params['author'];
    $c['comment_author_email'] = $params['author_email'];
    $c['comment_author_url'] = $params['author_url'];
    $c['comment_content'] = $params['content'];

    $c['blog'] = get_option('home');
    $c['blog_lang'] = get_locale();
    $c['blog_charset'] = get_option('blog_charset');
    $c['user_ip'] = $_SERVER['REMOTE_ADDR'];
    $c['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $c['referrer'] = $_SERVER['HTTP_REFERER'];
    $c['comment_type'] = 'contact-form';

    $datetime = date_create_immutable(
        '@'.$submission->get_meta('timestamp')
    );

    if ($datetime) {
        $c['comment_date_gmt'] = $datetime->format(DATE_ATOM);
    }

    if ($permalink = get_permalink()) {
        $c['permalink'] = $permalink;
    }

    $ignore = ['HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW'];

    foreach ($_SERVER as $key => $value) {
        if (!in_array($key, (array) $ignore, true)) {
            $c["{$key}"] = $value;
        }
    }

    $c = apply_filters('wpcf7_akismet_parameters', $c);

    if (wpcf7_akismet_comment_check($c)) {
        $spam = true;

        $submission->add_spam_log([
            'agent' => 'akismet',
            'reason' => __('Akismet returns a spam response.', 'contact-form-7'),
        ]);
    } else {
        $spam = false;
    }

    return $spam;
}

/**
 * Returns true if Akismet is active and has a valid API key.
 */
function wpcf7_akismet_is_available() {
    if (is_callable(['Akismet', 'get_api_key'])) {
        return (bool) Akismet::get_api_key();
    }

    return false;
}

/**
 * Returns an array of parameters based on the current form submission.
 * Returns false if Akismet is not active on the contact form.
 */
function wpcf7_akismet_submitted_params() {
    $akismet_tags = array_filter(
        wpcf7_scan_form_tags(),
        function ($tag) {
            $akismet_option = $tag->get_option(
                'akismet',
                '(author|author_email|author_url)',
                true
            );

            return (bool) $akismet_option;
        }
    );

    if (!$akismet_tags) { // Akismet is not active on this contact form.
        return false;
    }

    $params = [
        'author' => '',
        'author_email' => '',
        'author_url' => '',
        'content' => '',
    ];

    foreach ((array) $_POST as $key => $val) {
        if ('_wpcf7' == substr($key, 0, 6)
        || '_wpnonce' == $key) {
            continue;
        }

        $vals = array_filter(
            wpcf7_array_flatten($val),
            fn ($val) => '' !== trim($val)
        );

        if (empty($vals)) {
            continue;
        }

        if ($tags = wpcf7_scan_form_tags(['name' => $key])) {
            $tag = $tags[0];

            $akismet_option = $tag->get_option(
                'akismet',
                '(author|author_email|author_url)',
                true
            );

            if ('author' === $akismet_option) {
                $params['author'] = sprintf(
                    '%s %s',
                    $params['author'],
                    implode(' ', $vals)
                );

                continue;
            }

            if ('author_email' === $akismet_option
            && '' === $params['author_email']) {
                $params['author_email'] = $vals[0];

                continue;
            }

            if ('author_url' === $akismet_option
            && '' === $params['author_url']) {
                $params['author_url'] = $vals[0];

                continue;
            }

            $vals = array_filter(
                $vals,
                function ($val) use ($tag) {
                    if (wpcf7_form_tag_supports($tag->type, 'selectable-values')
                    && in_array($val, $tag->labels, true)) {
                        return false;
                    }

                    return true;
                }
            );
        }

        if ($vals) {
            $params['content'] .= "\n\n".implode(', ', $vals);
        }
    }

    $params = array_map('trim', $params);

    return $params;
}

/**
 * Sends data to Akismet.
 *
 * @param array $comment submission and environment data
 *
 * @return bool true if Akismet called it spam, or false otherwise
 */
function wpcf7_akismet_comment_check($comment) {
    $spam = false;
    $query_string = wpcf7_build_query($comment);

    if (is_callable(['Akismet', 'http_post'])) {
        $response = Akismet::http_post($query_string, 'comment-check');
    } else {
        return $spam;
    }

    if ('true' == $response[1]) {
        $spam = true;
    }

    if ($submission = WPCF7_Submission::get_instance()) {
        $submission->push('akismet', [
            'comment' => $comment,
            'spam' => $spam,
        ]);
    }

    return apply_filters('wpcf7_akismet_comment_check', $spam, $comment);
}

add_filter('wpcf7_posted_data', 'wpcf7_akismet_posted_data', 10, 1);

/**
 * Removes Akismet-related properties from the posted data.
 *
 * This does not affect the $_POST variable itself.
 *
 * @see https://plugins.trac.wordpress.org/browser/akismet/tags/5.0/_inc/akismet-frontend.js
 *
 * @param mixed $posted_data
 */
function wpcf7_akismet_posted_data($posted_data) {
    if (wpcf7_akismet_is_available()) {
        $posted_data = array_diff_key(
            $posted_data,
            [
                'ak_bib' => '',
                'ak_bfs' => '',
                'ak_bkpc' => '',
                'ak_bkp' => '',
                'ak_bmc' => '',
                'ak_bmcc' => '',
                'ak_bmk' => '',
                'ak_bck' => '',
                'ak_bmmc' => '',
                'ak_btmc' => '',
                'ak_bsc' => '',
                'ak_bte' => '',
                'ak_btec' => '',
                'ak_bmm' => '',
            ]
        );
    }

    return $posted_data;
}

add_filter(
    'wpcf7_default_template',
    'wpcf7_akismet_default_template',
    10,
    2
);

function wpcf7_akismet_default_template(string $template, string $prop): string {
    if (!wpcf7_akismet_is_available()) {
        return $template;
    }

    if ('form' === $prop) {
        $template = str_replace(
            [
                '[text* your-name ',
                '[email* your-email ',
            ],
            [
                '[text* your-name akismet:author ',
                '[email* your-email akismet:author_email ',
            ],
            $template
        );

        $privacy_notice = sprintf(
            '%s %s',
            __('This form uses Akismet to reduce spam.', 'contact-form-7'),
            wpcf7_link(
                'https://akismet.com/privacy/',
                __('Learn how your data is processed.', 'contact-form-7'),
                [
                    'target' => '_blank',
                    'rel' => 'nofollow noopener',
                ]
            )
        );

        $template .= "\n\n".$privacy_notice;
    }

    return $template;
}
