<?php
/**
 * Uninstall TLDRPress.
 *
 * Deletes the OpenAI API key secret. The secret name matches
 * TLDRPress_Settings::SECRET_NAME.
 *
 * @package TLDRPress
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'tldrpress_api_key_import' );

if ( function_exists( 'wp_is_secrets_api_available' ) && wp_is_secrets_api_available() && function_exists( 'wp_delete_secret' ) ) {
	wp_delete_secret( 'tldrpress/api-key' );
}
