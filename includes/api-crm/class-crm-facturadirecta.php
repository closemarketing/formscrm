<?php
/**
 * Factura Directa connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';
class CRMLIB_FACTURADIRECTA {
    /**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
	function login( $settings ) {
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


        if ($password) {
            $authkey = $password;
        } else {
            if(substr($url, -1) !='/') $url.='/'; //adds slash to url
            $url = $url.'api/login.xml';

            $param = "u=".$username."&p=".$password;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            $doc = new DomDocument();

            if(isset($result)) {
                $doc->loadXML($result);
            } else {
                echo '<div id="message" class="error below-h2">';
                echo __('Error. I could not access to Facturadirecta', 'gravityformscrm' );
                echo '</div>';
                return false;
            }
            curl_close($ch);

            $tokenId = $doc->getElementsByTagName("token")->item(0)->nodeValue;
            if(!empty($tokenId)){
                echo '<div id="message" class="updated below-h2"><p><strong>'.__('Logged correctly in', 'gravityformscrm').' Facturadirecta</strong></p></div>';
                $authkey = $tokenId;
            }
            else{
                $authkey = false;
            }
        }
        return $authkey;
        
      } else {
        return false;
      }
        
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
        
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        if(substr($url, -1) !='/') $url.='/'; //adds slash to url
        $url = $url."api/clients.xml?api_token=".$token;

        $param = $token.":x";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/xml'));
        //curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $param);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_PUT, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $p = xml_parser_create();
        xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($p, $result, $vals, $index);
        xml_parser_free($p);
        $level1tag="";
        $level2tag="";
        $level3tag="";
        $duedatecounter=0;

        debug_message($vals);
        if($vals[3]['value']=="FORBIDDEN") {
            echo '<div id="message" class="error below-h2">';
            echo __('Error. I could not access to Facturadirecta', 'gravityformscrm' );
            echo '</div>';
            return false;
        }

        foreach ($vals as $key=>$val) {
            //echo "\n".$val['tag']."\n";
            if($val['tag']=="client")
                continue;
            if($val['level']>=2){
                //echo $val['level'];
                if($val['type']=="open" && $val['level']==2){
                    $level1tag =$val['tag'].".";
                }
                else if ($val['type']=="close" && $val['level']==2){
                    $level1tag ="";
                }
                if($val['type']=="open" && $val['level']==3){
                    $level2tag =$val['tag'].".";
                }
                else if ($val['type']=="close" && $val['level']==3){
                    $level2tag ="";
                }
                if($val['type']=="open" && $val['level']==4){
                    $level3tag =$val['tag'].".";
                }
                else if ($val['type']=="close" && $val['level']==4){
                    $level3tag ="";
                }
            }
            $req=FALSE;
            if($val['level']=="2" &&$val['tag']=="name"){
                $req=TRUE;
            }
            if($val['type']=="complete"){
                $taglabel=str_replace(".", " ",$level1tag).str_replace(".", " ",$level2tag).str_replace(".", " ",$level3tag);
                $tagname=$level1tag.$level2tag.$level3tag;
                if($tagname == "billing.dueDates.dueDate."){
                    if($val['tag']=="delayInDays")
                        $duedatecounter=$duedatecounter+1;
                    $tagname=$level1tag.$level2tag.str_replace(".", $duedatecounter,$level3tag).".";
                    $taglabel=$taglabel.$duedatecounter." ";
                }
                if($tagname == "customAttributes.customAttribute."){
                    if($val['tag']=="label")
                        $fields[]=array(
                        'label' => $taglabel.$val['value'],
                        'name' =>  $tagname.$val['value'],
                        'required' => $req
                    );
                }
                else{
                    $fields[]=array(
                        'label' => $taglabel.$val['tag'],
                        'name' =>  $tagname.$val['tag'],
                        'required' => $req
                    );
                }
            }
        }
        return $fields;
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
        
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        if(substr($url, -1) !='/') $url.='/'; //adds slash to url
        $url = $url."api/clients.xml?api_token=".$token;
        $param = $token.":x";
        $xml = get_cleintxmlforcreate($mergevars);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $param);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        $doc = new DomDocument();
        $doc->loadXML($response);

        debug_message($doc->textContent);

        curl_close($ch);
        if(isset($doc->getElementsByTagName("id")->item(0)->nodeValue))
            $itemId = $doc->getElementsByTagName("id")->item(0)->nodeValue;
        $httpStatus = $doc->getElementsByTagName("httpStatus")->item(0)->nodeValue;

        //* error is true
        if(!isset($itemId) && ($httpStatus == '400') ) {
            debug_email_lead('FacturaDirecta',$doc->textContent,$mergevars);
            $returnValue = false;
        } else {
            if(!empty($httpStatus)&& !isset($itemId)){
                $returnValue = '0';
            }else{
                $returnValue = $itemId;
            }
        }
        return $returnValue;
    }
    /** 
	 * Helper functions
	 */
    function get_cleintxmlforcreate($mergevars){
        $xml  = "<?xml version='1.0' encoding='UTF-8'?>";
        $xml .= "<client>";
        // Obtain a list of columns
        foreach ($mergevars as $key => $row) {
            $akey[$key]  = $row['name'];
        }
        // Sort the data with volume descending, edition ascending
        // Add $data as the last parameter, to sort by the common key
        array_multisort($akey, SORT_ASC, $mergevars);
        //echo "<pre>";
        //print_r($mergevars);
        //echo "</pre>";
        $i=0;
        $count = count( $mergevars);
        $firstlevel="";
        $address="";
        $bank="";
        $billing="";
        $billingtax1="";
        $billingtax2="";
        $billingtax3="";
        $billingtax4="";
        $billingtax5="";
        $billingtax6="";
        $dueDate1="";
        $dueDate2="";
        $dueDate3="";
        $dueDate4="";
        for ( $i = 0; $i < $count; $i++ ){
            $field=$mergevars[$i]['name'];
            $fieldvalue=$mergevars[$i]['value'];
            $fieldvalues = explode(".",$field);
            $fieldLevel = count($fieldvalues);
            //if($fieldLevel==1)
            //    echo "<".$field."><![CDATA[".$fieldvalue."]]><".$field.">";
            if($fieldLevel>0){
                //echo "<owner><id><![CDATA[".$fieldvalue."]]><".$field.">";
                $xmlNode="";
                for($j = $fieldLevel-1; $j>= 0; $j--){
                    if($j == $fieldLevel-1){
                        if($fieldvalues[0]=="address"){
                            if( $fieldvalues[$j]!="address")
                                $address .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                        }
                        if($fieldvalues[0]=="customAttributes"){
                            if( $fieldvalues[$j]!="customAttributes" && $fieldvalues[$j]!="customAttribute")
                                $customAttributes .= "<customAttribute><label><![CDATA[".$fieldvalues[$j]."]]></label>";
                                $customAttributes .= "<value><![CDATA[".$fieldvalue."]]></value></customAttribute>";
                        }
                        elseif ($fieldvalues[0]=="billing"){
                            if( $fieldvalues[$j]!="billing"){

                                if($fieldLevel==2)
                                    $billing .= "<".$fieldvalues[1]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[1].">";
                                elseif ($fieldvalues[1]=="bank"){
                                    if( $fieldvalues[$j]!="bank")
                                        $bank .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                }
                                elseif (($field=="billing.tax1.name" && $fieldvalues[$j]="name")||($field=="billing.tax1.rate" && $fieldvalues[$j]="rate"))
                                    $billingtax1 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                elseif(($field=="billing.tax2.name" && $fieldvalues[$j]="name")||($field=="billing.tax2.rate" && $fieldvalues[$j]="rate"))
                                    $billingtax2 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                elseif(($field=="billing.tax3.name" && $fieldvalues[$j]="name")||($field=="billing.tax3.rate" && $fieldvalues[$j]="rate"))
                                    $billingtax3 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                elseif(($field=="billing.tax4.name" && $fieldvalues[$j]="name")||($field=="billing.tax4.rate" && $fieldvalues[$j]="rate"))
                                    $billingtax4 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                elseif(($field=="billing.tax5.name" && $fieldvalues[$j]="name")||($field=="billing.tax5.rate" && $fieldvalues[$j]="rate"))
                                    $billingtax5 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                elseif(($field=="billing.tax6.name" && $fieldvalues[$j]="name")||($field=="billing.tax6.rate" && $fieldvalues[$j]="rate"))
                                    $billingtax6 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                elseif ($fieldvalues[1]=="dueDates"){
                                    if( $fieldvalues[$j]!="dueDates"){
                                        if(($field=="billing.dueDates.dueDate1.delayInDays" && $fieldvalues[$j]="delayInDays")||($field=="billing.dueDates.dueDate1.rate" && $fieldvalues[$j]="rate"))
                                            $dueDate1 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                        if(($field=="billing.dueDates.dueDate2.delayInDays" && $fieldvalues[$j]="delayInDays")||($field=="billing.dueDates.dueDate2.rate" && $fieldvalues[$j]="rate"))
                                            $dueDate2 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                        if(($field=="billing.dueDates.dueDate3.delayInDays" && $fieldvalues[$j]="delayInDays")||($field=="billing.dueDates.dueDate3.rate" && $fieldvalues[$j]="rate"))
                                            $dueDate3 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                        if(($field=="billing.dueDates.dueDate4.delayInDays" && $fieldvalues[$j]="delayInDays")||($field=="billing.dueDates.dueDate4.rate" && $fieldvalues[$j]="rate"))
                                            $dueDate4 .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                                    }
                                }
                            }
                        }
                        else
                            $xmlNode .= "<".$fieldvalues[$j]."><![CDATA[".$fieldvalue."]]></".$fieldvalues[$j].">";
                    }
                    else {
                        if( $fieldvalues[$j]=="address" || $fieldvalues[0]=="billing" || $fieldvalues[0]=="customAttributes")
                            continue;
                        $xmlNode = "<".$fieldvalues[$j].">".$xmlNode."</".$fieldvalues[$j].">";
                    }
                }
                $xml .= $xmlNode;
            }
        }
        if($address!="")
            $xml .= "<address>".$address."</address>";
        if($bank!="")
            $billing .= "<bank>".$bank."</bank>";
        if($billingtax1!="")
            $billingtax1 = "<tax1>".$billingtax1."</tax1>";
        if($billingtax2!="")
            $billingtax2 = "<tax2>".$billingtax2."</tax2>";
        if($billingtax3!="")
            $billingtax3 = "<tax3>".$billingtax3."</tax3>";
        if($billingtax4!="")
            $billingtax4 = "<tax4>".$billingtax4."</tax4>";
        if($billingtax5!="")
            $billingtax5 = "<tax5>".$billingtax5."</tax5>";
        if($billingtax6!="")
            $billingtax6 = "<tax6>".$billingtax6."</tax6>";
        $billing.=$billingtax1.$billingtax2.$billingtax3.$billingtax4.$billingtax5.$billingtax6;
        if($dueDate1!="")
            $dueDate1 = "<dueDate>".$dueDate1."</dueDate>";
        if($dueDate2!="")
            $dueDate2 = "<dueDate>".$dueDate2."</dueDate>";
        if($dueDate3!="")
            $dueDate3 = "<dueDate>".$dueDate3."</dueDate>";
        if($dueDate4!="")
            $dueDate4 = "<dueDate>".$dueDate4."</dueDate>";
        $dueDates=$dueDate1.$dueDate2.$dueDate3.$dueDate4;
        if($dueDates!="")
            $billing .= "<dueDates>".$dueDates."</dueDates>";
        if($billing!="")
            $xml .= "<billing>".$billing."</billing>";

        if(isset($customAttributes)&& ($customAttributes!="") )
            $xml .= "<customAttributes>".$customAttributes."</customAttributes>";

        $xml .= "</client>";
        //echo $xml;
        return $xml;
    }
}