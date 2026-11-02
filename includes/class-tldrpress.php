<?php
/**
 * Main plugin loader.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TLDRPRESS_PATH . 'includes/class-tldrpress-settings.php';
require_once TLDRPRESS_PATH . 'includes/class-tldrpress-ai-provider-interface.php';
require_once TLDRPRESS_PATH . 'includes/class-tldrpress-openai-service.php';
require_once TLDRPRESS_PATH . 'includes/class-tldrpress-generator.php';
require_once TLDRPRESS_PATH . 'includes/class-tldrpress-rest.php';
require_once TLDRPRESS_PATH . 'admin/class-tldrpress-admin.php';
require_once TLDRPRESS_PATH . 'frontend/class-tldrpress-frontend.php';

/**
 * Main TLDRPress class.
 */
final class TLDRPress {
	/**
	 * Singleton instance.
	 *
	 * @var TLDRPress|null
	 */
	private static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var TLDRPress_Settings
	 */
	public $settings;

	/**
	 * Generator service.
	 *
	 * @var TLDRPress_Generator
	 */
	public $generator;

	/**
	 * Get singleton instance.
	 *
	 * @return TLDRPress
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings  = new TLDRPress_Settings();
		$provider        = new TLDRPress_OpenAI_Service( $this->settings );
		$this->generator = new TLDRPress_Generator( $this->settings, $provider );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		if ( is_admin() ) {
			new TLDRPress_Admin( $this->settings, $this->generator );
		}

		new TLDRPress_Frontend( $this->settings );
		new TLDRPress_REST( $this->settings );
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'tldrpress', false, dirname( plugin_basename( TLDRPRESS_FILE ) ) . '/languages' );
	}

	/**
	 * Register post meta.
	 *
	 * @return void
	 */
	public function init() {
		register_post_meta(
			'post',
			'_tldrpress_summary',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			'_tldrpress_summary_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			'post',
			'_tldrpress_summary_length',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

}
