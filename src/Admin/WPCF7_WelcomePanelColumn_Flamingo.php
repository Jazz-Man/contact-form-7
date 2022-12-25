<?php

namespace JazzMan\ContactForm7\Admin;

class WPCF7_WelcomePanelColumn_Flamingo extends WPCF7_WelcomePanelColumn {
	protected function icon() {
		return 'editor-help';
	}

	protected function title() {
		return esc_html(
			__('Before you cry over spilt mail&#8230;', 'contact-form-7')
		);
	}

	protected function content() {
		return [
			esc_html(__('Contact Form 7 does not store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.', 'contact-form-7')),
			sprintf(
			/* translators: %s: link labeled 'Flamingo' */
				esc_html(__('Install a message storage plugin before this happens to you. %s saves all messages through contact forms into the database. Flamingo is a free WordPress plugin created by the same author as Contact Form 7.', 'contact-form-7')),
				wpcf7_link(
					__('https://contactform7.com/save-submitted-messages-with-flamingo/', 'contact-form-7'),
					__('Flamingo', 'contact-form-7')
				)
			),
		];
	}
}
