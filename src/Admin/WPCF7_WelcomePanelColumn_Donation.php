<?php

namespace JazzMan\ContactForm7\Admin;

class WPCF7_WelcomePanelColumn_Donation extends WPCF7_WelcomePanelColumn {
	protected function icon() {
		return 'megaphone';
	}

	protected function title() {
		return esc_html(
			__('Contact Form 7 needs your support.', 'contact-form-7')
		);
	}

	protected function content() {
		return [
			esc_html(__('It is hard to continue development and support for this plugin without contributions from users like you.', 'contact-form-7')),
			sprintf(
			/* translators: %s: link labeled 'making a donation' */
				esc_html(__('If you enjoy using Contact Form 7 and find it useful, please consider %s.', 'contact-form-7')),
				wpcf7_link(
					__('https://contactform7.com/donate/', 'contact-form-7'),
					__('making a donation', 'contact-form-7')
				)
			),
			esc_html(__('Your donation will help encourage and support the plugin&#8217;s continued development and better user support.', 'contact-form-7')),
		];
	}
}
