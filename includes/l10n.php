<?php

/**
 * Retrieves an associative array of languages to which
 * this plugin is translated.
 *
 * @return array array of languages
 */
function wpcf7_l10n() {
    static $l10n = [];

    if (!empty($l10n)) {
        return $l10n;
    }

    if (!is_admin()) {
        return $l10n;
    }

    require_once ABSPATH.'wp-admin/includes/translation-install.php';

    $api = translations_api('plugins', [
        'slug' => 'contact-form-7',
        'version' => WPCF7_VERSION,
    ]);

    if (is_wp_error($api)
    || empty($api['translations'])) {
        return $l10n;
    }

    foreach ((array) $api['translations'] as $translation) {
        if (!empty($translation['language'])
        && !empty($translation['english_name'])) {
            $l10n[$translation['language']] = $translation['english_name'];
        }
    }

    return $l10n;
}

/**
 * Returns true if the given locale code looks valid.
 *
 * @param string $locale locale code
 */
function wpcf7_is_valid_locale($locale) {
    if (!is_string($locale)) {
        return false;
    }

    $pattern = '/^[a-z]{2,3}(?:_[a-zA-Z_]{2,})?$/';

    return (bool) preg_match($pattern, $locale);
}

/**
 * Returns true if the given locale is an RTL language.
 *
 * @param mixed $locale
 */
function wpcf7_is_rtl($locale = '') {
    static $rtl_locales = [
        'ar' => 'Arabic',
        'ary' => 'Moroccan Arabic',
        'azb' => 'South Azerbaijani',
        'fa_IR' => 'Persian',
        'haz' => 'Hazaragi',
        'he_IL' => 'Hebrew',
        'ps' => 'Pashto',
        'ug_CN' => 'Uighur',
    ];

    if (empty($locale)
    && function_exists('is_rtl')) {
        return is_rtl();
    }

    if (empty($locale)) {
        $locale = determine_locale();
    }

    return isset($rtl_locales[$locale]);
}

/**
 * Loads a translation file into the plugin's text domain.
 *
 * @param string $locale locale code
 *
 * @return bool true on success, false on failure
 */
function wpcf7_load_textdomain( string $locale = '') {
    $mofile = path_join(
        WP_LANG_DIR.'/plugins/',
        sprintf('%s-%s.mo', WPCF7_TEXT_DOMAIN, $locale)
    );

    return load_textdomain(WPCF7_TEXT_DOMAIN, $mofile, $locale);
}

/**
 * Unloads translations for the plugin's text domain.
 *
 * @param bool $reloadable whether the text domain can be loaded
 *                         just-in-time again
 *
 * @return bool true on success, false on failure
 */
function wpcf7_unload_textdomain(bool $reloadable = false) {
    return unload_textdomain(WPCF7_TEXT_DOMAIN, $reloadable);
}

/**
 * Switches translation locale, calls the callback, then switches back
 * to the original locale.
 *
 * @param string   $locale   locale code
 * @param callable $callback the callable to be called
 * @param mixed    $args     parameters to be passed to the callback
 *
 * @return mixed the return value of the callback
 */
function wpcf7_switch_locale($locale, callable $callback, ...$args) {
    static $available_locales = null;

    if (!isset($available_locales)) {
        $available_locales = array_merge(
            ['en_US'],
            get_available_languages()
        );
    }

    $previous_locale = determine_locale();

    $do_switch_locale = (
        $locale !== $previous_locale
        && in_array($locale, $available_locales, true)
        && in_array($previous_locale, $available_locales, true)
    );

    if ($do_switch_locale) {
        wpcf7_unload_textdomain();
        switch_to_locale($locale);
        wpcf7_load_textdomain($locale);
    }

    $result = call_user_func($callback, ...$args);

    if ($do_switch_locale) {
        wpcf7_unload_textdomain(true);
        restore_previous_locale();
        wpcf7_load_textdomain($previous_locale);
    }

    return $result;
}
