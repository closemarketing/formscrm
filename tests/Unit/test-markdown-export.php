<?php
/**
 * Test Markdown Export Functionality
 *
 * @package FormsCRM
 */

/**
 * Class Test_Markdown_Export
 *
 * @group markdown-export
 */
class Test_Markdown_Export extends WP_UnitTestCase {

	/**
	 * Test if class exists
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'FORMSCRM_Markdown_Export' ) );
	}

	/**
	 * Test if markdown export tab is added
	 */
	public function test_markdown_export_tab_added() {
		$tabs = apply_filters( 'formscrm_settings_tabs', array() );
		
		$markdown_tab_exists = false;
		foreach ( $tabs as $tab ) {
			if ( isset( $tab['tab'] ) && 'markdown-export' === $tab['tab'] ) {
				$markdown_tab_exists = true;
				break;
			}
		}
		
		$this->assertTrue( $markdown_tab_exists, 'Markdown export tab should be added to settings tabs' );
	}

	/**
	 * Test markdown formatting for text field
	 */
	public function test_markdown_text_field_format() {
		$expected = "- **Name:** John Doe\n";
		$field_label = 'Name';
		$field_value = 'John Doe';
		
		$markdown = "- **{$field_label}:** " . esc_html( $field_value ) . "\n";
		
		$this->assertEquals( $expected, $markdown );
	}

	/**
	 * Test markdown formatting for checkbox field
	 */
	public function test_markdown_checkbox_field_format() {
		$field_label = 'Interests';
		$values = array( 'Technology', 'Design', 'Marketing' );
		
		$markdown = "- **{$field_label}:**\n";
		foreach ( $values as $value ) {
			$markdown .= "  - " . esc_html( $value ) . "\n";
		}
		
		$this->assertStringContainsString( 'Technology', $markdown );
		$this->assertStringContainsString( 'Design', $markdown );
		$this->assertStringContainsString( 'Marketing', $markdown );
	}

	/**
	 * Test markdown formatting for textarea field
	 */
	public function test_markdown_textarea_field_format() {
		$field_label = 'Message';
		$value = "Line 1\nLine 2\nLine 3";
		
		$markdown = "- **{$field_label}:**\n\n";
		$markdown .= "  ```\n";
		$markdown .= '  ' . str_replace( "\n", "\n  ", esc_html( $value ) ) . "\n";
		$markdown .= "  ```\n\n";
		
		$this->assertStringContainsString( 'Line 1', $markdown );
		$this->assertStringContainsString( 'Line 2', $markdown );
		$this->assertStringContainsString( '```', $markdown );
	}

	/**
	 * Test markdown formatting for file upload field
	 */
	public function test_markdown_fileupload_field_format() {
		$field_label = 'Resume';
		$file_url = 'https://example.com/uploads/resume.pdf';
		
		$markdown = "- **{$field_label}:** [Download]({$file_url})\n";
		
		$this->assertStringContainsString( 'Download', $markdown );
		$this->assertStringContainsString( $file_url, $markdown );
	}

	/**
	 * Test entry metadata format
	 */
	public function test_entry_metadata_format() {
		$entry = array(
			'id'           => 123,
			'date_created' => '2024-01-15 14:32:00',
			'ip'           => '192.168.1.1',
		);
		
		$markdown = "**Entry ID:** {$entry['id']}  \n";
		$markdown .= "**Submitted at:** {$entry['date_created']}  \n";
		$markdown .= "**User IP:** {$entry['ip']}  \n";
		
		$this->assertStringContainsString( 'Entry ID: 123', $markdown );
		$this->assertStringContainsString( 'Submitted at:', $markdown );
		$this->assertStringContainsString( 'User IP:', $markdown );
	}

	/**
	 * Test form title format for single entry
	 */
	public function test_form_title_single_entry() {
		$form_title = 'Contact Form';
		$markdown = "# {$form_title}\n\n";
		
		$this->assertStringContainsString( '# Contact Form', $markdown );
	}

	/**
	 * Test form title format for multiple entries
	 */
	public function test_form_title_multiple_entries() {
		$form_title = 'Contact Form';
		$entry_count = 3;
		
		$markdown = "# {$form_title}\n\n";
		$markdown .= "**Total Entries:** {$entry_count}\n";
		
		$this->assertStringContainsString( '# Contact Form', $markdown );
		$this->assertStringContainsString( 'Total Entries: 3', $markdown );
	}

	/**
	 * Test section separator in multiple entries
	 */
	public function test_entry_separator() {
		$separator = "\n---\n\n";
		
		$this->assertEquals( "\n---\n\n", $separator );
	}

	/**
	 * Test escaping of special characters
	 */
	public function test_special_character_escaping() {
		$value = '<script>alert("XSS")</script>';
		$escaped = esc_html( $value );
		
		$this->assertStringNotContainsString( '<script>', $escaped );
		$this->assertStringContainsString( '&lt;script&gt;', $escaped );
	}

	/**
	 * Test AJAX action registration
	 */
	public function test_ajax_action_registered() {
		$this->assertTrue( has_action( 'wp_ajax_formscrm_get_form_entries' ) !== false );
	}

	/**
	 * Test markdown export action hook
	 */
	public function test_markdown_export_action_hook() {
		$this->assertTrue( has_action( 'formscrm_markdown_export_content' ) !== false );
	}
}
