<?php
/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
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
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, 
 *  USA.
 *
 *  $Id$
 */

// REPLACE THIS WITH PATH TO YOU CONFIG FILE

$CONFIG_FILE = (is_readable('lms.ini')) ? 'lms.ini' : '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************
ini_set('session.name','LMSSESSIONID');

// Parse configuration file
function lms_parse_ini_file($filename, $process_sections = false) 
{
	$ini_array = array();
	$sec_name = "";
	$lines = file($filename);
	foreach($lines as $line) 
	{
		$line = trim($line);
		
		if($line == "" || $line[0] == ";" || $line[0] == "#") 
			continue;
		
		if( sscanf($line, "[%[^]]", &$sec_name)==1 ) 
			$sec_name = trim($sec_name);
		else 
		{
			if ( sscanf($line, "%[^=] = '%[^']'", &$property, &$value) != 2 ) 
				if ( sscanf($line, "%[^=] = \"%[^\"]\"", &$property, &$value) != 2 ) 
					if( sscanf($line, "%[^=] = %[^;#]",    &$property, &$value) != 2 ) 
						continue;
			
			$property = trim($property);
			$value = trim($value);
			
			if($process_sections) 
				$ini_array[$sec_name][$property] = $value;
			else 
				$ini_array[$property] = $value;
		}
	}
	
	return $ini_array;
}

foreach(lms_parse_ini_file($CONFIG_FILE, true) as $key => $val)
	$_CONFIG[$key] = $val;

// config value tester
function chkconfig($value, $default = FALSE)
{
	if(eregi('^(1|y|on|yes|true|tak|t)$', $value))
		return TRUE;
	elseif(eregi('^(0|n|no|off|false|nie)$', $value))
		return FALSE;
	elseif(!isset($value) || $value == '')
		return $default;
	else
		trigger_error('B³êdna warto¶æ opcji "'.$value.'"');
}

// Check for configuration vars and set default values
$_CONFIG['directories']['sys_dir'] = (! $_CONFIG['directories']['sys_dir'] ? getcwd() : $_CONFIG['directories']['sys_dir']);
$_CONFIG['directories']['backup_dir'] = (! $_CONFIG['directories']['backup_dir'] ? $_CONFIG['directories']['sys_dir'].'/backups' : $_CONFIG['directories']['backup_dir']);
$_CONFIG['directories']['lib_dir'] = (! $_CONFIG['directories']['lib_dir'] ? $_CONFIG['directories']['sys_dir'].'/lib' : $_CONFIG['directories']['lib_dir']);
$_CONFIG['directories']['modules_dir'] = (! $_CONFIG['directories']['modules_dir'] ? $_CONFIG['directories']['sys_dir'].'/modules' : $_CONFIG['directories']['modules_dir']);
$_CONFIG['directories']['config_templates_dir'] = (! $_CONFIG['directories']['config_templates_dir'] ? $_CONFIG['directories']['sys_dir'].'/config_templates' : $_CONFIG['directories']['config_templates_dir']);
$_CONFIG['directories']['smarty_dir'] = (! $_CONFIG['directories']['smarty_dir'] ? (is_readable('/usr/share/php/smarty/libs/Smarty.class.php') ? '/usr/share/php/smarty/libs' : $_CONFIG['directories']['lib_dir'].'/Smarty') : $_CONFIG['directories']['smarty_dir']);
$_CONFIG['directories']['smarty_compile_dir'] = (! $_CONFIG['directories']['smarty_compile_dir'] ? $_CONFIG['directories']['sys_dir'].'/templates_c' : $_CONFIG['directories']['smarty_compile_dir']);
$_CONFIG['directories']['smarty_templates_dir'] = (! $_CONFIG['directories']['smarty_templates_dir'] ? $_CONFIG['directories']['sys_dir'].'/templates' : $_CONFIG['directories']['smarty_templates_dir']);

foreach(lms_parse_ini_file($_CONFIG['directories']['lib_dir'].'/config_defaults.ini', TRUE) as $section => $values)
	foreach($values as $key => $val)
		if(! isset($_CONFIG[$section][$key]))
			$_CONFIG[$section][$key] = $val;

