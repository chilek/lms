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
echo date("Y-m-d H:i:s")." lms-cashimport-bgz.php START " . PHP_EOL;

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

define('CONFIG_FILE', $CONFIG_FILE);

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

// Load autloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

// Init database

$DB = null;

try {
	$DB = LMSDB::getInstance();
} catch (Exception $ex) {
	trigger_error($ex->getMessage(), E_USER_WARNING);
	// can't working without database
	die("Fatal error: cannot connect to database!" . PHP_EOL);
}

//funkcje
//bug - modyfikacje błednego wsdl-a z BGZ-tu - zamiast http musi byc https!!
class My_SoapClient extends SoapClient {
//source http://www.victorstanciu.ro/php-soapclient-port-bug-workaround/ +modification
    public function __doRequest($request, $location, $action, $version, $one_way = 0) {
 			$location='https'.substr($location,4);
        $return = parent::__doRequest($request, $location, $action, $version, $one_way);
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
                                if (isset($pole[4])){
                                        $wplaty_parser[$i][$pole[1]]=trim($pole[2]).':'.trim($pole[3]).':'.trim($pole[4]);
                                }elseif(isset($pole[3])){
                                        $wplaty_parser[$i][$pole[1]]=trim($pole[2]).':'.trim($pole[3]);
                                }else{
					$wplaty_parser[$i][$pole[1]]=trim($pole[2]);
                                }
				switch ($pole[1]) {
                                    case '61':
                                        //kwota wplaty
                                        $wplaty_parser[$i]['value']=str_replace(",", ".", trim(substr($pole[2],11,strpos($pole[2],'NOTREF')-11)));
                                        //data zlecenia wplaty. Wyhaszowane poniewaz bierzemy date zaksiegowania (pola 62M i 62F)
					//$wplaty_parser[$i]['date']=trim('20'.substr($pole[2],0,2).'-'.substr($pole[2],2,2).'-'.substr($pole[2],4,2));
                                    break;
                                    case '62M':
                                    case '62F':
                                        //data zaksiegowania wplaty
                                        $wplaty_parser[$i]['date']=trim('20'.substr($pole[2],1,2).'-'.substr($pole[2],3,2).'-'.substr($pole[2],5,2));
                                    break;
                                    case '25':
                                        //id klienta
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
