<?php

/*
 * skrypt importu raportów płatności masowych z banku BGZ transferbgz.pl do LMS
 *
 *  (C) Copyright P.H.U. AVERTIS - Jan Michlik 
 *  na podstawie  pliku lms-cashimport-bph.php (Webvisor Sp. z o.o.) i innych
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
*/                      
/* UWAGA!!!!!!!!!!!!!!
 * Dla prawidłowego działania skryptu trzeba dodać kilka pól do tabeli sourcefiles: 
 * file - pobrany plik z banku (nieprzetworzony) 
 * fileid - identyfikator pliku (bgz)
 * state - status pliku
 * Komenda do wykoniania w mysql-u:
 * ALTER TABLE `sourcefiles` ADD COLUMN `file` BLOB NULL  AFTER `idate` , ADD COLUMN `fileid` INT(11) NULL  AFTER `file` , ADD COLUMN `state` INT NULL  AFTER `fileid` ;
 *
*/
echo date("Y-m-d H:i:s")." lms-cashimport-bgz.php START \n";

// Wpisz tutaj login, hasło i identyfikator do systemu bankowego     
$pLogin = "login";
$pPassword = "haslo";
$pIden = "identyfikator";
$soap_url = 'https://transferbgz.pl/bgz.blc.loader/WebService?wsdl';

// REPLACE THIS WITH PATH TO YOU CONFIG FILE

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

