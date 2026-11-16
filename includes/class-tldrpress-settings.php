<?php
/**
 * Settings storage.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings helper.
 */
class TLDRPress_Settings {
	const OPTION_NAME = 'tldrpress_settings';

	/**
	 * Namespaced secret for the OpenAI API key.
	 *
	 * @var string
	 */
	const SECRET_NAME = 'tldrpress/api-key';

	/**
	 * Temporary string option used only to import a plaintext key.
	 *
	 * wp_import_option_as_secret() reads a string option and flags the secret
	 * for rotation. The key otherwise lives inside the settings array, which
	 * that function cannot import.
	 *
	 * @var string
	 */
	const IMPORT_OPTION = 'tldrpress_api_key_import';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'migrate_plaintext_api_key' ), 1 );
	}

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public function defaults() {
		return array(
			'api_key'                 => '',
			'model'                   => 'gpt-5-mini',
			'auto_generate'           => 0,
			'enable_frontend_display' => 1,
			'default_summary_type'    => 'bullets',
			'default_summary_length'  => 'short',
			'placement'               => 'after_first_paragraph',
			'heading'                 => __( 'TL;DR', 'tldrpress' ),
			'show_icon'               => 1,
		);
	}

	/**
	 * Get all settings.
	 *
	 * api_key is always blank here. Use get( 'api_key' ) where the value is needed
	 * so the key is not copied into screens that print the settings array.
	 *
	 * @return array
	 */
	public function all() {
		$saved = get_option( self::OPTION_NAME, array() );

		if ( is_array( $saved ) ) {
			unset( $saved['api_key'] );
		} else {
			$saved = array();
		}

		$settings            = wp_parse_args( $saved, $this->defaults() );
		$settings['api_key'] = '';

		return $settings;
	}

	/**
	 * Get a setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( $key ) {
		if ( 'api_key' === $key ) {
			return $this->get_api_key();
		}

		$settings = $this->all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Whether the WordPress 7.2 Secrets API can store and read secrets.
	 *
	 * @return bool
	 */
	public function secrets_api_available() {
		return function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available();
	}

	/**
	 * Whether the active secrets provider accepts writes.
	 *
	 * @return bool
	 */
	public function provider_is_writable() {
		return $this->secrets_api_available()
			&& function_exists( 'wp_secrets_provider_is_writable' )
			&& wp_secrets_provider_is_writable();
	}

	/**
	 * Whether this site is on WordPress 7.2 without WP_SECRETS_KEY defined.
	 *
	 * @return bool
	 */
	public function secrets_key_missing() {
		return function_exists( 'wp_is_secrets_api_available' )
			&& ! wp_is_secrets_api_available()
			&& ! defined( 'WP_SECRETS_KEY' );
	}

	/**
	 * Whether a stored secret exists but cannot be read.
	 *
	 * @return bool
	 */
	public function api_key_is_unreadable() {
		return 'unreadable' === $this->secret_state();
	}

	/**
	 * How the settings screen should present the API key field.
	 *
	 * @return string One of locked, unreadable, saved, or empty.
	 */
	public function api_key_input_mode() {
		if ( $this->secrets_api_available() && ! $this->provider_is_writable() ) {
			return 'locked';
		}

		if ( $this->api_key_is_unreadable() ) {
			return 'unreadable';
		}

		if ( 'saved' === $this->secret_state() || '' !== $this->plaintext_api_key() ) {
			return 'saved';
		}

		return 'empty';
	}

	/**
	 * Move a plaintext key into the Secrets API.
	 *
	 * The settings option keeps the key until the import succeeds.
	 *
	 * @return void
	 */
	public function migrate_plaintext_api_key() {
		if ( ! $this->secrets_api_available() || ! $this->provider_is_writable() || ! function_exists( 'wp_import_option_as_secret' ) ) {
			return;
		}

		$plaintext = $this->plaintext_api_key();

		if ( '' === $plaintext ) {
			return;
		}

		/*
		 * add_option() is used instead of update_option() so the temporary copy
		 * is created non-autoloaded on every supported WordPress version.
		 */
		delete_option( self::IMPORT_OPTION );

		if ( ! add_option( self::IMPORT_OPTION, $plaintext, '', false ) ) {
			return;
		}

		$stored = get_option( self::IMPORT_OPTION, null );

		if ( ! is_string( $stored ) || '' === $stored ) {
			$this->delete_import_option();
			return;
		}

		$imported = wp_import_option_as_secret( self::IMPORT_OPTION, self::SECRET_NAME );
		$deleted  = $this->delete_import_option();

		if ( true !== $imported || ! $deleted ) {
			return;
		}

		$this->remove_plaintext_api_key();
	}

	/**
	 * Sanitize settings payload.
	 *
	 * @param array $input Raw settings.
	 * @return array
	 */
	public function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = $this->defaults();
		$posted   = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';

		$sanitized = array(
			'model'                   => isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : $defaults['model'],
			'auto_generate'           => empty( $input['auto_generate'] ) ? 0 : 1,
			'enable_frontend_display' => empty( $input['enable_frontend_display'] ) ? 0 : 1,
			'default_summary_type'    => $this->sanitize_choice( isset( $input['default_summary_type'] ) ? $input['default_summary_type'] : '', array( 'bullets', 'paragraph' ), $defaults['default_summary_type'] ),
			'default_summary_length'  => $this->sanitize_choice( isset( $input['default_summary_length'] ) ? $input['default_summary_length'] : '', array( 'short', 'medium', 'long' ), $defaults['default_summary_length'] ),
			'placement'               => $this->sanitize_choice( isset( $input['placement'] ) ? $input['placement'] : '', array( 'before_content', 'after_featured_image', 'after_first_paragraph' ), $defaults['placement'] ),
			'heading'                 => isset( $input['heading'] ) ? sanitize_text_field( $input['heading'] ) : $defaults['heading'],
			'show_icon'               => empty( $input['show_icon'] ) ? 0 : 1,
		);

		$api_key = $this->sanitize_api_key( $posted );

		if ( null !== $api_key ) {
			$sanitized['api_key'] = $api_key;
		}

		return $sanitized;
	}

	/**
	 * Read the API key from the secret, or from the settings option.
	 *
	 * A WP_Error from wp_get_secret() means the secret exists but cannot be
	 * read. That is not treated as a missing key.
	 *
	 * @return string
	 */
	private function get_api_key() {
		if ( $this->secrets_api_available() && function_exists( 'wp_get_secret' ) ) {
		$secret = wp_get_secret( self::SECRET_NAME );

		if ( class_exists( 'WP_Secret' ) && $secret instanceof WP_Secret ) {
			$value = $secret->reveal();

			// A withheld secret exists, but reveal() returns WP_Error instead of plaintext.
			if ( is_string( $value ) ) {
				return $value;
			}

			return '';
		}

		if ( null !== $secret ) {
			return '';
		}
		}

		return $this->plaintext_api_key();
	}

	/**
	 * Classify the current secret without revealing its value.
	 *
	 * @return string One of unavailable, missing, saved, or unreadable.
	 */
	private function secret_state() {
		if ( ! $this->secrets_api_available() || ! function_exists( 'wp_get_secret' ) ) {
			return 'unavailable';
		}

		$secret = wp_get_secret( self::SECRET_NAME );

		if ( class_exists( 'WP_Secret' ) && $secret instanceof WP_Secret ) {
			$value = $secret->reveal();

			if ( is_string( $value ) ) {
				return 'saved';
			}

			return 'unreadable';
		}

		if ( null === $secret ) {
			return 'missing';
		}

		return 'unreadable';
	}

	/**
	 * Decide what plaintext, if any, remains in the settings option.
	 *
	 * Null omits the key from the option. A string is the value to keep.
	 * A posted key is written with wp_set_secret() only when the provider
	 * accepts writes, and it is never copied into the option as a fallback.
	 *
	 * @param string $posted_key Sanitized value from the settings form.
	 * @return string|null
	 */
	private function sanitize_api_key( $posted_key ) {
		$existing = $this->plaintext_api_key();

		if ( ! $this->secrets_api_available() ) {
			return '' === $posted_key ? $existing : $posted_key;
		}

		if ( ! $this->provider_is_writable() ) {
			if ( '' !== $posted_key ) {
				$this->add_settings_error(
					'tldrpress_secrets_readonly',
					__( 'The secrets provider is read-only, so the OpenAI API key was not saved.', 'tldrpress' )
				);
			}

			return '' === $existing ? null : $existing;
		}

		if ( '' !== $posted_key && function_exists( 'wp_set_secret' ) ) {
			$result = wp_set_secret( self::SECRET_NAME, $posted_key );

			if ( is_wp_error( $result ) ) {
				$this->add_settings_error(
					'tldrpress_api_key_secret',
					$result->get_error_message()
				);

				return '' === $existing ? null : $existing;
			}

			return null;
		}

		if ( 'saved' === $this->secret_state() ) {
			return null;
		}

		return '' === $existing ? null : $existing;
	}

	/**
	 * Plaintext key still stored in the settings option.
	 *
	 * @return string
	 */
	private function plaintext_api_key() {
		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) || ! isset( $saved['api_key'] ) || ! is_string( $saved['api_key'] ) ) {
			return '';
		}

		return $saved['api_key'];
	}

	/**
	 * Remove the plaintext key after a successful import.
	 *
	 * @return void
	 */
	private function remove_plaintext_api_key() {
		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) || ! array_key_exists( 'api_key', $saved ) ) {
			return;
		}

		unset( $saved['api_key'] );
		update_option( self::OPTION_NAME, $saved );
	}

	/**
	 * Delete the temporary import option when it exists.
	 *
	 * @return bool True when no plaintext copy remains in the temp option.
	 */
	private function delete_import_option() {
		delete_option( self::IMPORT_OPTION );

		return null === get_option( self::IMPORT_OPTION, null );
	}

	/**
	 * Record a settings error when the admin settings API is loaded.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @return void
	 */
	private function add_settings_error( $code, $message ) {
		if ( ! function_exists( 'add_settings_error' ) ) {
			return;
		}

		add_settings_error( self::OPTION_NAME, $code, $message, 'error' );
	}

	/**
	 * Sanitize enum-like values.
	 *
	 * @param string $value Value.
	 * @param array  $allowed Allowed values.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function sanitize_choice( $value, array $allowed, $fallback ) {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
