<?php
/* lms-stck INIT FILE */

//Sorry, bo no-go for postgres :(
if (ConfigHelper::getConfig('database.database') == 'postgres') {
 	trigger_error("lms-stck doesn`t support PostgreSQL (yet)!", E_USER_WARNING);
	die('Fatal error: lms-stck doesn`t support PostgreSQL (yet)!');
}

define('STCK_DBVERSION', '2026030400');
define('STCK_DIR', $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'stock');

$ksef_number_pattern = '/^\d{10}-20\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])-[A-Z0-9]{12}-[A-Z0-9]{2}$/';

require('LMSStck.class.php');
#include('locale/'.$CONFIG['phpui']['lang'].'/strings.php');
Localisation::appendUiLanguage('stock'.DIRECTORY_SEPARATOR.'locale');
$LMSST = new LMSStck($DB, $AUTH, $CONFIG, $LMS);
?>
