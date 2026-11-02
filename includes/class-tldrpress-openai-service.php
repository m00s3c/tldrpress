<?php
/**
 * OpenAI provider.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI summary provider.
 */
class TLDRPress_OpenAI_Service implements TLDRPress_AI_Provider_Interface {
	/**
	 * Settings service.
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
	}

	/**
	 * Generate summary with OpenAI.
	 *
	 * @param string $content Post content.
	 * @param array  $args Generation args.
	 * @return string|WP_Error
	 */
	public function generate_summary( $content, array $args = array() ) {
		$api_key = $this->settings->get( 'api_key' );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'tldrpress_missing_api_key', __( 'OpenAI API key is missing.', 'tldrpress' ) );
		}

		$type   = isset( $args['type'] ) ? sanitize_key( $args['type'] ) : $this->settings->get( 'default_summary_type' );
		$length = isset( $args['length'] ) ? sanitize_key( $args['length'] ) : $this->settings->get( 'default_summary_length' );
		$model  = apply_filters( 'tldrpress_openai_model', $this->settings->get( 'model' ), $args );
		$prompt = $this->build_prompt( $content, $type, $length );

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					apply_filters(
						'tldrpress_openai_request_body',
						array(
							'model'        => $model,
							'instructions' => 'You write concise, accurate TL;DR summaries for publishers. Do not add facts that are not present in the article.',
							'input'        => $prompt,
						),
						$args
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'OpenAI request failed.', 'tldrpress' );
			return new WP_Error( 'tldrpress_openai_error', sanitize_text_field( $message ) );
		}

		$summary = $this->extract_response_text( $body );

		if ( empty( $summary ) ) {
			return new WP_Error( 'tldrpress_empty_response', __( 'OpenAI returned an empty summary.', 'tldrpress' ) );
		}

		return trim( wp_kses_post( $summary ) );
	}

	/**
	 * Extract text from a Responses API payload.
	 *
	 * @param array|null $body Response body.
	 * @return string
	 */
	private function extract_response_text( $body ) {
		if ( ! is_array( $body ) ) {
			return '';
		}

		if ( ! empty( $body['output_text'] ) ) {
			return (string) $body['output_text'];
		}

		if ( empty( $body['output'] ) || ! is_array( $body['output'] ) ) {
			return '';
		}

		$parts = array();

		foreach ( $body['output'] as $item ) {
			if ( empty( $item['content'] ) || ! is_array( $item['content'] ) ) {
				continue;
			}

			foreach ( $item['content'] as $content ) {
				if ( isset( $content['text'] ) ) {
					$parts[] = (string) $content['text'];
				}
			}
		}

		return trim( implode( "\n", $parts ) );
	}

	/**
	 * Build generation prompt.
	 *
	 * @param string $content Article content.
	 * @param string $type Summary type.
	 * @param string $length Summary length.
	 * @return string
	 */
	private function build_prompt( $content, $type, $length ) {
		$word_targets = array(
			'short'  => '40-70 words',
			'medium' => '80-130 words',
			'long'   => '140-220 words',
		);

		$format = 'paragraph' === $type
			? 'Return one clear paragraph.'
			: 'Return 3-5 concise bullet points. Start each bullet with "- ".';

		$plain_content = wp_strip_all_tags( strip_shortcodes( $content ) );
		$plain_content = substr( preg_replace( '/\s+/', ' ', $plain_content ), 0, 16000 );

		return sprintf(
			"Summarize this article in %s. %s\n\nArticle:\n%s",
			isset( $word_targets[ $length ] ) ? $word_targets[ $length ] : $word_targets['medium'],
			$format,
			$plain_content
		);
	}
}
