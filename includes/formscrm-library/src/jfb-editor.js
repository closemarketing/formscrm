/**
 * FormsCRM JetFormBuilder editor component.
 *
 * Registers the "FormsCRM" action in the JetFormBuilder block editor action panel.
 * Mirrors the GetResponse / MailChimp pattern:
 *   - Global credentials live in the JFB Settings → FormsCRM tab (tab handler).
 *   - Per-form: CRM type + credentials override (when not using global), module
 *     selection (loaded from a REST endpoint), and field mapping.
 */

const { createElement: el, Fragment } = window.React;
const { __ } = window.wp.i18n;
const { apiFetch } = window.wp;
const { Flex, SelectControl, TextControl, CheckboxControl, Spinner } = window.wp.components;
const {
	RowControl,
	WideLine,
	Label,
	RequiredLabel,
	RowControlEndStyle,
	ControlWithErrorStyle,
	StyledSelectControl,
	ClearBaseControlStyle,
} = window.jfb.components;
const { registerAction, FieldsMapField } = window.jfb.actions;
const { useFields } = window.jfb.blocksToActions;

// ─── Data provided by wp_localize_script in class-jetformbuilder.php ──────────
const { restUrl, nonce, choices: crmChoices, virtualFields: extraFields = [] } = window.formsCrmJfb || {};

/**
 * Map the raw formscrm_get_choices() array into WP SelectControl options.
 */
const crmTypeOptions = [
	{ value: '', label: __( '— Select CRM —', 'formscrm' ) },
	...( crmChoices || [] ).map( ( { value, label } ) => ( { value, label } ) ),
];

// CRM types that require each credential field (mirrors PHP dependency helpers).
const needsUrl         = [ 'bitrix24', 'espo_crm', 'facturadirecta', 'msdyn', 'mspfe', 'odoo', 'ofiweb', 'sugarcrm6', 'sugarcrm7', 'suitecrm_3_1', 'suitecrm_4_1', 'vtiger_6' ];
const needsUsername    = [ 'bitrix24', 'espo_crm', 'facturadirecta', 'msdyn', 'mspfe', 'odoo', 'salesforce', 'solve360', 'sugarcrm6', 'sugarcrm7', 'suitecrm_3_1', 'suitecrm_4_1', 'vtiger_6', 'zoho' ];
const needsPassword    = [ 'bitrix24', 'espo_crm', 'facturadirecta', 'msdyn', 'mspfe', 'sugarcrm6', 'sugarcrm7', 'suitecrm_3_1', 'suitecrm_4_1', 'zoho' ];
const needsApiPassword = [ 'hubspot', 'solve360', 'vtiger_6', 'odoo', 'holded', 'clientify', 'brevo', 'acumbamail', 'mailerlite', 'reach' ];
const needsApiSales    = [ 'salesforce' ];
const needsOdooDB      = [ 'odoo' ];

/**
 * POST to our REST endpoint and return parsed JSON.
 */
async function fetchFromRest( path, body ) {
	const response = await fetch( restUrl + path, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': nonce,
		},
		body: JSON.stringify( body ),
	} );
	if ( ! response.ok ) {
		throw new Error( await response.text() );
	}
	return response.json();
}

/**
 * Credential fields shown only when NOT using global settings.
 */
