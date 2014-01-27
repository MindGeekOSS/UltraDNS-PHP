<?php

    const CRLF = "\n\n";
    
    class UltraDNS
    {

        private static $mode = 'dev';
        private static $client;
        public static $user = '';
        public static $pass = '';
        private static $url = '';        
        private static $account_id = ''; 
        public static $debug = false;
	public static $test_mode = true;
	public static $format = false;
	public static $delim = ' ';
	
	public static $rrType = array(	'A' => 1, 'AAAA' => 28, 'ALL' => 0, 'ANY' => 255, 'CNAME' => 5, 'HINFO' => 13, 'MX' => 15, 'NAPTR' => 35,
					'NS' => 2, 'PTR' => 12, 'RP' => 17, 'SOA' => 6, 'SPF' => 99, 'SRV' => 33, 'TXT' => 16);
	
	
	public static function getRrType($type)
	{
		return isset(self::$rrType[strtoupper($type)]) ? self::$rrType[strtoupper($type)] : self::$rrType['ALL'];
	}
	
        public static function describe()
        {
            if(!self::$test_mode)
                self::$url = 'https://ultra-api.ultradns.com/UltraDNS_WS?wsdl';
            else          
                self::$url = 'https://testapi.ultradns.com/UltraDNS_WS/v01?wsdl';            
                             
            $client = new WSSoapClient(self::$url);
            return $client->__getFunctions();
        }
	
	
	public static function getAvailableCommands()
	{
		return array(
		        'list',
			'addforward',
			'getNameServers',
			'getZoneInfo',
			'getRegistrarForDomain',
			'getZonesOfAccount',
			'getResourceRecordsOfDNameByType',
			'getResourceRecordsOfZone',
			'getGeneralNotificationStatus',
			'getResourceRecordTemplate',
			'createPrimaryZone',
			'createResourceRecord',
			'addWebForward'
		);
		
	}
        
        
        private static function getBodyXml($type, $params)
        {
                
            switch($type){
                case 'getGeneralNotificationStatus':
                        $body = '<v01:'.$type.'/>';
                    break;
		    
		case 'getResourceRecordsOfDNameByType':
                        $body = '<v01:'.$type.'>
					<zoneName>'.$params['zoneName'].'.</zoneName>
					<hostName>'.$params['hostName'].'.</hostName>
					<rrType>'.$params['rrType'].'</rrType>
				</v01:'.$type.'>';
                    break;
		   
		case 'getResourceRecordsOfZone':
                        $body = '<v01:'.$type.'>
					<zoneName>'.$params['zoneName'].'.</zoneName>
					<rrType>'.$params['rrType'].'</rrType>
				</v01:'.$type.'>';
                    break;
                    
                case 'getZonesOfAccount':
                    $body = '<v01:'.$type.'>
                                <accountId>'.self::$account_id.'</accountId>
                                <zoneType>'.$params['zoneType'].'</zoneType>
                            </v01:'.$type.'>';
                    break;
                    
                case 'getNameServers':
                case 'getZoneInfo':
                case 'getRegistrarForDomain':
			$body = '
			<v01:'.$type.'>
				<zoneName>'.$params['zoneName'].'.</zoneName>
			</v01:'.$type.'>
			';
                    break;
		    
		case 'getResourceRecordTemplate':
			$body = '<v01:'.$type.'>
					<recordType>'.$params['rrType'].'</recordType>
				</v01:'.$type.'>';
			break;
		
		case 'createPrimaryZone':
			$body = '
			<v01:'.$type.'>
				<transactionID></transactionID>
				<accountId>'.self::$account_id.'</accountId>
				<zoneName>'.$params['zoneName'].'.</zoneName>
				<forceImport>'.(isset($params['forceImport']) && $params['forceImport'] == 'true' ? 'true' : 'false').'</forceImport> 
			</v01:'.$type.'>
			';
			break;
			
		case 'createResourceRecord':
			$ivals = array_slice($params, 4);
			$infoVals = array();
			$i=0;
			foreach($params as $k => $v){
				if($i < 4){
					$i++;
					continue;
				}
				$infoVals[] = "$k=\"$v\"";
				$i++;
			}
			$body = '<v01:'.$type.'>
					<transactionID></transactionID>
					<resourceRecord ZoneName="'.$params['zoneName'].'." Type="'.$params['rrType'].'" DName="'.$params['dName'].'." TTL="'.$params['ttl'].'">
						<sch:InfoValues '.implode(' ',$infoVals).'></sch:InfoValues>
					</resourceRecord>
				</v01:'.$type.'>';
			break;
			

		case 'addWebForward':

			$body = "<v01:addWebForward>
				<transactionID/>
				<requestTo>".$params['requestTo'].".</requestTo>
				<redirectTo>".$params['redirectTo']."</redirectTo>
				<forwardType>\"HTTP_".$params['forwardType']."_REDIRECT\"</forwardType>
				<advanced>true</advanced>
				<zoneName>".$params['zoneName'].".</zoneName>
			</v01:addWebForward>";


		     break;
                    
            }
            
            return $body;
            
        }
        
        
        private static function strip_default_ns( $xml = null, $ns_uri = '' ) {
            $ns_local = '';
            $ns_tag = '*';
            
            if ( empty($xml) ) return false;
            
            //remove document namespace
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            $dom->documentElement->removeAttributeNS($ns_uri, $ns_local);
            
            //strip element namespaces
            foreach ( $dom->getElementsByTagNameNS($ns_uri, $ns_tag) as $elem ) {
                $elem->removeAttributeNS($ns_uri, $ns_local);
            }

            return $dom->saveXML();
        }
                
        
        private static function getResult($type, $result, $as_return = false)
        {

		if($type == 'getNameServers'){
			$res = array(); 
			if($as_return)
				return $result;
			if(!isset($result['NameServer']['NameServerData']) || sizeof($result['NameServer']['NameServerData']) < 1)
				return $res;
			foreach($result['NameServer']['NameServerData'] as $ns){
			    foreach($ns['@attributes'] as $k => $v)
				$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim;
			    $res[] = "\n";
			}
		} elseif($type == 'getZoneInfo'){
			$res = array(); 
			if($as_return)
				return $result;
			if(!isset($result['UltraZone']['@attributes']) || sizeof($result['UltraZone']['@attributes']) < 1)
				return $res;
			foreach($result['UltraZone']['@attributes'] as $k => $v){
				$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim;
			}    
		} elseif($type == 'getZonesOfAccount'){
			$res = array();
		
			if($as_return)
				return $result;
			if(!isset($result['ZoneList']) || sizeof($result['ZoneList']) < 1)
				return $res;
			foreach($result['ZoneList']['UltraZone'] as $index => $fields){
				foreach($fields['@attributes'] as $k => $v)
						$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim;
				$res[] = CRLF;
			}    
		} elseif($type == 'getRegistrarForDomain'){
			$res = array(); 
			if($as_return)
				return $result;
			if(!isset($result['result']['ZoneInfoData']) || sizeof($result['result']['ZoneInfoData']) < 1)
				return $res;
			foreach($result['result']['ZoneInfoData'] as $ns){
				foreach($ns['@attributes'] as $k => $v)
					$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v";
				$res[] = "\n";
			}    
		} elseif($type == 'getResourceRecordsOfDNameByType'){
			$res = array(); 
			if($as_return)
				return $result;
				
			if(!isset($result['ResourceRecordList']) || sizeof($result['ResourceRecordList']) < 1)
				return $res;
				
			foreach($result['ResourceRecordList']['ResourceRecord']['@attributes'] as $k => $v){
				$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v";
			}    
		} elseif($type == 'getResourceRecordsOfZone'){
			$res = array(); 

			if($as_return)
				return $result;
			if(!isset($result['ResourceRecordList']) || sizeof($result['ResourceRecordList']) < 1)
				return $res;
				
			foreach($result['ResourceRecordList']['ResourceRecord'] as $elem){
				if(isset($elem['@attributes'])){
					foreach($elem['@attributes'] as $k => $v){
						$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim ;
					}
				}
				if(isset($elem['InfoValues']['@attributes'])){
					foreach($elem['InfoValues']['@attributes'] as $k => $v) {
						$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim ;
					} 	
				} elseif(isset($elem[0]['@attributes'])){
					foreach($elem[0]['@attributes'] as $k => $v) {
						$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim ;
					}
				}
				$res[] = "\n";
			}    
		} elseif($type == 'getResourceRecordTemplate'){
			$res = array(); 
			$res = $result;	
			
			if($as_return)
				return $res;

			if(!isset($result['ResourceRecordTemplate']['InfoTypes']['@attributes']) || sizeof($result['ResourceRecordTemplate']['InfoTypes']['@attributes']) < 1)
				return $res;
				
			foreach($result['ResourceRecordTemplate']['InfoTypes']['@attributes'] as $elem){
				foreach($elem['@attributes'] as $k => $v)
					$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim ;
				foreach($elem['InfoValues']['@attributes'] as $k => $v)
					$res[] = (self::$format) ? sprintf("%15s  %s\n", $k, $v) : "$k".self::$delim."$v".self::$delim ;
				$res[] = "\n";
			} 

		} elseif($type == 'createPrimaryZone'){
			$res = array(); 
			/** NEED TO COMPLETE **/
			print_r($result);

		} elseif($type == 'createResourceRecord'){
			$res = array(); 
			/** NEED TO COMPLETE **/
			print_r($result);
		} elseif($type == 'addWebForward'){
		  /** NEED TO COMPLETE **/
		  $res = array();
		  print_r($result);

		}	

		foreach($res as $ln){
			echo $ln;
            }

        }
        
        
        public static function doRequest($type, $params = array(), $as_return = false)
        {
		if(self::$test_mode)
			self::$url = 'http://testapi.ultradns.com/UltraDNS_WS/v01?wsdl';
		else {          
			self::$url = 'http://ultra-api.ultradns.com:8008/UltraDNS_WS?wsdl'; 	
		}
			     

		if($type == 'describe'){
			$client = new SoapClient(self::$url, array(   'trace' => 1, 
								'cache_wsdl' => WSDL_CACHE_NONE,
								'use' => SOAP_LITERAL,
								'style' => SOAP_DOCUMENT));

			return $client->__getFunctions();
		}


		$xml = '
			<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v01="http://webservice.api.ultra.neustar.com/v01/" xmlns:sch="http://schema.ultraservice.neustar.com/v01/">
			    <soapenv:Header>
				<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
				<wsse:UsernameToken wsu:Id="UsernameToken-16318950" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
				<wsse:Username>'.self::$user.'</wsse:Username>
				<wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">'.self::$pass.'</wsse:Password>
				</wsse:UsernameToken>
				</wsse:Security>
			    </soapenv:Header>
			    <soapenv:Body>
				'.self::getBodyXml($type, $params).'
			    </soapenv:Body>
			</soapenv:Envelope>
		';

		try{
			$soap_do = curl_init(); 
			curl_setopt($soap_do, CURLOPT_URL,            self::$url );   
			curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($soap_do, CURLOPT_TIMEOUT,        10); 
			curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
			curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);  
			curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false); 
			curl_setopt($soap_do, CURLOPT_POST,           true ); 
			curl_setopt($soap_do, CURLOPT_POSTFIELDS,    $xml); 
			curl_setopt($soap_do, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml; charset=utf-8', 'Content-Length: '.strlen($xml) )); 
			$raw_xml = curl_exec($soap_do);
			$info = curl_getinfo($soap_do);
			$err = curl_error($soap_do);         
			$result = str_replace(array('soap:', 'ns1:','ns2:'), '', $raw_xml);
			$result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)),true);
		} catch(Exception $e){
			return "ERROR: ".$e->message."\n"; 
		}


		if (UltraDNS::$debug == true){	
		   echo "\n--------------------------\nREQUEST BODY:\n------------------------\n\n" . $xml . "\n";
		   echo "\n--------------------------\nRESULT HEADERS:\n------------------------\n\n" . print_r($info) . "\n";
		   echo "\n--------------------------\nRESPONSE BODY:\n------------------------\n\n";
		   print_r($raw_xml) . "\n";    		
		   echo "\n--------------------------\nRESPONSE ARRAY:\n------------------------\n\n";
		   print_r($result) . "\n";     
		}

		if(isset($result['Body']['Fault'])){
			if(isset($result['Body']['Fault']['detail']['UltraWSException'])){
				$err = $result['Body']['Fault']['detail']['UltraWSException'];
				echo "\n\033[31m ERROR ".$err['errorCode']."\033[32m: ".$err['errorDescription']."\033[0m".CRLF;
			} elseif(isset($result['Body']['Fault']['faultstring'])) {
				$err = $result['Body']['Fault']['faultstring'];
				echo "\n\033[31m ERROR\033[0m : ".$result['Body']['Fault']['faultstring'].CRLF;
			}
		}
		return self::getResult($type, $result['Body'][$type.'Response'], $as_return);

        }
	
	
	public static function parseFlags($start, $finish)
	{
		$flags = $argv;
		foreach($flags as $f){
			
		}
	
	
	}

	public static function parseInfoFlags($start, $finish = -1)
	{
		global $argv;
		$res = array();
		for($i = 0; $i < sizeof($argv); $i++){
			if($i < $start){
				$i++;
				continue;
			}
			if($finish >= 0 && $i >= $finish)
				break;
			if(preg_match('/Info([0-9])Value:(.+)/i', $argv[$i], $matches)){
				$res['Info'.$matches[1].'Value'] = $matches[2];
			} 
		}

		return $res;
	}
        
    }
        
?>