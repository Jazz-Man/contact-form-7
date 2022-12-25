<?php

use JazzMan\ContactForm7\Admin\WPCF7_WelcomePanelColumn_AntiSpam;
use JazzMan\ContactForm7\Admin\WPCF7_WelcomePanelColumn_Donation;
use JazzMan\ContactForm7\Admin\WPCF7_WelcomePanelColumn_Flamingo;
use JazzMan\ContactForm7\Admin\WPCF7_WelcomePanelColumn_Integration;

function wpcf7_welcome_panel(): void {
    $columns = [];

    $flamingo_is_active = defined('FLAMINGO_VERSION');

    $sendinblue_is_active = false;

    if (class_exists('WPCF7_Sendinblue')
    && $sendinblue = WPCF7_Sendinblue::get_instance()) {
        $sendinblue_is_active = $sendinblue->is_active();
    }

    if ($flamingo_is_active && $sendinblue_is_active) {
        $columns[] = new WPCF7_WelcomePanelColumn_AntiSpam();
        $columns[] = new WPCF7_WelcomePanelColumn_Donation();
    } elseif ($flamingo_is_active) {
        $columns[] = new WPCF7_WelcomePanelColumn_Integration();
        $columns[] = new WPCF7_WelcomePanelColumn_AntiSpam();
    } elseif ($sendinblue_is_active) {
        $columns[] = new WPCF7_WelcomePanelColumn_Flamingo();
        $columns[] = new WPCF7_WelcomePanelColumn_AntiSpam();
    } else {
        $columns[] = new WPCF7_WelcomePanelColumn_Flamingo();
        $columns[] = new WPCF7_WelcomePanelColumn_Integration();
    }

    $classes = 'wpcf7-welcome-panel';

    $vers = (array) get_user_meta(
        get_current_user_id(),
        'wpcf7_hide_welcome_panel_on',
        true
    );

    if (wpcf7_version_grep(wpcf7_version('only_major=1'), $vers)) {
        $classes .= ' hidden';
    }

    ?>
<div id="wpcf7-welcome-panel" class="<?php echo esc_attr($classes); ?>">
	<?php wp_nonce_field('wpcf7-welcome-panel-nonce', 'welcomepanelnonce', false); ?>
	<a class="welcome-panel-close" href="<?php echo esc_url(menu_page_url('wpcf7', false)); ?>"><?php echo esc_html(__('Dismiss', 'contact-form-7')); ?></a>

	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">
<?php

        foreach ($columns as $column) {
            $column->print_content();
        }

    ?>
		</div>
	</div>
</div>
<?php
}

add_action(
    'wp_ajax_wpcf7-update-welcome-panel',
    'wpcf7_admin_ajax_welcome_panel',
    10,
    0
);

function wpcf7_admin_ajax_welcome_panel(): void {
    check_ajax_referer('wpcf7-welcome-panel-nonce', 'welcomepanelnonce');

    $vers = get_user_meta(
        get_current_user_id(),
        'wpcf7_hide_welcome_panel_on',
        true
    );

    if (empty($vers) || !is_array($vers)) {
        $vers = [];
    }

    if (empty($_POST['visible'])) {
        $vers[] = wpcf7_version('only_major=1');
    } else {
        $vers = array_diff($vers, [wpcf7_version('only_major=1')]);
    }

    $vers = array_unique($vers);

    update_user_meta(
        get_current_user_id(),
        'wpcf7_hide_welcome_panel_on',
        $vers
    );

    wp_die(1);
}

add_filter(
    'screen_settings',
    'wpcf7_welcome_panel_screen_settings',
    10,
    2
);

function wpcf7_welcome_panel_screen_settings($screen_settings, $screen) {
    if ('toplevel_page_wpcf7' !== $screen->id) {
        return $screen_settings;
    }

    $vers = (array) get_user_meta(
        get_current_user_id(),
        'wpcf7_hide_welcome_panel_on',
        true
    );

    $checkbox_id = 'wpcf7-welcome-panel-show';
    $checked = !in_array(wpcf7_version('only_major=1'), $vers, true);

    $checkbox = sprintf(
        '<input %s />',
        wpcf7_format_atts([
            'id' => $checkbox_id,
            'type' => 'checkbox',
            'checked' => $checked,
        ])
    );

    $screen_settings .= sprintf(
        '
<fieldset class="wpcf7-welcome-panel-options">
<legend>%1$s</legend>
<label for="%2$s">%3$s %4$s</label>
</fieldset>',
        esc_html(__('Welcome panel', 'contact-form-7')),
        esc_attr($checkbox_id),
        $checkbox,
        esc_html(__('Show welcome panel', 'contact-form-7'))
    );

    return $screen_settings;
}
