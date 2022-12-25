<?php

namespace JazzMan\ContactForm7\Admin;

class WPCF7_WelcomePanelColumn_AntiSpam extends WPCF7_WelcomePanelColumn {
	protected function icon() {
		return 'shield';
	}

	protected function title() {
		return esc_html(
			__('Getting spammed? You have protection.', 'contact-form-7')
		);
	}

	protected function content() {
		return [
			esc_html(__('Spammers target everything; your contact forms are not an exception. Before you get spammed, protect your contact forms with the powerful anti-spam features Contact Form 7 provides.', 'contact-form-7')),
			sprintf(
			/* translators: links labeled 1: 'Akismet', 2: 'reCAPTCHA', 3: 'disallowed list' */
				esc_html(__('Contact Form 7 supports spam-filtering with %1$s. Intelligent %2$s blocks annoying spambots. Plus, using %3$s, you can block messages containing specified keywords or those sent from specified IP addresses.', 'contact-form-7')),
				wpcf7_link(
					__('https://contactform7.com/spam-filtering-with-akismet/', 'contact-form-7'),
					__('Akismet', 'contact-form-7')
				),
				wpcf7_link(
					__('https://contactform7.com/recaptcha/', 'contact-form-7'),
					__('reCAPTCHA', 'contact-form-7')
				),
				wpcf7_link(
					__('https://contactform7.com/comment-blacklist/', 'contact-form-7'),
					__('disallowed list', 'contact-form-7')
				)
			),
		];
	}
}
