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
	 * @return array
	 */
	public function all() {
		$saved = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $this->defaults() );
	}

	/**
	 * Get a setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( $key ) {
		$settings = $this->all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
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

		return array(
			'api_key'                 => isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '',
			'model'                   => isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : $defaults['model'],
			'auto_generate'           => empty( $input['auto_generate'] ) ? 0 : 1,
			'enable_frontend_display' => empty( $input['enable_frontend_display'] ) ? 0 : 1,
			'default_summary_type'    => $this->sanitize_choice( isset( $input['default_summary_type'] ) ? $input['default_summary_type'] : '', array( 'bullets', 'paragraph' ), $defaults['default_summary_type'] ),
			'default_summary_length'  => $this->sanitize_choice( isset( $input['default_summary_length'] ) ? $input['default_summary_length'] : '', array( 'short', 'medium', 'long' ), $defaults['default_summary_length'] ),
			'placement'               => $this->sanitize_choice( isset( $input['placement'] ) ? $input['placement'] : '', array( 'before_content', 'after_featured_image', 'after_first_paragraph' ), $defaults['placement'] ),
			'heading'                 => isset( $input['heading'] ) ? sanitize_text_field( $input['heading'] ) : $defaults['heading'],
			'show_icon'               => empty( $input['show_icon'] ) ? 0 : 1,
		);
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
