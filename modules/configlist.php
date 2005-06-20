<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function GetConfigList($order='var,asc')
{
	global $DB;

	list($order, $direction) = sscanf($order, '%[^,],%s');
	
	$direction = ($direction != 'desc') ? 'asc' : 'desc';

	switch($order)
	{
		case 'section':
			$sqlord = " ORDER BY section $direction, var";
		break;
		default:
			$sqlord = " ORDER BY var $direction";
		break;
	}

	$config = $DB->GetAll('SELECT id, section, var, value, description as usercomment, disabled FROM uiconfig'.$sqlord);

	foreach ($config as $idx => $item) 
	{
		switch($item['section'])
		{
			case 'phpui':
				switch($item['var'])
				{
				case 'allow_from':
					$config[$idx]['description'] = trans('List of networks and IP addresses, with access to LMS. If empty, every IP address has access to LMS. When you write list of addresses or address classes here, LMS will dismiss every unwanted user with HTTP 403 error.');
				break;
				
				case 'lang':
					$config[$idx]['description'] = trans('User interface language code. If not set, language will be determined on browser settings. Default: en.');
				break;
				
				case 'timeout':
					$config[$idx]['description'] = trans('WWW session timeout. After that time (in seconds) user will be logged out if action has been made. Default: 600.');
				break;
				
				case 'customerlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in customers list. Default: no limit.');
				break;
				
				case 'nodelist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in nodes list. Default: no limit.');
				break;
				
				case 'balancelist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in customer\'s balance. Default: 100.');
				break;
				
				case 'configlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in UI config options list. Default: 100.');
				break;
				
				case 'invoicelist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in invoices list. Default: 100.');
				break;
				
				case 'ticketlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in tickets (requests) list. Default: 100.');
				break;
				
				case 'accountlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in accounts list. Default: 100.');
				break;
				
				case 'domainlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in domains list. Default: 100.');
				break;
				
				case 'aliaslist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in aliases list. Default: 100.');
				break;

				case 'receiptlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in cash receipts list. Default: 100.');
				break;
				
				case 'networkhosts_pagelimit':
					$config[$idx]['description'] = trans('Limit of nodes displayed on one page in Network Information. Default: 256. With 0, this information is omitted (page is displaying faster).');
				break;
				
				case 'force_ssl':
					$config[$idx]['description'] = trans('SSL Enforcing. Setting this option to 1 will effect with that LMS will enforce SSL connection with redirect to \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI] at every request without SSL. Default: 0 (off).');
				break;
				
				case 'reload_type':
					$config[$idx]['description'] = trans('Reload type. Allowed values: exec - call some command (most often with sudo, some script or something else, configurable below); sql - writes down to SQL (multiple queries separated with semicolon may be setup).');
				break;
				
				case 'reload_execcmd':
					$config[$idx]['description'] = trans('Command to run during reload, if reload_type is set to \'exec\'. By default /bin/true. That string is sent to command system(), so I propose you to think what you do and how :) Altogether, semicolons should be parsed by bash, but LMS splits that string and execute commands separately.');
				break;
				
				case 'reload_sqlquery':
					$config[$idx]['description'] = trans('SQL query executed while reload, if reload_type = sql. By default, query inserts into table \'timestamps\' value \'_force\'. You can use \'%TIME%\' as replacement to current unix timestamp. WARNING! Semicolon is handled as query separator, which means that you can enter couple of SQL queries separated by semicolon sign.');
				break;
				
				case 'allow_mac_sharing':
					$config[$idx]['description'] = trans('Allow nodes addition with duplicated MAC address (not checking that some computer have that MAC yet). Default: 0 (off).');
				break;
				
				case 'default_zip':
				case 'default_city':
				case 'default_address':
					$config[$idx]['description'] = trans('Default zip code, city, street, used while inserting new customer. Useful if you add majority of customers with the same street.');
				break;
				
				case 'lastonline_limit':
					$config[$idx]['description'] = trans('Specify time (in seconds), after which node will be marked offline. It should match with frequency of running nodes activity script (i.e. lms-fping). Default: 600.');
				break;

				case 'use_current_payday':
					$config[$idx]['description'] = trans('Qualify to use current day of month for payment day instead of most often used day. Default: 0 (off).');
				break;
				
				case 'smarty_debug':
					$config[$idx]['description'] = trans('Enable Smarty\'s debug console. Useful for tracking values passed from PHP to Smarty. Default: 0 (off).');
				break;
				
				case 'debug_email':
					$config[$idx]['description'] = trans('E-mail address for debugging - messages from \'Mailing\' module will be sent at this address, instead to real users.');
				break;
				
				case 'arpd_servers':
					$config[$idx]['description'] = trans('List of arpd servers for MAC addresses retrieval from remote networks. That list should include IP[:port] items separated with spaces. Default: empty.');
				break;
				
				case 'helpdesk_backend_mode':
					$config[$idx]['description'] = trans('When enabled, all messages in helpdesk system (except those sent to requester) will be sent to mail server corresponding queue address. lms-rtparser script should be running on server. Messages won\'t be written directly to database, but on solely responsibility of rtparser script. Default: disabled.');
				break;

				case 'helpdesk_sender_name':
					$config[$idx]['description'] = trans('Name of messages sender or predefined variables: "queue" - queue name, "user" - logged user name. Default: none.');
				break;

				case 'newticket_notify':
					$config[$idx]['description'] = trans('When enabled, system will sent notification to all users with rights for current queue after new ticket creation. Default: disabled.');
				break;
				
				case 'contract_template':
					$config[$idx]['description'] = trans('Specify customer contract template. It can include comma separated list of contract templates with their names. Default: contract.html.');
				break;
				
				case 'to_words_short_version':
					$config[$idx]['description'] = trans('Specify format of verbal amounts representation (on invoices). e.g. for value "1" verbal expand of 123,15 will be "one two thr 15/100". Default: 0.');
				break;
				
				case 'lang_debug':
					$config[$idx]['description'] = trans('Enable LMS language console. Useful for tracking missing translation strings. Default: 0 (off).');
				break;
				
				case 'timetable_days_forward':
					$config[$idx]['description'] = trans('Number of days (including current day) on timetable. Default: 7.');
				break;

				case 'gd_translate_to':
					$config[$idx]['description'] = trans('Charset of data gd library expects (useful if gd library needs ISO-8859-2 instead of UTF-8 to feed imagetext() function).');
				break;					

				case 'nodepassword_length':
					$config[$idx]['description'] = trans('Length of (auto-generated) node password. Max.32. Default: 16.');
				break;					
				
				case 'custom_accesstable':
					$config[$idx]['description'] = trans('PHP file with user-defined access rules in "lib" directory. Default: empty.');
				break;					

				case 'check_for_updates_period':
					$config[$idx]['description'] = trans('How often to check for LMS updates (in seconds). Default: 86400.');
				break;					

				case 'map_type':
					$config[$idx]['description'] = trans('Network map type. Use "flash" if you have Ming library or "gd" if your PHP supports gdlib. By default LMS will try to generate flash map, with fallback to GD if it fails.');
				break;					

				case 'homedir_prefix':
					$config[$idx]['description'] = trans('Prefix for account home directory. Default: /home/');
				break;					

				case 'smtp_port':
				case 'smtp_host':
				case 'smtp_username':
				case 'smtp_password':
				case 'smtp_auth_type':
					$config[$idx]['description'] = trans('SMTP settings.');
				break;					

				case 'default_taxrate':
					$config[$idx]['description'] = trans('Value of tax rate which will be selected by default on tax rates lists. Default: 22.0');
				break;					
				
				default:
					$config[$idx]['description'] = trans('Unknown option. No description.');
				break;
			} //end: var
			break;

