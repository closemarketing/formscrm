<?php
/**
 * UTM URL Builder admin page
 *
 * @package    WordPress
 * @author     CloseTechnology
 * @copyright  2026 CloseTechnology
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * UTM URL Builder admin page.
 */
class FormsCRM_UTM_Builder {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Registers the submenu page under Settings > FormsCRM.
	 *
	 * @return void
	 */
	public function add_page() {
		add_submenu_page(
			'options-general.php',
			__( 'UTM Builder', 'formscrm' ),
			__( 'UTM Builder', 'formscrm' ),
			'manage_options',
			'formscrm-utm-builder',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the UTM Builder page.
	 *
	 * @return void
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'UTM URL Builder', 'formscrm' ); ?></h1>
			<p><?php esc_html_e( 'Build campaign URLs with UTM parameters to track traffic sources in FormsCRM.', 'formscrm' ); ?></p>

			<div style="max-width:700px;">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="fcrm-utm-url"><?php esc_html_e( 'URL', 'formscrm' ); ?> <span style="color:red;">*</span></label></th>
						<td><input type="url" id="fcrm-utm-url" class="regular-text" placeholder="https://example.com/landing-page" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="fcrm-utm-source"><?php esc_html_e( 'Campaign Source', 'formscrm' ); ?> <span style="color:red;">*</span></label></th>
						<td>
							<input type="text" id="fcrm-utm-source" class="regular-text" placeholder="google, newsletter, facebook…" />
							<p class="description"><?php esc_html_e( 'The referrer: google, newsletter, etc.', 'formscrm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fcrm-utm-medium"><?php esc_html_e( 'Campaign Medium', 'formscrm' ); ?></label></th>
						<td>
							<input type="text" id="fcrm-utm-medium" class="regular-text" placeholder="cpc, email, social…" />
							<p class="description"><?php esc_html_e( 'Marketing medium: cpc, email, social, etc.', 'formscrm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fcrm-utm-campaign"><?php esc_html_e( 'Campaign Name', 'formscrm' ); ?></label></th>
						<td>
							<input type="text" id="fcrm-utm-campaign" class="regular-text" placeholder="spring_sale, promo_2026…" />
							<p class="description"><?php esc_html_e( 'The specific campaign name.', 'formscrm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fcrm-utm-term"><?php esc_html_e( 'Campaign Term', 'formscrm' ); ?></label></th>
						<td>
							<input type="text" id="fcrm-utm-term" class="regular-text" placeholder="running+shoes…" />
							<p class="description"><?php esc_html_e( 'Paid keywords (optional).', 'formscrm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="fcrm-utm-content"><?php esc_html_e( 'Campaign Content', 'formscrm' ); ?></label></th>
						<td>
							<input type="text" id="fcrm-utm-content" class="regular-text" placeholder="banner_top, text_link…" />
							<p class="description"><?php esc_html_e( 'Used for A/B testing or to differentiate ads (optional).', 'formscrm' ); ?></p>
						</td>
					</tr>
				</table>

				<p>
					<button type="button" id="fcrm-utm-generate" class="button button-primary">
						<?php esc_html_e( 'Generate URL', 'formscrm' ); ?>
					</button>
				</p>

				<div id="fcrm-utm-result" style="display:none;margin-top:16px;">
					<label for="fcrm-utm-output"><strong><?php esc_html_e( 'Generated URL', 'formscrm' ); ?></strong></label>
					<div style="display:flex;gap:8px;margin-top:6px;">
						<input type="text" id="fcrm-utm-output" class="large-text" readonly />
						<button type="button" id="fcrm-utm-copy" class="button">
							<?php esc_html_e( 'Copy', 'formscrm' ); ?>
						</button>
					</div>
					<p id="fcrm-utm-copied" style="display:none;color:#46b450;margin-top:4px;">
						<?php esc_html_e( 'Copied to clipboard!', 'formscrm' ); ?>
					</p>
				</div>
			</div>
		</div>

		<script>
		( function () {
			document.getElementById( 'fcrm-utm-generate' ).addEventListener( 'click', function () {
				var base = document.getElementById( 'fcrm-utm-url' ).value.trim();
				if ( ! base ) {
					alert( '<?php echo esc_js( __( 'Please enter a URL.', 'formscrm' ) ); ?>' );
					return;
				}

				var params = {};
				var fields = {
					utm_source:   document.getElementById( 'fcrm-utm-source' ).value.trim(),
					utm_medium:   document.getElementById( 'fcrm-utm-medium' ).value.trim(),
					utm_campaign: document.getElementById( 'fcrm-utm-campaign' ).value.trim(),
					utm_term:     document.getElementById( 'fcrm-utm-term' ).value.trim(),
					utm_content:  document.getElementById( 'fcrm-utm-content' ).value.trim(),
				};

				Object.keys( fields ).forEach( function ( key ) {
					if ( fields[ key ] ) {
						params[ key ] = fields[ key ];
					}
				} );

				var separator = base.indexOf( '?' ) === -1 ? '?' : '&';
				var query = Object.keys( params ).map( function ( k ) {
					return encodeURIComponent( k ) + '=' + encodeURIComponent( params[ k ] );
				} ).join( '&' );

				var result = query ? base + separator + query : base;

				document.getElementById( 'fcrm-utm-output' ).value = result;
				document.getElementById( 'fcrm-utm-result' ).style.display = 'block';
				document.getElementById( 'fcrm-utm-copied' ).style.display = 'none';
			} );

			document.getElementById( 'fcrm-utm-copy' ).addEventListener( 'click', function () {
				var output = document.getElementById( 'fcrm-utm-output' );
				output.select();
				navigator.clipboard.writeText( output.value ).then( function () {
					document.getElementById( 'fcrm-utm-copied' ).style.display = 'block';
				} );
			} );
		}() );
		</script>
		<?php
	}
}

new FormsCRM_UTM_Builder();
