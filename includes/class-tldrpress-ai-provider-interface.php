<?php
/**
 * AI provider contract.
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for summary generation providers.
 */
interface TLDRPress_AI_Provider_Interface {
	/**
	 * Generate a summary.
	 *
	 * @param string $content Post content.
	 * @param array  $args Generation arguments.
	 * @return string|WP_Error
	 */
	public function generate_summary( $content, array $args = array() );
}
