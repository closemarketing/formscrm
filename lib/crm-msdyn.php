<?php

////////////////////////////////
//////// MS DYNAMICS CRM ///////
  ////////////////////////////////

function msdyn_apiurl($url) {
			$pos = strpos($url, 'api');
			if ($pos == false) {
				$pos = strpos($url, '.');
				$url = substr_replace($url, '.api', $pos, 0);
			}
    $url = $url.'XRMServices/2011/Organization.svc';

			return $url;
}

function msdyn_login($username, $password, $url) {
    require_once "dynamics/LiveIDManager.php";
    require_once "dynamics/EntityUtils.php";

    $url = $this->msdyn_apiurl($url);

    if (WP_DEBUG==true) { print_r($url); }

    //Return true or false for logged in
    $liveIDManager = new LiveIDManager();

	    $securityData = $liveIDManager->authenticateWithLiveID($url, $username, $password);

    	if (WP_DEBUG==true) { print_r($liveIDManager); } //prints debug information

	    if($securityData!=null && isset($securityData)){
	        //echo ("\nKey Identifier:" . $securityData->getKeyIdentifier());
	        //echo ("\nSecurity Token 1:" . $securityData->getSecurityToken0());
	        //echo ("\nSecurity Token 2:" . $securityData->getSecurityToken1());
	        //echo "User Authentication : Succcess.<br>";
	        return true;
	    }else{
	        echo '<div id="message" class="error below-h2">
	                <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').' </strong></p></div>';
	        return false;
	    }
	    return false;
}

function msdyn_listfields($username, $password, $url, $module){
    include_once "dynamics/LiveIDManager.php";
    include_once "dynamics/EntityUtils.php";

    $url = $this->msdyn_apiurl($url);

   //Return true or false for logged in
    $liveIDManager = new LiveIDManager();

$securityData = $liveIDManager->authenticateWithLiveID($url, $username, $password);

if($securityData!=null && isset($securityData)){
}else{
    echo '<div id="message" class="error below-h2">
            <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
    return;
}

        $domainname = substr($url,8,-1);

        $pos = strpos($domainname, "/");

        $domainname = substr($domainname,0,$pos);

        $retriveRequest = EntityUtils::getCRMSoapHeader($url, $securityData) .
        '
              <s:Body>
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
                    </s:Body>
            </s:Envelope>
            ';
    $response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $url, $retriveRequest);

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

                    $required =$kvp->getElementsbyTagName("RequiredLevel")->item(0)->getElementsbyTagName("Value")->item(0)->textContent;
                    if($required == 'Recommended' || $required == 'ApplicationRequired')
                        $record['required']= true;
                    else
                        $record['required']= false;

                    $entityArray[] = $record;
            }
        }

    return $entityArray;
    }

function msdyn_create_lead($username, $password, $url, $module, $mergevars) {
    include_once "dynamics/LiveIDManager.php";
    include_once "dynamics/EntityUtils.php";

    $url = $this->msdyn_apiurl($url);
 //Return true or false for logged in
    $liveIDManager = new LiveIDManager();

	$securityData = $liveIDManager->authenticateWithLiveID($url, $username, $password);

	if($securityData!=null && isset($securityData)){
	}else{
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


  $domainname = substr($url,8,-1);
        $pos = strpos($domainname, "/");
        $domainname = substr($domainname,0,$pos);
        $entityCreateRequest = EntityUtils::getCreateCRMSoapHeader($url, $securityData).
        '
              <s:Body>
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
                </s:Body>
            </s:Envelope>
            ';

    $response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $url, $entityCreateRequest);

        $createResult ="";
        if($response!=null && $response!=""){
            preg_match('/<CreateResult>(.*)<\/CreateResult>/', $response, $matches);
            $createResult =  $matches[1];
        }

	debug_message($response);

    return $createResult;
}

////////////////////////////////
