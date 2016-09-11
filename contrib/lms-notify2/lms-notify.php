#!/usr/bin/php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

ini_set('error_reporting', E_ALL&~E_NOTICE);

$parameters = array(
	'C:' => 'config-file:',
	'q' => 'quiet',
	'h' => 'help',
	't' => 'test',
	'f' => 'force',
	'm:' => 'mode:',
	'i:' => 'info:',
	'd:' => 'divisor:',
	'g:' => 'group:',
	'v' => 'version',
);

foreach ($parameters as $key => $val) {
	$val = preg_replace('/:/', '', $val);
	$newkey = preg_replace('/:/', '', $key);
	$short_to_longs[$newkey] = $val;
}
$options = getopt(implode('', array_keys($parameters)), $parameters);
foreach ($short_to_longs as $short => $long)
	if (array_key_exists($short, $options)) {
		$options[$long] = $options[$short];
		unset($options[$short]);
	}

if (array_key_exists('version', $options)) {
	print <<<EOF
lms-notify.php
(C) 2001-2016 LMS Developers

EOF;
	exit(0);
}

if (array_key_exists('help', $options)) {
	print <<<EOF
lms-notify.php
(C) 2001-2016 LMS Developers

-C, --config-file=/etc/lms/lms.ini      alternate config file (default: /etc/lms/lms.ini);
-h, --help                      print this help and exit;
-v, --version                   print version info and exit;
-t, --test			do not send any messages
-m, --mode			notify via: sms, e-mail, warning
-d, --divisor			notify customers with balance less then divisor*liability
-i, --info			notify with template #id
-g, --group			notify customers belongs to selected group
-f, --force			force set warning to customer
-q, --quiet                     suppress any output, except errors

EOF;
	exit(0);
}

$quiet = array_key_exists('quiet', $options);
if (!$quiet) {
	print <<<EOF
lms-notify.php
(C) 2001-2016 LMS Developers

EOF;
}

if (array_key_exists('config-file', $options))
	$CONFIG_FILE = $options['config-file'];
else
	$CONFIG_FILE = '/etc/lms/lms.ini';

if (!$quiet)
	echo "Using file ".$CONFIG_FILE." as config." . PHP_EOL;
define('CONFIG_FILE', $CONFIG_FILE);

$test = array_key_exists('test', $options);
$force = array_key_exists('force', $options);
$mode = (array_key_exists('mode', $options) ? $options['mode'] : 'warning');
$divisor = (array_key_exists('divisor', $options) ? $options['divisor'] : 0);
$info = (array_key_exists('info', $options) ? $options['info'] : NULL);
$group = (array_key_exists('group', $options) ? $options['group'] : NULL);

if (!is_readable($CONFIG_FILE))
	die("Unable to read configuration file [".$CONFIG_FILE."]!" . PHP_EOL);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'].'/lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

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

$debug_email = ConfigHelper::getConfig('mail.debug_email','');
$mail_to     = ConfigHelper::getConfig('mail.mailto','');
$mail_from   = ConfigHelper::getConfig('mail.mailfrom','');
$mail_fname  = ConfigHelper::getConfig('mail.mailfname','');
$footer      = ConfigHelper::getConfig('mail.footer','');

/* ****************************************
   Good place for config value analysis
   ****************************************/


// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'unstrip.php');
//require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'LMS.class.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'SYSLOG.class.php');

if (ConfigHelper::checkConfig('phpui.logging') && class_exists('SYSLOG'))
	$SYSLOG = new SYSLOG($DB);
else
	$SYSLOG = null;

// Initialize Session, Auth and LMS classes

$AUTH = NULL;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
$LMS->ui_lang = $_ui_language;
$LMS->lang = $_language;

$month = intval(strftime("%m", time()));
$year = strftime("%Y", time());
$yearday = strftime("01/%m", time());
$mday = strftime("%s",mktime(0,0,0,$month,1,$year));

/* ********************************************************************
   We should have all hard work here which is being done by our script!
   ********************************************************************/

