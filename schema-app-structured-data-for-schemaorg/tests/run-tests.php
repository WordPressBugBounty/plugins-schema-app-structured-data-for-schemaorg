#!/usr/bin/env php
<?php
/**
 * Test runner for RemoveMicrodata tests.
 *
 * Usage: php tests/run-tests.php
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

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/RemoveMicrodataTest.php';

fwrite( STDOUT, "WP_HTML_Tag_Processor available: " . ( class_exists( 'WP_HTML_Tag_Processor' ) ? 'Yes' : 'No' ) . "\n" );

$test = new RemoveMicrodataTest();
$test->run_all_tests();
$success = $test->print_results();

exit( $success ? 0 : 1 );