function CredentialFields( { settings, onChangeSettingObj } ) {
	const type = settings.fc_crm_type || '';

	return el(
		Fragment,
		null,
		el( RowControl, null,
			el( Label, null, __( 'CRM Type', 'formscrm' ) ),
			el( StyledSelectControl, {
				value: type,
				options: crmTypeOptions,
				onChange: ( v ) => onChangeSettingObj( { fc_crm_type: v, fc_crm_module: '', fields_map: {} } ),
			} )
		),

		type && needsUrl.includes( type ) && el( RowControl, null,
			el( Label, null, __( 'URL', 'formscrm' ) ),
			el( TextControl, {
				value: settings.fc_crm_url || '',
				onChange: ( v ) => onChangeSettingObj( { fc_crm_url: v } ),
				placeholder: 'https://your-crm.example.com',
			} )
		),

		type && needsUsername.includes( type ) && el( RowControl, null,
			el( Label, null, __( 'Username', 'formscrm' ) ),
			el( TextControl, {
				value: settings.fc_crm_username || '',
				onChange: ( v ) => onChangeSettingObj( { fc_crm_username: v } ),
			} )
		),

		type && needsPassword.includes( type ) && el( RowControl, null,
			el( Label, null, __( 'Password', 'formscrm' ) ),
			el( TextControl, {
				type: 'password',
				value: settings.fc_crm_password || '',
				onChange: ( v ) => onChangeSettingObj( { fc_crm_password: v } ),
			} )
		),

		type && needsApiPassword.includes( type ) && el( RowControl, null,
			el( Label, null, __( 'API Key / Password', 'formscrm' ) ),
			el( TextControl, {
				type: 'password',
				value: settings.fc_crm_apipassword || '',
				onChange: ( v ) => onChangeSettingObj( { fc_crm_apipassword: v } ),
			} )
		),

		type && needsApiSales.includes( type ) && el( RowControl, null,
			el( Label, null, __( 'API Sales', 'formscrm' ) ),
			el( TextControl, {
				value: settings.fc_crm_apisales || '',
				onChange: ( v ) => onChangeSettingObj( { fc_crm_apisales: v } ),
			} )
		),

		type && needsOdooDB.includes( type ) && el( RowControl, null,
			el( Label, null, __( 'Odoo DB', 'formscrm' ) ),
			el( TextControl, {
				value: settings.fc_crm_odoodb || '',
				onChange: ( v ) => onChangeSettingObj( { fc_crm_odoodb: v } ),
			} )
		),
	);
}

/**
 * Module selector — fetches modules from the REST endpoint when credentials change.
 */
