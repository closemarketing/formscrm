<?php
    class CrmExecuteSoap {
        /**
         * Executes the SOAP request.
         * @return String SOAP response.
         * @param CrmAuthenticationHeader $authHeader
         *        	The authenticated CrmAuthenticationHeader.
         * @param String $request
         *        	The SOAP request body.
         * @param String $url
         *        	The CRM URL.
         */
        public function ExecuteSOAPRequest($authHeader, $request, $url, $action) {
            $url = rtrim ( $url, "/" );
            $xml = "<s:Envelope xmlns:s=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:a=\"http://www.w3.org/2005/08/addressing\">";
            $xml .= str_replace('Execute', $action, $authHeader->Header);
            $xml .= $request;
            $xml .= "</s:Envelope>";
    
            $headers = array (
                    "POST " . "/Organization.svc" . " HTTP/1.1",
                    "Host: " . str_replace("http://","", str_replace ( "https://", "", $url )),
                    'Connection: Keep-Alive',
                    "Content-type: application/soap+xml; charset=UTF-8",
                    "Content-length: " . strlen ( $xml ) 
            );
    
            $cURL = curl_init ();
            curl_setopt ( $cURL, CURLOPT_URL, $url . "/XRMServices/2011/Organization.svc" );
            curl_setopt ( $cURL, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt ( $cURL, CURLOPT_TIMEOUT, 60 );
            curl_setopt ( $cURL, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt ( $cURL, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
            curl_setopt ( $cURL, CURLOPT_HTTPHEADER, $headers );
            curl_setopt ( $cURL, CURLOPT_POST, 1 );
            curl_setopt ( $cURL, CURLOPT_POSTFIELDS, $xml );
    
            $response = curl_exec ( $cURL );
            curl_close ( $cURL );
    
            return $response;
        }
    }
