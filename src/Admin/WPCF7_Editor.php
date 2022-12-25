<?php

namespace JazzMan\ContactForm7\Admin;

use JazzMan\ContactForm7\WPCF7_ContactForm;

use function wpcf7_is_name;

class WPCF7_Editor {
	private WPCF7_ContactForm $contact_form;

	private array $panels = [];

	public function __construct( WPCF7_ContactForm $contact_form ) {
		$this->contact_form = $contact_form;
	}

	public function add_panel( $panel_id, $title, $callback ): void {
		if ( wpcf7_is_name( $panel_id ) ) {
			$this->panels[ $panel_id ] = [
				'title'    => $title,
				'callback' => $callback,
			];
		}
	}

	public function display(): void {
		if ( empty( $this->panels ) ) {
			return;
		}

		echo '<ul id="contact-form-editor-tabs">';

		foreach ( $this->panels as $panel_id => $panel ) {
			echo sprintf(
				'<li id="%1$s-tab"><a href="#%1$s">%2$s</a></li>',
				esc_attr( $panel_id ),
				esc_html( $panel['title'] )
			);
		}

		echo '</ul>';

		foreach ( $this->panels as $panel_id => $panel ) {
			echo sprintf(
				'<div class="contact-form-editor-panel" id="%1$s">',
				esc_attr( $panel_id )
			);

			if ( is_callable( $panel['callback'] ) ) {
				$this->notice( $panel_id, $panel );
				call_user_func( $panel['callback'], $this->contact_form );
			}

			echo '</div>';
		}
	}

	public function notice( $panel_id, $panel ): void {
		echo '<div class="config-error"></div>';
	}
}
