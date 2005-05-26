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

$DB->BeginTrans();

foreach($LMS->CONFIG['phpui'] as $key => $val)
{
	switch($key)
	{
		case 'allow_from':
			$desc = trans('List of networks and IP addresses, with access to LMS. If empty, every IP address has access to LMS. When you write list of addresses or address classes here, LMS will dismiss every unwanted user with HTTP 403 error.');
		break;
		
		case 'lang':
			$desc = trans('User interface language code. If not set, language will be determined on browser settings. Default: en.');
		break;
		
		case 'timeout':
			$desc = trans('WWW session timeout. After that time (in seconds) user will be logged out if action has been made. Default: 600.');
		break;
		
		case 'customerlist_pagelimit':
			$desc = trans('Limit of records displayed on one page in customers list. Default: no limit.');
		break;
		
		case 'nodelist_pagelimit':
			$desc = trans('Limit of records displayed on one page in nodes list. Default: no limit.');
		break;
		
		case 'balancelist_pagelimit':
			$desc = trans('Limit of records displayed on one page in customer\'s balance. Default: 100.');
		break;
		
		case 'configlist_pagelimit':
			$desc = trans('Limit of records displayed on one page in UI config options list. Default: 100.');
		break;
		
		case 'invoicelist_pagelimit':
			$desc = trans('Limit of records displayed on one page in invoices list. Default: 100.');
		break;
		
		case 'ticketlist_pagelimit':
			$desc = trans('Limit of records displayed on one page in tickets (requests) list. Default: 100.');
		break;
		
		case 'accountlist_pagelimit':
			$desc = trans('Limit of records displayed on one page in accounts list. Default: 100.');
		break;
		
		case 'domainlist_pagelimit':
			$desc = trans('Limit of records displayed on one page in domains list. Default: 100.');
		break;
		
		case 'aliaslist_pagelimit':
			$desc = trans('Limit of records displayed on one page in aliases list. Default: 100.');
		break;
		
		case 'networkhosts_pagelimit':
			$desc = trans('Limit of nodes displayed on one page in Network Information. Default: 256. With 0, this information is omitted (page is displaying faster).');
		break;
		
		case 'force_ssl':
			$desc = trans('SSL Enforcing. Setting this option to 1 will effect with that LMS will enforce SSL connection with redirect to \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI] at every request without SSL. Default: 0 (off).');
		break;
		
		case 'reload_type':
			$desc = trans('Reload type. Allowed values: exec - call some command (most often with sudo, some script or something else, configurable below); sql - writes down to SQL (multiple queries separated with semicolon may be setup).');
		break;
		
		case 'reload_execcmd':
			$desc = trans('Command to run during reload, if reload_type is set to \'exec\'. By default /bin/true. That string is sent to command system(), so I propose you to think what you do and how :) Altogether, semicolons should be parsed by bash, but LMS splits that string and execute commands separately.');
		break;
		
		case 'reload_sqlquery':
			$desc = trans('SQL query executed while reload, if reload_type = sql. By default, query inserts into table \'timestamps\' value \'_force\'. You can use \'%TIME%\' as replacement to current unix timestamp. WARNING! Semicolon is handled as query separator, which means that you can enter couple of SQL queries separated by semicolon sign.');
		break;
		
		case 'allow_mac_sharing':
			$desc = trans('Allow nodes addition with duplicated MAC address (not checking that some computer have that MAC yet). Default: 0 (off).');
		break;
		
		case 'default_zip':
		case 'default_city':
		case 'default_address':
			$desc = trans('Default zip code, city, street, used while inserting new customer. Useful if you add majority of customers with the same street.');
		break;
		
		case 'lastonline_limit':
			$desc = trans('Specify time (in seconds), after which node will be marked offline. It should match with frequency of running nodes activity script (i.e. lms-fping). Default: 600.');
		break;

		case 'use_current_payday':
			$desc = trans('Qualify to use current day of month for payment day instead of most often used day. Default: 0 (off).');
		break;
		
		case 'smarty_debug':
			$desc = trans('Enable Smarty\'s debug console. Useful for tracking values passed from PHP to Smarty. Default: 0 (off).');
		break;
		
		case 'debug_email':
			$desc = trans('E-mail address for debugging - messages from \'Mailing\' module will be sent at this address, instead to real users.');
		break;
		
		case 'arpd_servers':
			$desc = trans('List of arpd servers for MAC addresses retrieval from remote networks. That list should include IP[:port] items separated with spaces. Default: empty.');
		break;
		
		case 'helpdesk_backend_mode':
			$desc = trans('When enabled, all messages in helpdesk system (except those sent to requester) will be sent to mail server corresponding queue address. lms-rtparser script should be running on server. Messages won\'t be written directly to database, but on solely responsibility of rtparser script. Default: disabled.');
		break;

		case 'helpdesk_sender_name':
			$desc = trans('Name of messages sender or predefined variables: "queue" - queue name, "user" - logged user name. Default: none.');
		break;

		case 'newticket_notify':
			$desc = trans('When enabled, system will sent notification to all users with rights for current queue after new ticket creation. Default: disabled.');
		break;
		
		case 'contract_template':
			$desc = trans('Specify customer contract template. It can include comma separated list of contract templates with their names. Default: contract.html.');
		break;
		
		case 'to_words_short_version':
			$desc = trans('Specify format of verbal amounts representation (on invoices). e.g. for value "1" verbal expand of 123,15 will be "one two thr 15/100". Default: 0.');
		break;
		
		case 'lang_debug':
			$desc = trans('Enable LMS language console. Useful for tracking missing translation strings. Default: 0 (off).');
		break;
		
		case 'timetable_days_forward':
			$desc = trans('Number of days (including current day) on timetable. Default: 7.');
		break;

		case 'gd_translate_to':
			$desc = trans('Charset of data gd library expects (useful if gd library needs ISO-8859-2 instead of UTF-8 to feed imagetext() function).');
		break;					

		case 'nodepassword_length':
			$desc = trans('Length of (auto-generated) node password. Max.32. Default: 16.');
		break;					
		
		case 'custom_accesstable':
			$desc = trans('PHP file with user-defined access rules in "lib" directory. Default: empty.');
		break;					

		case 'check_for_updates_period':
			$desc = trans('How often to check for LMS updates (in seconds). Default: 86400.');
		break;					

		case 'map_type':
			$desc = trans('Network map type. Use "flash" if you have Ming library or "gd" if your PHP supports gdlib. By default LMS will try to generate flash map, with fallback to GD if it fails.');
		break;					

		case 'homedir_prefix':
			$desc = trans('Prefix for account home directory. Default: /home/');
		break;					

		case 'smtp_port':
		case 'smtp_host':
		case 'smtp_username':
		case 'smtp_password':
		case 'smtp_auth_type':
			$desc = trans('SMTP settings.');
		break;					
		
		default:
			$desc = trans('Unknown option. No description.');
		break;
	}
	
	$DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
			array('phpui', $key, $val, $desc)
			);

}

