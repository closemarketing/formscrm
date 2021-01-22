<?php
/**
 * MS DYNAMICS CRM On Premise PFE connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

require_once 'debug.php';
/**
 * Library Connector for MSPFE CRM
 */
class CRMLIB_MSPFE {
	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings  settings from Gravity Forms options.
	 * @return false or id      returns false if cannot login and string if gets token.
	 */
	public function login( $settings ) {

    $url = null;
    if( isset( $settings['gf_crm_url'] ) ) {
      $url = check_url_crm($settings['gf_crm_url']);
    }
    $username = null;
    if( isset( $settings['gf_crm_username'] ) ) {
      $username = $settings['gf_crm_username'];
    }
    $password = null;
    if( isset( $settings['gf_crm_apipassword'] ) ) {
      $password = $settings['gf_crm_apipassword'];
    }
    if( $url && $username && $password ) {

      include_once 'dynamics/CrmAuth.php';
      include_once 'dynamics/CrmExecuteSoap.php';
      include_once 'dynamics/CrmAuthenticationHeader.php';

      // GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online.
      $crm_auth    = new CrmAuth();
      $auth_header = $crm_auth->GetHeaderOnPremise( $username, $password, $url );

      debug_message( $auth_header );

      if ( null === $auth_header ) {
        echo '<div id="message" class="error below-h2">
                  <p><strong>' . __( 'Unable to authenticate LiveId.', 'gravityformscrm' ) . ': </strong></p></div>';
        return false;
      }

      $xml  = '<s:Body>';
      $xml .= '<Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">';
      $xml .= '<request i:type="c:WhoAmIRequest" xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance" xmlns:c="http://schemas.microsoft.com/crm/2011/Contracts">';
      $xml .= '<b:Parameters xmlns:d="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>';
      $xml .= '<b:RequestId i:nil="true"/>';
      $xml .= '<b:RequestName>WhoAmI</b:RequestName>';
      $xml .= '</request>';
      $xml .= '</Execute>';
      $xml .= '</s:Body>';

      $execute_soap = new CrmExecuteSoap();
      $response     = $execute_soap->ExecuteSOAPRequest( $auth_header, $xml, $url, 'Execute' );

      $responsedom = new DomDocument();
      $responsedom->loadXML( $response );

      $values = $responsedom->getElementsbyTagName( 'KeyValuePairOfstringanyType' );

      foreach ( $values as $value ) {
        if ( 'UserId' === $value->firstChild->textContent ) {
          return $value->lastChild->textContent;
        }
      }
      return false;
      
    } else {
      return false;
      
    }
	}
	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings      settings from Gravity Forms options.
	 * @return array $list_modules  returns an array of mudules.
	 */
	public function list_modules( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		return $list_modules;
	}

