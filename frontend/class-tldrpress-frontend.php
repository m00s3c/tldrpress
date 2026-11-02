<?php
/**
 * Frontend rendering.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend class.
 */
class TLDRPress_Frontend {
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
	 * @param bool               $register_hooks Whether to register WordPress hooks.
	 */
	public function __construct( TLDRPress_Settings $settings, $register_hooks = true ) {
		$this->settings = $settings;

		if ( ! $register_hooks ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'inject_summary' ), 12 );
		add_shortcode( 'tldrpress', array( $this, 'shortcode' ) );
	}

	/**
	 * Enqueue frontend CSS.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'tldrpress-frontend', TLDRPRESS_URL . 'assets/css/frontend.css', array(), TLDRPRESS_VERSION );
	}

	/**
	 * Inject summary into single post content.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function inject_summary( $content ) {
		if ( ! $this->settings->get( 'enable_frontend_display' ) || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();

		if ( ! $post_id || has_shortcode( $content, 'tldrpress' ) ) {
			return $content;
		}

		$box = $this->render_summary( $post_id );

		if ( empty( $box ) ) {
			return $content;
		}

		$placement = $this->settings->get( 'placement' );

		if ( 'before_content' === $placement || 'after_featured_image' === $placement ) {
			return $box . $content;
		}

		return $this->insert_after_first_paragraph( $box, $content );
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => get_the_ID(),
			),
			$atts,
			'tldrpress'
		);

		return $this->render_summary( absint( $atts['id'] ) );
	}

	/**
	 * Render summary box.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function render_summary( $post_id ) {
		$summary = get_post_meta( $post_id, '_tldrpress_summary', true );

		if ( empty( $summary ) ) {
			return '';
		}

		$type    = get_post_meta( $post_id, '_tldrpress_summary_type', true );
		$type    = $type ? $type : $this->settings->get( 'default_summary_type' );
		$heading = $this->settings->get( 'heading' );
		$icon    = $this->settings->get( 'show_icon' );
		$body    = 'bullets' === $type ? $this->format_bullets( $summary ) : wpautop( wp_kses_post( $summary ) );

		$html = sprintf(
			'<aside class="tldrpress-box" aria-label="%1$s"><div class="tldrpress-header">%2$s<h2>%3$s</h2></div><div class="tldrpress-content">%4$s</div></aside>',
			esc_attr( $heading ),
			$icon ? '<span class="tldrpress-icon" aria-hidden="true">i</span>' : '',
			esc_html( $heading ),
			$body
		);

		return apply_filters( 'tldrpress_summary_box_html', $html, $post_id, $summary, $type );
	}

	/**
	 * Format plain bullet text as a list.
	 *
	 * @param string $summary Summary.
	 * @return string
	 */
	private function format_bullets( $summary ) {
		$lines = preg_split( '/\r\n|\r|\n/', wp_strip_all_tags( $summary ) );
		$items = array();

		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/^\s*[-*\x{2022}]\s*/u', '', $line ) );

			if ( '' !== $line ) {
				$items[] = '<li>' . esc_html( $line ) . '</li>';
			}
		}

		if ( empty( $items ) ) {
			return wpautop( wp_kses_post( $summary ) );
		}

		return '<ul>' . implode( '', $items ) . '</ul>';
	}

	/**
	 * Insert markup after first paragraph.
	 *
	 * @param string $insert Markup.
	 * @param string $content Content.
	 * @return string
	 */
	private function insert_after_first_paragraph( $insert, $content ) {
		$closing_p = '</p>';
		$position  = strpos( $content, $closing_p );

		if ( false === $position ) {
			return $insert . $content;
		}

		$position += strlen( $closing_p );

		return substr( $content, 0, $position ) . $insert . substr( $content, $position );
	}
}
