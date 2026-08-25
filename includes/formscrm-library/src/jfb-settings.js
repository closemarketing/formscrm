/**
 * FormsCRM — JetFormBuilder global settings tab.
 *
 * Rendered inside JetForm > Settings as a Vue 2 component (same framework as
 * the other JFB settings tabs: ActiveCampaign, MailChimp, etc.).
 *
 * The component receives saved credentials via the `incoming` prop (populated
 * from FORMSCRM_JFB_Tab_Handler::on_load()) and returns updated values via
 * getRequestOnSave() which the JFB settings page POSTs to our AJAX handler.
 */

( () => {
	const { __ } = wp.i18n;
	const { addFilter } = wp.hooks;

	// CRM types that require specific credential fields — mirrors the PHP helpers.
	const needsUrl         = [ 'bitrix24', 'espo_crm', 'facturadirecta', 'msdyn', 'mspfe', 'odoo', 'ofiweb', 'sugarcrm6', 'sugarcrm7', 'suitecrm_3_1', 'suitecrm_4_1', 'vtiger_6' ];
	const needsUsername    = [ 'bitrix24', 'espo_crm', 'facturadirecta', 'msdyn', 'mspfe', 'odoo', 'salesforce', 'solve360', 'sugarcrm6', 'sugarcrm7', 'suitecrm_3_1', 'suitecrm_4_1', 'vtiger_6', 'zoho' ];
	const needsPassword    = [ 'bitrix24', 'espo_crm', 'facturadirecta', 'msdyn', 'mspfe', 'sugarcrm6', 'sugarcrm7', 'suitecrm_3_1', 'suitecrm_4_1', 'zoho' ];
	const needsApiPassword = [ 'hubspot', 'solve360', 'vtiger_6', 'odoo', 'holded', 'clientify', 'brevo', 'acumbamail', 'mailerlite', 'holded_pro', 'reach' ];
	const needsApiSales    = [ 'salesforce' ];
	const needsOdooDB      = [ 'odoo' ];

	/**
	 * Build the vue render function template string in a safe way —
	 * we write plain JS so it can be loaded directly without a build step.
	 */
	const FormsCrmSettingsComponent = {
		name: 'formscrm-tab',

		props: {
			incoming: {
				type: Object,
				default: () => ( {} ),
			},
		},

		data() {
			const choices = ( window.formsCrmJfb && window.formsCrmJfb.choices ) || [];
			return {
				crmChoices: choices,
				fc_crm_type:        '',
				fc_crm_url:         '',
				fc_crm_username:    '',
				fc_crm_password:    '',
				fc_crm_apipassword: '',
				fc_crm_apisales:    '',
				fc_crm_odoodb:      '',
			};
		},

		created() {
			this.fc_crm_type        = this.incoming.fc_crm_type        || '';
			this.fc_crm_url         = this.incoming.fc_crm_url         || '';
			this.fc_crm_username    = this.incoming.fc_crm_username    || '';
			this.fc_crm_password    = this.incoming.fc_crm_password    || '';
			this.fc_crm_apipassword = this.incoming.fc_crm_apipassword || '';
			this.fc_crm_apisales    = this.incoming.fc_crm_apisales    || '';
			this.fc_crm_odoodb      = this.incoming.fc_crm_odoodb      || '';
		},

		computed: {
			showUrl()         { return needsUrl.includes( this.fc_crm_type ); },
			showUsername()    { return needsUsername.includes( this.fc_crm_type ); },
			showPassword()    { return needsPassword.includes( this.fc_crm_type ); },
			showApiPassword() { return needsApiPassword.includes( this.fc_crm_type ); },
			showApiSales()    { return needsApiSales.includes( this.fc_crm_type ); },
			showOdooDB()      { return needsOdooDB.includes( this.fc_crm_type ); },

			crmOptions() {
				return [
					{ value: '', label: __( '— Select CRM —', 'formscrm' ) },
					...this.crmChoices,
				];
			},
		},

		methods: {
			getRequestOnSave() {
				return {
					data: {
						fc_crm_type:        this.fc_crm_type,
						fc_crm_url:         this.fc_crm_url,
						fc_crm_username:    this.fc_crm_username,
						fc_crm_password:    this.fc_crm_password,
						fc_crm_apipassword: this.fc_crm_apipassword,
						fc_crm_apisales:    this.fc_crm_apisales,
						fc_crm_odoodb:      this.fc_crm_odoodb,
					},
				};
			},
		},

		// Vue 2 render function — avoids needing a template compiler.
		render( h ) {
			const self = this;

			const fields = [];

			// CRM Type select.
			fields.push(
				h( 'cx-vui-select', {
					attrs: {
						label:          __( 'CRM Type', 'formscrm' ),
						'wrapper-css':  [ 'equalwidth' ],
						size:           'fullwidth',
						'options-list': self.crmOptions,
						value:          self.fc_crm_type,
					},
					on: {
						input: ( v ) => { self.fc_crm_type = v; },
					},
				} )
			);

			if ( self.showUrl ) {
				fields.push(
					h( 'cx-vui-input', {
						attrs: {
							label:        __( 'URL', 'formscrm' ),
							'wrapper-css': [ 'equalwidth' ],
							size:         'fullwidth',
						},
						model: {
							value:      self.fc_crm_url,
							callback:   ( v ) => { self.fc_crm_url = v; },
							expression: 'fc_crm_url',
						},
					} )
				);
			}

			if ( self.showUsername ) {
				fields.push(
					h( 'cx-vui-input', {
						attrs: {
							label:        __( 'Username', 'formscrm' ),
							'wrapper-css': [ 'equalwidth' ],
							size:         'fullwidth',
						},
						model: {
							value:      self.fc_crm_username,
							callback:   ( v ) => { self.fc_crm_username = v; },
							expression: 'fc_crm_username',
						},
					} )
				);
			}

			if ( self.showPassword ) {
				fields.push(
					h( 'cx-vui-input', {
						attrs: {
							label:         __( 'Password', 'formscrm' ),
							'wrapper-css':  [ 'equalwidth' ],
							size:          'fullwidth',
							type:          'password',
						},
						model: {
							value:      self.fc_crm_password,
							callback:   ( v ) => { self.fc_crm_password = v; },
							expression: 'fc_crm_password',
						},
					} )
				);
			}

			if ( self.showApiPassword ) {
				fields.push(
					h( 'cx-vui-input', {
						attrs: {
							label:         __( 'API Key / Password', 'formscrm' ),
							'wrapper-css':  [ 'equalwidth' ],
							size:          'fullwidth',
							type:          'password',
						},
						model: {
							value:      self.fc_crm_apipassword,
							callback:   ( v ) => { self.fc_crm_apipassword = v; },
							expression: 'fc_crm_apipassword',
						},
					} )
				);
			}

			if ( self.showApiSales ) {
				fields.push(
					h( 'cx-vui-input', {
						attrs: {
							label:        __( 'API Sales', 'formscrm' ),
							'wrapper-css': [ 'equalwidth' ],
							size:         'fullwidth',
						},
						model: {
							value:      self.fc_crm_apisales,
							callback:   ( v ) => { self.fc_crm_apisales = v; },
							expression: 'fc_crm_apisales',
						},
					} )
				);
			}

			if ( self.showOdooDB ) {
				fields.push(
					h( 'cx-vui-input', {
						attrs: {
							label:        __( 'Odoo DB', 'formscrm' ),
							'wrapper-css': [ 'equalwidth' ],
							size:         'fullwidth',
						},
						model: {
							value:      self.fc_crm_odoodb,
							callback:   ( v ) => { self.fc_crm_odoodb = v; },
							expression: 'fc_crm_odoodb',
						},
					} )
				);
			}

			return h( 'div', fields );
		},
	};

	const tabDefinition = {
		title:     __( 'FormsCRM', 'formscrm' ),
		component: FormsCrmSettingsComponent,
	};

	addFilter(
		'jet.fb.register.settings-page.tabs',
		'formscrm/jfb-settings-tab',
		( tabs ) => {
			tabs.push( tabDefinition );
			return tabs;
		}
	);
} )();
