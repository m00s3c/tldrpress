<?php
/**
 * REST API.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller.
 */
class TLDRPress_REST {
	/**
	 * Settings.
	 *
	 * @var TLDRPress_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param TLDRPress_Settings $settings Settings.
	 */
	public function __construct( TLDRPress_Settings $settings ) {
		$this->settings = $settings;
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'tldrpress/v1',
			'/summary/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_summary' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'required'          => true,
					),
				),
			)
		);
	}

	/**
	 * Get public summary payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_summary( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$post    = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return new WP_Error( 'tldrpress_not_found', __( 'Summary not found.', 'tldrpress' ), array( 'status' => 404 ) );
		}

		$summary = get_post_meta( $post_id, '_tldrpress_summary', true );

		if ( empty( $summary ) ) {
			return new WP_REST_Response(
				array(
					'post_id'  => $post_id,
					'summary'  => '',
					'has_tldr' => false,
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'post_id'  => $post_id,
				'summary'  => wp_kses_post( $summary ),
				'type'     => sanitize_key( get_post_meta( $post_id, '_tldrpress_summary_type', true ) ),
				'length'   => sanitize_key( get_post_meta( $post_id, '_tldrpress_summary_length', true ) ),
				'has_tldr' => true,
			),
			200
		);
	}
}
