<?php
<?php
/*  Copyright 2013-2015 David Perez (email: david at closemarketing.es)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


function wpcf7_crm_add_crm($args) {
	$cf7_crm_defaults = array();
	$cf7_crm = get_option( 'cf7_crm_'.$args->id(), $cf7_crm_defaults );
?>

<div class="metabox-holder">

	<div class="cme-main-fields">

		<p>
			<label for="wpcf7-crm-email"><?php echo esc_html( __( 'CRM Type:', 'wpcf7' ) ); ?></label><br />
            <select id="wpcf7-type" name="name" value="<?php echo (isset ( $cf7_crm['type'] ) ) ? esc_attr( $cf7_crm['type'] ) : ''; ?>">
                <option value="">--</option>
                <option value="vTiger">vTiger</option>
                <option value="SugarCRM">SugarCRM</option>
                <option value="SugarCRM7">SugarCRM7</option>
                <option value="SuiteCRM">SuiteCRM</option>
                <option value="VTECRM">VTE CRM</option>
                <option value="odoo8">Odoo 8</option>
                <option value="msdynamics">Microsoft Dynamics CRM</option>
                <option value="espocrm">ESPO CRM</option>
                <option value="zohocrm">ZOHO CRM</option>
                <option value="salesforce">Salesforce</option>
            </select>

		<p>
			<label for="wpcf7-crm-username"><?php echo esc_html( __( 'Username:', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-username" name="wpcf7-crm[username]" class="wide" size="70" placeholder="[CRM Username]" value="<?php echo (isset ( $cf7_crm['username'] ) ) ? esc_attr( $cf7_crm['username'] ) : ''; ?>" />
		</p>

		<p>
			<label for="wpcf7-crm-email"><?php echo esc_html( __( 'Subscriber Email:', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-email" name="wpcf7-crm[email]" class="wide" size="70" placeholder="[your-email]" value="<?php echo (isset ( $cf7_crm['email'] ) ) ? esc_attr( $cf7_crm['email'] ) : ''; ?>" />
		</p>


		<p>
			<label for="wpcf7-crm-name"><?php echo esc_html( __( 'Subscriber Name:', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-name" name="wpcf7-crm[name]" class="wide" size="70" placeholder="[your-name]" value="<?php echo (isset ($cf7_crm['name'] ) ) ? esc_attr( $cf7_crm['name'] ) : '' ; ?>" />
		</p>


		<p>
			<label for="wpcf7-crm-accept"><?php echo esc_html( __( 'Required Acceptance Field:', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-accept" name="wpcf7-crm[accept]" class="wide" size="70" placeholder="[opt-in]" value="<?php echo (isset ($cf7_crm['accept'] ) ) ? esc_attr( $cf7_crm['accept'] ) : '' ; ?>" />
		</p>


		<p>
			<label for="wpcf7-crm-api"><?php echo esc_html( __( 'Client API Key:', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-api" name="wpcf7-crm[api]" class="wide" size="70" placeholder="512a2673a8fc4e588499e82e2d43680d100a824e8ba55394" value="<?php echo (isset($cf7_crm['api']) ) ? esc_attr( $cf7_crm['api'] ) : ''; ?>" />
		</p>


		<p>
			<label for="wpcf7-crm-list"><?php echo esc_html( __( 'API Subscriber List ID:', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-list" name="wpcf7-crm[list]" class="wide" size="70" placeholder="aadc9ca0b08c83fbb714490354463186" value="<?php echo (isset( $cf7_crm['list']) ) ?  esc_attr( $cf7_crm['list']) : '' ; ?>" />
		</p>


		<p>
			<input type="checkbox" id="wpcf7-crm-resubscribeoption" name="wpcf7-crm[resubscribeoption]" value="1"<?php echo ( isset($cf7_crm['resubscribeoption']) ) ? ' checked="checked"' : ''; ?> />
			<label for="wpcf7-crm-resubscribeoption"><?php echo esc_html( __( 'Allow Users to Resubscribe after being Deleted or Unsubscribed? (checked = true)', 'wpcf7' ) ); ?></label>
		</p>


		<p>
			<input type="checkbox" id="wpcf7-crm-cf-active" name="wpcf7-crm[cfactive]" value="1"<?php echo ( isset($cf7_crm['cfactive']) ) ? ' checked="checked"' : ''; ?> />
			<label for="wpcf7-crm-cfactive"><?php echo esc_html( __( 'Use Custom Fields', 'wpcf7' ) ); ?></label>
		</p>

	</div>

	<div class="crm-custom-fields">

		<?php for($i=1;$i<=13;$i++){ ?>

		<div class="col-6">
				<label for="wpcf7-crm-CustomValue<?php echo $i; ?>"><?php echo esc_html( __( 'Contact Form Value '.$i.':', 'wpcf7' ) ); ?></label><br />
				<input type="text" id="wpcf7-crm-CustomValue<?php echo $i; ?>" name="wpcf7-crm[CustomValue<?php echo $i; ?>]" class="wide" size="70" placeholder="[your-example-value]" value="<?php echo (isset( $cf7_crm['CustomValue'.$i]) ) ?  esc_attr( $cf7_crm['CustomValue'.$i]) : '' ;  ?>" />
		</div>


		<div class="col-6">
			<label for="wpcf7-crm-CustomKey<?php echo $i; ?>"><?php echo esc_html( __( 'crm Custom Field Name '.$i.':', 'wpcf7' ) ); ?></label><br />
			<input type="text" id="wpcf7-crm-CustomKey<?php echo $i; ?>" name="wpcf7-crm[CustomKey<?php echo $i; ?>]" class="wide" size="70" placeholder="example-field" value="<?php echo (isset( $cf7_crm['CustomKey'.$i]) ) ?  esc_attr( $cf7_crm['CustomKey'.$i]) : '' ;  ?>" />
		</div>

		<?php } ?>

	</div>



</div>

<?php

}


function wpcf7_crm_save_crm($args) {

	update_option( 'cf7_crm_'.$args->id, $_POST['wpcf7-crm'] );

}
add_action( 'wpcf7_after_save', 'wpcf7_crm_save_crm' );



function show_cm_metabox ( $panels ) {

	$new_page = array(
		'cme-Extension' => array(
			'title' => __( 'CRM', 'contact-form-7' ),
			'callback' => 'wpcf7_crm_add_crm'
		)
	);

	$panels = array_merge($panels, $new_page);

	return $panels;

}
add_filter( 'wpcf7_editor_panels', 'show_cm_metabox' );



function wpcf7_crm_subscribe($obj) {

	$cf7_crm = get_option( 'cf7_crm_'.$obj->id() );
	$submission = WPCF7_Submission::get_instance();

	if( $cf7_crm )
	{
		$subscribe = false;

		$regex = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/';
		$callback = array( &$obj, 'cf7_crm_callback' );

		$email = cf7_crm_tag_replace( $regex, $cf7_crm['email'], $submission->get_posted_data() );
		$name = cf7_crm_tag_replace( $regex, $cf7_crm['name'], $submission->get_posted_data() );

		$lists = cf7_crm_tag_replace( $regex, $cf7_crm['list'], $submission->get_posted_data() );
		$listarr = explode(',',$lists);

		if( isset($cf7_crm['accept']) && strlen($cf7_crm['accept']) != 0 )
		{
			$accept = cf7_crm_tag_replace( $regex, $cf7_crm['accept'], $submission->get_posted_data() );
			if($accept != $cf7_crm['accept'])
			{
				if(strlen($accept) > 0)
					$subscribe = true;
			}
		}
		else
		{
			$subscribe = true;
		}

		for($i=1;$i<=20;$i++){

			if( isset($cf7_crm['CustomKey'.$i]) && isset($cf7_crm['CustomValue'.$i]) && strlen(trim($cf7_crm['CustomValue'.$i])) != 0 )
			{
				$CustomFields[] = array('Key'=>trim($cf7_crm['CustomKey'.$i]), 'Value'=>cf7_crm_tag_replace( $regex, trim($cf7_crm['CustomValue'.$i]), $submission->get_posted_data() ) );
			}

		}

		if( isset($cf7_crm['resubscribeoption']) && strlen($cf7_crm['resubscribeoption']) != 0 )
		{
			$ResubscribeOption = true;
		}
			else
		{
			$ResubscribeOption = false;
		}

		if($subscribe && $email != $cf7_crm['email'])
		{

			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'../api/csrest_subscribers.php');

			$wrap = new SPARTAN_CS_REST_Subscribers( trim($listarr[0]), $cf7_crm['api'] );
			foreach($listarr as $listid)
			{
				$wrap->set_list_id(trim($listid));
				$wrap->add(array(
					'EmailAddress' => $email,
					'Name' => $name,
					'CustomFields' => $CustomFields,
					'Resubscribe' => $ResubscribeOption
				));
			}

		}

	}
}
add_action( 'wpcf7_before_send_mail', 'wpcf7_crm_subscribe' );



function cf7_crm_tag_replace( $pattern, $subject, $posted_data, $html = false ) {
	if( preg_match($pattern,$subject,$matches) > 0)
	{

		if ( isset( $posted_data[$matches[1]] ) ) {
			$submitted = $posted_data[$matches[1]];

			if ( is_array( $submitted ) )
				$replaced = join( ', ', $submitted );
			else
				$replaced = $submitted;

			if ( $html ) {
				$replaced = strip_tags( $replaced );
				$replaced = wptexturize( $replaced );
			}

			$replaced = apply_filters( 'wpcf7_mail_tag_replaced', $replaced, $submitted );

			return stripslashes( $replaced );
		}

		if ( $special = apply_filters( 'wpcf7_special_mail_tags', '', $matches[1] ) )
			return $special;

		return $matches[0];
	}
	return $subject;
}



function cme_ext_author_form_class_attr( $class ) {

  $class .= ' cmonitor-ext-' . SPARTAN_CME_VERSION;
  return $class;

}
add_filter( 'wpcf7_form_class_attr', 'cme_ext_author_form_class_attr' );



function cme_ext_author_wpcf7($cme_author) {

	$author_pre = 'Contact form 7 CRM extension by ';
	$author_name = 'David Perez';
	$author_url = 'http://renzojohnson.com';
	$author_title = 'Renzo Johnson - Front end Developer - Full-stack Developer';

  $cme_author .= '<p class="wpcf7-display-none">';
  $cme_author .= $author_pre;
  $cme_author .= '<a href="'.$author_url.'" ';
  $cme_author .= 'title="'.$author_title.'" ';
  $cme_author .= 'alt="'.$author_title.'" ';
  $cme_author .= 'target="_blank">';
  $cme_author .= ''.$author_title.'';
  $cme_author .= '</a>';
  $cme_author .= '</p>'. "\n";

  return $cme_author;

}
add_filter('wpcf7_form_response_output', 'cme_ext_author_wpcf7', 10);