	/**
	 * List Fields
	 *
	 * @param array $settings     settings from Gravity Forms options.
	 * @return array $list_fields retunrs array of certain module
	 */
	public function list_fields( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		include_once 'dynamics/CrmAuth.php';
		include_once 'dynamics/CrmExecuteSoap.php';
		include_once 'dynamics/CrmAuthenticationHeader.php';
		$crm_auth = new CrmAuth();

		// GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online.
		$auth_header = $crm_auth->GetHeaderOnPremise( $username, $password, $url );

		debug_message( $auth_header );

		if ( null === $auth_header ) {
			echo '<div id="message" class="error below-h2">
                <p><strong>' . __( 'Unable to authenticate LiveId.', 'gravityformscrm' ) . ': </strong></p></div>';
			return false;
		}

		$xml = '<s:Body>
                    <Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                            <request i:type="b:RetrieveEntityRequest" xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                                    <b:Parameters xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                                            <b:KeyValuePairOfstringanyType>
                                                    <c:key>EntityFilters</c:key>
                                                    <c:value i:type="b:EntityFilters" xmlns:b="http://schemas.microsoft.com/xrm/2011/Metadata">Attributes</c:value>
                                            </b:KeyValuePairOfstringanyType>
                                            <b:KeyValuePairOfstringanyType>
                                                    <c:key>MetadataId</c:key>
                                                    <c:value i:type="d:guid" xmlns:d="http://schemas.microsoft.com/2003/10/Serialization/">00000000-0000-0000-0000-000000000000</c:value>
                                            </b:KeyValuePairOfstringanyType>
                                            <b:KeyValuePairOfstringanyType>
                                                    <c:key>RetrieveAsIfPublished</c:key>
                                                    <c:value i:type="d:boolean" xmlns:d="http://www.w3.org/2001/XMLSchema">true</c:value>
                                            </b:KeyValuePairOfstringanyType>
                                            <b:KeyValuePairOfstringanyType>
                                                    <c:key>LogicalName</c:key>
                                                    <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">' . $module . '</c:value>
                                            </b:KeyValuePairOfstringanyType>
                                    </b:Parameters>
                                    <b:RequestId i:nil="true"/><b:RequestName>RetrieveEntity</b:RequestName>
                            </request>
                    </Execute>
                    </s:Body>';

		$execute_soap = new CrmExecuteSoap();
		$response    = $execute_soap->ExecuteSOAPRequest( $auth_header, $xml, $url, 'Execute' );

		$entity_array = array();
		if ( null !== $response && '' !== $response ) {
			$responsedom = new DomDocument();
			$responsedom->loadXML( $response );
			$entities = $responsedom->getElementsbyTagName( 'AttributeMetadata' );
			$record   = array();
			// $kvptypes = $entities[0]->getElementsbyTagName("KeyValuePairOfstringanyType");
			foreach ( $entities as $kvp ) {
				if ( $kvp->getElementsbyTagName( 'DisplayName' )->item( 0 ) != null && $kvp->getElementsbyTagName( 'DisplayName' )->item( 0 )->getElementsbyTagName( 'Label' )->item( 0 ) != null ) {
					$record['label'] = $kvp->getElementsbyTagName( 'DisplayName' )->item( 0 )->getElementsbyTagName( 'Label' )->item( 0 )->textContent;
				} else {
					continue;
				}
				// $record['label']="";
				$record['name'] = $kvp->getElementsbyTagName( 'LogicalName' )->item( 0 )->textContent;
				$requiredvar    = $kvp->getElementsbyTagName( 'RequiredLevel' )->item( 0 )->getElementsbyTagName( 'Value' )->item( 0 )->textContent;
				if ( 'ApplicationRequired' === $requiredvar ) {
					$record['required'] = true;
				} else {
					$record['required'] = false;
				}
				// $record['required']=$kvp->getElementsbyTagName("RequiredLevel")->item(0)->getElementsbyTagName("Value")->item(0)->textContent;
				$entity_array[] = $record;
			}
		}

		return $entity_array;
	}

	/**
	 * Creates an entry of the module
	 *
	 * @param array $settings     settings from Gravity Forms options.
	 * @param array $merge_vars   array of values to create an entry.
	 * @return string id or false of the entry created.
	 */
	public function create_entry( $settings, $merge_vars ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		include_once 'dynamics/CrmAuth.php';
		include_once 'dynamics/CrmExecuteSoap.php';
		include_once 'dynamics/CrmAuthenticationHeader.php';
		$crm_auth = new CrmAuth();
		// GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online.
		$auth_header = $crm_auth->GetHeaderOnPremise( $username, $password, $url );

		debug_message( $auth_header );

		if ( null === $auth_header ) {
			debug_email_lead( 'MSDynamics', 'Error: Unable to authenticate LiveId.', $merge_vars );
			return false;
		}

		$attributedata = '';
		foreach ( $mergevars as $attribute ) {
			$attributedata = $attributedata .
			'<b:KeyValuePairOfstringanyType>
            <c:key>' . $attribute['name'] . '</c:key>
            <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">' . $attribute['value'] . '</c:value>
        </b:KeyValuePairOfstringanyType>';
		}
		$xml         = '<s:Body>
                    <Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                        <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                            <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                                ' . $attributedata . '
                            </b:Attributes>
                            <b:EntityState i:nil="true"/>
                            <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                            <b:Id>00000000-0000-0000-0000-000000000000</b:Id>
                            <b:LogicalName>' . $module . '</b:LogicalName>
                            <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                        </entity>
                    </Create>
                </s:Body>';
		$execute_soap = new CrmExecuteSoap();
		$response    = $execute_soap->ExecuteSOAPRequest( $auth_header, $xml, $url, 'Create' );

		debug_message( $xml );

		$create_result = '';
		if ( null !== $response && '' !== $response ) {
			preg_match( '/<CreateResult>(.*)<\/CreateResult>/', $response, $matches );
			if ( isset( $matches[1] ) ) {
				$create_result = $matches[1];
			}
		} else {
			debug_email_lead( 'MSDynamics PFE', 'Error', $merge_vars );
		}

		return $create_result;
	}
}
