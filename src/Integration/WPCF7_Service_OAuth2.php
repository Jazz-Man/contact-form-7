<?php

namespace JazzMan\ContactForm7\Integration;

use function add_query_arg;
use function admin_url;
use function menu_page_url;
use function wp_parse_args;
use function wp_redirect;
use function wp_remote_post;
use function wp_remote_request;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;
use function wp_safe_redirect;
use function wpcf7_log_remote_request;

use const WP_DEBUG;

/**
 * Class for services that use OAuth.
 *
 * While this is not an abstract class, subclassing this class for
 * your aim is advised.
 */
class WPCF7_Service_OAuth2 extends WPCF7_Service {
	protected $client_id = '';

	protected $client_secret = '';

	protected $access_token = '';

	protected $refresh_token = '';

	protected $authorization_endpoint = 'https://example.com/authorization';

	protected $token_endpoint = 'https://example.com/token';

	public function get_title(): string {
		return '';
	}

	public function is_active(): bool {
		return ! empty( $this->refresh_token );
	}

	public function load( $action = '' ): void {
		if ( 'auth_redirect' == $action ) {
			$code = $_GET['code'] ?? '';

			if ( $code ) {
				$this->request_token( $code );
			}

			if ( ! empty( $this->access_token ) ) {
				$message = 'success';
			} else {
				$message = 'failed';
			}

			wp_safe_redirect( $this->menu_page_url(
					[
							'action'  => 'setup',
							'message' => $message,
					]
			) );

			exit;
		}
	}

	protected function save_data(): void {
	}

	protected function reset_data(): void {
	}

	protected function get_redirect_uri() {
		return admin_url();
	}

	protected function menu_page_url( $args = '' ): string {
		return menu_page_url( 'wpcf7-integration', false );
	}

	protected function authorize( $scope = '' ): void {
		$endpoint = add_query_arg(
				[
						'response_type' => 'code',
						'client_id'     => $this->client_id,
						'redirect_uri'  => urlencode( $this->get_redirect_uri() ),
						'scope'         => $scope,
				],
				$this->authorization_endpoint
		);

		if ( wp_redirect( sanitize_url( $endpoint ) ) ) {
			exit;
		}
	}

	protected function get_http_authorization_header( string $scheme = 'basic' ) {
		$scheme = strtolower( trim( $scheme ) );

		switch ( $scheme ) {
			case 'bearer':
				return sprintf( 'Bearer %s', $this->access_token );

			case 'basic':
			default:
				return sprintf(
						'Basic %s',
						base64_encode( $this->client_id . ':' . $this->client_secret )
				);
		}
	}

	protected function request_token( $authorization_code ) {
		$endpoint = add_query_arg(
				[
						'code'         => $authorization_code,
						'redirect_uri' => urlencode( $this->get_redirect_uri() ),
						'grant_type'   => 'authorization_code',
				],
				$this->token_endpoint
		);

		$request = [
				'headers' => [
						'Authorization' => $this->get_http_authorization_header( 'basic' ),
				],
		];

		$response      = wp_remote_post( sanitize_url( $endpoint ), $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_body = json_decode( $response_body, true );

		if ( WP_DEBUG && 400 <= $response_code ) {
			$this->log( $endpoint, $request, $response );
		}

		if ( 401 == $response_code ) { // Unauthorized
			$this->access_token  = null;
			$this->refresh_token = null;
		} else {
			if ( isset( $response_body['access_token'] ) ) {
				$this->access_token = $response_body['access_token'];
			} else {
				$this->access_token = null;
			}

			if ( isset( $response_body['refresh_token'] ) ) {
				$this->refresh_token = $response_body['refresh_token'];
			} else {
				$this->refresh_token = null;
			}
		}

		$this->save_data();

		return $response;
	}

	protected function refresh_token() {
		$endpoint = add_query_arg(
				[
						'refresh_token' => $this->refresh_token,
						'grant_type'    => 'refresh_token',
				],
				$this->token_endpoint
		);

		$request = [
				'headers' => [
						'Authorization' => $this->get_http_authorization_header( 'basic' ),
				],
		];

		$response      = wp_remote_post( sanitize_url( $endpoint ), $request );
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_body = json_decode( $response_body, true );

		if ( WP_DEBUG && 400 <= $response_code ) {
			$this->log( $endpoint, $request, $response );
		}

		if ( 401 == $response_code ) { // Unauthorized
			$this->access_token  = null;
			$this->refresh_token = null;
		} else {
			if ( isset( $response_body['access_token'] ) ) {
				$this->access_token = $response_body['access_token'];
			} else {
				$this->access_token = null;
			}

			if ( isset( $response_body['refresh_token'] ) ) {
				$this->refresh_token = $response_body['refresh_token'];
			}
		}

		$this->save_data();

		return $response;
	}

	protected function remote_request( string $url, $request = [] ) {
		static $refreshed = false;

		$request = wp_parse_args( $request, [] );

		$request['headers'] = array_merge(
				$request['headers'],
				[
						'Authorization' => $this->get_http_authorization_header( 'bearer' ),
				]
		);

		$response = wp_remote_request( sanitize_url( $url ), $request );

		if ( 401 === wp_remote_retrieve_response_code( $response )
			 && ! $refreshed ) {
			$this->refresh_token();
			$refreshed = true;

			$response = $this->remote_request( $url, $request );
		}

		return $response;
	}

	protected function log( string $url, $request, $response ): void {
		wpcf7_log_remote_request( $url, $request, $response );
	}
}
