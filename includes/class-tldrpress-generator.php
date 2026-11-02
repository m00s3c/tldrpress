<?php
/**
 * Summary generator service.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates post summary generation.
 */
class TLDRPress_Generator {
	/**
	 * Settings.
	 *
	 * @var TLDRPress_Settings
	 */
	private $settings;

	/**
	 * Provider.
	 *
	 * @var TLDRPress_AI_Provider_Interface
	 */
	private $provider;

	/**
	 * Constructor.
	 *
	 * @param TLDRPress_Settings              $settings Settings.
	 * @param TLDRPress_AI_Provider_Interface $provider Provider.
	 */
	public function __construct( TLDRPress_Settings $settings, TLDRPress_AI_Provider_Interface $provider ) {
		$this->settings = $settings;
		$this->provider = apply_filters( 'tldrpress_ai_provider', $provider, $settings );
	}

	/**
	 * Generate and store post summary.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $args Generation args.
	 * @return string|WP_Error
	 */
	public function generate_for_post( $post_id, array $args = array() ) {
		$post = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type ) {
			return new WP_Error( 'tldrpress_invalid_post', __( 'Invalid post.', 'tldrpress' ) );
		}

		$type   = isset( $args['type'] ) ? sanitize_key( $args['type'] ) : $this->settings->get( 'default_summary_type' );
		$length = isset( $args['length'] ) ? sanitize_key( $args['length'] ) : $this->settings->get( 'default_summary_length' );

		$result = $this->provider->generate_summary(
			$post->post_content,
			apply_filters(
				'tldrpress_generation_args',
				array(
					'post_id' => $post_id,
					'type'    => $type,
					'length'  => $length,
				),
				$post
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_post_meta( $post_id, '_tldrpress_summary', $result );
		update_post_meta( $post_id, '_tldrpress_summary_type', $type );
		update_post_meta( $post_id, '_tldrpress_summary_length', $length );
		update_post_meta( $post_id, '_tldrpress_generated_at', current_time( 'mysql' ) );

		do_action( 'tldrpress_summary_generated', $post_id, $result, $type, $length );

		return $result;
	}
}
