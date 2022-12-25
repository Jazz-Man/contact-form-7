<?php

namespace JazzMan\ContactForm7;

use ReturnTypeWillChange;
use WP_Error;

use function is_wp_error;
use function wpcf7_is_name;
use function wpcf7_scan_form_tags;

/**
 * Server-side user input validation manager.
 */
class WPCF7_Validation implements \ArrayAccess {
	private array $invalid_fields = [];

	private array $container = [];

	public function __construct() {
		$this->container = [
			'valid'  => true,
			'reason' => [],
			'idref'  => [],
		];
	}

	/**
	 * Marks a form control as an invalid field.
	 *
	 * @param  array|string|WPCF7_FormTag  $context  context representing the
	 *                                            target field
	 * @param  string|WP_Error  $error  the error of the field
	 */
	public function invalidate( $context, $error ): void {
		$tag = null;

		if ( $context instanceof WPCF7_FormTag ) {
			$tag = $context;
		} elseif ( is_array( $context ) ) {
			$tag = new WPCF7_FormTag( $context );
		} elseif ( is_string( $context ) ) {
			$tags = wpcf7_scan_form_tags( [ 'name' => trim( $context ) ] );
			$tag  = $tags ? new WPCF7_FormTag( $tags[0] ) : null;
		}

		$name = ! empty( $tag ) ? $tag->name : null;

		if ( empty( $name )
		     || ! wpcf7_is_name( $name ) ) {
			return;
		}

		if ( is_wp_error( $error ) ) {
			$message = $error->get_error_message();
		} else {
			$message = $error;
		}

		if ( $tag instanceof \JazzMan\ContactForm7\WPCF7_FormTag && $this->is_valid( $name ) ) {
			$id = $tag->get_id_option();

			if ( empty( $id )
			     || ! wpcf7_is_name( $id ) ) {
				$id = null;
			}

			$this->invalid_fields[ $name ] = [
				'reason' => (string) $message,
				'idref'  => $id,
			];
		}
	}

	/**
	 * Returns true if the target field is valid.
	 *
	 * @param  null|string  $name  Optional. If specified, this is the name of
	 *                          the target field. Default null.
	 *
	 * @return bool True if the target field has no error. If no target is
	 *              specified, returns true if all fields are valid.
	 *              Otherwise false.
	 */
	public function is_valid( ?string $name = null ): bool {
		if ( ! empty( $name ) ) {
			return ! isset( $this->invalid_fields[ $name ] );
		}

		return empty( $this->invalid_fields );
	}

	/**
	 * Retrieves an associative array of invalid fields.
	 *
	 * @return array the associative array of invalid fields
	 */
	public function get_invalid_fields(): array {
		return $this->invalid_fields;
	}

	/**
	 * Assigns a value to the specified offset.
	 *
	 * @see https://www.php.net/manual/en/arrayaccess.offsetset.php
	 */
	#[ReturnTypeWillChange]
	public function offsetSet( $offset, $value ): void {
		if ( isset( $this->container[ $offset ] ) ) {
			$this->container[ $offset ] = $value;
		}

		if ( 'reason' == $offset
		     && is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$this->invalidate( $k, $v );
			}
		}
	}

	/**
	 * Returns the value at specified offset.
	 *
	 * @see https://www.php.net/manual/en/arrayaccess.offsetget.php
	 */
	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		if ( isset( $this->container[ $offset ] ) ) {
			return $this->container[ $offset ];
		}
	}

	/**
	 * Returns true if the specified offset exists.
	 *
	 * @see https://www.php.net/manual/en/arrayaccess.offsetexists.php
	 */
	#[ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->container[ $offset ] );
	}

	/**
	 * Unsets an offset.
	 *
	 * @see https://www.php.net/manual/en/arrayaccess.offsetunset.php
	 */
	#[ReturnTypeWillChange]
	public function offsetUnset( $offset ): void {
	}
}
