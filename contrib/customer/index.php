<?

/*
 * LMS version 1.0-cvs
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

$CONFIG_FILE = "/etc/lms/lms.ini";

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!

// Parse configuration file

foreach(parse_ini_file($CONFIG_FILE, true) as $key=>$val) $_CONFIG[$key] = $val;

// Define directories and configuration vars

$_SYSTEM_DIR = (! $_CONFIG[directories]['sys_dir'] ? getcwd() : $_CONFIG[directories]['sys_dir']);
$_LIB_DIR = (! $_CONFIG[directories]['lib_dir'] ? $_SYSTEM_DIR."/lib/" : $_CONFIG[directories]['lib_dir']);
$_SMARTY_DIR = (! $_CONFIG[directories]['smarty_dir'] ? $_LIB_DIR."/Smarty/" : $_CONFIG[directories]['smarty_dir']);
$_SMARTY_COMPILE_DIR = (! $_CONFIG[directories]['smarty_compile_dir'] ? $_SYSTEM_DIR."/templates_c" : $_CONFIG[directories]['smarty_compile_dir']);
$_ADODB_DIR = (! $_CONFIG[directories]['adodb_dir'] ? $_LIB_DIR."/adodb/" : $_CONFIG[directories]['adodb_dir']);

// Define database variables

$_DBTYPE = (! $_CONFIG[database]['type'] ? "mysql" : $_CONFIG[database]['type']);
$_DBHOST = (! $_CONFIG[database]['host'] ? "localhost" : $_CONFIG[database]['host']);
$_DBUSER = (! $_CONFIG[database]['user'] ? "root" : $_CONFIG[database]['user']);
$_DBPASS = (! $_CONFIG[database]['password'] ? "" : $_CONFIG[database]['password']);
$_DBNAME = (! $_CONFIG[database]['database'] ? "lms" : $_CONFIG[database]['database']);

// Set our sweet polish locales :>

//setlocale (LC_ALL, 'pl_PL');

// include required files

require_once($_SMARTY_DIR.'/Smarty.class.php');
require_once($_ADODB_DIR.'/adodb.inc.php');
require_once($_LIB_DIR."/LMS.class.php");

// Initialize ADODB object

$ADB = ADONewConnection($_DBTYPE);
$ADB->Connect($_DBHOST,$_DBUSER,$_DBPASS,$_DBNAME);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

// Initialize database and template classes

$LMS = new LMS($ADB,NULL);

$SMARTY = new Smarty;

// test for proper version of Smarty

$SMARTY->template_dir = getcwd();
$SMARTY->compile_dir = $_SMARTY_COMPILE_DIR;

$layout[lmsv]='1.0-cvs';

$SMARTY->assign("menu",$menu);
$SMARTY->assign("layout",$layout);

header('X-Powered-By: LMS/'.$layout[lmsv]);

$_SERVER[REMOTE_ADDR] = str_replace("::ffff:","",$_SERVER[REMOTE_ADDR]);

$userid = $LMS->GetNodeOwner($LMS->GetNodeIDByIP((isset($_SERVER[HTTP_X_FORWARDED_FOR]) ? $_SERVER[HTTP_X_FORWARDED_FOR] : $_SERVER[REMOTE_ADDR])));

$balance = $LMS->GetUserBalanceList($userid);
$userinfo = $LMS->GetUser($userid);

$SMARTY->assign("userinfo",$userinfo);
$SMARTY->assign("balance",$balance);
$SMARTY->display("customer.html");

?>
