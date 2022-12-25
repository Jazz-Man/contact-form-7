<?php

namespace JazzMan\ContactForm7\Admin;

class WPCF7_WelcomePanelColumn_Integration extends WPCF7_WelcomePanelColumn {
	protected function icon() {
		return 'superhero-alt';
	}

	protected function title() {
		return esc_html(
			__('You have strong allies to back you up.', 'contact-form-7')
		);
	}

	protected function content() {
		return [
			sprintf(
			/* translators: 1: link labeled 'Sendinblue', 2: link labeled 'Constant Contact' */
				esc_html(__('Your contact forms will become more powerful and versatile by integrating them with external APIs. With CRM and email marketing services, you can build your own contact lists (%1$s and %2$s).', 'contact-form-7')),
				wpcf7_link(
					__('https://contactform7.com/sendinblue-integration/', 'contact-form-7'),
					__('Sendinblue', 'contact-form-7')
				),
				wpcf7_link(
					__('https://contactform7.com/constant-contact-integration/', 'contact-form-7'),
					__('Constant Contact', 'contact-form-7')
				)
			),
			sprintf(
			/* translators: 1: link labeled 'reCAPTCHA', 2: link labeled 'Stripe' */
				esc_html(__('With help from cloud-based machine learning, anti-spam services will protect your forms (%1$s). Even payment services are natively supported (%2$s).', 'contact-form-7')),
				wpcf7_link(
					__('https://contactform7.com/recaptcha/', 'contact-form-7'),
					__('reCAPTCHA', 'contact-form-7')
				),
				wpcf7_link(
					__('https://contactform7.com/stripe-integration/', 'contact-form-7'),
					__('Stripe', 'contact-form-7')
				)
			),
		];
	}
}