function ModuleSelector( { settings, onChangeSettingObj } ) {
	const { useState, useEffect } = window.React;
	const [ modules, setModules ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( '' );

	const type   = settings.fc_crm_type || '';
	const module = settings.fc_crm_module || '';

	useEffect( () => {
		if ( ! type ) {
			setModules( [] );
			return;
		}
		setLoading( true );
		setError( '' );
		fetchFromRest( '/modules', settings )
			.then( ( data ) => {
				setModules( data );
				if ( data.length && ! module ) {
					onChangeSettingObj( { fc_crm_module: data[ 0 ].value } );
				}
			} )
			.catch( ( err ) => setError( err.message ) )
			.finally( () => setLoading( false ) );
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ type, settings.use_global, settings.fc_crm_url, settings.fc_crm_username, settings.fc_crm_password, settings.fc_crm_apipassword ] );

	if ( ! type ) {
		return null;
	}

	const options = [
		{ value: '', label: __( '— Select Module —', 'formscrm' ) },
		...modules.map( ( { value, label } ) => ( { value, label } ) ),
	];

	return el( RowControl, null,
		el( Label, null, __( 'CRM Module', 'formscrm' ) ),
		loading
			? el( Spinner )
			: error
				? el( 'p', { style: { color: 'red', margin: 0 } }, error )
				: el( StyledSelectControl, {
					value: module,
					options,
					onChange: ( v ) => onChangeSettingObj( { fc_crm_module: v, fields_map: {} } ),
				} )
	);
}

/**
 * Fields mapping table — shows CRM fields on the left, form fields on the right.
 */
function FieldsMap( { settings, getMapField, setMapField } ) {
	const { useState, useEffect } = window.React;
	const [ crmFields, setCrmFields ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( '' );

	const formFields = [
		...useFields( { withInner: false, placeholder: '--' } ),
		...extraFields,
	];

	const type   = settings.fc_crm_type || '';
	const module = settings.fc_crm_module || '';

	useEffect( () => {
		if ( ! type || ! module ) {
			setCrmFields( [] );
			return;
		}
		setLoading( true );
		setError( '' );
		fetchFromRest( '/fields', settings )
			.then( ( data ) => setCrmFields( data ) )
			.catch( ( err ) => setError( err.message ) )
			.finally( () => setLoading( false ) );
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ type, module, settings.use_global, settings.fc_crm_url, settings.fc_crm_username, settings.fc_crm_password, settings.fc_crm_apipassword ] );

	if ( ! type || ! module ) {
		return null;
	}

	if ( loading ) {
		return el( Spinner );
	}

	if ( error ) {
		return el( 'p', { style: { color: 'red' } }, error );
	}

	if ( ! crmFields.length ) {
		return el( 'p', null, __( 'No CRM fields found. Check your credentials and module.', 'formscrm' ) );
	}

	return el(
		RowControl,
		{ createId: false },
		el( Label, null, __( 'Fields Map', 'formscrm' ) ),
		el(
			Flex,
			{ direction: 'column', gap: 4 },
			...crmFields.map( ( field ) =>
				el( FieldsMapField, {
					key: field.name,
					tag: field.name,
					label: field.label,
					isRequired: field.required,
					formFields,
					value: getMapField( { name: field.name } ),
					onChange: ( v ) => setMapField( { nameField: field.name, value: v } ),
				} )
			)
		)
	);
}

/**
 * Main edit component rendered in the JFB action panel.
 */
function FormsCrmEdit( { settings, onChangeSettingObj, getMapField, setMapField } ) {
	const useGlobal = settings.use_global || false;

	// When using global settings the server resolves credentials from the saved
	// option. We pass use_global:true so the REST endpoints know to do that.
	// We set a sentinel fc_crm_type so the module selector renders — the actual
	// type will be resolved server-side.
	const effectiveSettings = useGlobal
		? { ...settings, fc_crm_type: settings.fc_crm_type || '__global__', use_global: true }
		: settings;

	return el(
		Flex,
		{ direction: 'column' },

		// "Use Global Settings" toggle + link to JFB settings page.
		el(
			CheckboxControl,
			{
				className: ClearBaseControlStyle,
				checked: useGlobal,
				onChange: ( v ) => onChangeSettingObj( { use_global: Boolean( v ) } ),
				label: el(
					Fragment,
					null,
					__( 'Use', 'formscrm' ) + ' ',
					el(
						'a',
						{ href: ( window.JetFormEditorData?.global_settings_url || '#' ) + '#formscrm-tab' },
						__( 'Global Settings', 'formscrm' )
					)
				),
			}
		),

		el( WideLine, null ),

		// Credential fields (hidden when using global settings).
		! useGlobal && el( CredentialFields, { settings, onChangeSettingObj } ),
		! useGlobal && el( WideLine, null ),

		// Module selector — uses effective settings (global or per-form).
		el( ModuleSelector, { settings: effectiveSettings, onChangeSettingObj } ),
		( settings.fc_crm_module ) && el( WideLine, null ),

		// Field mapping.
		el( FieldsMap, { settings: effectiveSettings, getMapField, setMapField } ),
	);
}

// ─── Icon ────────────────────────────────────────────────────────────────────
const FormsCrmIcon = el( window.wp.components.Dashicon, { icon: 'admin-plugins' } );

// ─── Validators ──────────────────────────────────────────────────────────────
const validators = [
	( { settings } ) =>
		! settings.use_global && ! settings.fc_crm_type && {
			type: 'empty',
			property: 'fc_crm_type',
		},
	( { settings } ) => {
		// When using global, check that global settings actually has a CRM type.
		if ( settings.use_global ) {
			const global = ( window.JetFBPageConfig && window.JetFBPageConfig[ 'formscrm-tab' ] ) || {};
			return ! global.fc_crm_type && {
				type: 'empty',
				property: 'fc_crm_type',
			};
		}
		return ! settings.fc_crm_module && {
			type: 'empty',
			property: 'fc_crm_module',
		};
	},
];

// ─── Register ────────────────────────────────────────────────────────────────
registerAction( {
	type: 'formscrm',
	label: __( 'FormsCRM', 'formscrm' ),
	edit: FormsCrmEdit,
	icon: FormsCrmIcon,
	category: 'communication',
	validators,
} );
