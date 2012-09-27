#!/usr/bin/php
<?php
/* 
 * wylaczenie warningow, jesli suma zobowiazan jest >= 0
 *
 */

ini_set('error_reporting', E_ALL&~E_NOTICE);

$CONFIG_FILE = '/etc/lms/lms.ini';

if (!is_readable($CONFIG_FILE))
	die('Unable to read configuration file ['.$CONFIG_FILE.']!'); 

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);
// Do some checks and load config defaults

require_once(LIB_DIR.'/config.php');

// Init database
 
$_DBTYPE = $CONFIG['database']['type'];
$_DBHOST = $CONFIG['database']['host'];
$_DBUSER = $CONFIG['database']['user'];
$_DBPASS = $CONFIG['database']['password'];
$_DBNAME = $CONFIG['database']['database'];

require(LIB_DIR.'/LMSDB.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

if(!$DB)
{
	// can't working without database
	die("Fatal error: cannot connect to database!\n");
}

// Read configuration from database

$arr = $DB->GetAll('
	SELECT cash.customerid, SUM(cash.value) AS suma 
	FROM cash JOIN nodes ON (cash.customerid=nodes.ownerid) 
	WHERE nodes.warning=1 
	GROUP BY cash.customerid
	HAVING SUM(cash.value)>=0');
if(empty($arr))
{
    echo 'Czysto brak dodatnich bilansow z ustawionym nodes.warning=1';
}else{
    foreach($arr as $row)
    {
	echo 'ID: '.$row['customerid'].' SUM: '.$row['suma'].PHP_EOL;
	$DB->Execute("UPDATE nodes SET warning=0 WHERE ownerid = ?", array($row['customerid']));
    }
}




$DB->Destroy();

?>

