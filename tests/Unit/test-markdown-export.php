<?php
/**
 * Class MarkdownExportTest
 *
 * Command: composer test-debug --filter MarkdownExportTest
 *
 * @package Formscrm
 */

/**
 * Test case for Markdown Export functionality.
 */
class MarkdownExportTest extends WP_UnitTestCase {

	/**
	 * Markdown export instance.
	 *
	 * @var FormsCRM_GravityForms_Markdown_Export
	 */
	private $markdown_export;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Load required files.
		require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/class-gravityforms-markdown-export.php';

		// Create instance using reflection to access private methods.
		$this->markdown_export = new FormsCRM_GravityForms_Markdown_Export();
	}

	/**
	 * Test generate_filename method.
	 */
	public function test_generate_filename() {
		$entry = array(
			'id' => 123,
		);

		$form = array(
			'title' => 'Contact Form',
		);

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'generate_filename' );
		$method->setAccessible( true );

		$filename = $method->invokeArgs( $this->markdown_export, array( $entry, $form ) );

		$this->assertEquals( 'Contact-Form-entry-123.md', $filename );
	}

	/**
	 * Test generate_filename with special characters.
	 */
	public function test_generate_filename_with_special_chars() {
		$entry = array(
			'id' => 456,
		);

		$form = array(
			'title' => 'Contact Form & Feedback!',
		);

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'generate_filename' );
		$method->setAccessible( true );

		$filename = $method->invokeArgs( $this->markdown_export, array( $entry, $form ) );

		// Check that special characters are sanitized.
		$this->assertStringContainsString( 'entry-456.md', $filename );
		$this->assertStringNotContainsString( '&', $filename );
		$this->assertStringNotContainsString( '!', $filename );
	}

	/**
	 * Test escape_markdown method.
	 */
	public function test_escape_markdown() {
		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'escape_markdown' );
		$method->setAccessible( true );

		$text = 'Text with *asterisk* and [brackets]';
		$result = $method->invokeArgs( $this->markdown_export, array( $text ) );

		$this->assertEquals( 'Text with \*asterisk\* and \[brackets\]', $result );
	}

	/**
	 * Test escape_markdown doesn't escape links.
	 */
	public function test_escape_markdown_preserves_links() {
		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'escape_markdown' );
		$method->setAccessible( true );

		$text = '[File](https://example.com/file.pdf)';
		$result = $method->invokeArgs( $this->markdown_export, array( $text ) );

		// Links should not be escaped.
		$this->assertEquals( '[File](https://example.com/file.pdf)', $result );
	}

	/**
	 * Test format_field_markdown for inline fields.
	 */
	public function test_format_field_markdown_inline() {
		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'format_field_markdown' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->markdown_export, array( 'Email', 'test@example.com', 'email' ) );

		// Markdown special characters are escaped.
		$this->assertEquals( "- **Email:** test@example\\.com\n", $result );
	}

	/**
	 * Test format_field_markdown for textarea fields.
	 */
	public function test_format_field_markdown_textarea() {
		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'format_field_markdown' );
		$method->setAccessible( true );

		$value  = "\nThis is a multiline\ntext content";
		$result = $method->invokeArgs( $this->markdown_export, array( 'Message', $value, 'textarea' ) );

		$this->assertEquals( "- **Message:**\nThis is a multiline\ntext content\n\n", $result );
	}

	/**
	 * Test get_field_value for checkbox fields.
	 */
	public function test_get_field_value_checkbox() {
		$entry = array(
			'1.1' => 'Option 1',
			'1.2' => 'Option 2',
			'1.3' => '',
		);

		$field = (object) array(
			'id'     => 1,
			'type'   => 'checkbox',
			'inputs' => array(
				array( 'id' => '1.1' ),
				array( 'id' => '1.2' ),
				array( 'id' => '1.3' ),
			),
		);

		$form = array();

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'get_field_value' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->markdown_export, array( $entry, $field, $form ) );

		$this->assertEquals( 'Option 1, Option 2', $result );
	}

	/**
	 * Test get_field_value for name fields.
	 */
	public function test_get_field_value_name() {
		$entry = array(
			'2.3' => 'John',
			'2.6' => 'Doe',
		);

		$field = (object) array(
			'id'     => 2,
			'type'   => 'name',
			'inputs' => array(
				array( 'id' => '2.3' ),
				array( 'id' => '2.6' ),
			),
		);

		$form = array();

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'get_field_value' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->markdown_export, array( $entry, $field, $form ) );

		$this->assertEquals( 'John Doe', $result );
	}

	/**
	 * Test get_field_value for address fields.
	 */
	public function test_get_field_value_address() {
		$entry = array(
			'3.1' => '123 Main St',
			'3.3' => 'New York',
			'3.4' => 'NY',
			'3.5' => '10001',
		);

		$field = (object) array(
			'id'     => 3,
			'type'   => 'address',
			'inputs' => array(
				array( 'id' => '3.1' ),
				array( 'id' => '3.3' ),
				array( 'id' => '3.4' ),
				array( 'id' => '3.5' ),
			),
		);

		$form = array();

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'get_field_value' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->markdown_export, array( $entry, $field, $form ) );

		$this->assertEquals( '123 Main St, New York, NY, 10001', $result );
	}

	/**
	 * Test get_field_value for file upload fields.
	 */
	public function test_get_field_value_fileupload() {
		$entry = array(
			'4' => 'https://example.com/uploads/file.pdf',
		);

		$field = (object) array(
			'id'   => 4,
			'type' => 'fileupload',
		);

		$form = array();

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'get_field_value' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->markdown_export, array( $entry, $field, $form ) );

		$this->assertEquals( '[File](https://example.com/uploads/file.pdf)', $result );
	}

	/**
	 * Test get_field_value for textarea fields.
	 */
	public function test_get_field_value_textarea() {
		$entry = array(
			'5' => "Line 1\nLine 2\nLine 3",
		);

		$field = (object) array(
			'id'   => 5,
			'type' => 'textarea',
		);

		$form = array();

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'get_field_value' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $this->markdown_export, array( $entry, $field, $form ) );

		$this->assertEquals( "\nLine 1\nLine 2\nLine 3", $result );
	}

	/**
	 * Test generate_markdown creates proper structure.
	 */
	public function test_generate_markdown_structure() {
		$entry = array(
			'id'           => 789,
			'date_created' => '2024-01-15 14:30:00',
			'1'            => 'test@example.com',
		);

		$field = (object) array(
			'id'    => 1,
			'label' => 'Email',
			'type'  => 'email',
		);

		$form = array(
			'title'  => 'Test Form',
			'fields' => array( $field ),
		);

		$reflection = new ReflectionClass( $this->markdown_export );
		$method     = $reflection->getMethod( 'generate_markdown' );
		$method->setAccessible( true );

		$markdown = $method->invokeArgs( $this->markdown_export, array( $entry, $form ) );

		// Check structure.
		$this->assertStringContainsString( '# Test Form', $markdown );
		$this->assertStringContainsString( '**Entry ID:** 789', $markdown );
		$this->assertStringContainsString( '**Submitted at:** 2024-01-15 14:30', $markdown );
		$this->assertStringContainsString( '## Fields', $markdown );
		// Markdown special characters are escaped.
		$this->assertStringContainsString( '- **Email:** test@example\\.com', $markdown );
	}

	/**
	 * Test add_bulk_action filter.
	 */
	public function test_add_bulk_action() {
		$actions = array(
			'delete' => 'Delete',
		);

		$result = $this->markdown_export->add_bulk_action( $actions, 1 );

		$this->assertArrayHasKey( 'export_markdown', $result );
		$this->assertStringContainsString( 'Export to Markdown', $result['export_markdown'] );
	}

	/**
	 * Test add_export_metabox filter.
	 */
	public function test_add_export_metabox() {
		$meta_boxes = array();
		$entry      = array( 'id' => 1 );
		$form       = array( 'id' => 1 );

		$result = $this->markdown_export->add_export_metabox( $meta_boxes, $entry, $form );

		$this->assertArrayHasKey( 'formscrm_markdown', $result );
		$this->assertStringContainsString( 'FormsCRM: Export', $result['formscrm_markdown']['title'] );
	}
}
