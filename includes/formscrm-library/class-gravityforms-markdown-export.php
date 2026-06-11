<?php
/**
 * Markdown Export for Gravity Forms
 *
 * Handles exporting GravityForms entries to Markdown format.
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2026 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for Markdown Export
 */
class FormsCRM_GravityForms_Markdown_Export {

	/**
	 * Construct of Class
	 */
	public function __construct() {
		// Add export action to entry list bulk actions.
		add_filter( 'gform_entry_list_bulk_actions', array( $this, 'add_bulk_action' ), 10, 2 );
		add_action( 'gform_entry_list_action_export_markdown', array( $this, 'process_bulk_export' ), 10, 3 );

		// Add export button to entry detail page.
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'add_export_metabox' ), 10, 3 );

		// Handle single entry export via query parameter.
		// Using multiple hooks to ensure it catches the request early enough.
		add_action( 'init', array( $this, 'handle_single_export' ), 1 );
		add_action( 'admin_init', array( $this, 'handle_single_export' ), 1 );
	}

	/**
	 * Add bulk action to export entries to Markdown.
	 *
	 * @param array $actions Current bulk actions.
	 * @param int   $form_id Form ID.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_action( $actions, $form_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by WordPress filter hook.
		$actions['export_markdown'] = esc_html__( 'FormsCRM: Export to Markdown', 'formscrm' );
		return $actions;
	}

	/**
	 * Process bulk export of entries to Markdown.
	 *
	 * @param string $action  Action name.
	 * @param array  $entries Entry IDs.
	 * @param int    $form_id Form ID.
	 * @return void
	 */
	public function process_bulk_export( $action, $entries, $form_id ) {
		if ( 'export_markdown' !== $action || empty( $entries ) ) {
			return;
		}

		$form = GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return;
		}

		// Create ZIP file for multiple entries.
		$zip_filename = $this->create_zip_export( $entries, $form );

		if ( $zip_filename ) {
			// Force download the ZIP file.
			$this->download_file( $zip_filename );
		}
	}

	/**
	 * Add meta box to entry detail page for Markdown export.
	 *
	 * @param array $meta_boxes Current meta boxes.
	 * @param array $entry      Entry data.
	 * @param array $form       Form object.
	 * @return array Modified meta boxes.
	 */
	public function add_export_metabox( $meta_boxes, $entry, $form ) {
		$meta_boxes['formscrm_markdown'] = array(
			'title'         => esc_html__( 'FormsCRM: Export', 'formscrm' ),
			'callback'      => array( $this, 'render_export_metabox' ),
			'context'       => 'side',
			'callback_args' => array( $entry, $form ),
		);

		return $meta_boxes;
	}

	/**
	 * Render the export meta box content.
	 *
	 * @param array $args Arguments containing entry and form.
	 * @return void
	 */
	public function render_export_metabox( $args ) {
		$entry    = ! empty( $args['entry'] ) ? $args['entry'] : array();
		$form     = ! empty( $args['form'] ) ? $args['form'] : array();
		$entry_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
		$form_id  = isset( $form['id'] ) ? (int) $form['id'] : 0;

		if ( ! $entry_id || ! $form_id ) {
			echo '<p>' . esc_html__( 'Invalid entry or form.', 'formscrm' ) . '</p>';
			return;
		}

		$export_url = add_query_arg(
			array(
				'page'                     => 'gf_entries',
				'view'                     => 'entry',
				'id'                       => $form_id,
				'lid'                      => $entry_id,
				'formscrm_export_markdown' => '1',
				'nonce'                    => wp_create_nonce( 'formscrm_export_' . $entry_id ),
			),
			admin_url( 'admin.php' )
		);

		echo '<p>' . esc_html__( 'Export this entry as a Markdown file.', 'formscrm' ) . '</p>';
		echo '<a href="' . esc_url( $export_url ) . '" class="button button-primary">' . esc_html__( 'Download Markdown', 'formscrm' ) . '</a>';
	}

	/**
	 * Handle single entry export via admin_init.
	 *
	 * @return void
	 */
	public function handle_single_export() {
		// Check if export request first (fastest check).
		if ( ! isset( $_GET['formscrm_export_markdown'] ) || '1' !== $_GET['formscrm_export_markdown'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
			return;
		}

		// Check if we're in admin area.
		if ( ! is_admin() ) {
			return;
		}

		// Verify we're on the right page.
		if ( ! isset( $_GET['page'] ) || 'gf_entries' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
			return;
		}

		$entry_id = isset( $_GET['lid'] ) ? absint( $_GET['lid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$form_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below.
		$nonce    = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified here.

		// Basic validation.
		if ( ! $entry_id || ! $form_id ) {
			wp_die( esc_html__( 'Invalid entry or form ID.', 'formscrm' ) );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'formscrm_export_' . $entry_id ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'formscrm' ) );
		}

		// Check capabilities using GravityForms API.
		$has_permission = false;

		if ( class_exists( 'GFCommon' ) && method_exists( 'GFCommon', 'current_user_can_any' ) ) {
			// @phpstan-ignore staticMethod.notFound
			$has_permission = GFCommon::current_user_can_any( array( 'gravityforms_view_entries', 'gravityforms_edit_entries', 'gravityforms_export_entries' ) );
		}

		// If GFCommon not available or permission still false, check standard capabilities.
		if ( ! $has_permission ) {
			// phpcs:disable WordPress.WP.Capabilities.Unknown -- GravityForms custom capabilities.
			$has_permission = current_user_can( 'gravityforms_view_entries' ) ||
			current_user_can( 'gravityforms_export_entries' ) ||
			current_user_can( 'gravityforms_edit_entries' ) ||
			current_user_can( 'gform_full_access' ) ||
			current_user_can( 'manage_options' );
			// phpcs:enable WordPress.WP.Capabilities.Unknown
		}

		if ( ! $has_permission ) {
			// Debug info for troubleshooting.
			$current_user = wp_get_current_user();
			$user_roles   = implode( ', ', $current_user->roles );
			wp_die(
				esc_html__( 'You do not have permission to export entries.', 'formscrm' ) .
				'<br><br><small>User roles: ' . esc_html( $user_roles ) . '</small>'
			);
		}

		// Verify GravityForms API is available.
		if ( ! class_exists( 'GFAPI' ) ) {
			wp_die( esc_html__( 'GravityForms API not available.', 'formscrm' ) );
		}

		$entry = GFAPI::get_entry( $entry_id );
		$form  = GFAPI::get_form( $form_id );

		// @phpstan-ignore function.impossibleType
		if ( ! $entry || is_wp_error( $entry ) ) {
			wp_die( esc_html__( 'Entry not found.', 'formscrm' ) );
		}

		// @phpstan-ignore function.impossibleType
		if ( ! $form || is_wp_error( $form ) ) {
			wp_die( esc_html__( 'Form not found.', 'formscrm' ) );
		}

		// Generate markdown content.
		$markdown = $this->generate_markdown( $entry, $form );

		// Create filename.
		$filename = $this->generate_filename( $entry, $form );

		// Force download.
		$this->force_download_markdown( $markdown, $filename );

		die();
	}

	/**
	 * Generate Markdown content for an entry.
	 *
	 * @param array $entry Entry data.
	 * @param array $form  Form object.
	 * @return string Markdown content.
	 */
	private function generate_markdown( $entry, $form ) {
		$markdown   = '';
		$form_title = isset( $form['title'] ) ? $form['title'] : __( 'Form', 'formscrm' );
		$entry_id   = isset( $entry['id'] ) ? $entry['id'] : '';
		$date       = isset( $entry['date_created'] ) ? $entry['date_created'] : '';

		// Format the date.
		if ( $date ) {
			$date_obj = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date );
			if ( $date_obj ) {
				$date = $date_obj->format( 'Y-m-d H:i' );
			}
		}

		// Header.
		$markdown .= "# {$form_title}\n\n";
		$markdown .= "**Entry ID:** {$entry_id}  \n";
		$markdown .= "**Submitted at:** {$date}\n\n";
		$markdown .= "## Fields\n\n";

		// Process fields.
		if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$field_id    = isset( $field->id ) ? $field->id : '';
				$field_label = isset( $field->label ) ? $field->label : '';
				$field_type  = isset( $field->type ) ? $field->type : '';

				// Skip fields without labels.
				if ( empty( $field_label ) ) {
					continue;
				}

				// Get field value based on type.
				$field_value = $this->get_field_value( $entry, $field, $form );

				// Skip empty values.
				if ( '' === $field_value || null === $field_value ) {
					continue;
				}

				// Format field in markdown.
				$markdown .= $this->format_field_markdown( $field_label, $field_value, $field_type );
			}
		}

		return $markdown;
	}

	/**
	 * Get field value based on field type.
	 *
	 * @param array  $entry Entry data.
	 * @param object $field Field object.
	 * @param array  $form  Form object.
	 * @return string Field value.
	 */
	private function get_field_value( $entry, $field, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Form parameter reserved for future enhancements.

		$field_id   = $field->id;
		$field_type = $field->type;
		$value      = '';

		switch ( $field_type ) {
			case 'checkbox':
				// Get all checkbox values.
				$values = array();
				if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
					foreach ( $field->inputs as $input ) {
						$input_id = (string) $input['id'];
						if ( ! empty( $entry[ $input_id ] ) ) {
							$values[] = $entry[ $input_id ];
						}
					}
				}
				$value = ! empty( $values ) ? implode( ', ', $values ) : '';
				break;

			case 'multiselect':
				// Multiselect returns comma-separated values.
				$value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
				$value = str_replace( ',', ', ', $value );
				break;

			case 'name':
				// Combine name parts.
				$parts = array();
				if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
					foreach ( $field->inputs as $input ) {
						$input_id = (string) $input['id'];
						if ( ! empty( $entry[ $input_id ] ) ) {
							$parts[] = $entry[ $input_id ];
						}
					}
				}
				$value = implode( ' ', $parts );
				break;

			case 'address':
				// Combine address parts.
				$address_parts = array();
				if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
					foreach ( $field->inputs as $input ) {
						$input_id = (string) $input['id'];
						if ( ! empty( $entry[ $input_id ] ) ) {
							$address_parts[] = $entry[ $input_id ];
						}
					}
				}
				$value = implode( ', ', $address_parts );
				break;

			case 'fileupload':
				// Handle file uploads.
				$file_value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
				if ( ! empty( $file_value ) ) {
					// Check if it's a JSON array of files.
					$files = json_decode( $file_value, true );
					if ( is_array( $files ) ) {
						$file_links = array();
						foreach ( $files as $file_url ) {
							$file_links[] = "[File]({$file_url})";
						}
						$value = implode( "\n", $file_links );
					} else {
						$value = "[File]({$file_value})";
					}
				}
				break;

			case 'list':
				// Handle list fields.
				$list_value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
				if ( ! empty( $list_value ) ) {
					$list_items = unserialize( $list_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- GravityForms stores list data serialized.
					if ( is_array( $list_items ) ) {
						$formatted_items = array();
						foreach ( $list_items as $item ) {
							if ( is_array( $item ) ) {
								$formatted_items[] = '- ' . implode( ', ', $item );
							} else {
								$formatted_items[] = '- ' . $item;
							}
						}
						$value = "\n" . implode( "\n", $formatted_items );
					}
				}
				break;

			case 'textarea':
			case 'post_content':
				// Preserve line breaks in textarea.
				$value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
				if ( ! empty( $value ) ) {
					$value = "\n" . $value;
				}
				break;

			default:
				// Default handling for text, email, number, etc.
				$value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
				break;
		}

		return $value;
	}

	/**
	 * Format field as Markdown.
	 *
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @param string $type  Field type.
	 * @return string Formatted markdown string.
	 */
	private function format_field_markdown( $label, $value, $type ) {
		// Escape markdown special characters in label.
		$label = $this->escape_markdown( $label );

		// For textarea and similar, use block format.
		if ( in_array( $type, array( 'textarea', 'post_content', 'list' ), true ) ) {
			return "- **{$label}:**{$value}\n\n";
		}

		// For other fields, use inline format.
		$value = $this->escape_markdown( $value );
		return "- **{$label}:** {$value}\n";
	}

	/**
	 * Escape markdown special characters.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	private function escape_markdown( $text ) {
		// Don't escape file links or already formatted markdown.
		if ( false !== strpos( $text, '](http' ) ) {
			return $text;
		}

		$special_chars = array( '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '#', '+', '-', '.', '!' );
		foreach ( $special_chars as $char ) {
			$text = str_replace( $char, '\\' . $char, $text );
		}
		return $text;
	}

	/**
	 * Generate filename for Markdown export.
	 *
	 * @param array $entry Entry data.
	 * @param array $form  Form object.
	 * @return string Filename.
	 */
	private function generate_filename( $entry, $form ) {
		$form_title = isset( $form['title'] ) ? $form['title'] : 'form';
		$entry_id   = isset( $entry['id'] ) ? $entry['id'] : 'entry';

		// Sanitize form title for filename.
		$form_title = sanitize_file_name( $form_title );

		return "{$form_title}-entry-{$entry_id}.md";
	}

	/**
	 * Force download of Markdown file.
	 *
	 * @param string $content  Markdown content.
	 * @param string $filename Filename.
	 * @return void
	 */
	private function force_download_markdown( $content, $filename ) {
		// Clear all output buffers.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		// Prevent any caching.
		nocache_headers();

		// Set headers for download.
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );

		// Output content.
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown content is properly escaped in generation.

		// Ensure we stop execution.
		die();
	}

	/**
	 * Create ZIP file for multiple entries export.
	 *
	 * @param array $entry_ids Entry IDs to export.
	 * @param array $form      Form object.
	 * @return string|false Path to ZIP file or false on failure.
	 */
	private function create_zip_export( $entry_ids, $form ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/formscrm-exports/';

		// Create temp directory if it doesn't exist.
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		// Generate unique filename for ZIP.
		$form_title   = isset( $form['title'] ) ? sanitize_file_name( $form['title'] ) : 'form';
		$timestamp    = gmdate( 'Y-m-d-His' );
		$zip_filename = "{$temp_dir}{$form_title}-entries-{$timestamp}.zip";

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_filename, ZipArchive::CREATE ) ) {
			return false;
		}

		// Add each entry as a Markdown file to the ZIP.
		foreach ( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( $entry_id );
			if ( ! $entry ) {
				continue;
			}

			$markdown = $this->generate_markdown( $entry, $form );
			$filename = $this->generate_filename( $entry, $form );

			$zip->addFromString( $filename, $markdown );
		}

		$zip->close();

		return $zip_filename;
	}

	/**
	 * Force download of a file.
	 *
	 * @param string $filepath Path to file.
	 * @return void
	 */
	private function download_file( $filepath ) {
		if ( ! file_exists( $filepath ) ) {
			return;
		}

		// Clear output buffer.
		if ( ob_get_level() ) {
			ob_end_clean();
		}

		// Set headers for download.
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . basename( $filepath ) . '"' );
		header( 'Content-Length: ' . filesize( $filepath ) );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Expires: 0' );

		// Output file.
		readfile( $filepath ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Using readfile for file download.

		// Delete temp file.
		wp_delete_file( $filepath );

		exit;
	}
}

new FormsCRM_GravityForms_Markdown_Export();
