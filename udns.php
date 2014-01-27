#!/usr/bin/php
<?php

	include('UltraDNS.php');
	include('config.php');

	UltraDNS::$user = UDNS_USER;
	UltraDNS::$pass = UDNS_PASS;
	UltraDNS::$format = false;	// Formats the code to be properly displayed in the terminal
	UltraDNS::$debug = DEBUG;  	// When debug mode set to false, the Production UltraDNS API is used
	UltraDNS::$test_mode = TEST_MODE;
	
	if(UltraDNS::$debug)
		echo CRLF."*** RUNNING ON TEST API ***".CRLF;
	


	if(preg_match("/[0-9a-z]+\.[a-z]+/i", $argv[1], $matches)){

	   echo "\nResults of query on {$argv[1]}:\n\n";
           UltraDNS::doRequest('getZoneInfo', array('zoneName' => $argv[1]));

	}  elseif($argv[1] == 'list' && isset($argv[2]) && isset($argv[3])){

          UltraDNS::doRequest('getResourceRecordsOfZone', array('zoneName' => $argv[2], 'rrType' => UltraDNS::getRrType($argv[3])) );

        } elseif($argv[1] == 'list' && isset($argv[2])){

	  UltraDNS::doRequest('getResourceRecordsOfZone', array('zoneName' => $argv[2], 'rrType' => UltraDNS::getRrType('ALL')) );

	} elseif($argv[1] == 'list') {

	  UltraDNS::doRequest('getZonesOfAccount', array('zoneType' => 'A') );

	} elseif($argv[1] == 'create') {
	  
	  if(!isset($argv[2]) || !isset($argv[3]) || !is_bool($argv[3])){
                echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[forceImport]\033[0m".CRLF;
          } else {
                UltraDNS::doRequest('createPrimaryZone', array('zoneName' => $argv[2], 'forceImport' => $argv[3]) );
          }
	
	} elseif($argv[1] == 'addforward') {

	  if(!isset($argv[2]) || !isset($argv[3]) || !isset($argv[4]) ){
                echo CRLF."\033[36mSample Usage:\033[0m {$argv[1]} \033[31m[requestTo]\033[0m \033[32m[redirectTo]\033[0m \033[32m[forwardType]\033[0m \033[32m[zoneName]\033[0m".CRLF;
          } else {
                UltraDNS::doRequest('addWebForward', array('requestTo' => $argv[2], 'redirectTo' => $argv[3], 'forwardType' => $argv[4], 'zoneName' => $argv[5]) );
          }
	  

	} elseif($argv[1] == 'resource') {

	  /********************** SAME AS createResourceRecord ***************/

	  if(!isset($argv[2])){
                        echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[rrType]\033[0m "
                        ."\033[32m[dName]\033[0m \033[32m[ttl]\033[0m "
                        ."\033[32m[info1Value:value]\033[0m ... \033[32m[info8Value:value]\033[0m"
                        .CRLF;
                        die("\n");
                } else {
                        $call_params = array(
                                'zoneName' => $argv[2], 'rrType' => UltraDNS::getRrType($argv[3]), 'dName' => $argv[4], 'ttl' => $argv[5]
                        );

                        // Determine the necessary parameters
                        $res = UltraDNS::doRequest('getResourceRecordTemplate', array('rrType' => $argv[3]), true);
                        $infoTypesRequired = array();
                        if(!isset($res['ResourceRecordTemplate']['InfoTypes']) || !sizeof($res['ResourceRecordTemplate']['InfoTypes']['@attributes'])){
                                echo "Error: Invalid resource record type!".CRLF;
                               die("\n");
                        } else {
                                foreach($res['ResourceRecordTemplate']['InfoTypes']['@attributes'] as $infoName => $value) {
                                        $infoTypesRequired[str_replace('Type','Value',$infoName)] = $value;
                                }
                        }

                        // Now match against what options were passed
                        $infoTypesSupplied = UltraDNS::parseInfoFlags(6);
                        $missing = array_diff_key($infoTypesRequired, $infoTypesSupplied);

                        if(sizeof($missing)){
                                $resp = "\n\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[rrType]\033[0m "
                                ."\033[32m[dName]\033[0m \033[32m[ttl]\033[0m";
                                foreach($infoTypesRequired as $k => $v){
                                        $resp .= " \033[32m".str_replace('Type','Value', $k).":[".$v."]\033[0m";
                                }

                                echo $resp."\n";
                                die("\n");
                        } else {
                                foreach($infoTypesSupplied as $k => $v){
                                        $call_params[$k] = $v;
                                }
                        }

                        UltraDNS::doRequest('createResourceRecord', $call_params);
                }


	

	} elseif($argv[1] == 'getGeneralNotificationStatus') {
	  
	  UltraDNS::doRequest($argv[1]);

	} elseif($argv[1] == 'describe') {

	  UltraDNS::doRequest($argv[1]);

	} elseif($argv[1] == 'getNameServers' || $argv[1] == 'getRegistrarForDomain' || $argv[1] == 'getZoneInfo') {
	
		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m ".CRLF;
		     
		} else {
			echo "\nResults of query on {$argv[2]}:\n\n";
			UltraDNS::doRequest($argv[1], array('zoneName' => $argv[2]));
		     
		}



	} elseif($argv[1] == 'getZonesOfAccount') {
	
		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneType]\033[0m".CRLF;
		     
		} else {
			echo "\nResults of query for zones in account {$argv[2]}:\n\n";
			UltraDNS::doRequest($argv[1], array('zoneType' => (isset($argv[2]) ? $argv[2] : 'ALL') ) );
		     
		}


	} elseif($argv[1] == 'getResourceRecordsOfDNameByType') {

		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[hostName]\033[0m \033[32m[rrType]\033[0m".CRLF;
		     
		} else {
			UltraDNS::doRequest($argv[1], array('zoneName' => $argv[2], 'hostName' => $argv[3], 'rrType' => UltraDNS::getRrType($argv[4])) );
		     
		}


	} elseif($argv[1] == 'getResourceRecordsOfZone') {

		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[rrType]\033[0m".CRLF;
		     
		} else {
			UltraDNS::doRequest($argv[1], array('zoneName' => $argv[2], 'rrType' => UltraDNS::getRrType($argv[4])) );
		     
		}


	} elseif($argv[1] == 'getResourceRecordTemplate') {

		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[rrType]\033[0m".CRLF;
		     
		} else {
			UltraDNS::doRequest($argv[1], array('rrType' => $argv[2]) );
		     
		}

	} elseif($argv[1] == 'createPrimaryZone') {

		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[forceImport]\033[0m".CRLF;
		     
		} else {
			UltraDNS::doRequest($argv[1], array('zoneName' => $argv[2], 'forceImport' => $argv[3]) );
		     
		}

	} elseif($argv[1] == 'createPrimaryRecord') {

		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[forceImport]\033[0m".CRLF;
		     
		} else {
			UltraDNS::doRequest($argv[1], array('zoneName' => $argv[2], 'forceImport' => $argv[3]) );
		     
		}


	} elseif($argv[1] == 'createResourceRecord') {
	
		if(!isset($argv[2])){
			echo CRLF."\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[rrType]\033[0m "
			."\033[32m[dName]\033[0m \033[32m[ttl]\033[0m "
			."\033[32m[info1Value:value]\033[0m ... \033[32m[info8Value:value]\033[0m"
			.CRLF;
			die("\n");
		} else {
			$call_params = array(
				'zoneName' => $argv[2], 'rrType' => UltraDNS::getRrType($argv[3]), 'dName' => $argv[4], 'ttl' => $argv[5]
			);

			// Determine the necessary parameters
			$res = UltraDNS::doRequest('getResourceRecordTemplate', array('rrType' => $argv[3]), true);
			$infoTypesRequired = array();
			if(!isset($res['ResourceRecordTemplate']['InfoTypes']) || !sizeof($res['ResourceRecordTemplate']['InfoTypes']['@attributes'])){
				echo "Error: Invalid resource record type!".CRLF;
				die("\n");
			} else {
				foreach($res['ResourceRecordTemplate']['InfoTypes']['@attributes'] as $infoName => $value) {
					$infoTypesRequired[str_replace('Type','Value',$infoName)] = $value;
				}
			}

			// Now match against what options were passed
			$infoTypesSupplied = UltraDNS::parseInfoFlags(6);
			$missing = array_diff_key($infoTypesRequired, $infoTypesSupplied);

			if(sizeof($missing)){
				$resp = "\n\033[36mSample Usage:\033[0m {$argv[0]} \033[31m{$argv[1]}\033[0m \033[32m[zoneName]\033[0m \033[32m[rrType]\033[0m "
				."\033[32m[dName]\033[0m \033[32m[ttl]\033[0m";
				foreach($infoTypesRequired as $k => $v){
					$resp .= " \033[32m".str_replace('Type','Value', $k).":[".$v."]\033[0m";
				}

				echo $resp."\n";
				die("\n");
			} else {
				foreach($infoTypesSupplied as $k => $v){
					$call_params[$k] = $v;
				}
			}

			UltraDNS::doRequest($argv[1], $call_params);
		}


	} elseif($argv[1] == 'help' || $argv[1] == '' || !isset($argv[1]) ) {

		echo "\nAvailable commands: \n\n";
		$commands = UltraDNS::getAvailableCommands();
		sort($commands);
		foreach($commands as $cmd)
			echo "\t\033[31m$cmd\033[0m\n";

		echo "\n\033[36mSample Usage:\033[0m {$argv[0]} \033[31m[queryType]\033[0m \033[32m[param1]\033[0m \033[32m[param2]\033[0m ...\n\n";


	} 


	    echo "\n";
    
?>