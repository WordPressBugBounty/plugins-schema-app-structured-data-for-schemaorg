<?php
/**
 * Tests for the RemoveMicrodata functionality.
 *
 * These tests verify that:
 * 1. Microdata attributes (itemscope, itemtype, itemprop, itemid, itemref) are properly removed
 * 2. Microdata link/meta tags are properly removed
 * 3. Attribute boundary safety is maintained (content inside attribute values
 *    is never promoted to become a new attribute)
 * 4. Other content (scripts, styles, iframes, JSON-LD) is preserved
 * 5. Full page structure (doctype, html/body attributes, entities) is preserved
 *
 * IMPORTANT: These tests use benign markers (data-* attributes and sentinel
 * strings) to verify security properties. NO executable XSS payloads are used.
 *
 * @package SchemaApp
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
// phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript

if ( ! defined( 'ABSPATH' ) ) {
	if ( 'cli' !== PHP_SAPI ) {
		exit;
	}
}

class RemoveMicrodataTest {

	/**
	 * Instance of SchemaFront for testing.
	 *
	 * @var SchemaFront
	 */
	private $schema_front;

	/**
	 * Track test results.
	 *
	 * @var array
	 */
	private $results = array();

	/**
	 * Run all tests.
	 *
	 * @return array Test results.
	 */
	public function run_all_tests() {
		$this->results = array();

		$this->schema_front = new SchemaFront();

		$this->test_removes_itemscope_attribute();
		$this->test_removes_itemtype_attribute();
		$this->test_removes_itemprop_attribute();
		$this->test_removes_itemid_attribute();
		$this->test_removes_itemref_attribute();
		$this->test_removes_microdata_link_tags();
		$this->test_removes_microdata_meta_tags();
		$this->test_preserves_non_microdata_link_tags();
		$this->test_preserves_non_microdata_meta_tags();
		$this->test_preserves_scripts();
		$this->test_preserves_styles();
		$this->test_preserves_iframes();
		$this->test_preserves_json_ld_script();
		$this->test_preserves_data_schema_attributes();
		$this->test_preserves_data_source_attributes();
		$this->test_attribute_boundary_safety_basic();
		$this->test_attribute_boundary_safety_with_marker();
		$this->test_attribute_boundary_safety_nested_quotes();
		$this->test_attribute_boundary_safety_multiple_attributes();
		$this->test_attribute_boundary_safety_angle_brackets_in_value();
		$this->test_no_new_attributes_created();
		$this->test_complex_html_document();
		$this->test_full_page_preservation();
		$this->test_greater_than_in_meta_content();
		$this->test_greater_than_in_attribute_value();
		$this->test_marker_like_text_in_content();
		$this->test_many_microdata_tags();
		$this->test_uppercase_and_self_closing();
		$this->test_html_like_content_in_meta();
		$this->test_empty_input();
		$this->test_no_microdata_passthrough();

		return $this->results;
	}

	/**
	 * Assert that a condition is true.
	 *
	 * @param bool   $condition The condition to check.
	 * @param string $message   Test description.
	 * @param string $details   Additional details on failure.
	 */
	private function assert_true( $condition, $message, $details = '' ) {
		$this->results[] = array(
			'test'    => $message,
			'passed'  => (bool) $condition,
			'details' => $details,
		);
	}

	/**
	 * Assert that two strings are equal.
	 *
	 * @param string $expected Expected value.
	 * @param string $actual   Actual value.
	 * @param string $message  Test description.
	 */
	private function assert_equals( $expected, $actual, $message ) {
		$passed = $expected === $actual;
		$details = '';
		if ( ! $passed ) {
			$details = "Expected:\n$expected\n\nActual:\n$actual";
		}
		$this->results[] = array(
			'test'    => $message,
			'passed'  => $passed,
			'details' => $details,
		);
	}

	/**
	 * Assert that a string contains a substring.
	 *
	 * @param string $needle   The substring to find.
	 * @param string $haystack The string to search in.
	 * @param string $message  Test description.
	 */
	private function assert_contains( $needle, $haystack, $message ) {
		$passed = strpos( $haystack, $needle ) !== false;
		$details = '';
		if ( ! $passed ) {
			$details = "String '$needle' not found in:\n$haystack";
		}
		$this->results[] = array(
			'test'    => $message,
			'passed'  => $passed,
			'details' => $details,
		);
	}

	/**
	 * Assert that a string does NOT contain a substring.
	 *
	 * @param string $needle   The substring that should not be present.
	 * @param string $haystack The string to search in.
	 * @param string $message  Test description.
	 */
	private function assert_not_contains( $needle, $haystack, $message ) {
		$passed = strpos( $haystack, $needle ) === false;
		$details = '';
		if ( ! $passed ) {
			$details = "String '$needle' was found but should not be present in:\n$haystack";
		}
		$this->results[] = array(
			'test'    => $message,
			'passed'  => $passed,
			'details' => $details,
		);
	}

	/**
	 * Test: itemscope attribute is removed.
	 */
	public function test_removes_itemscope_attribute() {
		$input = '<div itemscope class="product">Product</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemscope', $output, 'Removes itemscope attribute from div' );
		$this->assert_contains( 'class="product"', $output, 'Preserves class attribute when removing itemscope' );
		$this->assert_contains( 'Product', $output, 'Preserves content when removing itemscope' );
	}

	/**
	 * Test: itemtype attribute is removed.
	 */
	public function test_removes_itemtype_attribute() {
		$input = '<div itemtype="http://schema.org/Product" class="item">Item</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemtype', $output, 'Removes itemtype attribute from div' );
		$this->assert_not_contains( 'schema.org/Product', $output, 'Removes itemtype value' );
		$this->assert_contains( 'class="item"', $output, 'Preserves class attribute when removing itemtype' );
	}

	/**
	 * Test: itemprop attribute is removed.
	 */
	public function test_removes_itemprop_attribute() {
		$input = '<span itemprop="name" class="title">Product Name</span>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemprop', $output, 'Removes itemprop attribute from span' );
		$this->assert_contains( 'class="title"', $output, 'Preserves class attribute when removing itemprop' );
		$this->assert_contains( 'Product Name', $output, 'Preserves content when removing itemprop' );
	}

	/**
	 * Test: itemid attribute is removed.
	 */
	public function test_removes_itemid_attribute() {
		$input = '<div itemid="http://example.com/product/123" class="product">Product</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemid', $output, 'Removes itemid attribute from div' );
		$this->assert_contains( 'class="product"', $output, 'Preserves class attribute when removing itemid' );
	}

	/**
	 * Test: itemref attribute is removed.
	 */
	public function test_removes_itemref_attribute() {
		$input = '<div itemref="details pricing" class="product">Product</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemref', $output, 'Removes itemref attribute from div' );
		$this->assert_contains( 'class="product"', $output, 'Preserves class attribute when removing itemref' );
	}

	/**
	 * Test: microdata link tags are removed.
	 */
	public function test_removes_microdata_link_tags() {
		$input = '<head><link itemprop="url" href="http://example.com"><link rel="stylesheet" href="style.css"></head>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemprop="url"', $output, 'Removes link tag with itemprop' );
		$this->assert_contains( 'rel="stylesheet"', $output, 'Preserves non-microdata link tag' );
	}

	/**
	 * Test: microdata meta tags are removed.
	 */
	public function test_removes_microdata_meta_tags() {
		$input = '<head><meta itemprop="datePublished" content="2024-01-01"><meta name="description" content="Test"></head>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemprop="datePublished"', $output, 'Removes meta tag with itemprop' );
		$this->assert_contains( 'name="description"', $output, 'Preserves non-microdata meta tag' );
	}

	/**
	 * Test: non-microdata link tags are preserved.
	 */
	public function test_preserves_non_microdata_link_tags() {
		$input = '<link rel="stylesheet" href="style.css"><link rel="icon" href="favicon.ico">';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'rel="stylesheet"', $output, 'Preserves stylesheet link' );
		$this->assert_contains( 'rel="icon"', $output, 'Preserves favicon link' );
	}

	/**
	 * Test: non-microdata meta tags are preserved.
	 */
	public function test_preserves_non_microdata_meta_tags() {
		$input = '<meta name="viewport" content="width=device-width"><meta charset="UTF-8">';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'name="viewport"', $output, 'Preserves viewport meta' );
		$this->assert_contains( 'charset', $output, 'Preserves charset meta' );
	}

	/**
	 * Test: script tags are preserved.
	 */
	public function test_preserves_scripts() {
		$input = '<script src="app.js"></script><script>var x = 1;</script>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( '<script', $output, 'Preserves script tags' );
		$this->assert_contains( 'src="app.js"', $output, 'Preserves external script' );
		$this->assert_contains( 'var x = 1;', $output, 'Preserves inline script' );
	}

	/**
	 * Test: style tags are preserved.
	 */
	public function test_preserves_styles() {
		$input = '<style>.test { color: red; }</style>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( '<style', $output, 'Preserves style tags' );
		$this->assert_contains( 'color: red', $output, 'Preserves style content' );
	}

	/**
	 * Test: iframe tags are preserved.
	 */
	public function test_preserves_iframes() {
		$input = '<iframe src="https://example.com" width="100"></iframe>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( '<iframe', $output, 'Preserves iframe tags' );
		$this->assert_contains( 'src="https://example.com"', $output, 'Preserves iframe src' );
	}

	/**
	 * Test: JSON-LD script tags are preserved.
	 */
	public function test_preserves_json_ld_script() {
		$input = '<script type="application/ld+json">{"@type": "Product"}</script>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'application/ld+json', $output, 'Preserves JSON-LD script type' );
		$this->assert_contains( '@type', $output, 'Preserves JSON-LD content' );
	}

	/**
	 * Test: data-schema attributes are preserved (plugin's own output).
	 */
	public function test_preserves_data_schema_attributes() {
		$input = '<script type="application/ld+json" data-schema="123-post-App">{"@type":"Article"}</script>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'data-schema="123-post-App"', $output, 'Preserves data-schema attribute' );
	}

	/**
	 * Test: data-source attributes are preserved (plugin's own output).
	 */
	public function test_preserves_data_source_attributes() {
		$input = '<script type="application/ld+json" data-source="DataFeed:WordPress">{"@type":"Article"}</script>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'data-source="DataFeed:WordPress"', $output, 'Preserves data-source attribute' );
	}

	/**
	 * Test: Attribute boundary safety - basic case.
	 *
	 * Verifies that content inside an attribute value that happens to contain
	 * microdata-like text does NOT get processed in a way that creates new attributes.
	 */
	public function test_attribute_boundary_safety_basic() {
		$input = '<div data-content="itemscope itemtype itemprop" class="safe">Content</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'data-content=', $output, 'Preserves data-content attribute' );
		$this->assert_contains( 'class="safe"', $output, 'Class attribute remains intact' );
		$this->assert_not_contains( 'data-marker-benign', $output, 'No new benign marker attribute created' );
	}

	/**
	 * Test: Attribute boundary safety with benign marker.
	 *
	 * Uses a sentinel data-* attribute name to verify that text inside an
	 * attribute value is never promoted to become a real attribute.
	 *
	 * If the fix is working, the string "data-marker-benign" should remain
	 * inside the attribute value and NOT become a separate attribute.
	 */
	public function test_attribute_boundary_safety_with_marker() {
		$input = '<span data-test="fake> itemscope data-marker-benign=test" title="normal">Safe</span>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$doc = new DOMDocument();
		@$doc->loadHTML( '<div>' . $output . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$spans = $doc->getElementsByTagName( 'span' );

		$has_marker_as_real_attribute = false;
		if ( $spans->length > 0 ) {
			$has_marker_as_real_attribute = $spans->item( 0 )->hasAttribute( 'data-marker-benign' );
		}

		$this->assert_true(
			! $has_marker_as_real_attribute,
			'Benign marker inside attribute value does NOT become a real attribute',
			"Output was: $output"
		);

		$this->assert_contains( 'title="normal"', $output, 'Other attributes remain intact' );
	}

	/**
	 * Test: Attribute boundary safety with nested quotes.
	 */
	public function test_attribute_boundary_safety_nested_quotes() {
		$input = '<div data-json=\'{"itemscope": true, "marker": "data-marker-benign"}\' class="wrapper">Content</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$has_marker_as_attribute = preg_match( '/\sdata-marker-benign\s*=/', $output );
		$this->assert_true(
			! $has_marker_as_attribute,
			'Marker in JSON attribute value is not promoted to attribute',
			"Output was: $output"
		);
		$this->assert_contains( 'class="wrapper"', $output, 'Wrapper class preserved' );
	}

	/**
	 * Test: Attribute boundary safety with multiple attributes.
	 */
	public function test_attribute_boundary_safety_multiple_attributes() {
		$input = '<div data-a="itemscope" data-b="itemtype" data-c="itemprop" class="multi">Multi</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'data-a=', $output, 'Preserves data-a attribute' );
		$this->assert_contains( 'data-b=', $output, 'Preserves data-b attribute' );
		$this->assert_contains( 'data-c=', $output, 'Preserves data-c attribute' );
		$this->assert_contains( 'class="multi"', $output, 'Preserves class attribute' );
	}

	/**
	 * Test: Attribute boundary safety with angle brackets in values.
	 *
	 * This tests the specific vulnerability pattern where a > inside an
	 * attribute value could confuse regex-based parsing.
	 */
	public function test_attribute_boundary_safety_angle_brackets_in_value() {
		$input = '<span data-expr="x > 0 itemscope data-marker-benign=escaped" class="expr">Expression</span>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$doc = new DOMDocument();
		@$doc->loadHTML( '<div>' . $output . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$spans = $doc->getElementsByTagName( 'span' );

		$has_marker_as_real_attribute = false;
		if ( $spans->length > 0 ) {
			$has_marker_as_real_attribute = $spans->item( 0 )->hasAttribute( 'data-marker-benign' );
		}

		$this->assert_true(
			! $has_marker_as_real_attribute,
			'Angle bracket in attribute value does not break parsing',
			"Output was: $output"
		);
		$this->assert_contains( 'class="expr"', $output, 'Class attribute preserved' );
	}

	/**
	 * Test: No new attributes are ever created by the removal process.
	 *
	 * Counts attributes before and after processing to ensure none are added.
	 */
	public function test_no_new_attributes_created() {
		$input = '<div id="test" class="normal" title="safe" data-info="value itemscope data-marker-benign=x">Content</div>';

		$count_before = preg_match_all( '/\s[a-zA-Z-]+\s*=/', $input, $matches_before );
		$output = $this->schema_front->RemoveMicrodata( $input );
		$count_after = preg_match_all( '/\s[a-zA-Z-]+\s*=/', $output, $matches_after );

		$this->assert_true(
			$count_after <= $count_before,
			'No new attributes created during processing',
			"Before: $count_before attributes, After: $count_after attributes\nOutput: $output"
		);
	}

	/**
	 * Test: Complex HTML document with mixed content.
	 */
	public function test_complex_html_document() {
		$input = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta itemprop="datePublished" content="2024-01-01">
<link rel="stylesheet" href="style.css">
<link itemprop="url" href="http://example.com">
<script type="application/ld+json" data-schema="1-post-App" data-source="DataFeed:WordPress">{"@type":"Article"}</script>
</head>
<body>
<div itemscope itemtype="http://schema.org/Product" class="product">
<h1 itemprop="name">Product</h1>
<span itemprop="price">$99</span>
<p data-content="itemscope test data-marker-benign=x" class="description">Description</p>
</div>
<script>var analytics = {};</script>
<style>.product { color: blue; }</style>
<iframe src="https://youtube.com/embed/xyz"></iframe>
</body>
</html>';

		$output = $this->schema_front->RemoveMicrodata( $input );

		$doc = new DOMDocument();
		@$doc->loadHTML( $output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR );
		$xpath = new DOMXPath( $doc );

		$itemscope_elements = $xpath->query( '//*[@itemscope]' );
		$this->assert_true(
			$itemscope_elements->length === 0,
			'Complex: itemscope attribute removed from all elements',
			"Found {$itemscope_elements->length} elements with itemscope"
		);

		$itemtype_elements = $xpath->query( '//*[@itemtype]' );
		$this->assert_true(
			$itemtype_elements->length === 0,
			'Complex: itemtype attribute removed from all elements',
			"Found {$itemtype_elements->length} elements with itemtype"
		);

		$itemprop_elements = $xpath->query( '//*[@itemprop]' );
		$this->assert_true(
			$itemprop_elements->length === 0,
			'Complex: itemprop attribute removed from all elements',
			"Found {$itemprop_elements->length} elements with itemprop"
		);

		$this->assert_contains( 'charset="UTF-8"', $output, 'Complex: charset meta preserved' );
		$this->assert_contains( 'rel="stylesheet"', $output, 'Complex: stylesheet link preserved' );

		$this->assert_contains( 'application/ld+json', $output, 'Complex: JSON-LD preserved' );
		$this->assert_contains( 'data-schema=', $output, 'Complex: data-schema preserved' );
		$this->assert_contains( 'data-source=', $output, 'Complex: data-source preserved' );

		$this->assert_contains( 'class="product"', $output, 'Complex: product class preserved' );
		$this->assert_contains( 'class="description"', $output, 'Complex: description class preserved' );
		$this->assert_contains( 'var analytics', $output, 'Complex: analytics script preserved' );
		$this->assert_contains( 'color: blue', $output, 'Complex: style preserved' );
		$this->assert_contains( 'youtube.com', $output, 'Complex: iframe preserved' );

		$marker_elements = $xpath->query( '//*[@data-marker-benign]' );
		$this->assert_true(
			$marker_elements->length === 0,
			'Complex: No marker attribute promoted to real attribute',
			"Found {$marker_elements->length} elements with data-marker-benign attribute\nOutput: $output"
		);
	}

	/**
	 * Test: Full page structure is preserved exactly except microdata attributes.
	 *
	 * Tests a realistic document with:
	 * - DOCTYPE declaration
	 * - HTML, head, body tags with attributes
	 * - Inline script containing '</div>' (edge case for DOM parsers)
	 * - JSON-LD script
	 * - Self-closing tags (br, hr, img)
	 * - HTML entities
	 * - Microdata attributes to be removed
	 *
	 * Link/meta tags with itemprop remain in place with their non-microdata
	 * attributes preserved. The output should equal the input with only
	 * microdata attributes removed.
	 */
	public function test_full_page_preservation() {
		$input = '<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta itemprop="datePublished" content="2024-01-01">
<title>Test &amp; Demo Page</title>
<link rel="stylesheet" href="style.css">
<link itemprop="url" href="http://example.com/page">
<script type="application/ld+json" data-schema="1-post-App" data-source="DataFeed:WordPress">{"@context":"https://schema.org","@type":"Article"}</script>
</head>
<body class="page-template" data-page-id="123">
<div itemscope itemtype="http://schema.org/Product" itemid="http://example.com/product/1" itemref="details" class="product">
<h1 itemprop="name">Product &ldquo;Name&rdquo;</h1>
<img src="product.jpg" alt="Product" />
<br>
<hr>
<span itemprop="price">$99</span>
</div>
<div id="details">
<p itemprop="description">Description with &amp; entity</p>
</div>
<script>
// This script contains </div> which can break DOM parsers
var config = { wrapper: "</div>", name: "test" };
if (x < 5 && y > 3) { console.log("ok"); }
</script>
<noscript><p>Please enable JavaScript</p></noscript>
</body>
</html>';

		$expected = '<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta  content="2024-01-01">
<title>Test &amp; Demo Page</title>
<link rel="stylesheet" href="style.css">
<link  href="http://example.com/page">
<script type="application/ld+json" data-schema="1-post-App" data-source="DataFeed:WordPress">{"@context":"https://schema.org","@type":"Article"}</script>
</head>
<body class="page-template" data-page-id="123">
<div     class="product">
<h1 >Product &ldquo;Name&rdquo;</h1>
<img src="product.jpg" alt="Product" />
<br>
<hr>
<span >$99</span>
</div>
<div id="details">
<p >Description with &amp; entity</p>
</div>
<script>
// This script contains </div> which can break DOM parsers
var config = { wrapper: "</div>", name: "test" };
if (x < 5 && y > 3) { console.log("ok"); }
</script>
<noscript><p>Please enable JavaScript</p></noscript>
</body>
</html>';

		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemscope', $output, 'Full page: itemscope removed' );
		$this->assert_not_contains( 'itemtype', $output, 'Full page: itemtype removed' );
		$this->assert_not_contains( 'itemprop', $output, 'Full page: itemprop removed' );
		$this->assert_not_contains( 'itemid', $output, 'Full page: itemid removed' );
		$this->assert_not_contains( 'itemref', $output, 'Full page: itemref removed' );

		$this->assert_equals( $expected, $output, 'Full page: output matches expected exactly' );
	}

	/**
	 * Test: Greater-than sign inside meta content value is preserved.
	 *
	 * A '>' inside an attribute value must not be treated as the end of the tag.
	 */
	public function test_greater_than_in_meta_content() {
		$input = '<meta itemprop="condition" content="x > 0 and y < 10"><p>Content</p>';
		$expected = '<meta  content="x > 0 and y < 10"><p>Content</p>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_equals( $expected, $output, 'Greater-than in meta content: output exact' );
		$this->assert_contains( 'x > 0', $output, 'Greater-than in meta content: preserved' );
	}

	/**
	 * Test: Greater-than sign inside ordinary element attribute value.
	 */
	public function test_greater_than_in_attribute_value() {
		$input = '<div itemscope data-expr="a > b" class="test">Content</div>';
		$expected = '<div  data-expr="a > b" class="test">Content</div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_equals( $expected, $output, 'Greater-than in attribute: output exact' );
	}

	/**
	 * Test: Text resembling internal markers in post content is unchanged.
	 *
	 * Any text that might look like internal markers should not affect processing.
	 */
	public function test_marker_like_text_in_content() {
		$input = '<div itemscope><p>Text with data-schemaapp-remove-f7e2b="1" in content</p></div>';
		$expected = '<div ><p>Text with data-schemaapp-remove-f7e2b="1" in content</p></div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_equals( $expected, $output, 'Marker-like text in content: preserved exactly' );
		$this->assert_contains( 'data-schemaapp-remove-f7e2b="1"', $output, 'Marker-like text: still present' );
	}

	/**
	 * Test: Large number of meta/link tags with itemprop (> 150).
	 *
	 * Verifies no iteration cap issues and all microdata is removed.
	 */
	public function test_many_microdata_tags() {
		$tags = '';
		for ( $i = 0; $i < 160; $i++ ) {
			$tags .= '<meta itemprop="item' . $i . '" content="value' . $i . '">';
		}
		$input = '<head>' . $tags . '</head><body><div itemscope>Content</div></body>';

		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'itemprop', $output, 'Many tags: all itemprop removed' );
		$this->assert_not_contains( 'itemscope', $output, 'Many tags: itemscope removed' );

		for ( $i = 0; $i < 160; $i++ ) {
			$this->assert_contains( 'content="value' . $i . '"', $output, 'Many tags: content ' . $i . ' preserved' );
		}
	}

	/**
	 * Test: Uppercase tag names and self-closing variants.
	 */
	public function test_uppercase_and_self_closing() {
		$input = '<META ITEMPROP="test" CONTENT="value" /><LINK ITEMPROP="url" HREF="http://example.com" /><DIV ITEMSCOPE>Content</DIV>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_not_contains( 'ITEMPROP', $output, 'Uppercase: ITEMPROP removed' );
		$this->assert_not_contains( 'itemprop', $output, 'Uppercase: itemprop removed' );
		$this->assert_not_contains( 'ITEMSCOPE', $output, 'Uppercase: ITEMSCOPE removed' );
		$this->assert_not_contains( 'itemscope', $output, 'Uppercase: itemscope removed' );
		$this->assert_contains( 'CONTENT="value"', $output, 'Uppercase: CONTENT preserved' );
		$this->assert_contains( 'HREF="http://example.com"', $output, 'Uppercase: HREF preserved' );
	}

	/**
	 * Test: Meta tag with HTML content containing angle brackets.
	 *
	 * Verifies that content like '<b id=LIVE>' inside a meta value doesn't
	 * become live markup.
	 */
	public function test_html_like_content_in_meta() {
		$input = '<meta itemprop="snippet" content="Use &lt;b id=LIVE&gt; for bold"><p>Safe</p>';
		$expected = '<meta  content="Use &lt;b id=LIVE&gt; for bold"><p>Safe</p>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_equals( $expected, $output, 'HTML in meta content: output exact' );
		$this->assert_not_contains( '<b id=LIVE>', $output, 'HTML in meta content: no live tag created' );
	}

	/**
	 * Test: Empty input returns empty output.
	 */
	public function test_empty_input() {
		$output = $this->schema_front->RemoveMicrodata( '' );
		$this->assert_equals( '', $output, 'Empty input returns empty output' );
	}

	/**
	 * Test: HTML without microdata passes through unchanged.
	 */
	public function test_no_microdata_passthrough() {
		$input = '<div class="normal"><p>Just regular content</p></div>';
		$output = $this->schema_front->RemoveMicrodata( $input );

		$this->assert_contains( 'class="normal"', $output, 'No microdata: class preserved' );
		$this->assert_contains( 'Just regular content', $output, 'No microdata: content preserved' );
	}

	/**
	 * Print test results to console.
	 */
	public function print_results() {
		$passed = 0;
		$failed = 0;

		fwrite( STDOUT, "\n=== RemoveMicrodata Test Results ===\n\n" );

		foreach ( $this->results as $result ) {
			$status = $result['passed'] ? '✓ PASS' : '✗ FAIL';
			fwrite( STDOUT, "$status: {$result['test']}\n" );

			if ( ! $result['passed'] && ! empty( $result['details'] ) ) {
				fwrite( STDOUT, "  Details: {$result['details']}\n" );
			}

			if ( $result['passed'] ) {
				$passed++;
			} else {
				$failed++;
			}
		}

		fwrite( STDOUT, "\n=== Summary ===\n" );
		fwrite( STDOUT, "Passed: $passed\n" );
		fwrite( STDOUT, "Failed: $failed\n" );
		fwrite( STDOUT, "Total:  " . ( $passed + $failed ) . "\n\n" );

		return $failed === 0;
	}
}

