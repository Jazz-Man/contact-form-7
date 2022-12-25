<?php

namespace JazzMan\ContactForm7\Admin;

use JazzMan\ContactForm7\WPCF7_ContactForm;

use function wp_parse_args;
use function wpcf7_is_name;

class WPCF7_TagGenerator {
	private static WPCF7_TagGenerator $instance;

	private array $panels = [];

	private function __construct() {
	}

	public static function get_instance(): self {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function add( string $id, string $title, ?callable $callback, $options = [] ): bool {
		$id = trim( $id );

		if ( '' === $id
		     || ! wpcf7_is_name( $id ) ) {
			return false;
		}

		$this->panels[ $id ] = [
			'title'    => $title,
			'content'  => 'tag-generator-panel-' . $id,
			'options'  => $options,
			'callback' => $callback,
		];

		return true;
	}

	public function print_buttons(): void {
		echo '<span id="tag-generator-list">';

		foreach ( (array) $this->panels as $panel ) {
			echo sprintf(
				'<a href="#TB_inline?width=900&height=500&inlineId=%1$s" class="thickbox button" title="%2$s">%3$s</a>',
				esc_attr( $panel['content'] ),
				esc_attr( sprintf(
				/* translators: %s: title of form-tag like 'email' or 'checkboxes' */
					__( 'Form-tag Generator: %s', 'contact-form-7' ),
					$panel['title']
				) ),
				esc_html( $panel['title'] )
			);
		}

		echo '</span>';
	}

	public function print_panels( WPCF7_ContactForm $contact_form ): void {
		foreach ( (array) $this->panels as $id => $panel ) {
			$callback = $panel['callback'];

			$options = wp_parse_args( $panel['options'], [] );
			$options = array_merge( $options, [
				'id'      => $id,
				'title'   => $panel['title'],
				'content' => $panel['content'],
			] );

			if ( is_callable( $callback ) ) {
				echo sprintf(
					'<div id="%s" class="hidden">',
					esc_attr( $options['content'] )
				);
				echo sprintf(
					'<form action="" class="tag-generator-panel" data-id="%s">',
					$options['id']
				);

				call_user_func( $callback, $contact_form, $options );

				echo '</form></div>';
			}
		}
	}
}
