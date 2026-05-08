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
			if ( 'settings_page_formscrm' !== $hook ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['tab'] ) || 'error-log' !== $_GET['tab'] ) {
				return;
			}

			wp_enqueue_script(
				'formscrm-error-log',
				FORMSCRM_PLUGIN_URL . 'includes/admin/js/error-log.js',
				array(),
				FORMSCRM_VERSION,
				true
			);

			wp_localize_script(
				'formscrm-error-log',
				'formscrmErrorLog',
				array(
					'ajaxurl'                => admin_url( 'admin-ajax.php' ),
					'nonce'                  => wp_create_nonce( 'formscrm_error_log_nonce' ),
					'confirmClear'           => __( 'Are you sure you want to clear all error logs? This action cannot be undone.', 'formscrm' ),
					'confirmDelete'          => __( 'Are you sure you want to delete this log entry?', 'formscrm' ),
					'resending'              => __( 'Resending...', 'formscrm' ),
					'clearing'               => __( 'Clearing...', 'formscrm' ),
					'clearAll'               => __( 'Clear All Logs', 'formscrm' ),
					'viewDetails'            => __( 'Details', 'formscrm' ),
					'hideDetails'            => __( 'Hide', 'formscrm' ),
					'successText'            => __( 'Success', 'formscrm' ),
					'selectActionMessage'    => __( 'Please select an action', 'formscrm' ),
					'selectLogsMessage'      => __( 'Please select at least one log', 'formscrm' ),
					'confirmBulkDelete'      => __( 'Are you sure you want to delete %d selected logs? This action cannot be undone.', 'formscrm' ),
					'confirmBulkResend'      => __( 'Are you sure you want to resend %d selected logs?', 'formscrm' ),
					'bulkDeleteSuccess'      => __( 'Logs deleted successfully', 'formscrm' ),
					'bulkDeleteError'        => __( 'Failed to delete logs', 'formscrm' ),
					'bulkResendSuccess'      => __( 'Resend complete', 'formscrm' ),
					'bulkResendError'        => __( 'Failed to resend logs', 'formscrm' ),
					'bulkResendSuccessful'   => __( 'successful', 'formscrm' ),
					'bulkResendFailed'       => __( 'failed', 'formscrm' ),
					'bulkResendDetails'      => __( 'Details', 'formscrm' ),
					'ajaxError'              => __( 'AJAX Error', 'formscrm' ),
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
					<div class="fcrm-error-log-filters">
						<form method="get" class="fcrm-error-log-filters-form">
							<input type="hidden" name="page" value="formscrm">
							<input type="hidden" name="tab" value="error-log">

							<select name="filter_status" class="fcrm-form-input fcrm-filter-select">
								<option value=""><?php esc_html_e( 'All Status', 'formscrm' ); ?></option>
								<option value="failed" <?php selected( $status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'formscrm' ); ?></option>
								<option value="success" <?php selected( $status, 'success' ); ?>><?php esc_html_e( 'Success', 'formscrm' ); ?></option>
							</select>

							<select name="filter_crm" class="fcrm-form-input fcrm-filter-select">
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

					<!-- Bulk Actions Section -->
					<div class="fcrm-bulk-actions-bar">
						<select id="fcrm-bulk-action-select" class="fcrm-form-input">
							<option value=""><?php esc_html_e( 'Bulk Actions', 'formscrm' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete Selected', 'formscrm' ); ?></option>
							<option value="resend"><?php esc_html_e( 'Resend Selected', 'formscrm' ); ?></option>
						</select>
						<button type="button" class="fcrm-button fcrm-button-secondary" id="fcrm-bulk-action-btn">
							<?php esc_html_e( 'Apply', 'formscrm' ); ?>
						</button>
						<div id="fcrm-bulk-action-status" class="fcrm-bulk-action-status" style="display: none;">
							<div class="fcrm-progress-bar">
								<div id="fcrm-progress-fill" class="fcrm-progress-fill"></div>
							</div>
							<p id="fcrm-status-text" class="fcrm-status-text"></p>
						</div>
					</div>

					<!-- Stats Summary -->
					<div class="fcrm-stats-summary">
						<strong><?php esc_html_e( 'Total Entries:', 'formscrm' ); ?></strong> <?php echo esc_html( $total_items ); ?>
					</div>

					<!-- Error Log Table -->
					<?php if ( empty( $logs ) ) : ?>
						<div class="fcrm-notice fcrm-notice-info">
							<p><?php esc_html_e( 'No error logs found.', 'formscrm' ); ?></p>
						</div>
					<?php else : ?>
						<div class="fcrm-error-log-table-wrapper">
							<table class="fcrm-table">
								<thead>
									<tr>
										<th class="fcrm-table-checkbox">
											<input type="checkbox" id="fcrm-select-all-logs" class="fcrm-select-all-checkbox">
										</th>
										<th><?php esc_html_e( 'ID', 'formscrm' ); ?></th>
										<th><?php esc_html_e( 'Date', 'formscrm' ); ?></th>
										<th><?php esc_html_e( 'CRM', 'formscrm' ); ?></th>
										<th><?php esc_html_e( 'Form', 'formscrm' ); ?></th>
										<th><?php esc_html_e( 'Error', 'formscrm' ); ?></th>
										<th><?php esc_html_e( 'Status', 'formscrm' ); ?></th>
										<th><?php esc_html_e( 'Attempts', 'formscrm' ); ?></th>
										<th class="fcrm-table-actions"><?php esc_html_e( 'Actions', 'formscrm' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $logs as $log ) : ?>
										<tr data-log-id="<?php echo esc_attr( $log->id ); ?>">
											<td class="fcrm-table-checkbox">
												<input type="checkbox" class="fcrm-log-checkbox" value="<?php echo esc_attr( $log->id ); ?>">
											</td>
											<td>
												<strong><?php echo esc_html( $log->id ); ?></strong>
											</td>
											<td>
												<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->error_date ) ) ); ?>
											</td>
											<td>
												<strong><?php echo esc_html( ucfirst( $log->crm_type ) ); ?></strong>
											</td>
											<td>
												<?php
												if ( $log->form_name ) {
													echo esc_html( $log->form_name );
												}
												if ( $log->form_type_title ) {
													echo '<br><small class="fcrm-form-subtitle">' . esc_html( $log->form_type_title ) . '</small>';
												}
												?>
											</td>
											<td>
												<div class="fcrm-error-message">
													<?php echo esc_html( wp_trim_words( $log->error_message, 15 ) ); ?>
												</div>
											</td>
											<td>
												<?php
												$status_class = 'failed' === $log->status ? 'error' : 'success';
												$status_text  = 'failed' === $log->status ? __( 'Failed', 'formscrm' ) : __( 'Success', 'formscrm' );
												?>
												<span class="fcrm-status fcrm-status-<?php echo esc_attr( $status_class ); ?>">
													<?php echo esc_html( $status_text ); ?>
												</span>
											</td>
											<td>
												<?php
												echo esc_html( $log->resend_attempts ) . '/3';

												// Show next retry info if scheduled and not at max attempts.
												if ( 'failed' === $log->status && $log->resend_attempts < 3 ) {
													global $formscrm_error_log;
													$next_retry = $formscrm_error_log->get_next_retry_time( $log->id );

													if ( $next_retry ) {
														$time_diff = human_time_diff( time(), $next_retry );
														echo '<br><small style="color: #666;">';
														/* translators: %s: Time until next retry */
														printf( esc_html__( 'Next: in %s', 'formscrm' ), esc_html( $time_diff ) );
														echo '</small>';
													}
												}
												?>
											</td>
											<td class="fcrm-table-actions">
												<button 
													type="button" 
													class="fcrm-button fcrm-button-small fcrm-resend-btn"
													data-log-id="<?php echo esc_attr( $log->id ); ?>"
												>
													<?php esc_html_e( 'Resend', 'formscrm' ); ?>
												</button>
												<button 
													type="button" 
													class="fcrm-button fcrm-button-small fcrm-button-secondary fcrm-view-details-btn"
													data-log-id="<?php echo esc_attr( $log->id ); ?>"
												>
													<?php esc_html_e( 'Details', 'formscrm' ); ?>
												</button>
												<button 
													type="button" 
													class="fcrm-button fcrm-button-small fcrm-button-danger fcrm-delete-log-btn"
													data-log-id="<?php echo esc_attr( $log->id ); ?>"
												>
													<?php esc_html_e( 'Delete', 'formscrm' ); ?>
												</button>
											</td>
										</tr>

										<!-- Details Row (Hidden by default) -->
										<tr class="fcrm-log-details" id="fcrm-details-<?php echo esc_attr( $log->id ); ?>">
											<td colspan="8" class="fcrm-details-cell">
												<div class="fcrm-details-grid">
													<!-- Lead Data -->
													<div class="fcrm-details-section">
														<h4><?php esc_html_e( 'Lead Data', 'formscrm' ); ?></h4>
														<div class="fcrm-details-box fcrm-details-box-scroll">
															<?php
															$lead_data = json_decode( $log->lead_data, true );
															if ( $lead_data ) {
																echo '<table class="fcrm-lead-data-table">';
																foreach ( $lead_data as $item ) {
																	if ( isset( $item['name'] ) && isset( $item['value'] ) ) {
																		echo '<tr>';
																		echo '<td class="fcrm-lead-data-name">' . esc_html( $item['name'] ) . ':</td>';
																		echo '<td class="fcrm-lead-data-value">' . esc_html( $item['value'] ) . '</td>';
																		echo '</tr>';
																	}
																}
																echo '</table>';
															}
															?>
														</div>
													</div>

													<!-- Technical Details -->
													<div class="fcrm-details-section">
														<h4><?php esc_html_e( 'Technical Details', 'formscrm' ); ?></h4>
														<div class="fcrm-details-box">
															<?php if ( $log->api_url ) : ?>
																<p><strong><?php esc_html_e( 'API URL:', 'formscrm' ); ?></strong><br>
																<code class="fcrm-code"><?php echo esc_html( $log->api_url ); ?></code></p>
															<?php endif; ?>

															<?php if ( $log->json_request ) : ?>
																<p><strong><?php esc_html_e( 'JSON Request:', 'formscrm' ); ?></strong><br>
																<code class="fcrm-code fcrm-code-block">
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
													<div class="fcrm-details-section fcrm-details-full">
														<h4><?php esc_html_e( 'Full Error Message', 'formscrm' ); ?></h4>
														<div class="fcrm-error-box">
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
							<div class="fcrm-pagination">
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
