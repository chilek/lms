<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

// Load autloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/");
}

$_MAILDBTYPE = ConfigHelper::getConfig('database.mail_db_type');
$_MAILDBHOST = ConfigHelper::getConfig('database.mail_db_host');
$_MAILDBUSER = ConfigHelper::getConfig('database.mail_db_user');
$_MAILDBPASS = ConfigHelper::getConfig('database.mail_db_password');
$_MAILDBNAME = ConfigHelper::getConfig('database.mail_db_database');

// Initialize mail database

$DB_MAIL = null;

try {

    $DB_MAIL = LMSDB::getDB($_MAILDBTYPE, $_MAILDBHOST, $_MAILDBUSER, $_MAILDBPASS, $_MAILDBNAME);

} catch (Exception $ex) {
    
    trigger_error($ex->getMessage(), E_USER_WARNING);
    
    // can't working without database
    die("Fatal error: cannot connect to database!\n");
    
}


if (defined('USERPANEL_SETUPMODE'))
{
    function module_setup()
    {
	global $SMARTY, $LMS;
	
	$SMARTY->assign('mail_limit', ConfigHelper::getConfig('userpanel.mail_limit'));
	$SMARTY->assign('mail_allowed_domains', ConfigHelper::getConfig('userpanel.mail_allowed_domains'));

	$SMARTY->display('module:accounts:setup.html');
    }
	    
    function module_submit_setup()
    {
	global $DB;
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'mail_limit\'',array($_POST['mail_limit']));
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'mail_allowed_domains\'',array($_POST['mail_allowed_domains']));

        header('Location: ?m=userpanel&module=accounts');
    }
}

function CheckMail($user,$domain,$mail)
{
    global $LMS,$DB_MAIL,$_POST,$SMARTY;
    $mail_err = '';

    // długość znaków
    if (strlen($user) < 3)
    {
	$mail_err = 'Konto musi mieć przynajmniej 3 znaki !';
    }

    // istnienie konta	
    $query = 'select username from mailbox where username = \''.$mail.'\';';
    $mailbox = $DB_MAIL->GetOne($query);
    if (strcmp($mail,$mailbox) == 0)
    {
	$mail_err = 'Konto '.$mail.' już istnieje !';
    }

    if (strlen($mail_err) > 0)
    {
	$_POST['mail_err'] = $mail_err;

    	header('Location: ?m=accounts&f=mailadd&mail_err='.$mail_err);
	die();
    }

}

function CheckPass($password1,$password2)
{
    global $_POST;
    $pass_err = '';

	//długość hasła
	if (strlen($password1) < 5)
	{
	    $pass_err = 'Hasło musi mieć przynajmniej 5 znaków !';
	}

	//czy dwa razy takie same
	if (strcmp($password1,$password2) > 0)
	{
	    $pass_err = 'Oba wpisane hasła muszą być identyczne !';
	}

	if (strlen($pass_err) > 0)
	{
		$_POST['pass_err'] = $pass_err;
	//	module_mailadd();
	//	die();
    	header('Location: ?m=accounts&f=mailadd&pass_err='.$pass_err);
	die();		
	}
}

function module_mailadd()
{
    global $SMARTY,$_GET,$SESSION,$DB_MAIL,$LMS,$_POST;

    $mail_limit = ConfigHelper::getConfig('userpanel.mail_limit');
    $mail_allowed_domains = ConfigHelper::getConfig('userpanel.mail_allowed_domains');

    $mailboxes = GetCustomerMailBoxes($SESSION->id);

    $mail_allowed_domains_array = explode(' ',$mail_allowed_domains);
    asort($mail_allowed_domains_array);

    if ($mailboxes['total'] < $mail_limit)
    {
	$SMARTY->assign('mail_err',$_GET['mail_err']);
        $SMARTY->assign('pass_err',$_GET['pass_err']);
	$SMARTY->assign('mail_allowed_domains_array',$mail_allowed_domains_array);
    	$SMARTY->display('module:mailboxnew.html');
    } else
    {
    	header('Location: ?m=accounts');
    }
}