// Parse configuration file
$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (! $CONFIG['directories']['sys_dir'] ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['backup_dir'] = (! $CONFIG['directories']['backup_dir'] ? $CONFIG['directories']['sys_dir'].'/backups' : $CONFIG['directories']['backup_dir']);
$CONFIG['directories']['lib_dir'] = (! $CONFIG['directories']['lib_dir'] ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);
$CONFIG['directories']['modules_dir'] = (! $CONFIG['directories']['modules_dir'] ? $CONFIG['directories']['sys_dir'].'/modules' : $CONFIG['directories']['modules_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
define('BACKUP_DIR', $CONFIG['directories']['backup_dir']);
define('MODULES_DIR', $CONFIG['directories']['modules_dir']);

// Load config defaults

require_once(LIB_DIR.'/config.php');

// Init database 

$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require_once(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Read configuration of LMS-UI from database

if($cfg = $DB->GetAll('SELECT section, var, value FROM uiconfig WHERE disabled=0'))
	foreach($cfg as $row)
		$CONFIG[$row['section']][$row['var']] = $row['value'];
		
		
//funkcje
//bug - modyfikacje błednego wsdl-a z BGZ-tu - zamiast http musi byc https!!
class My_SoapClient extends SoapClient {
//source http://www.victorstanciu.ro/php-soapclient-port-bug-workaround/ +modification
    public function __doRequest($request, $location, $action, $version) {
 			$location='https'.substr($location,4);
        $return = parent::__doRequest($request, $location, $action, $version);
        return $return;
    }
}

function mt940Parser($file){
	//podzielenie na wplaty
	$wplaty = preg_split('/-}/',$file,0,PREG_SPLIT_NO_EMPTY);
	//usuwanie pustych pozycji w tablicy (do dopracowania dzielenie powyzej - narazie tak)
	sort($wplaty);
	array_shift($wplaty);	
	$wplaty_parser=array();
	$i=0;
				//dla kazdej wplaty ...
	foreach($wplaty as $wplata) {
				//podzielenie na linie
		$tab = preg_split('/\n/',$wplata);
	  			//dla kazdej linii ...
		foreach($tab as $line) {
			$dwukropek=stripos($line,':');
			if ( ($dwukropek==0)&&($dwukropek!==false) ){
				$pole=preg_split('/:/',$line);
				$wplaty_parser[$i][$pole[1]]=trim($pole[2]);
				switch ($pole[1]) {
   				case '61':
   					$wplaty_parser[$i]['value']=str_replace(",", ".", trim(substr($pole[2],11,strpos($pole[2],'NOTREF')-11)));
   					$wplaty_parser[$i]['date']=trim('20'.substr($pole[2],0,2).'-'.substr($pole[2],2,2).'-'.substr($pole[2],4,2));
   				break;
					case '25':
						$wplaty_parser[$i]['customerid']=(int)trim(substr($pole[2],14,26));
						$wplaty_parser[$i]['customer']=trim($pole[2]);
  					break;
  				}
			}
		}
		$wplaty_parser[$i]['discription']=trim($wplaty_parser[$i][86].' Transaction no.:'.$wplaty_parser[$i]['28C']);
		$wplaty_parser[$i]['hash']=md5(trim($wplaty_parser[$i]['date'].$wplaty_parser[$i]['value'].$wplaty_parser[$i]['customer'].$wplaty_parser[$i]['28C']));
		$i++; //nastepna wplata	
	}			
	return $wplaty_parser;
}

function insertCashImport($wplaty_parser,$sourceid='',$sourcefileid=''){
//$wplaty_parser - tablica asocjacyjna z wplatami
//$sourceid - id zrodla importu z tabeli cashsources
//$sourcefileid - id pliku z tabeli sourcefiles
	global $DB;
	$i=0;
	foreach($wplaty_parser as $wplata){
		if($select_wplata = $DB->GetAll("SELECT * FROM cashimport WHERE Hash='$wplata[hash]'")){
			echo 'Wpłata '.$wplata[customer].',hash:'.$wplata[hash] .' jest już w bazie.'."\n"; 
		}else{ //dodac do bazy
			echo 'Dodaje wpłate '.$wplata[customer].',hash:'.$wplata[hash] .' do bazy.'."\n";
			$query = "Insert into cashimport (Date,Value,Customer,Description,CustomerId,Hash,sourceid, sourcefileid) 
				values (UNIX_TIMESTAMP('$wplata[date]'),'$wplata[value]','$wplata[customer]','$wplata[discription]',
				'$wplata[customerid]','$wplata[hash]','$sourceid','$sourcefileid')";
			echo $query;
			$DB->Execute($query);
			$i++;
		}
	}
	return $i;
}

		
// dzialania:

//probranie cashsourcesid
if($cfg = $DB->GetAll('SELECT * FROM cashsources WHERE name = "IDEN BGŻ"'))
	$cashsourcesid=$cfg[0]['id'];

//utworzenie polaczenia z bankiem
   $soap_client = new My_SoapClient($soap_url,
                         array(   
                          	'trace' 			=> true,
                          	'exceptions' 	=> true,
									'cache_wsdl' 	=> WSDL_CACHE_NONE 
                            ));

// pobranie listy plikow z banku
$bgzDocuments = $soap_client->getDocuments(array('in0'=>$pLogin,'in1'=>$pPassword,'in2'=>$pIden));

//dla kazdego pliku  
foreach($bgzDocuments->out->Document as $row){
		//sprawdzenie czy plik jest zapisany w bazie
		$query= "SELECT * FROM sourcefiles WHERE fileid = $row->id and name ='$row->name' and idate =UNIX_TIMESTAMP('". substr($row->fileDate,0,10)."')";
		//echo $query; 
		if($sql_plik = $DB->GetAll($query)){
		echo "Plik ".$row->name." jest już w bazie.\n"; 
		
		}else{
			echo "Dodaje plik ".$row->name." do bazy.\n"; 
			//pobranie pliku		
			$bgzDocument = $soap_client->getDocument(array('in0'=>$row->id,'in1'=>$pLogin,'in2'=>$pPassword,'in3'=>$pIden));
			$plik=iconv("ISO-8859-2","UTF-8",$bgzDocument->out);
			//dodanie pliku do bazy danych 
			$query= "Insert INTO sourcefiles (name,idate,file,fileid,state)
		 	values('$row->name',UNIX_TIMESTAMP('". substr($row->fileDate,0,10)."'),'".addslashes($plik)."',$row->id,$row->state ) ";
			$DB->Execute($query);
			$sourcefileid=$DB->GetLastInsertID();			
			//uruchmienie przetwarzania plikow na wpłaty ******* 
			$wplaty_parser=mt940Parser($plik);
			//zapis wpłat do tabeli cashimport
			$insert=insertCashImport($wplaty_parser, $sourceid, $sourcefileid);
			echo 'Ilość zapisanych wpłat:'.$insert."\n";			
		
		}
}//koniec dla kazdego pliku

echo date("Y-m-d H:i:s")." lms-cashimport-bgz.php END \n";
?>