/*
foreach($LMS->CONFIG['directories'] as $key => $val)
{
    switch($key)
    {
     case 'sys_dir':
         $desc = 'Katalog systemowy. Jest to miejsce gdzie jest ca³a zawarto¶æ UI LMS\'a, czyli index.php, grafiki, templejty i reszta. Domy¶lnie index.php stara siê sam odnale¼æ w filesystemie u¿ywaj±c getcwd(), ale lepiej by by³o gdyby mu powiedzieæ gdzie jest';
         break;
     case 'modules_dir':
         $desc = 'Katalog z "modu³ami" LMS\'a - kawa³kami kodu które szumnie kto¶ (czyli Baseciq) nazwa³ modu³ami. Domy¶lnie jest to podkatalog modules w sys_dir';
         break;
     case 'lib_dir':
         $desc = 'Katalog z "bibliotekami" LMS\'a. Czyli zawarto¶æ katalogu lib. Domy¶lnie to podkatalog lib w sys_dir';
         break;
     case 'backup_dir':
         $desc = 'Katalog z backupami SQL\'owymi - miejsce gdzie LMS zapisuje dumpy z bazy. Domy¶lnie jest to podkatalog "backups". Naprawdê dobrze by by³o go przenie¶æ poza miejsce osi±galne przez przegl±darkê';
         break;
     case 'config_templates_dir':
         $desc = 'Katalog z templejtami plików konfiguracyjnych. Nieu¿ywana';
         break;
     case 'smarty_dir':
         $desc = 'Katalog z bibliotek± Smarty - domy¶lnie podkatalog Smarty w lib_dir';
         break;
     case 'smarty_compile_dir':
         $desc = 'Katalog kompilacji Smartyego. Miejsce gdzie Smarty psuje nasze templejty. Domy¶lnie to templates_c w katalogu sysdir';
         break;
     case 'smarty_templates_dir':
         $desc = 'Katalog z templejtami którymi karmimy Smartiego. Domy¶lnie to podkatalog templates z sys_dir';
         break;
     default:
         $desc = 'Nieznana opcja. Brak opisu';
         break;
    }    
    
    $DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
          array('directories', $key, $val, $desc)
          );
}
*/

foreach($LMS->CONFIG['invoices'] as $key => $val)
{
	switch($key)
	{
		case 'header':
			$desc = trans('This is a seller data. A new line replacement is "\n" sign, e.g. header = SuperNet ISP\n00-950 Warsaw\nWiosenna 52\n0 49 3883838\n\naccounting@supernet.pl\n\nNIP: 123-123-12-23');
		break;
		
		case 'footer':
			$desc = trans('Small font footer will appear at the bottom of page, e.g. footer = Our Bank: SNETISP, 828823917293871928371\nPhone number 555 123 123');
		break;
		
		case 'default_author':
			$desc = trans('Default invoice issuer');
		break;
		
		case 'number_template':
			$desc = trans('Invoice number template. Default: number/LMS/year, ie. %N/LMS/%Y. Allowed variables: %N - successive number in year, %M - drow-up month, %Y - drow-up year.');
		break;
		
		case 'cplace':
			$desc = trans('Invoice draw-up place.');
		break;
		
		case 'template_file':
			$desc = trans('Invoice template file. Default: "invoice.html". Should be placed in templates directory.');
		break;
		
		case 'content_type':
			$desc = trans('Content-type for invoice. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".');
		break;
			
		case 'attachment_name':
			$desc = trans('File name for saving finished invoice printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print invoice, and prompt for save on disk + bonus browser crash (6.0SP1 on WinXP). Default: empty.');
		break;
		
		case 'monthly_numbering':
			$desc = trans('Enabling this option will reset numbering of invoices at beginning of every month.');
		break;
		
		default:
			$desc = trans('Unknown option. No description.');
		break;
	
	}
	
	$DB->Execute('INSERT INTO uiconfig(section, var, value, description) VALUES(?,?,?,?)',
			array('invoices', $key, $val, $desc)
			);
}

$DB->CommitTrans();

header('Location: ?m=configlist');

?>
