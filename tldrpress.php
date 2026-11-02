<?php
/**
 * Plugin Name: TLDRPress
 * Plugin URI: https://github.com/m00s3c/tldrpress
 * Description: Generate and display AI-powered TL;DR summaries for WordPress posts.
 * Version: 1.0.3
 * Author: webmaster.sh
 * Author URI: https://webmaster.sh
 * Text Domain: tldrpress
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 *
 * @package TLDRPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TLDRPRESS_VERSION', '1.0.3' );
define( 'TLDRPRESS_FILE', __FILE__ );
define( 'TLDRPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'TLDRPRESS_URL', plugin_dir_url( __FILE__ ) );

require_once TLDRPRESS_PATH . 'includes/class-tldrpress.php';

/**
 * Bootstrap the plugin.
 *
 * @return TLDRPress
 */
function tldrpress() {
	return TLDRPress::instance();
}

tldrpress();
