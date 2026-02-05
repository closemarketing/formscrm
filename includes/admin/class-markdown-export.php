<?php
/**
 * Markdown Export for GravityForms Entries
 *
 * Handles exporting GravityForms entries to Markdown format.
 *
 * @package    WordPress
 * @author     Closetechnology
 * @copyright  2026 Closetechnology
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FORMSCRM_Markdown_Export' ) ) {
	/**
	 * Class FORMSCRM_Markdown_Export
	 *
	 * Handles Markdown export functionality for GravityForms entries.
	 */
	class FORMSCRM_Markdown_Export {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'formscrm_markdown_export_content', array( $this, 'render_export_page' ) );
			add_action( 'admin_init', array( $this, 'handle_export' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Enqueue scripts and styles
		 *
		 * @param string $hook Current admin page hook.
		 * @return void
		 */
		public function enqueue_scripts( $hook ) {
			if ( 'toplevel_page_formscrm' !== $hook ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['tab'] ) || 'markdown-export' !== $_GET['tab'] ) {
				return;
			}

			wp_enqueue_script(
				'formscrm-markdown-export',
				FORMSCRM_PLUGIN_URL . 'includes/admin/js/markdown-export.js',
				array( 'jquery' ),
				FORMSCRM_VERSION,
				true
			);

			wp_localize_script(
				'formscrm-markdown-export',
				'formscrmMarkdownExport',
				array(
					'selectEntries' => __( 'Please select at least one entry to export.', 'formscrm' ),
					'selectForm'    => __( 'Please select a form first.', 'formscrm' ),
					'loading'       => __( 'Loading entries...', 'formscrm' ),
					'noEntries'     => __( 'No entries found for this form.', 'formscrm' ),
					'ajaxError'     => __( 'Error loading entries. Please try again.', 'formscrm' ),
					'selectAll'     => __( 'Select All', 'formscrm' ),
					'nonce'         => wp_create_nonce( 'formscrm_markdown_export_nonce' ),
				)
			);
		}

		/**
		 * Handle export requests
		 *
		 * @return void
		 */
		public function handle_export() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
			if ( ! isset( $_POST['formscrm_export_markdown'] ) ) {
				return;
			}

			// Verify nonce.
			if ( ! isset( $_POST['formscrm_markdown_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formscrm_markdown_nonce'] ) ), 'formscrm_export_markdown' ) ) {
				wp_die( esc_html__( 'Security check failed', 'formscrm' ) );
			}

			// Check capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to export entries', 'formscrm' ) );
			}

			$form_id     = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
			$export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( wp_unslash( $_POST['export_type'] ) ) : 'all';
			$entry_ids   = isset( $_POST['entry_ids'] ) ? array_map( 'absint', (array) $_POST['entry_ids'] ) : array();
			// phpcs:enable

			if ( empty( $form_id ) ) {
				wp_die( esc_html__( 'Please select a form', 'formscrm' ) );
			}

			if ( 'selected' === $export_type && empty( $entry_ids ) ) {
				wp_die( esc_html__( 'Please select entries to export', 'formscrm' ) );
			}

			// Get entries.
			$entries = $this->get_entries_for_export( $form_id, $export_type, $entry_ids );

			if ( empty( $entries ) ) {
				wp_die( esc_html__( 'No entries found to export', 'formscrm' ) );
			}

			// Generate markdown.
			$markdown_content = $this->generate_markdown( $form_id, $entries );

			// Send file for download.
			$this->send_markdown_download( $form_id, $markdown_content );
		}

		/**
		 * Get entries for export
		 *
		 * @param int    $form_id     Form ID.
		 * @param string $export_type Export type (all, selected, recent).
		 * @param array  $entry_ids   Entry IDs for selected export.
		 * @return array Entries array.
		 */
		private function get_entries_for_export( $form_id, $export_type, $entry_ids = array() ) {
			if ( ! class_exists( 'GFAPI' ) ) {
				return array();
			}

			$search_criteria = array(
				'status' => 'active',
			);

			$paging = array(
				'offset'    => 0,
				'page_size' => 999999,
			);

			if ( 'selected' === $export_type && ! empty( $entry_ids ) ) {
				$search_criteria['field_filters'][] = array(
					'key'      => 'id',
					'operator' => 'in',
					'value'    => $entry_ids,
				);
			} elseif ( 'recent' === $export_type ) {
				$paging['page_size'] = 50;
			}

			$sorting = array(
				array(
					'key'        => 'date_created',
					'direction'  => 'DESC',
					'is_numeric' => false,
				),
			);

			return GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );
		}

		/**
		 * Generate markdown content
		 *
		 * @param int   $form_id Form ID.
		 * @param array $entries Entries array.
		 * @return string Markdown content.
		 */
		private function generate_markdown( $form_id, $entries ) {
			if ( ! class_exists( 'GFAPI' ) ) {
				return '';
			}

			$form = GFAPI::get_form( $form_id );
			if ( ! $form ) {
				return '';
			}

			$markdown  = '';
			$is_single = 1 === count( $entries );

			if ( ! $is_single ) {
				// Multiple entries - add main title.
				$markdown .= "# {$form['title']}\n\n";
				$markdown .= "**Total Entries:** " . count( $entries ) . "\n";
				$markdown .= "**Export Date:** " . current_time( 'Y-m-d H:i:s' ) . "\n\n";
				$markdown .= "---\n\n";
			}

			foreach ( $entries as $entry ) {
				$markdown .= $this->generate_entry_markdown( $form, $entry, $is_single );

				if ( ! $is_single ) {
					$markdown .= "\n---\n\n";
				}
			}

			return $markdown;
		}

		/**
		 * Generate markdown for a single entry
		 *
		 * @param array $form     Form array.
		 * @param array $entry    Entry array.
		 * @param bool  $is_single Whether this is a single entry export.
		 * @return string Markdown content for entry.
		 */
		private function generate_entry_markdown( $form, $entry, $is_single = false ) {
			$markdown = '';

			// Title.
			if ( $is_single ) {
				$markdown .= "# {$form['title']}\n\n";
			} else {
				$markdown .= "## Entry #{$entry['id']}\n\n";
			}

			// Metadata.
			$markdown .= "**Entry ID:** {$entry['id']}  \n";
			$markdown .= "**Submitted at:** {$entry['date_created']}  \n";
			$markdown .= "**User IP:** {$entry['ip']}  \n";

			if ( ! empty( $entry['created_by'] ) && '0' !== $entry['created_by'] ) {
				$user = get_userdata( $entry['created_by'] );
				if ( $user ) {
					$markdown .= "**User:** {$user->display_name}  \n";
				}
			}

			$markdown .= "\n## Fields\n\n";

			// Process form fields.
			foreach ( $form['fields'] as $field ) {
				$field_markdown = $this->process_field( $field, $entry );
				if ( ! empty( $field_markdown ) ) {
					$markdown .= $field_markdown;
				}
			}

			return $markdown;
		}

		/**
		 * Process a field and return markdown
		 *
		 * @param object $field Field object.
		 * @param array  $entry Entry array.
		 * @return string Markdown content for field.
		 */
		private function process_field( $field, $entry ) {
			$markdown    = '';
			$field_label = ! empty( $field->adminLabel ) ? $field->adminLabel : $field->label;
			$field_type  = RGFormsModel::get_input_type( $field );
			$field_id    = $field->id;

			// Skip hidden/admin fields.
			if ( in_array( $field_type, array( 'hiddenproduct', 'calculation', 'page' ), true ) ) {
				return '';
			}

			// Get field value.
			$value = $this->get_field_value( $field, $entry );

			// Skip empty values.
			if ( empty( $value ) || '[]' === $value ) {
				return '';
			}

			// Format based on field type.
			switch ( $field_type ) {
				case 'checkbox':
					$markdown .= "- **{$field_label}:**\n";
					$checkbox_values = is_array( $value ) ? $value : explode( '|', $value );
					foreach ( $checkbox_values as $checkbox_value ) {
						if ( ! empty( trim( $checkbox_value ) ) ) {
							$markdown .= "  - " . esc_html( trim( $checkbox_value ) ) . "\n";
						}
					}
					break;

				case 'textarea':
				case 'post_content':
					$markdown .= "- **{$field_label}:**\n\n";
					$markdown .= "  ```\n";
					$markdown .= '  ' . str_replace( "\n", "\n  ", esc_html( $value ) ) . "\n";
					$markdown .= "  ```\n\n";
					break;

				case 'fileupload':
					$markdown .= "- **{$field_label}:** ";
					$files     = json_decode( $value, true );
					if ( is_array( $files ) ) {
						$markdown .= "\n";
						foreach ( $files as $file ) {
							$markdown .= "  - [{$file}]({$file})\n";
						}
					} else {
						// Single file.
						$markdown .= "[Download]({$value})\n";
					}
					break;

				case 'section':
					$markdown .= "\n### {$field_label}\n\n";
					break;

				default:
					// Standard field.
					if ( strlen( $value ) > 100 ) {
						$markdown .= "- **{$field_label}:**\n\n  " . str_replace( "\n", "\n  ", esc_html( $value ) ) . "\n\n";
					} else {
						$markdown .= "- **{$field_label}:** " . esc_html( $value ) . "\n";
					}
					break;
			}

			return $markdown;
		}

		/**
		 * Get field value from entry
		 *
		 * @param object $field Field object.
		 * @param array  $entry Entry array.
		 * @return string|array Field value.
		 */
		private function get_field_value( $field, $entry ) {
			$field_type = RGFormsModel::get_input_type( $field );
			$field_id   = $field->id;

			// Handle special field types.
			if ( 'checkbox' === $field_type ) {
				$values = array();
				foreach ( $field->inputs as $input ) {
					$input_id = (string) $input['id'];
					if ( ! empty( $entry[ $input_id ] ) ) {
						$values[] = $entry[ $input_id ];
					}
				}
				return $values;
			} elseif ( 'name' === $field_type && false === strpos( $field_id, '.' ) ) {
				// Full name field.
				$name_parts = array();
				if ( ! empty( $entry[ $field_id . '.3' ] ) ) {
					$name_parts[] = $entry[ $field_id . '.3' ];
				}
				if ( ! empty( $entry[ $field_id . '.6' ] ) ) {
					$name_parts[] = $entry[ $field_id . '.6' ];
				}
				return implode( ' ', $name_parts );
			} elseif ( 'address' === $field_type && false === strpos( $field_id, '.' ) ) {
				// Full address field.
				$address_parts = array();
				if ( ! empty( $entry[ $field_id . '.1' ] ) ) {
					$address_parts[] = $entry[ $field_id . '.1' ];
				}
				if ( ! empty( $entry[ $field_id . '.2' ] ) ) {
					$address_parts[] = $entry[ $field_id . '.2' ];
				}
				if ( ! empty( $entry[ $field_id . '.3' ] ) ) {
					$address_parts[] = $entry[ $field_id . '.3' ];
				}
				if ( ! empty( $entry[ $field_id . '.4' ] ) ) {
					$address_parts[] = $entry[ $field_id . '.4' ];
				}
				if ( ! empty( $entry[ $field_id . '.5' ] ) ) {
					$address_parts[] = $entry[ $field_id . '.5' ];
				}
				if ( ! empty( $entry[ $field_id . '.6' ] ) ) {
					$address_parts[] = $entry[ $field_id . '.6' ];
				}
				return implode( ', ', $address_parts );
			}

			// Standard field.
			return isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
		}

		/**
		 * Send markdown file for download
		 *
		 * @param int    $form_id         Form ID.
		 * @param string $markdown_content Markdown content.
		 * @return void
		 */
		private function send_markdown_download( $form_id, $markdown_content ) {
			$form = GFAPI::get_form( $form_id );
			if ( ! $form ) {
				wp_die( esc_html__( 'Form not found', 'formscrm' ) );
			}

			$filename = sanitize_file_name( $form['title'] ) . '-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';

			// Send headers.
			header( 'Content-Type: text/markdown; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// Output content.
			echo $markdown_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markdown content for download.
			exit;
		}

		/**
		 * Render export page
		 *
		 * @return void
		 */
		public function render_export_page() {
			if ( ! class_exists( 'GFAPI' ) ) {
				?>
				<div class="fcrm-notice fcrm-notice-error">
					<p><?php esc_html_e( 'GravityForms is not installed or activated. This feature requires GravityForms to be active.', 'formscrm' ); ?></p>
				</div>
				<?php
				return;
			}

			$forms = GFAPI::get_forms();

			?>
			<!-- Markdown Export Section -->
			<div class="fcrm-section">
				<div class="fcrm-section-header">
					<h2 class="fcrm-section-title">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
						echo formscrm_get_svg_icon( 'icon-document' );
						?>
						<?php esc_html_e( 'Export GravityForms Entries to Markdown', 'formscrm' ); ?>
					</h2>
					<p class="fcrm-section-description">
						<?php esc_html_e( 'Export your GravityForms entries to clean, readable Markdown files.', 'formscrm' ); ?>
					</p>
				</div>

				<div class="fcrm-section-content">
					<form method="post" action="" id="formscrm-markdown-export-form">
						<?php wp_nonce_field( 'formscrm_export_markdown', 'formscrm_markdown_nonce' ); ?>

						<!-- Form Selection -->
						<div class="fcrm-form-group">
							<label class="fcrm-form-label">
								<?php esc_html_e( 'Select Form', 'formscrm' ); ?>
							</label>
							<select name="form_id" id="formscrm-form-select" class="fcrm-form-input" required>
								<option value=""><?php esc_html_e( '-- Select a form --', 'formscrm' ); ?></option>
								<?php foreach ( $forms as $form ) : ?>
									<option value="<?php echo esc_attr( $form['id'] ); ?>">
										<?php echo esc_html( $form['title'] ) . ' (ID: ' . esc_html( $form['id'] ) . ')'; ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<!-- Export Type -->
						<div class="fcrm-form-group">
							<label class="fcrm-form-label">
								<?php esc_html_e( 'Export Type', 'formscrm' ); ?>
							</label>
							<div class="fcrm-radio-group">
								<label class="fcrm-radio-label">
									<input type="radio" name="export_type" value="all" checked>
									<span><?php esc_html_e( 'All Entries', 'formscrm' ); ?></span>
								</label>
								<label class="fcrm-radio-label">
									<input type="radio" name="export_type" value="recent">
									<span><?php esc_html_e( 'Recent 50 Entries', 'formscrm' ); ?></span>
								</label>
								<label class="fcrm-radio-label">
									<input type="radio" name="export_type" value="selected" id="export-type-selected">
									<span><?php esc_html_e( 'Selected Entries', 'formscrm' ); ?></span>
								</label>
							</div>
						</div>

						<!-- Entry Selection (shown when "Selected Entries" is chosen) -->
						<div id="formscrm-entry-selection" class="fcrm-form-group" style="display: none;">
							<label class="fcrm-form-label">
								<?php esc_html_e( 'Select Entries', 'formscrm' ); ?>
							</label>
							<div id="formscrm-entries-list" class="fcrm-entries-list">
								<p class="fcrm-help-text"><?php esc_html_e( 'Please select a form first to see available entries.', 'formscrm' ); ?></p>
							</div>
						</div>

						<!-- Export Button -->
						<div class="fcrm-form-actions">
							<button type="submit" name="formscrm_export_markdown" class="fcrm-button fcrm-button-primary">
								<?php esc_html_e( 'Export to Markdown', 'formscrm' ); ?>
							</button>
						</div>
					</form>

					<!-- Info Box -->
					<div class="fcrm-info-box" style="margin-top: 30px;">
						<h3><?php esc_html_e( 'About Markdown Export', 'formscrm' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Exports entries in a clean, readable Markdown format', 'formscrm' ); ?></li>
							<li><?php esc_html_e( 'Includes form title, entry ID, submission date, and all field values', 'formscrm' ); ?></li>
							<li><?php esc_html_e( 'Handles special field types: checkboxes, file uploads, textareas, and more', 'formscrm' ); ?></li>
							<li><?php esc_html_e( 'Perfect for documentation, sharing, or version control', 'formscrm' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
			<?php
		}
	}
}

if ( is_admin() ) {
	new FORMSCRM_Markdown_Export();
}
