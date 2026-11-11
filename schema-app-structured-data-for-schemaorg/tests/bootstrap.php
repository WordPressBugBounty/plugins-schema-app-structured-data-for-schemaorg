<?php
/**
 * Test bootstrap file.
 *
 * This file sets up the minimal environment needed to run tests. It loads
 * WP_HTML_Tag_Processor from WordPress core and fails loudly if it cannot
 * be loaded, since the tests must exercise the Tag Processor code path.
 *
 * @package SchemaApp
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

if ( ! defined( 'ABSPATH' ) ) {
	if ( 'cli' !== PHP_SAPI ) {
		exit;
	}
}

define( 'ABSPATH', '/tmp/wordpress/' );
define( 'WPINC', 'wp-includes' );

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return array();
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

$html_api_path = ABSPATH . 'wp-includes/html-api/';
$tag_processor_file = $html_api_path . 'class-wp-html-tag-processor.php';

if ( ! file_exists( $tag_processor_file ) ) {
	fwrite( STDERR, "FATAL: WordPress HTML API not found at $tag_processor_file\n" );
	fwrite( STDERR, "Tests require WordPress 6.2+ to be installed at " . ABSPATH . "\n" );
	fwrite( STDERR, "Run: cd /tmp && wp core download --allow-root\n" );
	exit( 1 );
}

if ( ! function_exists( 'wp_has_noncharacters' ) && file_exists( ABSPATH . 'wp-includes/utf8.php' ) ) {
	require_once ABSPATH . 'wp-includes/utf8.php';
}

if ( ! function_exists( 'wp_kses_uri_attributes' ) ) {
	function wp_kses_uri_attributes() {
		return array(
			'action', 'archive', 'background', 'cite', 'classid', 'codebase', 'data',
			'formaction', 'href', 'icon', 'longdesc', 'manifest', 'poster', 'profile',
			'src', 'usemap', 'xmlns',
		);
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url, $protocols = null, $_context = 'display' ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

$html_api_files = array(
	'class-wp-html-span.php',
	'class-wp-html-text-replacement.php',
	'class-wp-html-attribute-token.php',
	'class-wp-html-decoder.php',
	'class-wp-html-tag-processor.php',
);

foreach ( $html_api_files as $file ) {
	$filepath = $html_api_path . $file;
	if ( file_exists( $filepath ) ) {
		require_once $filepath;
	}
}

if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	fwrite( STDERR, "FATAL: WP_HTML_Tag_Processor class not available after loading HTML API files.\n" );
	fwrite( STDERR, "Tests cannot pass without exercising the Tag Processor code path.\n" );
	exit( 1 );
}

require_once dirname( __DIR__ ) . '/lib/SchemaFront.php';
