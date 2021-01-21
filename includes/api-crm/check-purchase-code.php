<?php

$result = false; // have we got a valid purchase code?
 // check if they've bought this item id.
$code = 'f8502554-6c7e-41f1-b519-7a242c47f9cd';



    function verifyPurchase($purchaseCode) {
        $userName = 'closemarketing'; // authors username
        $apiKey = 'z6o6fpzsplrzt9izk78ajs8zf1fliay3'; // api key from my account area
        $itemId = 10521695;

        // Open cURL channel
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, "http://marketplace.envato.com/api/edge/$userName/$apiKey/verify-purchase:$purchaseCode.json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ENVATO-PURCHASE-VERIFY'); //api requires any user agent to be set

        // Decode returned JSON
        $result = json_decode( curl_exec($ch) , true );

        //check if purchase code is correct
        if ( !empty($result['verify-purchase']['item_id']) && $result['verify-purchase']['item_id'] ) {
            //if no item name is given - any valid purchase code will work
            if ( !$itemId ) return true;
            //else - also check if purchased item is given item to check
            echo 'Comprado';
            return $result['verify-purchase']['item_id'] == $itemId;
        }

        //invalid purchase code
        return false;

    }

echo verifyPurchase('f8502554-6c7e-41f1-b519-7a242c47f9cd');