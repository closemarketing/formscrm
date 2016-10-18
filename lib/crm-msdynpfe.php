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

include_once 'debug.php';

function msdynpfe_login($username, $password, $url) {
    include_once 'dynamicspfe/CrmAuth.php';
    include_once 'dynamicspfe/CrmExecuteSoap.php';
    include_once "dynamicspfe/CrmAuthenticationHeader.php";

    $crmAuth = new CrmAuth ();
    $authHeader = $crmAuth->GetHeaderOnPremise( $username, $password, $url ); //GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online

    debug_message($authHeader); //prints debug information

    if($authHeader == null ){
    echo '<div id="message" class="error below-h2">
            <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
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

function msdynpfe_listfields($username, $password, $url, $module){
    include_once 'dynamicspfe/CrmAuth.php';
    include_once 'dynamicspfe/CrmExecuteSoap.php';
    include_once "dynamicspfe/CrmAuthenticationHeader.php";
    $crmAuth = new CrmAuth ();

    $authHeader = $crmAuth->GetHeaderOnPremise( $username, $password, $url ); //GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online

    debug_message($authHeader); //prints debug information

    if($authHeader == null ){
    echo '<div id="message" class="error below-h2">
            <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
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
        $record['name']=$kvp->getElementsbyTagName("LogicalName")->item(0)->textContent;
        $requiredvar = $kvp->getElementsbyTagName("RequiredLevel")->item(0)->getElementsbyTagName("Value")->item(0)->textContent;
        if($requiredvar=='ApplicationRequired') $record['required']= true; else $record['required']= false;
        //$record['required']=$kvp->getElementsbyTagName("RequiredLevel")->item(0)->getElementsbyTagName("Value")->item(0)->textContent;
        $entityArray[] = $record;
    }
    }

    return $entityArray;
}

function msdynpfe_create_lead($username, $password, $url, $module, $mergevars) {
    include_once 'dynamicspfe/CrmAuth.php';
    include_once 'dynamicspfe/CrmExecuteSoap.php';
    include_once "dynamicspfe/CrmAuthenticationHeader.php";
    $crmAuth = new CrmAuth ();

    $authHeader = $crmAuth->GetHeaderOnPremise( $username, $password, $url ); //GetHeaderOnPremise - for IFD or OnPremise, GetHeaderOnline - Online

    debug_message($authHeader); //prints debug information

    if($authHeader == null ){
    echo '<div id="message" class="error below-h2">
            <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
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

    debug_message($xml);

    $createResult ="";

    if($response!=null && $response!=""){
        preg_match('/<CreateResult>(.*)<\/CreateResult>/', $response, $matches);
        if(isset($matches[1]) ) $createResult =  $matches[1];
    }

    return $createResult;
}
