<?php
/**
 * MS Dynamics connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.3.0
 */

include_once 'debug.php';

date_default_timezone_set('UTC');
Class CRMLIB_MSDYN {
   /**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
    function login( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

        include_once 'dynamics/CrmAuth.php';
        include_once 'dynamics/CrmExecuteSoap.php';
        include_once 'dynamics/CrmAuthenticationHeader.php';

        $password = htmlspecialchars($password, ENT_QUOTES, "UTF-8");
        $crmAuth = new CrmAuth ();
        $authHeader = $crmAuth->GetHeaderOnline( $username, $password, $url ); //GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online
        debug_message($authHeader); //prints debug information
        if($authHeader == null ){
        echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').' </strong></p></div>';
        return false;
        }

        $xml = "<s:Body>";
        $xml .= "<Execute xmlns=\"http://schemas.microsoft.com/xrm/2011/Contracts/Services\">";
        $xml .= "<request i:type=\"c:WhoAmIRequest\" xmlns:b=\"http://schemas.microsoft.com/xrm/2011/Contracts\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:c=\"http://schemas.microsoft.com/crm/2011/Contracts\">";
        $xml .= "<b:Parameters xmlns:d=\"http://schemas.datacontract.org/2004/07/System.Collections.Generic\"/>";
        $xml .= "<b:RequestId i:nil=\"true\"/>";
        $xml .= "<b:RequestName>WhoAmI</b:RequestName>";
        $xml .= "</request>";
        $xml .= "</Execute>";
        $xml .= "</s:Body>";

        $executeSoap = new CrmExecuteSoap ();
        $response = $executeSoap->ExecuteSOAPRequest ( $authHeader, $xml, $url, "Execute" );

        $responsedom = new DomDocument ();
        $responsedom->loadXML ( $response );

        $values = $responsedom->getElementsbyTagName ( "KeyValuePairOfstringanyType" );

        foreach ( $values as $value ) {
            if ($value->firstChild->textContent == "UserId") {
                return $value->lastChild->textContent;
            }
        }

        return false;
    }
    /**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return array           returns an array of mudules
	 */
	function list_modules( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
        $password = $settings['gf_crm_apipassword'];
        
    }    



   /**
	 * List Fields
	 */
	function list_fields( $settings ) {
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

        $password = htmlspecialchars($password, ENT_QUOTES, "UTF-8");
        $crmAuth = new CrmAuth ();

        $authHeader = $crmAuth->GetHeaderOnline( $username, $password, $url ); //GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online

        if($authHeader == null ){
            echo '<div id="message" class="error below-h2">
                    <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').' </strong></p></div>';
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
                                                    <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">'.$module.'</c:value>
                                            </b:KeyValuePairOfstringanyType>
                                    </b:Parameters>
                                    <b:RequestId i:nil="true"/><b:RequestName>RetrieveEntity</b:RequestName>
                            </request>
                    </Execute>
                    </s:Body>';

        $executeSoap = new CrmExecuteSoap ();
        $response = $executeSoap->ExecuteSOAPRequest ( $authHeader, $xml, $url, "Execute" );

        $entityArray = array();
        if($response!=null && $response!=""){
        $responsedom = new DomDocument();
        $responsedom->loadXML($response);
        $entities = $responsedom->getElementsbyTagName("AttributeMetadata");
        $record = array();
        //$kvptypes = $entities[0]->getElementsbyTagName("KeyValuePairOfstringanyType");

        foreach($entities as $kvp){
            if($kvp->getElementsbyTagName("DisplayName")->item(0)!=null&& $kvp->getElementsbyTagName("DisplayName")->item(0)->getElementsbyTagName("Label")->item(0)!=null)
                $record['label']=$kvp->getElementsbyTagName("DisplayName")->item(0)->getElementsbyTagName("Label")->item(0)->textContent;
            else
                continue;
            //$record['label']="";
            $name = $kvp->getElementsbyTagName("LogicalName")->item(0)->textContent;
            $record['name']= $name;
            $required = $kvp->getElementsbyTagName("RequiredLevel")->item(0)->getElementsbyTagName("Value")->item(0)->textContent;
            if($required=='None'||$name=='leadid') $record['required']= false; else $record['required']=true;
            $entityArray[] = $record;
        }
        }

        return $entityArray;
    }
   /**
	 * Create Entry
	 */
	function create_entry( $settings, $merge_vars ) {
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

        $password = htmlspecialchars($password, ENT_QUOTES, "UTF-8");
        $crmAuth = new CrmAuth ();

        $authHeader = $crmAuth->GetHeaderOnline( $username, $password, $url ); //GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online


        if($authHeader == null ){
            debug_email_lead('MSDynamics','Error: Unable to authenticate LiveId.',$merge_vars);
            return false;
        }

    $attributedata='';
    foreach($mergevars as $attribute){
        $attributedata=$attributedata.
        '<b:KeyValuePairOfstringanyType>
            <c:key>'.$attribute['name'].'</c:key>
            <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">'.$attribute['value'].'</c:value>
        </b:KeyValuePairOfstringanyType>';
    }
        $xml = '<s:Body>
                    <Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                        <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                            <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                                '.$attributedata.'
                            </b:Attributes>
                            <b:EntityState i:nil="true"/>
                            <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                            <b:Id>00000000-0000-0000-0000-000000000000</b:Id>
                            <b:LogicalName>'.$module.'</b:LogicalName>
                            <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                        </entity>
                    </Create>
                </s:Body>';
        $executeSoap = new CrmExecuteSoap();
        $response = $executeSoap->ExecuteSOAPRequest( $authHeader, $xml, $url, "Create");

        $createResult ="";
        debug_message($response);

        if($response!=null && $response!=""){
            preg_match('/<CreateResult>(.*)<\/CreateResult>/', $response, $matches);
            $createResult =  $matches[1];
        } else {
            debug_email_lead('MSDynamics','Error',$merge_vars);
        }

        return $createResult;
    }
}