<?

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

$CONFIG_FILE = '/etc/lms/lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!

ini_set('session.name','LMSSESSIONID');

// Parse configuration file

foreach(parse_ini_file($CONFIG_FILE, true) as $key=>$val) $_CONFIG[$key] = $val;

// config value tester

function chkconfig($value,$default=FALSE)
{
	if(eregi('^(1|y|on|yes|true|tak|t)$',$value))
		return TRUE;
	elseif(eregi('^(0|n|no|off|false|nie)$',$value))
		return FALSE;
	elseif(!isset($value)||$value=='')
		return $default;
	else
		trigger_error('B³êdna warto¶æ opcji "'.$value.'"');
}
									
// Define directories and configuration vars

$_SYSTEM_DIR = (! $_CONFIG[directories]['sys_dir'] ? getcwd() : $_CONFIG[directories]['sys_dir']);
$_BACKUP_DIR = (! $_CONFIG[directories]['backup_dir'] ? $_SYSTEM_DIR.'/backups' : $_CONFIG[directories]['backup_dir']);
$_LIB_DIR = (! $_CONFIG[directories]['lib_dir'] ? $_SYSTEM_DIR.'/lib' : $_CONFIG[directories]['lib_dir']);
$_MODULES_DIR = (! $_CONFIG[directories]['modules_dir'] ? $_SYSTEM_DIR.'/modules' : $_CONFIG[directories]['modules_dir']);
$_SMARTY_DIR = (! $_CONFIG[directories]['smarty_dir'] ? $_LIB_DIR.'/Smarty' : $_CONFIG[directories]['smarty_dir']);
$_SMARTY_COMPILE_DIR = (! $_CONFIG[directories]['smarty_compile_dir'] ? $_SYSTEM_DIR.'/templates_c' : $_CONFIG[directories]['smarty_compile_dir']);
$_SMARTY_TEMPLATES_DIR = (! $_CONFIG[directories]['smarty_templates_dir'] ? $_SYSTEM_DIR.'/templates' : $_CONFIG[directories]['smarty_templates_dir']);
$_ADODB_DIR = (! $_CONFIG[directories]['adodb_dir'] ? $_LIB_DIR.'/adodb' : $_CONFIG[directories]['adodb_dir']);
$_TIMEOUT = (! $_CONFIG[phpui]['timeout'] ? 600 : $_CONFIG[phpui]['timeout']);
$_FORCE_SSL = chkconfig($_CONFIG[phpui]['force_ssl']);

// Redirect to SSL

if($_FORCE_SSL && $_SERVER[HTTPS] != 'on')
{
	header('Location: https://'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]);
	exit(0);
}

// Define database variables

$_DBTYPE = (! $_CONFIG[database]['type'] ? 'mysql' : $_CONFIG[database]['type']);
$_DBHOST = (! $_CONFIG[database]['host'] ? 'localhost' : $_CONFIG[database]['host']);
$_DBUSER = (! $_CONFIG[database]['user'] ? 'root' : $_CONFIG[database]['user']);
$_DBPASS = (! $_CONFIG[database]['password'] ? '' : $_CONFIG[database]['password']);
$_DBNAME = (! $_CONFIG[database]['database'] ? 'lms' : $_CONFIG[database]['database']);

// include required files

require_once($_LIB_DIR.'/common.php');
require_once($_LIB_DIR.'/checkip.php');
require_once($_LIB_DIR.'/checkdirs.php');
require_once($_LIB_DIR.'/unstrip.php');
require_once($_SMARTY_DIR.'/Smarty.class.php');
require_once($_ADODB_DIR.'/adodb.inc.php');
require_once($_LIB_DIR.'/LMS.class.php');
require_once($_LIB_DIR.'/Session.class.php');
require_once($_LIB_DIR.'/leftmenu.php');
require_once($_LIB_DIR.'/TipOfTheDay.php');
require_once($_LIB_DIR.'/accesstable.php');

// include language file (temporary, hardcoded pl.php, after making full language support
// this will be changed.

require_once($_LIB_DIR.'/lang/pl.php');

// Initialize ADODB object

$ADB = ADONewConnection($_DBTYPE);
$ADB->debug = chkconfig($_CONFIG[phpui][adodb_debug]);
if($_CONFIG[phpui][adodb_debug_log] && is_writeable($_CONFIG[phpui][adodb_debug_log]))
	define(ADODB_OUTP,'writelog');
$ADB->Connect($_DBHOST,$_DBUSER,$_DBPASS,$_DBNAME);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

// Initialize database and template classes

$SESSION = new Session($ADB,$_TIMEOUT);

$LMS = new LMS($ADB,$SESSION);
$LMS->CONFIG[backup_dir] = $_BACKUP_DIR;
$LMS->CONFIG[debug_email] = $_CONFIG[phpui][debug_email];

$SMARTY = new Smarty;

// test for proper version of Smarty

if(version_compare('2.5.0',$SMARTY->_version) > 0)
	die('<B>Niepoprawna wersja engine Smarty! Proszê sci±gn±æ nowszê wersjê spod adresu <A HREF="http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz">http://smarty.php.net/distributions/Smarty-2.5.0.tar.gz</A>!</B>');

$SMARTY->template_dir = $_SMARTY_TEMPLATES_DIR;
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;
$SMARTY->debugging = chkconfig($_CONFIG[phpui][smarty_debug]);

$layout[logname]=$SESSION->logname;
$layout[logid]=$SESSION->id;
$layout[lmsv]='1.1-cvs ('.$LMS->_version.'/'.$SESSION->_version.')';
$layout[smarty_version] = $SMARTY->_version;
$layout[adodb_version] = eregi_replace('(.*)\(c\).*','\1',$ADODB_vers);
$layout[uptime]=uptime();
$layout[hostname]=hostname();
$layout[date]=pldate();

$SMARTY->assign('menu',$menu);
$SMARTY->assign('layout',$layout);

header('X-Powered-By: LMS/'.$layout[lmsv]);
if($SESSION->islogged)
{

	if($SESSION->passwd == '')
		$SMARTY->assign('emptypasswd',TRUE);

	$module=$_GET['m'];
	
	if (file_exists($_MODULES_DIR.'/'.$module.'.php'))
	{
		if(eregi($access[allow],$module))
			$allow = TRUE;
		else{
			$rights = $LMS->GetAdminRights($SESSION->id);
			if($rights)
				foreach($rights as $level)
					if(isset($access[table][$level][deny_reg]) && eregi($access[table][$level][deny_reg],$module))
						$deny = TRUE;
					elseif(isset($access[table][$level][allow_reg]) && eregi($access[table][$level][allow_reg],$module))
						$allow = TRUE;
		}

		if($allow && ! $deny)
		{
			$layout[module]=$module;
			include($_MODULES_DIR.'/'.$module.'.php');
		}else
			$SMARTY->display('noaccess.html');
	}elseif($module==''){
		$layout[module]='welcome';
		include($_MODULES_DIR.'/welcome.php');
	}else{
		$layout[module]='notfound';
		$layout[pagetitle]="B³±d!";
		$SMARTY->assign("layout",$layout);
		$SMARTY->assign("server",$_SERVER);
		$SMARTY->display("notfound.html");
	}
	
	if($_SESSION[lastmodule]!=$module)
		$_SESSION[lastmodule]=$module;
}
else
{
	$SMARTY->assign('error',$SESSION->error);
	$SMARTY->assign('target','?'.$_SERVER[QUERY_STRING]);
	$SMARTY->display('login.html');
	
}

?>