$_SYSTEM_DIR = $_CONFIG['directories']['sys_dir'];
$_BACKUP_DIR = $_CONFIG['directories']['backup_dir'];
$_LIB_DIR = $_CONFIG['directories']['lib_dir'];
$_MODULES_DIR = $_CONFIG['directories']['modules_dir'];
$_SMARTY_DIR = $_CONFIG['directories']['smarty_dir'];
$_SMARTY_COMPILE_DIR = $_CONFIG['directories']['smarty_compile_dir'];
$_SMARTY_TEMPLATES_DIR = $_CONFIG['directories']['smarty_templates_dir'];
$_TIMEOUT = $_CONFIG['phpui']['timeout'];
$_FORCE_SSL = chkconfig($_CONFIG['phpui']['force_ssl']);
$_DBTYPE = $_CONFIG['database']['type'];
$_DBHOST = $_CONFIG['database']['host'];
$_DBUSER = $_CONFIG['database']['user'];
$_DBPASS = $_CONFIG['database']['password'];
$_DBNAME = $_CONFIG['database']['database'];



// Redirect to SSL

if($_FORCE_SSL && $_SERVER['HTTPS'] != 'on')
{
	header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	exit(0);
}

// Set our sweet polish locales :>

//setlocale (LC_ALL, 'pl_PL');

// include required files

require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/checkip.php');
require_once($_LIB_DIR.'/checkdirs.php');
require_once($_LIB_DIR.'/unstrip.php');
require_once($_SMARTY_DIR.'/Smarty.class.php');
require_once($_LIB_DIR.'/LMSDB.php');
require_once($_LIB_DIR.'/LMS.class.php');
require_once($_LIB_DIR.'/Session.class.php');
require_once($_LIB_DIR.'/accesstable.php');
require_once($_LIB_DIR.'/language.php');

$DB = DBInit($_DBTYPE, $_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);

// Initialize database and template classes

$SESSION = new Session($DB, $_TIMEOUT);

$LMS = new LMS($DB, $SESSION, $_CONFIG);
$LMS->CONFIG = $_CONFIG;

$SMARTY = new Smarty;
$SMARTY->assign('_config',$_CONFIG);

// test for proper version of Smarty

if(version_compare('2.5.0', $SMARTY->_version) > 0)
	die('<B>Niepoprawna wersja engine Smarty! Proszê sci±gn±æ nowszê wersjê spod adresu <A HREF="http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz">http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz</A>!</B>');

$SMARTY->template_dir = $_SMARTY_TEMPLATES_DIR;
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;
$SMARTY->debugging = chkconfig($_CONFIG['phpui']['smarty_debug']);
require_once($_LIB_DIR.'/smarty_addons.php');

$layout['logname'] = $SESSION->logname;
$layout['logid'] = $SESSION->id;
$layout['lmsv'] = '1.1-cvs ('.$LMS->_revision.'/'.$SESSION->_revision.')';
$layout['lmsdbv'] = $DB->_version;
$layout['smarty_version'] = $SMARTY->_version;
$layout['uptime'] = uptime();
$layout['hostname'] = hostname();

$SMARTY->assign('menu', $LMS->MENU);
$SMARTY->assign('layout', $layout);

header('X-Powered-By: LMS/'.$layout['lmsv']);
if($SESSION->islogged)
{

	if($SESSION->passwd == '')
		$SMARTY->assign('emptypasswd',TRUE);

	$module = $_GET['m'];
	
	if (file_exists($_MODULES_DIR.'/'.$module.'.php'))
	{
		if(eregi($access['allow'], $module))
			$allow = TRUE;
		else{
			$rights = $LMS->GetAdminRights($SESSION->id);
			if($rights)
				foreach($rights as $level)
					if(isset($access['table'][$level]['deny_reg']) && eregi($access['table'][$level]['deny_reg'], $module))
						$deny = TRUE;
					elseif(isset($access['table'][$level]['allow_reg']) && eregi($access['table'][$level]['allow_reg'], $module))
						$allow = TRUE;
		}

		if($allow && ! $deny)
		{
			$layout['module'] = $module;
			include($_MODULES_DIR.'/'.$module.'.php');
		}
		else
			$SMARTY->display('noaccess.html');
	}
	elseif($module == '')
	{
		$layout['module'] = 'welcome';
		$SMARTY->assign('warning',!chkconfig($_CONFIG['phpui']['disable_devel_warning']));
		include($_MODULES_DIR.'/welcome.php');
	}
	else
	{
		$layout['module'] = 'notfound';
		$layout['pagetitle'] = 'B³±d!';
		$SMARTY->assign('layout', $layout);
		$SMARTY->assign('server', $_SERVER);
		$SMARTY->display('notfound.html');
	}
	
	if($_SESSION['lastmodule'] != $module)
		$_SESSION['lastmodule'] = $module;
}
else
{
	$SMARTY->assign('error', $SESSION->error);
	$SMARTY->assign('target','?'.$_SERVER['QUERY_STRING']);
	$SMARTY->display('login.html');
	
}