function module_mailsave()
{
    global $SMARTY,$_GET,$_POST,$SESSION,$DB_MAIL;
    $mail = $_POST['account']['account'].'@'.$_POST['account']['domain'];
    $domain = $_POST['account']['domain'];
    $password1 = $_POST['account']['password1'];
    $password2 = $_POST['account']['password2'];

    CheckMail($_POST['account']['account'],$domain,$mail);
    CheckPass($password1,$password2);

 
	$pw_crypted = md5crypt($password1);

    	$query = 'INSERT INTO mailbox (username,password,name,maildir,quota,domain,created,modified,customerid)
				 values (\''.$mail.'\',\''.$pw_crypted.'\',\'Created from Ebok\',\''.$domain.'/'.$mail.'\',
				 50000000,\''.$domain.'/\',now(),now(),'.$SESSION->id.');';

	$DB_MAIL->Execute($query);


    header('Location: ?m=accounts');
}

function module_maildelete()
{
    global $SMARTY,$_GET,$SESSION,$DB_MAIL;

    $query = 'delete from mailbox where customerid='.$SESSION->id.' and username=\''.$_GET['account'].'\';';
    $DB_MAIL->Execute($query);

    header('Location: ?m=accounts');
}

function module_mailhpasswd()
{
    global $_GET,$SMARTY;

    $SMARTY->assign('account',$_GET['acount']);
    $SMARTY->display('module:mailboxpasswd.html');

}

function module_mailhpasswdsave()
{
    global $_POST,$SESSION,$DB_MAIL;

    $mail = $_POST['pwd']['account'];
    $pw1  = $_POST['pwd']['password1'];
    $pw2  = $_POST['pwd']['password2'];

    if ($password1 == $password2)
    {
	$pw_crypted = md5crypt($pw1);
	$query = 'update mailbox set password = \''.$pw_crypted.
			'\' where username = \''.$mail.'\' and customerid = '.$SESSION->id.';';

	$DB_MAIL->Execute($query);
    }
    header('Location: ?m=accounts');
}


function module_main()
{
    global $SMARTY,$_GET,$SESSION,$LMS;

    $mailboxes = GetCustomerMailBoxes($SESSION->id);
    $SMARTY->assign('mail_limit', ConfigHelper::getConfig('userpanel.mail_limit'));
    $SMARTY->assign('mailboxes', $mailboxes);
    $SMARTY->display('module:accounts.html');

}


function GetCustomerMailBoxes($customerid)
{
	global $DB_MAIL;

	$mailboxes = $DB_MAIL->GetAll('SELECT username, quota, created, modified, domain, active FROM mailbox WHERE customerid =?',
		array($customerid));

	$mailboxes['total'] = sizeof($mailboxes);

	return($mailboxes);
}

function md5crypt ($pw, $salt="", $magic="")
{
   $MAGIC = "$1$";

   if ($magic == "") $magic = $MAGIC;
   if ($salt == "") $salt = create_salt ();
   $slist = explode ("$", $salt);
   if ($slist[0] == "1") $salt = $slist[1];

   $salt = substr ($salt, 0, 8);
   $ctx = $pw . $magic . $salt;
   $final = hex2bin (md5 ($pw . $salt . $pw));

   for ($i=strlen ($pw); $i>0; $i-=16)
   {
      if ($i > 16)
      {
         $ctx .= substr ($final,0,16);
      }
      else
      {
         $ctx .= substr ($final,0,$i);
      }
   }
   $i = strlen ($pw);

   while ($i > 0)
   {
      if ($i & 1) $ctx .= chr (0);
      else $ctx .= $pw[0];
      $i = $i >> 1;
   }
   $final = hex2bin (md5 ($ctx));

   for ($i=0;$i<1000;$i++)
   {
      $ctx1 = "";
      if ($i & 1)
      {
         $ctx1 .= $pw;
      }
      else
      {
         $ctx1 .= substr ($final,0,16);
      }
      if ($i % 3) $ctx1 .= $salt;
      if ($i % 7) $ctx1 .= $pw;
      if ($i & 1)
      {
         $ctx1 .= substr ($final,0,16);
      }
      else
      {
         $ctx1 .= $pw;
      }
      $final = hex2bin (md5 ($ctx1));
   }
   $passwd = "";
   $passwd .= to64 (((ord ($final[0]) << 16) | (ord ($final[6]) << 8) | (ord ($final[12]))), 4);
   $passwd .= to64 (((ord ($final[1]) << 16) | (ord ($final[7]) << 8) | (ord ($final[13]))), 4);
   $passwd .= to64 (((ord ($final[2]) << 16) | (ord ($final[8]) << 8) | (ord ($final[14]))), 4);
   $passwd .= to64 (((ord ($final[3]) << 16) | (ord ($final[9]) << 8) | (ord ($final[15]))), 4);
   $passwd .= to64 (((ord ($final[4]) << 16) | (ord ($final[10]) << 8) | (ord ($final[5]))), 4);
   $passwd .= to64 (ord ($final[11]), 2);
   return "$magic$salt\$$passwd";
}

function create_salt ()
{
   srand ((double) microtime ()*1000000);
   $salt = substr (md5 (rand (0,9999999)), 0, 8);
   return $salt;
}

if (!function_exists('hex2bin')) {
	function hex2bin($str) {
		$len = strlen($str);
		$nstr = "";
		for ($i = 0; $i < $len; $i += 2) {
			$num = sscanf (substr ($str,$i,2), "%x");
			$nstr.= chr ($num[0]);
		}
		return $nstr;
	}
}

function to64 ($v, $n)
{
   $ITOA64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
   $ret = "";
   while (($n - 1) >= 0)
   {
      $n--;
      $ret .= $ITOA64[$v & 0x3f];
      $v = $v >> 6;
   }
   return $ret;
}



?>
