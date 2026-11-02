<?php
/**
 * Admin UI.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class TLDRPress_Admin {
	/**
	 * Settings service.
	 *
	 * @var TLDRPress_Settings
	 */
	private $settings;

	/**
	 * Generator service.
	 *
	 * @var TLDRPress_Generator
	 */
	private $generator;

	/**
	 * Constructor.
	 *
	 * @param TLDRPress_Settings  $settings Settings.
	 * @param TLDRPress_Generator $generator Generator.
	 */
	public function __construct( TLDRPress_Settings $settings, TLDRPress_Generator $generator ) {
		$this->settings  = $settings;
		$this->generator = $generator;

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'maybe_auto_generate' ), 10, 3 );
		add_action( 'tldrpress_generate_post_summary_event', array( $this, 'generate_scheduled_summary' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_tldrpress_generate_summary', array( $this, 'ajax_generate_summary' ) );
		add_action( 'wp_ajax_tldrpress_bulk_generate', array( $this, 'ajax_bulk_generate' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'TLDRPress Settings', 'tldrpress' ),
			__( 'TLDRPress', 'tldrpress' ),
			'manage_options',
			'tldrpress',
			array( $this, 'render_settings_page' )
		);

		add_management_page(
			__( 'Bulk Generate', 'tldrpress' ),
			__( 'TLDRPress', 'tldrpress' ),
			'manage_options',
			'tldrpress-bulk-generate',
			array( $this, 'render_bulk_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'tldrpress_settings_group',
			TLDRPress_Settings::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this->settings, 'sanitize' ),
				'default'           => $this->settings->defaults(),
			)
		);
	}

	/**
	 * Add post meta box.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'tldrpress_meta_box',
			__( 'TLDRPress Summary', 'tldrpress' ),
			array( $this, 'render_meta_box' ),
			'post',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		if ( 'post' === $screen->post_type || false !== strpos( $hook, 'tldrpress' ) ) {
			wp_enqueue_style( 'tldrpress-admin', TLDRPRESS_URL . 'assets/css/admin.css', array(), TLDRPRESS_VERSION );
			wp_enqueue_script( 'tldrpress-admin', TLDRPRESS_URL . 'assets/js/admin.js', array( 'jquery' ), TLDRPRESS_VERSION, true );
			wp_localize_script(
				'tldrpress-admin',
				'tldrpressAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'tldrpress_admin' ),
					'i18n'    => array(
						'generating' => __( 'Generating...', 'tldrpress' ),
						'complete'    => __( 'Complete', 'tldrpress' ),
						'error'       => __( 'Something went wrong.', 'tldrpress' ),
					),
				)
			);
		}
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'tldrpress_save_meta', 'tldrpress_meta_nonce' );

		$summary = get_post_meta( $post->ID, '_tldrpress_summary', true );
		$type    = get_post_meta( $post->ID, '_tldrpress_summary_type', true );
		$length  = get_post_meta( $post->ID, '_tldrpress_summary_length', true );
		$type    = $type ? $type : $this->settings->get( 'default_summary_type' );
		$length  = $length ? $length : $this->settings->get( 'default_summary_length' );
		?>
		<div class="tldrpress-meta-box" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<div class="tldrpress-field-row">
				<label for="tldrpress_summary_type"><?php esc_html_e( 'Summary type', 'tldrpress' ); ?></label>
				<select id="tldrpress_summary_type" name="tldrpress_summary_type">
					<option value="bullets" <?php selected( $type, 'bullets' ); ?>><?php esc_html_e( 'Bullets', 'tldrpress' ); ?></option>
					<option value="paragraph" <?php selected( $type, 'paragraph' ); ?>><?php esc_html_e( 'Paragraph', 'tldrpress' ); ?></option>
				</select>
			</div>
			<div class="tldrpress-field-row">
				<label for="tldrpress_summary_length"><?php esc_html_e( 'Summary length', 'tldrpress' ); ?></label>
				<select id="tldrpress_summary_length" name="tldrpress_summary_length">
					<option value="short" <?php selected( $length, 'short' ); ?>><?php esc_html_e( 'Short', 'tldrpress' ); ?></option>
					<option value="medium" <?php selected( $length, 'medium' ); ?>><?php esc_html_e( 'Medium', 'tldrpress' ); ?></option>
					<option value="long" <?php selected( $length, 'long' ); ?>><?php esc_html_e( 'Long', 'tldrpress' ); ?></option>
				</select>
			</div>
			<textarea id="tldrpress_summary" name="tldrpress_summary" rows="7" class="large-text" placeholder="<?php esc_attr_e( 'Generated summary will appear here. You can edit it before saving.', 'tldrpress' ); ?>"><?php echo esc_textarea( $summary ); ?></textarea>
			<div class="tldrpress-actions">
				<button type="button" class="button button-primary tldrpress-generate-button"><?php esc_html_e( 'Generate Summary', 'tldrpress' ); ?></button>
				<button type="button" class="button tldrpress-generate-button"><?php esc_html_e( 'Regenerate Summary', 'tldrpress' ); ?></button>
				<span class="spinner"></span>
				<span class="tldrpress-status" aria-live="polite"></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Save post meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( ! isset( $_POST['tldrpress_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tldrpress_meta_nonce'] ) ), 'tldrpress_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['tldrpress_summary'] ) ) {
			update_post_meta( $post_id, '_tldrpress_summary', wp_kses_post( wp_unslash( $_POST['tldrpress_summary'] ) ) );
		}

		if ( isset( $_POST['tldrpress_summary_type'] ) ) {
			update_post_meta( $post_id, '_tldrpress_summary_type', sanitize_key( wp_unslash( $_POST['tldrpress_summary_type'] ) ) );
		}

		if ( isset( $_POST['tldrpress_summary_length'] ) ) {
			update_post_meta( $post_id, '_tldrpress_summary_length', sanitize_key( wp_unslash( $_POST['tldrpress_summary_length'] ) ) );
		}
	}

	/**
	 * Auto-generate when a post is first published.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function maybe_auto_generate( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status || 'post' !== $post->post_type ) {
			return;
		}

		if ( ! $this->settings->get( 'auto_generate' ) || get_post_meta( $post->ID, '_tldrpress_summary', true ) ) {
			return;
		}

		if ( ! wp_next_scheduled( 'tldrpress_generate_post_summary_event', array( $post->ID ) ) ) {
			wp_schedule_single_event( time() + 10, 'tldrpress_generate_post_summary_event', array( $post->ID ) );
		}
	}

	/**
	 * Generate a scheduled summary.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function generate_scheduled_summary( $post_id ) {
		$post_id = absint( $post_id );

		if ( $post_id && ! get_post_meta( $post_id, '_tldrpress_summary', true ) ) {
			$this->generator->generate_for_post( $post_id );
		}
	}

	/**
	 * AJAX single generation.
	 *
	 * @return void
	 */
	public function ajax_generate_summary() {
		check_ajax_referer( 'tldrpress_admin', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tldrpress' ) ), 403 );
		}

		$result = $this->generator->generate_for_post(
			$post_id,
			array(
				'type'   => isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : null,
				'length' => isset( $_POST['length'] ) ? sanitize_key( wp_unslash( $_POST['length'] ) ) : null,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success( array( 'summary' => $result ) );
	}

	/**
	 * AJAX bulk generation batch.
	 *
	 * @return void
	 */
	public function ajax_bulk_generate() {
		check_ajax_referer( 'tldrpress_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tldrpress' ) ), 403 );
		}

		$mode     = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'missing';
		$offset   = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$per_page = 3;
		$meta     = array();

		if ( ! in_array( $mode, array( 'missing', 'regenerate', 'all' ), true ) ) {
			$mode = 'missing';
		}

		if ( 'missing' === $mode ) {
			$meta = array(
				'relation' => 'OR',
				array(
					'key'     => '_tldrpress_summary',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_tldrpress_summary',
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => $per_page,
				'offset'                 => 'missing' === $mode ? 0 : $offset,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		$processed = 0;
		$errors    = array();

		foreach ( $query->posts as $post_id ) {
			$result = $this->generator->generate_for_post( $post_id );
			$processed++;

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf( '#%d: %s', $post_id, $result->get_error_message() );
			}
		}

		$total     = (int) $query->found_posts;
		$next      = 'missing' === $mode ? 0 : $offset + $per_page;
		$all_failed = $processed > 0 && count( $errors ) === $processed;
		$completed  = 'missing' === $mode ? $total <= $per_page || $all_failed : $next >= $total;

		wp_send_json_success(
			array(
				'processed' => $processed,
				'offset'    => $next,
				'total'     => $total,
				'done'      => $completed,
				'errors'    => $errors,
			)
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = $this->settings->all();
		?>
		<div class="wrap tldrpress-admin-page">
			<h1><?php esc_html_e( 'TLDRPress Settings', 'tldrpress' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'tldrpress_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="tldrpress_api_key"><?php esc_html_e( 'OpenAI API key', 'tldrpress' ); ?></label></th>
						<td><input type="password" class="regular-text" id="tldrpress_api_key" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[api_key]" value="<?php echo esc_attr( $options['api_key'] ); ?>" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="tldrpress_model"><?php esc_html_e( 'Model', 'tldrpress' ); ?></label></th>
						<td>
							<select id="tldrpress_model" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[model]">
								<?php foreach ( array( 'gpt-5-mini', 'gpt-5-nano', 'gpt-5', 'gpt-4.1-mini', 'gpt-4.1', 'gpt-4o-mini' ) as $model ) : ?>
									<option value="<?php echo esc_attr( $model ); ?>" <?php selected( $options['model'], $model ); ?>><?php echo esc_html( $model ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automation', 'tldrpress' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[auto_generate]" value="1" <?php checked( $options['auto_generate'], 1 ); ?>> <?php esc_html_e( 'Auto-generate on publish', 'tldrpress' ); ?></label><br>
							<label><input type="checkbox" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[enable_frontend_display]" value="1" <?php checked( $options['enable_frontend_display'], 1 ); ?>> <?php esc_html_e( 'Enable automatic frontend display', 'tldrpress' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Defaults', 'tldrpress' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[default_summary_type]">
								<option value="bullets" <?php selected( $options['default_summary_type'], 'bullets' ); ?>><?php esc_html_e( 'Bullets', 'tldrpress' ); ?></option>
								<option value="paragraph" <?php selected( $options['default_summary_type'], 'paragraph' ); ?>><?php esc_html_e( 'Paragraph', 'tldrpress' ); ?></option>
							</select>
							<select name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[default_summary_length]">
								<option value="short" <?php selected( $options['default_summary_length'], 'short' ); ?>><?php esc_html_e( 'Short', 'tldrpress' ); ?></option>
								<option value="medium" <?php selected( $options['default_summary_length'], 'medium' ); ?>><?php esc_html_e( 'Medium', 'tldrpress' ); ?></option>
								<option value="long" <?php selected( $options['default_summary_length'], 'long' ); ?>><?php esc_html_e( 'Long', 'tldrpress' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tldrpress_placement"><?php esc_html_e( 'Summary placement', 'tldrpress' ); ?></label></th>
						<td>
							<select id="tldrpress_placement" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[placement]">
								<option value="before_content" <?php selected( $options['placement'], 'before_content' ); ?>><?php esc_html_e( 'Before content', 'tldrpress' ); ?></option>
								<option value="after_featured_image" <?php selected( $options['placement'], 'after_featured_image' ); ?>><?php esc_html_e( 'After featured image', 'tldrpress' ); ?></option>
								<option value="after_first_paragraph" <?php selected( $options['placement'], 'after_first_paragraph' ); ?>><?php esc_html_e( 'After first paragraph', 'tldrpress' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="tldrpress_heading"><?php esc_html_e( 'Heading text', 'tldrpress' ); ?></label></th>
						<td><input type="text" class="regular-text" id="tldrpress_heading" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[heading]" value="<?php echo esc_attr( $options['heading'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Icon', 'tldrpress' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( TLDRPress_Settings::OPTION_NAME ); ?>[show_icon]" value="1" <?php checked( $options['show_icon'], 1 ); ?>> <?php esc_html_e( 'Show icon in summary box', 'tldrpress' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Manual placement', 'tldrpress' ); ?></th>
						<td>
							<p>
								<code>[tldrpress]</code>
								<?php esc_html_e( 'Displays the saved TL;DR summary for the current post.', 'tldrpress' ); ?>
							</p>
							<p>
								<code>[tldrpress id="123"]</code>
								<?php esc_html_e( 'Displays the saved TL;DR summary for a specific post ID.', 'tldrpress' ); ?>
							</p>
							<p class="description"><?php esc_html_e( 'For full placement control, disable automatic frontend display and add this shortcode where you want the summary to appear. In page builders, use their standard shortcode widget.', 'tldrpress' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render bulk page.
	 *
	 * @return void
	 */
	public function render_bulk_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap tldrpress-admin-page">
			<h1><?php esc_html_e( 'Bulk Generate', 'tldrpress' ); ?></h1>
			<div class="tldrpress-bulk-card">
				<p><?php esc_html_e( 'Generate summaries in small AJAX batches to avoid timeouts.', 'tldrpress' ); ?></p>
				<select id="tldrpress-bulk-mode">
					<option value="missing"><?php esc_html_e( 'Generate only missing summaries', 'tldrpress' ); ?></option>
					<option value="regenerate"><?php esc_html_e( 'Regenerate all summaries', 'tldrpress' ); ?></option>
					<option value="all"><?php esc_html_e( 'Generate summaries for all existing posts', 'tldrpress' ); ?></option>
				</select>
				<button type="button" class="button button-primary" id="tldrpress-bulk-start"><?php esc_html_e( 'Start Bulk Generation', 'tldrpress' ); ?></button>
				<div class="tldrpress-progress" aria-hidden="true"><span></span></div>
				<p class="tldrpress-bulk-status" aria-live="polite"></p>
			</div>
		</div>
		<?php
	}
}