/*
	case 'directories':
    switch($item['var'])
    {
     case 'sys_dir':
         $config[$idx]['description'] = 'Katalog systemowy. Jest to miejsce gdzie jest ca³a zawarto¶æ UI LMS\'a, czyli index.php, grafiki, templejty i reszta. Domy¶lnie index.php stara siê sam odnale¼æ w filesystemie u¿ywaj±c getcwd(), ale lepiej by by³o gdyby mu powiedzieæ gdzie jest';
         break;
     case 'modules_dir':
         $config[$idx]['description'] = 'Katalog z "modu³ami" LMS\'a - kawa³kami kodu które szumnie kto¶ (czyli Baseciq) nazwa³ modu³ami. Domy¶lnie jest to podkatalog modules w sys_dir';
         break;
     case 'lib_dir':
         $config[$idx]['description'] = 'Katalog z "bibliotekami" LMS\'a. Czyli zawarto¶æ katalogu lib. Domy¶lnie to podkatalog lib w sys_dir';
         break;
     case 'backup_dir':
         $config[$idx]['description'] = 'Katalog z backupami SQL\'owymi - miejsce gdzie LMS zapisuje dumpy z bazy. Domy¶lnie jest to podkatalog "backups". Naprawdê dobrze by by³o go przenie¶æ poza miejsce osi±galne przez przegl±darkê';
         break;
     case 'config_templates_dir':
         $config[$idx]['description'] = 'Katalog z templejtami plików konfiguracyjnych. Nieu¿ywana';
         break;
     case 'smarty_dir':
         $config[$idx]['description'] = 'Katalog z bibliotek± Smarty - domy¶lnie podkatalog Smarty w lib_dir';
         break;
     case 'smarty_compile_dir':
         $config[$idx]['description'] = 'Katalog kompilacji Smartyego. Miejsce gdzie Smarty psuje nasze templejty. Domy¶lnie to templates_c w katalogu sysdir';
         break;
     case 'smarty_templates_dir':
         $config[$idx]['description'] = 'Katalog z templejtami którymi karmimy Smartiego. Domy¶lnie to podkatalog templates z sys_dir';
         break;
     default:
         $config[$idx]['description'] = 'Nieznana opcja. Brak opisu';
         break;
    }    
	break;
*/
			case 'finances':
				switch($item['var'])
				{
					case 'suspension_percentage':
						$config[$idx]['description'] = trans('Percentage of suspended liabilities. Default: 0');
					break;
				
					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			case 'invoices':
				switch($item['var'])
				{
					case 'header':
						$config[$idx]['description'] = trans('This is a seller data. A new line replacement is "\n" sign, e.g. header = SuperNet ISP\n00-950 Warsaw\nWiosenna 52\n0 49 3883838\n\naccounting@supernet.pl\n\nNIP: 123-123-12-23');
					break;
					
					case 'footer':
						$config[$idx]['description'] = trans('Small font footer will appear at the bottom of page, e.g. footer = Our Bank: SNETISP, 828823917293871928371\nPhone number 555 123 123');
					break;
					
					case 'default_author':
						$config[$idx]['description'] = trans('Default invoice issuer');
					break;
					
					case 'number_template':
						$config[$idx]['description'] = trans('Document number template. Default: number/LMS/year, ie. %N/LMS/%Y. Allowed variables: %N - successive number in year, %M - drow-up month, %Y - drow-up year.');
					break;
					
					case 'cplace':
						$config[$idx]['description'] = trans('Invoice draw-up place.');
					break;
					
					case 'template_file':
						$config[$idx]['description'] = trans('Invoice template file. Default: "invoice.html". Should be placed in templates directory.');
					break;
					
					case 'content_type':
						$config[$idx]['description'] = trans('Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".');
					break;
						
					case 'attachment_name':
						$config[$idx]['description'] = trans('File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk + bonus browser crash (6.0SP1 on WinXP). Default: empty.');
					break;
					
					case 'monthly_numbering':
						$config[$idx]['description'] = trans('Enabling this option will reset numbering of documents at beginning of every month.');
					break;
					
					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			case 'receipts':
				switch($item['var'])
				{
					case 'number_template':
						$config[$idx]['description'] = trans('Document number template. Default: number/LMS/year, ie. %N/LMS/%Y. Allowed variables: %N - successive number in year, %M - drow-up month, %Y - drow-up year.');
					break;
					
					case 'template_file':
						$config[$idx]['description'] = trans('Cash receipt template file. Default: "receipt.html". Should be placed in templates directory.');
					break;
					
					case 'content_type':
						$config[$idx]['description'] = trans('Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".');
					break;
						
					case 'attachment_name':
						$config[$idx]['description'] = trans('File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk + bonus browser crash (6.0SP1 on WinXP). Default: empty.');
					break;
					
					case 'monthly_numbering':
						$config[$idx]['description'] = trans('Enabling this option will reset numbering of documents at beginning of every month.');
					break;
					
					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			default:
				$config[$idx]['description'] = trans('Unknown option. No description.');
			break;
		} //end: section
	} //end: foreach

	$config['total'] = sizeof($config);
	$config['order'] = $order;
	$config['direction'] = $direction;

	return $config;
}

$layout['pagetitle'] = trans('User Interface Configuration');

if(!isset($_GET['o']))
	$SESSION->restore('clo', $o);
else
	$o = $_GET['o'];
$SESSION->save('clo', $o);

if ($SESSION->is_set('clp') && !isset($_GET['page']))
	$SESSION->restore('clp', $_GET['page']);

$configlist = GetConfigList($o);
$listdata['total'] = $configlist['total'];
$listdata['order'] = $configlist['order'];
$listdata['direction'] = $configlist['direction'];
unset($configlist['total']);
unset($configlist['order']);
unset($configlist['direction']);
	    
$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = (! $LMS->CONFIG['phpui']['configlist_pagelimit'] ? $listdata['total'] : $LMS->CONFIG['phpui']['configlist_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('clp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('configlist', $configlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('layout',$layout);
$SMARTY->display('configlist.html');

?>