function parse_data($id,$tresc,$customer) {
	global $LMS, $LANGDEFS;
	$tresc = preg_replace("/\%dzis/", date("d/m/Y"), $tresc);
	$tresc = preg_replace("/\%balance/", sprintf("%6.2f",$customer['balance']), $tresc);
	$tresc = preg_replace("/\%bankaccount/", format_bankaccount($customer['bankaccount']), $tresc);
        if(!(strpos($tresc, '%last_10_in_a_table') === FALSE))
        {
                $last10 = '';
                if($last10_array = $LMS->DB->GetAll('SELECT comment, time, value
                        FROM cash WHERE customerid = ?
                        ORDER BY time DESC LIMIT 10', array($customer['id'])))
                {
                        foreach($last10_array as $r)
                        {
                                $last10 .= date("Y/m/d | ", $r['time']);
                                $last10 .= sprintf("%20s | ", sprintf($LANGDEFS[$LMS->ui_lang]['money_format'], $r['value']));
                                $last10 .= $r['comment']."\n";
                        }
                }
                $tresc = str_replace('%last_10_in_a_table', $last10, $tresc);
        }



	return($tresc);
}
function create_message() {
	global $DB,$mode,$tmpl;
	if ($mode=='sms' or $mode=='e-mail') {
		if ($mode=='e-mail') $type=1; else $type=2;
		$DB->Execute("INSERT INTO messages (type,cdate,subject,body) VALUES (?,?NOW?,?,?)",array($type,$tmpl['name'],$tmpl['message']));
        	return $DB->GetLastInsertID('messages');
	} else 
		return(0);
}
function send_email($msgid, $cid, $rmail, $rname, $subject, $body) {
        global $LMS, $DB, $mail_from, $footer;
        $DB->Execute("INSERT INTO messageitems
                (messageid, customerid, destination, status)
                VALUES (?, ?, ?, ?)",
                array($msgid, $cid, $rmail, 1));
	//echo "$msgid, $cid, $rmail, 1\n";
        $headers = array('From' => $mail_from, 'To' => qp_encode($rname) . ' <' . $rmail . '>',
		'Subject' => $subject, 'X-msgid' => $DB->GetLastInsertID('messageitems'),
		'X-customerid' => $cid, 'Return-receipt-to' => $mail_from);
	if ($footer!='') 
		$body.="\n\n".$footer."\n";
        $result = $LMS->SendMail($rmail, $headers, $body);

        $query = "UPDATE messageitems
                SET status = ?, lastdate = ?NOW?, error = ?
                WHERE messageid = ? AND customerid = ?";

        if (is_string($result))
                $DB->Execute($query, array(3, $result, $msgid, $cid));
        else // MSG_SENT
                $DB->Execute($query, array($result, null, $msgid, $cid));
	return($result);
}
function send_sms($msgid, $cid, $phone, $data) {
        global $LMS, $DB;
        $DB->Execute("INSERT INTO messageitems
                (messageid, customerid, destination, status)
                VALUES (?, ?, ?, ?)",
                array($msgid, $cid, $phone, 1));

        $result = $LMS->SendSMS(str_replace(' ', '', $phone), $data, $msgid);
        $query = "UPDATE messageitems
                SET status = ?, lastdate = ?NOW?, error = ?
                WHERE messageid = ? AND customerid = ?";

        if (preg_match("/[^0-9]/", $result))
                $DB->Execute($query, array(3, $result, $msgid, $cid));
        elseif ($result == 2) // MSG_SENT
                $DB->Execute($query, array($result, null, $msgid, $cid));
	return($result);
}



function send_message($mode,$id,$message,$msgid,$oplata=0) {
	//echo "send_message:\n";
	global $LMS,$DB,$tmpl,$debug_email,$test,$divisor,$force;
	$customer=$LMS->GetCustomer($id);
	if ($mode=='e-mail') {
		$emails=$customer['emails'];
		//print_r($emails);
		if (is_array($emails)) {
			foreach ($emails AS $email) {
				$body=parse_data($id,$tmpl['message'],$customer);
				$name = $customer['lastname'] . ' ' . $customer['name'];
				$email = ($debug_email ? $debug_email : $email['email']);
				if (!$test)
					$result=send_email($msgid,$customer['id'],$email,$name,$tmpl['name'],$body);
				$message.=" $result\n";
			}
		} else {
			if ($test) $message.=" Brak e-maila!\n";
			else $message='';
		}

	} elseif ($mode=='sms') {
		$data=parse_data($id,$tmpl['message'],$customer);
		//echo $data."\n";
		$sms=0;
		if (!is_array($customer['contacts'])) {
			if ($test)
				return($message." Brak nr telefonów!\n");
		}
		foreach ($customer['contacts'] AS $contact) {
			if ($contact['type']==1) {
				$sms=1;
				if (!$test) {
					$result=send_sms($msgid, $customer['id'], $contact['phone'], $data);
				}
				$message.=' '.$contact['phone'];
				if ($oplata) {
					if (!$test) {
						$add=array(
							'value' => -ConfigHelper::getConfig('finances.sms_cost'),
							'userid' => 0,
							'customerid' => $customer['id'],
							'comment' => 'Opłata za monitorowanie płatności - SMS'
						);
						if ($LMS->AddBalance($add))
							$message.=' [K]';
					}
				}
			}
		}
		if (!$sms) {
			if ($test)
				$message.=" Brak nr komórkowych!\n"; 
			else
				$message='';
		} else 
			$message.="\n";
	} else {
		$warning=0;
		$access=1;
		$nodes=$LMS->GetCustomerNodes($customer['id']);
		$groups=$LMS->CustomergroupGetForCustomer($customer['id']);
		if (count($groups)) foreach ($groups AS $group) 
			if ($group['name']=='SILENT') 
				//return(''); #opcja: 
				return("S ".$message."\n");
		if (count($nodes)) foreach ($nodes AS $node) {
			$access*=$node['access'];
			if ($node['warning']) $warning=1;
		}
		if (!$access) {
			//return(''); #opcja: 
			$message="! ".$message."\n";
		} elseif ($warning and !$force) {
			//return(''); #opcja: 
			$message="* ".$message."\n";
		} else {
			if (!$test) {
				$DB->Execute("UPDATE customers SET message='".$tmpl['message']."' WHERE id=".$customer['id']);
				$LMS->NodeSetWarnU($customer['id'],1);
			}
			if ($force) {
				if ($warning) $message="F ".$message."\n";
				else $message="  ".$message."\n";
			} else
				$message="  ".$message."\n";
		}
	}
	return $message;
}


/* ****************************************************************** */
if (!empty($mail_fname))
        $mail_from = qp_encode($mail_fname) . ' <' . $mail_from . '>';
if ($mode!='sms' AND $mode!='e-mail' AND $mode!='warning') {
	echo "Nieznany sposób wysyłki: $mode!\n";
	exit(0);
}
if ($info) {
	$templates=$DB->GetAll("SELECT * FROM templates WHERE id=$info");
	if (count($templates)) 
		$tmpl=$templates[0];	
}
if (!isset($tmpl)) {
	echo "Błędny szablon wiadomości!\n";
	exit(0);
}
//echo $tmpl['message'];
/* ****************************************************************** 
	GŁÓWNA PROCEDURA SEGREGACJI I WYSYŁKI 
   ****************************************************************** */
if (isset($group)) {
	$groups=$DB->GetAll("SELECT * FROM customergroups WHERE name='$group'");
	if (count($groups)!=1) {
		echo "Grupa $group nie istnieje!\n";
		exit(0);
	}
	echo "Wysyłanie powiadomień do grupy '$group' [".$groups[0]['id']."]\n";
	$customers=$DB->GetALL("SELECT * FROM customerassignments WHERE customergroupid=".$groups[0]['id']);
	foreach ($customers AS $customer) {
		$name=$LMS->GetCustomerName($customer['customerid']);
		printf("%4d: %s\n",$customer['customerid'],$name);
	}
} else {
	$customers=$DB->GetAll('SELECT * FROM customers WHERE status=3 and deleted=0');
	if (count($customers) and !$test) {
		$msgid = create_message($tmpl['name'],$tmpl['message']);
	}
	$razem=0;
	$ilosc=0;
	$tresc='';
	foreach ($customers AS $customer) {
		$id=$customer['id'];
		$balance=$LMS->GetCustomerBalance($id);
		$covenant=0;
		$oplata=0;
		$assignments=$LMS->GetCustomerAssignments($id,true);
		//if ($customer['id']==2) print_r($assignments);
		if (is_array($assignments)) foreach ($assignments as $assignment) {
			if ($assignment['liabilityid']){
				//print $assignment['period']."\n";
				if (($assignment['period']=='rocznie' and $assignment['at']==$yearday) OR
				    ($assignment['period']=='jednorazowo' and $assignment['at']==$mday))
					$covenant+=$assignment['discounted_value'];
				//else echo "Poza zakresem!: '".$assignment['at']."'/'".$yearday."'\n";
			} elseif ($assignment['datefrom']==0 or $assignment['datefrom']<time()) {
				if ($assignment['period']=='rocznie')
					$covenant+=11*$assignment['discounted_value'];
				elseif ($assignment['period']=='kwartalnie')
					$covenant+=3*$assignment['discounted_value'];
				else
					$covenant+=$assignment['discounted_value'];
				if (preg_match('/'.ConfigHelper::getConfig('finances.tariff_regexp').'/i',$assignment['name'])) {
					$oplata=1;
				}
			}
		}
		if ($covenant>0 and $balance<0 and $balance<=-$covenant*$divisor) {
			$ilosc++;
			$razem+=$balance;
			$message=sprintf ("%4d %-50s saldo:%8.2f (abo:%8.2f) ",$customer['id'],$customer['lastname'].' '.$customer['name'],$balance,$covenant);
			$tresc.=send_message($mode,$customer['id'],$message,$msgid,$oplata);
		}
	}
	$tresc=sprintf("Łącznie %d klientów na kwotę %2.2f:",$ilosc,-$razem)."\n".$tresc;
	$tytul=ucfirst($mode).' o zadłużeniu: ';
	//if ($divisor>0) 
	$tytul.=sprintf('%3d%%|%4d|%9.2f',$divisor*100,$ilosc,-$razem);
	if ($test) {
		echo $tytul."\n".$tresc;
		$tytul='TEST: '.$tytul;
	}
	if (!$test) {
		$to=$mail_to;
		$headers = array('From' => $mail_from, 'To' => $to, 'Subject' => $tytul);
		$LMS->SendMail($to,$headers,$tresc);
	}
}
?>