$DB->Destroy();

/*
 * $Log$
 * Revision 1.122  2003/12/04 04:39:14  lukasz
 * - porz±dki
 * - trochê pod³ubane przy parsowaniu pliku konfiguracyjnego
 *
 * Revision 1.121  2003/12/02 15:03:24  alec
 * te pierdo³y ju¿ kto¶ kiedy¶ wywali³
 *
 * Revision 1.120  2003/12/01 06:13:37  lukasz
 * - temporary save, do not touch
 *
 * Revision 1.119  2003/12/01 04:21:18  lukasz
 * - tsave (nowe faktury)
 * - kosmetyka
 *
 * Revision 1.118  2003/11/27 03:19:48  lukasz
 * - no i leftmenu polecia³o w niepamiêæ ;-)
 *
 * Revision 1.117  2003/11/26 18:23:36  alec
 * nie wiem czy to tak mia³o byæ, ale teraz dziala
 *
 * Revision 1.116  2003/11/18 20:32:17  alec
 * 100of c & php iniparsers compatibility
 *
 * Revision 1.115  2003/11/14 18:41:52  alec
 * nowa funkcja parsuj±ca konfig
 *
 * Revision 1.114  2003/11/11 20:44:53  alec
 * function for ini file parsing, compatible with almsd ini value strings
 *
 * Revision 1.113  2003/10/27 21:29:52  warden
 * - czesc hopaki, wpadlem cos zepsuc w 1.1 ;-)
 *
 * Revision 1.112  2003/10/22 17:50:51  lukasz
 * - generator configów
 *
 * Revision 1.111  2003/10/22 12:20:33  lukasz
 * - small changes in $_CONFIG handling
 *
 * Revision 1.110  2003/10/11 20:01:14  alec
 * kto¶ zapomnia³ o wykrzyknikach
 *
 * Revision 1.109  2003/10/11 03:45:20  lukasz
 * - http://lists.rulez.pl/lms/1242.html
 *
 * Revision 1.108  2003/10/08 00:05:51  lukasz
 * - lokalizowalna data
 *
 * Revision 1.107  2003/10/02 10:00:32  lukasz
 * - code cleanups
 *
 * Revision 1.106  2003/10/01 21:12:29  lukasz
 * - added language.php
 *
 * Revision 1.105  2003/09/25 15:13:13  lukasz
 * - force stats te be show
 *
 * Revision 1.104  2003/09/24 22:33:54  lukasz
 * - s/TipOfTheDay/fortunes/g
 *
 * Revision 1.103  2003/09/12 21:58:43  lexx
 * - blak
 *
 * Revision 1.102  2003/09/12 21:10:40  lexx
 * - netdev
 *
 * Revision 1.101  2003/09/10 00:16:19  lukasz
 * - LMSDB::Destroy();
 *
 * Revision 1.100  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.99  2003/09/08 03:13:00  lukasz
 * - dodane rozszerzenie do smartyego. WHAZAA! Jeszcze mniej roboty w pehapie
 *   bêdzie ;-)
 *
 * Revision 1.98  2003/09/05 19:48:59  lexx
 * - enable_stats
 *
 * Revision 1.97  2003/09/05 02:07:04  lukasz
 * - massive attack: s/this->ADB->/this->DB->/g
 *
 * Revision 1.96  2003/09/01 22:21:40  lukasz
 * - literówka
 *
 * Revision 1.95  2003/09/01 22:16:21  lukasz
 * - ostrze¿enie o wersji rozwojowej ;>
 *
 * Revision 1.94  2003/08/24 13:53:30  lukasz
 * - do not change empty dbhost with postgres
 *
 * Revision 1.93  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.92  2003/08/22 12:59:48  lukasz
 * -
 *
 * Revision 1.91  2003/08/22 00:24:29  lukasz
 * - temporary save - adodb no longer needed
 *
 * Revision 1.90  2003/08/19 01:18:53  lukasz
 * - cleanups
 *
 * Revision 1.89  2003/08/18 17:16:25  lukasz
 * - temporary save
 *
 * Revision 1.88  2003/08/18 16:57:00  lukasz
 * - more cvs tags :>
 *
 */

?>
