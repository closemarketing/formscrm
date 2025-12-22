<?php
/**
 * Error Log Page
 *
 * Displays error log page with table and filters.
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2024 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FORMSCRM_Error_Log_Page' ) ) {
	/**
	 * Class FORMSCRM_Error_Log_Page
	 *
	 * Handles error log page display and filtering.
	 */
	class FORMSCRM_Error_Log_Page {
		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'formscrm_error_log_content', array( $this, 'render_error_log_page' ) );
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
			if ( ! isset( $_GET['tab'] ) || 'error-log' !== $_GET['tab'] ) {
				return;
			}

			wp_enqueue_script(
				'formscrm-error-log',
				FORMSCRM_PLUGIN_URL . 'includes/admin/js/error-log.js',
				array( 'jquery' ),
				FORMSCRM_VERSION,
				true
			);

			wp_localize_script(
				'formscrm-error-log',
				'formscrmErrorLog',
				array(
					'ajaxurl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'formscrm_error_log_nonce' ),
					'confirmClear'  => __( 'Are you sure you want to clear all error logs? This action cannot be undone.', 'formscrm' ),
					'confirmDelete' => __( 'Are you sure you want to delete this log entry?', 'formscrm' ),
					'resending'     => __( 'Resending...', 'formscrm' ),
					'clearing'      => __( 'Clearing...', 'formscrm' ),
					'clearAll'      => __( 'Clear All Logs', 'formscrm' ),
					'viewDetails'   => __( 'Details', 'formscrm' ),
					'hideDetails'   => __( 'Hide', 'formscrm' ),
					'successText'   => __( 'Success', 'formscrm' ),
				)
			);
		}

		/**
		 * Render error log page
		 *
		 * @return void
		 */
		public function render_error_log_page() {
			global $formscrm_error_log;

			// Get filter parameters.
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$status   = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';
			$crm_type = isset( $_GET['filter_crm'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_crm'] ) ) : '';
			$page_num = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
			// phpcs:enable

			$per_page = 20;

			$args = array(
				'per_page' => $per_page,
				'page'     => $page_num,
				'status'   => $status,
				'crm_type' => $crm_type,
			);

			$logs        = $formscrm_error_log->get_logs( $args );
			$total_items = $formscrm_error_log->get_total_count( $args );
			$total_pages = ceil( $total_items / $per_page );

			?>
			<!-- Error Log Section -->
			<div class="fcrm-section">
				<div class="fcrm-section-header">
					<h2 class="fcrm-section-title">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
						echo formscrm_get_svg_icon( 'icon-document' );
						?>
						<?php esc_html_e( 'Error Log', 'formscrm' ); ?>
					</h2>
					<p class="fcrm-section-description">
						<?php esc_html_e( 'Track and manage failed form submissions to your CRM.', 'formscrm' ); ?>
					</p>
				</div>

				<div class="fcrm-section-content">
					<!-- Filters -->
					<div class="fcrm-error-log-filters" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
						<form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
							<input type="hidden" name="page" value="formscrm">
							<input type="hidden" name="tab" value="error-log">

							<select name="filter_status" class="fcrm-form-input" style="max-width: 150px;">
								<option value=""><?php esc_html_e( 'All Status', 'formscrm' ); ?></option>
								<option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'formscrm' ); ?></option>
								<option value="success" <?php selected( $status, 'success' ); ?>><?php esc_html_e( 'Success', 'formscrm' ); ?></option>
							</select>

							<select name="filter_crm" class="fcrm-form-input" style="max-width: 150px;">
								<option value=""><?php esc_html_e( 'All CRMs', 'formscrm' ); ?></option>
								<?php
								$crm_choices = formscrm_get_choices();
								foreach ( $crm_choices as $crm ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $crm['value'] ),
										selected( $crm_type, $crm['value'], false ),
										esc_html( $crm['label'] )
									);
								}
								?>
							</select>

							<button type="submit" class="fcrm-button fcrm-button-secondary">
								<?php esc_html_e( 'Filter', 'formscrm' ); ?>
							</button>

							<a href="?page=formscrm&tab=error-log" class="fcrm-button fcrm-button-secondary">
								<?php esc_html_e( 'Reset', 'formscrm' ); ?>
							</a>
						</form>

						<button type="button" class="fcrm-button fcrm-button-danger" id="fcrm-clear-all-logs">
							<?php esc_html_e( 'Clear All Logs', 'formscrm' ); ?>
						</button>
					</div>

					<!-- Stats Summary -->
					<div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
						<strong><?php esc_html_e( 'Total Entries:', 'formscrm' ); ?></strong> <?php echo esc_html( $total_items ); ?>
					</div>

					<!-- Error Log Table -->
					<?php if ( empty( $logs ) ) : ?>
						<div class="fcrm-notice fcrm-notice-info">
							<p><?php esc_html_e( 'No error logs found.', 'formscrm' ); ?></p>
						</div>
					<?php else : ?>
						<div class="fcrm-error-log-table-wrapper" style="overflow-x: auto;">
							<table class="fcrm-table" style="width: 100%; border-collapse: collapse;">
								<thead>
									<tr style="background: #f9f9f9;">
										<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Date', 'formscrm' ); ?></th>
										<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'CRM', 'formscrm' ); ?></th>
										<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Form', 'formscrm' ); ?></th>
										<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Error', 'formscrm' ); ?></th>
										<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Status', 'formscrm' ); ?></th>
										<th style="padding: 12px; text-align: left; border-bottom: 2px solid #ddd;"><?php esc_html_e( 'Attempts', 'formscrm' ); ?></th>
										<th style="padding: 12px; text-align: center; border-bottom: 2px solid #ddd; width: 280px; min-width: 280px;"><?php esc_html_e( 'Actions', 'formscrm' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $logs as $log ) : ?>
										<tr data-log-id="<?php echo esc_attr( $log->id ); ?>" style="border-bottom: 1px solid #eee;">
											<td style="padding: 12px;">
												<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->error_date ) ) ); ?>
											</td>
											<td style="padding: 12px;">
												<strong><?php echo esc_html( ucfirst( $log->crm_type ) ); ?></strong>
											</td>
										<td style="padding: 12px;">
											<?php
											if ( $log->form_name ) {
												echo esc_html( $log->form_name );
											}
											if ( $log->form_type_title ) {
												echo '<br><small style="color: #666;">' . esc_html( $log->form_type_title ) . '</small>';
											}
											?>
										</td>
											<td style="padding: 12px;">
												<div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
													<?php echo esc_html( wp_trim_words( $log->error_message, 15 ) ); ?>
												</div>
											</td>
											<td style="padding: 12px;">
												<?php
												$status_class = 'failed' === $log->status ? 'error' : 'success';
												$status_text  = 'failed' === $log->status ? __( 'Failed', 'formscrm' ) : __( 'Success', 'formscrm' );
												?>
												<span class="fcrm-status fcrm-status-<?php echo esc_attr( $status_class ); ?>" style="
													padding: 4px 8px;
													border-radius: 3px;
													font-size: 12px;
													font-weight: 600;
													<?php echo 'failed' === $log->status ? 'background: #ffebee; color: #d32f2f;' : 'background: #e8f5e9; color: #2e7d32;'; ?>
												">
													<?php echo esc_html( $status_text ); ?>
												</span>
											</td>
											<td style="padding: 12px;">
												<?php echo esc_html( $log->resend_attempts ); ?>
											</td>
										<td style="padding: 12px; text-align: center; width: 280px; min-width: 280px;">
											<button 
												type="button" 
												class="fcrm-button fcrm-button-small fcrm-resend-btn"
												data-log-id="<?php echo esc_attr( $log->id ); ?>"
												style="margin-right: 5px; font-size: 12px; padding: 6px 12px;"
											>
												<?php esc_html_e( 'Resend', 'formscrm' ); ?>
											</button>
											<button 
												type="button" 
												class="fcrm-button fcrm-button-small fcrm-button-secondary fcrm-view-details-btn"
												data-log-id="<?php echo esc_attr( $log->id ); ?>"
												style="margin-right: 5px; font-size: 12px; padding: 6px 12px;"
											>
												<?php esc_html_e( 'Details', 'formscrm' ); ?>
											</button>
											<button 
												type="button" 
												class="fcrm-button fcrm-button-small fcrm-button-danger fcrm-delete-log-btn"
												data-log-id="<?php echo esc_attr( $log->id ); ?>"
												style="font-size: 12px; padding: 6px 12px;"
											>
												<?php esc_html_e( 'Delete', 'formscrm' ); ?>
											</button>
										</td>
										</tr>

										<!-- Details Row (Hidden by default) -->
										<tr class="fcrm-log-details" id="fcrm-details-<?php echo esc_attr( $log->id ); ?>" style="display: none;">
											<td colspan="7" style="padding: 20px; background: #f9f9f9;">
												<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
													<!-- Lead Data -->
													<div>
														<h4 style="margin-top: 0;"><?php esc_html_e( 'Lead Data', 'formscrm' ); ?></h4>
														<div style="background: white; padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto;">
															<?php
															$lead_data = json_decode( $log->lead_data, true );
															if ( $lead_data ) {
																echo '<table style="width: 100%;">';
																foreach ( $lead_data as $item ) {
																	if ( isset( $item['name'] ) && isset( $item['value'] ) ) {
																		echo '<tr>';
																		echo '<td style="padding: 5px; font-weight: 600;">' . esc_html( $item['name'] ) . ':</td>';
																		echo '<td style="padding: 5px;">' . esc_html( $item['value'] ) . '</td>';
																		echo '</tr>';
																	}
																}
																echo '</table>';
															}
															?>
														</div>
													</div>

													<!-- Technical Details -->
													<div>
														<h4 style="margin-top: 0;"><?php esc_html_e( 'Technical Details', 'formscrm' ); ?></h4>
														<div style="background: white; padding: 15px; border-radius: 5px;">
															<?php if ( $log->api_url ) : ?>
																<p><strong><?php esc_html_e( 'API URL:', 'formscrm' ); ?></strong><br>
																<code style="word-break: break-all; font-size: 11px;"><?php echo esc_html( $log->api_url ); ?></code></p>
															<?php endif; ?>

															<?php if ( $log->json_request ) : ?>
																<p><strong><?php esc_html_e( 'JSON Request:', 'formscrm' ); ?></strong><br>
																<code style="display: block; word-break: break-all; font-size: 11px; max-height: 150px; overflow-y: auto; background: #f5f5f5; padding: 10px; border-radius: 3px;">
																	<?php echo esc_html( $log->json_request ); ?>
																</code></p>
															<?php endif; ?>

															<?php if ( $log->last_resend_date ) : ?>
																<p><strong><?php esc_html_e( 'Last Resend:', 'formscrm' ); ?></strong><br>
																<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->last_resend_date ) ) ); ?></p>
															<?php endif; ?>
														</div>
													</div>

													<!-- Error Details -->
													<div style="grid-column: 1 / -1;">
														<h4 style="margin-top: 0;"><?php esc_html_e( 'Full Error Message', 'formscrm' ); ?></h4>
														<div style="background: #ffebee; padding: 15px; border-radius: 5px; border-left: 4px solid #d32f2f;">
															<?php echo esc_html( $log->error_message ); ?>
														</div>
													</div>
												</div>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>

						<!-- Pagination -->
						<?php if ( $total_pages > 1 ) : ?>
							<div class="fcrm-pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
								<?php
								$base_url = add_query_arg(
									array(
										'page'          => 'formscrm',
										'tab'           => 'error-log',
										'filter_status' => $status,
										'filter_crm'    => $crm_type,
									),
									admin_url( 'admin.php' )
								);

								// Previous button.
								if ( $page_num > 1 ) {
									printf(
										'<a href="%s" class="fcrm-button fcrm-button-secondary">« %s</a>',
										esc_url( add_query_arg( 'paged', $page_num - 1, $base_url ) ),
										esc_html__( 'Previous', 'formscrm' )
									);
								}

								// Page numbers.
								for ( $i = 1; $i <= $total_pages; $i++ ) {
									$class = $i === $page_num ? 'fcrm-button fcrm-button-primary' : 'fcrm-button fcrm-button-secondary';
									printf(
										'<a href="%s" class="%s">%d</a>',
										esc_url( add_query_arg( 'paged', $i, $base_url ) ),
										esc_attr( $class ),
										esc_html( $i )
									);
								}

								// Next button.
								if ( $page_num < $total_pages ) {
									printf(
										'<a href="%s" class="fcrm-button fcrm-button-secondary">%s »</a>',
										esc_url( add_query_arg( 'paged', $page_num + 1, $base_url ) ),
										esc_html__( 'Next', 'formscrm' )
									);
								}
								?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}
}

if ( is_admin() ) {
	new FORMSCRM_Error_Log_Page();
}
