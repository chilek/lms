<?php

/*
 * LMS version 1.11-git
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

function GetConfigList($order='var,asc', $section='', $search='')
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

	$config = $DB->GetAll('SELECT id, section, var, value, description as usercomment, disabled 
			FROM uiconfig WHERE section != \'userpanel\''
			.($section ? ' AND section = '.$DB->Escape($section) : '')
			.($search ? ' AND var ?LIKE? '.$DB->Escape('%'.$search.'%') : '')
			.$sqlord);

	if($config) foreach ($config as $idx => $item)
	{
		switch($item['section'])
		{
			case 'phpui':
				switch($item['var'])
				{	
				case 'autosuggest_max_length':
					$config[$idx]['description'] = trans('Max length of auto suggest proposal, further characters will be dotted.');
				break;

				case 'default_autosuggest_placement':
					$config[$idx]['description'] = trans('Default placement of suggestion window (left/right/top/bottom)');
				break;

				case 'allow_from2':
					$config[$idx]['description'] = trans('List of networks and IP addresses, with access to LMS. If empty, every IP address has access to LMS. When you write list of addresses or address pools here, LMS will dismiss every unwanted user with HTTP 403 error.');
				break;

				case 'allow_from':
					$config[$idx]['description'] = trans('List of networks and IP addresses, with access to LMS. If empty, every IP address has access to LMS. When you write list of addresses or address pools here, LMS will dismiss every unwanted user with HTTP 403 error.');
				break;

				case 'lang':
					$config[$idx]['description'] = trans('System language code. If not set, language will be determined on browser settings. Default: en.');
				break;

				case 'timeout':
					$config[$idx]['description'] = trans('WWW session timeout. After that time (in seconds) user will be logged out if action has been made. Default: 600.');
				break;

				case 'customerlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in customers list. Default: 100.');
				break;

				case 'nodelist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in nodes list. Default: 100.');
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

				case 'taxratelist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in tax rates list. Default: 100.');
				break;
				
				case 'numberplanlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in numbering plans list. Default: 100.');
				break;

				case 'divisionlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in divisions list. Default: 100.');
				break;

				case 'documentlist_pagelimit':
					$config[$idx]['description'] = trans('Limit of records displayed on one page in documents list. Default: 100.');
				break;

				case 'networkhosts_pagelimit':
					$config[$idx]['description'] = trans('Limit of nodes displayed on one page in Network Information. Default: 256. With 0, this information is omitted (page is displaying faster).');
				break;

				case 'force_ssl':
					$config[$idx]['description'] = trans('SSL Enforcing. Setting this option to 1 will effect with that LMS will enforce SSL connection with redirect to \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI] at every request without SSL. Default: 0 (off).');
				break;

				case 'reload_timer':
					$config[$idx]['description'] = trans('Reload timer. If set to true it will display remaining time to configuration reload. If using more than one host, remember to sync time between them.');
				break;
				
				case 'reload_type':
					$config[$idx]['description'] = trans('Reload type. Allowed values: exec - call some command (most often with sudo, some script or something else, configurable below); sql - writes down to SQL (multiple queries separated with semicolon may be setup).');
				break;
				
				case 'reload_execcmd':
					$config[$idx]['description'] = trans('Command to run during reload, if reload_type is set to \'exec\'. By default /bin/true. That string is sent to command system(), so I propose you to think what you do and how :) Altogether, semicolons should be parsed by bash, but LMS splits that string and execute commands separately.');
				break;
				
				case 'reload_sqlquery':
					$config[$idx]['description'] = trans('SQL query executed while reload, if reload_type = sql. Default: empty. You can use \'%TIME%\' as replacement to current unix timestamp. WARNING! Semicolon is handled as query separator, which means that you can enter couple of SQL queries separated by semicolon sign.');
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
					$config[$idx]['description'] = trans('Qualify to use current day of month for payment day. Default: 0 (off).');
				break;

				case 'default_monthly_payday':
					$config[$idx]['description'] = trans('Qualify the day of month for payment day. Default: 0 (undefined).');
				break;
				
				case 'smarty_debug':
					$config[$idx]['description'] = trans('Enable Smarty\'s debug console. Useful for tracking values passed from PHP to Smarty. Default: 0 (off).');
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
				
				case 'to_words_short_version':
					$config[$idx]['description'] = trans('Specify format of verbal amounts representation (on invoices). e.g. for value "1" verbal expand of 123,15 will be "one two thr 15/100". Default: 0.');
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

				case 'default_taxrate':
					$config[$idx]['description'] = trans('Value of tax rate which will be selected by default on tax rates lists. Default: 22.0');
				break;

				case 'default_prodid':
					$config[$idx]['description'] = trans ('Value of product ID. Default: empty');
				break;

				case 'helpdesk_reply_body':
					$config[$idx]['description'] = trans('Adds body of message in ticket reply. Default: false');
				break;

				case 'big_networks':
					$config[$idx]['description'] = trans('Support for big ISPs e.g. hidding long customers selection dropdowns. Default: false');
				break;

				case 'short_pagescroller':
					$config[$idx]['description'] = trans('Enables page scroller designed for lists with very big number of pages. Default: false');
				break;

				case 'ewx_support':
					$config[$idx]['description'] = trans('Support for EtherWerX devices. Default: false');
				break;

				case 'helpdesk_stats':
					$config[$idx]['description'] = trans('Adds helpdesk requests causes stats on ticket view and print pages. Default: true');
				break;

				case 'helpdesk_customerinfo':
					$config[$idx]['description'] = trans('Adds customer basic information on ticket view and in notifications. Default: true');
				break;

				case 'ticket_template_file':
					$config[$idx]['description'] = trans('Helpdesk ticket printout template file. Default: rtticketprint.html');
				break;
			
				case 'ticketlist_status':
					$config[$idx]['description'] = trans('Default status filter setting on tickets list. For allowed values see html source code. Default: not set');
				break;

				case 'use_invoices':
					$config[$idx]['description'] = trans('Makes option "with invoice" checked by default. Default: false');
				break;

				case 'default_module':
					$config[$idx]['description'] = trans('Start-up module (filename from /modules without .php). Default: welcome');
				break;

				case 'default_assignment_period':
					$config[$idx]['description'] = trans('Default period value for assignment. Default: 0');
				break;

				case 'arp_table_backend':
					$config[$idx]['description'] = trans('Command which returns IP-MAC bindings. Default: internal backend');
				break;

				case 'report_type':
					$config[$idx]['description'] = trans('Documents type. You can use "html" or "pdf". Default: html.');
				break;

				case 'hide_toolbar':
					$config[$idx]['description'] = trans('Hide toolbar from user interface. Default: false.');
				break;

				case 'logging':
					$config[$idx]['description'] = trans('Does this LMS have transaction log support (not opensource). Default: false.');
				break;
			
				case 'add_customer_group_required':
					$config[$idx]['description'] = trans('If isset "true" when adding new customer select group is required. Default "false"');
				break;

				case 'event_max_userlist_size':
					$config[$idx]['description'] = trans('Automatically adjusts the size of the selection list to the number of users when set to 0.');
				break;

				case 'ping_type':
					$config[$idx]['description'] = trans('Default ping type. You can use "1" for ping or "2" for arping. Default: 1.');
				break;
				
                                case 'default_teryt_city':
					$config[$idx]['description'] = trans('Default City in TERYT. Set city id in TERYT.');
				break;

				default:
					$config[$idx]['description'] = trans('Unknown option. No description.');
				break;
			} //end: var
			break;

			case 'payments':
				switch($item['var'])
				{
					case 'date_format':
						$config[$idx]['description'] = trans('Define date format for variable: %period, %aligned_period, %current_month used in payments.comment and payments.settlement_comment');
					break;

					case 'default_unit_name':
						$config[$idx]['description'] = trans('Unit name on invoice, default: "pcs."');
					break;

					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			case 'finances':
				switch($item['var'])
				{
					case 'suspension_percentage':
						$config[$idx]['description'] = trans('Percentage of suspended liabilities. Default: 0');
					break;

					case 'cashimport_checkinvoices':
						$config[$idx]['description'] = trans('Check invoices as accounted when importing cash operations. Default: false');
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
						$config[$idx]['description'] = trans('This is a seller data. A new line replacement is "\n" sign, e.g. SuperNet ISP\n00-950 Warsaw\nWiosenna 52\n0 49 3883838\n\naccounting@supernet.pl\n\nNIP: 123-123-12-23');
					break;

					case 'footer':
						$config[$idx]['description'] = trans('Small font text appearing in selected (in template) place of the invoice, e.g. Our Bank: SNETISP, 828823917293871928371\nPhone number 555 123 123');
					break;

					case 'default_author':
						$config[$idx]['description'] = trans('Default invoice issuer');
					break;

					case 'cplace':
						$config[$idx]['description'] = trans('Invoice draw-up place.');
					break;

					case 'template_file':
						$config[$idx]['description'] = trans('Invoice template file. Default: "invoice.html". Should be placed in templates directory.');
					break;

					case 'cnote_template_file':
						$config[$idx]['description'] = trans('Credit note template file. Default: "invoice.html". Should be placed in templates directory.');
					break;

					case 'content_type':
						$config[$idx]['description'] = trans('Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".');
					break;

					case 'attachment_name':
						$config[$idx]['description'] = trans('File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk. Default: empty.');
					break;

					case 'type':
						$config[$idx]['description'] = trans('Documents type. You can use "html" or "pdf". Default: html.');
					break;

					case 'print_balance_history':
						$config[$idx]['description'] = trans('If true on invoice (html) will be printed history of financial operations on customer account. Default: not set.');
					break;

					case 'print_balance_history_limit':
						$config[$idx]['description'] = trans('Number of Records on customer balance list on invoice. Specify last x records. Default: 10.');
					break;

					case 'default_printpage':
						$config[$idx]['description'] = trans('Coma-separated list of default invoice printout pages. You can use "original", "copy", "duplicate". Default: "original,copy".');
					break;

					case 'radius':
						$config[$idx]['description'] = trans('Enable RADIUS support. Default: 1');
					break;

					case 'public_ip':
						$config[$idx]['description'] = trans('Enable public IP address fields. Default: 1');
					break;

					case 'paytime':
						$config[$idx]['description'] = trans('Default documents paytime in days. Default: 14');
					break;

					case 'paytype':
						$config[$idx]['description'] = trans('Default invoices paytype. Default: "1" (cash)');
					break;

					case 'customer_bankaccount':
						$config[$idx]['description'] = trans('Show bankaccount on invoice. Default: 0');
					break;

					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			case 'notes':
				switch($item['var'])
				{
					case 'template_file':
						$config[$idx]['description'] = trans('Debit note template file. Default: "note.html". Should be placed in templates directory.');
					break;

					case 'content_type':
						$config[$idx]['description'] = trans('Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".');
					break;

					case 'attachment_name':
						$config[$idx]['description'] = trans('File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk. Default: empty.');
					break;

					case 'type':
						$config[$idx]['description'] = trans('Documents type. You can use "html" or "pdf". Default: html.');
					break;

					case 'paytime':
						$config[$idx]['description'] = trans('Default documents paytime in days. Default: 14');
					break;

					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			case 'receipts':
				switch($item['var'])
				{
					case 'template_file':
						$config[$idx]['description'] = trans('Cash receipt template file. Default: "receipt.html". Should be placed in templates directory.');
					break;

					case 'content_type':
						$config[$idx]['description'] = trans('Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".');
					break;

					case 'attachment_name':
						$config[$idx]['description'] = trans('File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk. Default: empty.');
					break;

					case 'type':
						$config[$idx]['description'] = trans('Documents type. You can use "html" or "pdf". Default: html.');
					break;

					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;

			case 'mail':
				switch($item['var'])
				{
					case 'debug_email':
						$config[$idx]['description'] = trans('E-mail address for debugging - messages from \'Mailing\' module will be sent at this address, instead to real users.');
					break;
					case 'smtp_port':
					case 'smtp_host':
					case 'smtp_username':
					case 'smtp_password':
					case 'smtp_auth_type':
						$config[$idx]['description'] = trans('SMTP settings.');
					break;

					case 'backend':
						$config[$idx]['description'] = trans('Mail backend settings. Available options: pear or phpmailer.');
					break;

					case 'phpmailer_from':
						$config[$idx]['description'] = trans('E-mail address from which we send mail.');
					break;

					case 'phpmailer_from_name':
						$config[$idx]['description'] = trans('E-mail address name from which we send mail.');
					break;

					case 'phpmailer_is_html':
						$config[$idx]['description'] = trans('Email message in html format.');
					break;

					case 'smtp_secure':
						$config[$idx]['description'] = trans('Security protocol. Available options: ssl or tls.');
					break;

					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;

				} //end: var
			break;

			case 'sms':
				switch($item['var'])
				{
					case 'service':
						$config[$idx]['description'] = trans('Default service type for sending text messages.');
					break;

					case 'prefix':
						$config[$idx]['description'] = trans('Country prefix code, needed for number validation. Default: 48');
					break;

					case 'from':
						$config[$idx]['description'] = trans('Default sender of a text message.');
					break;

					case 'username':
						$config[$idx]['description'] = trans('Username for smscenter service');
					break;

					case 'password':
						$config[$idx]['description'] = trans('Password for smscenter service');
					break;

					case 'smscenter_type':
						$config[$idx]['description'] = trans('Type of account you have at smscenter service. LMS will add sender at the end of message, when static type has been set. Correct values are: static and dynamic');
					break;

					default:
						$config[$idx]['description'] = trans('Unknown option. No description.');
					break;
				} //end: var
			break;
                       case 'zones':
                                switch($item['var'])
                                {
                                        case 'hostmaster_mail':
                                                $config[$idx]['description'] = trans('Domain admin e-mail address.');
                                        break;
                                        case 'master_dns':
                                                $config[$idx]['description'] = trans('Primary Name Server name (should be a FQDN).');
                                        break;
                                        case 'slave_dns':
                                                $config[$idx]['description'] = trans('Secondary Name Server name (should be a FQDN).');
                                        break;
                                        case 'default_mx':
                                                $config[$idx]['description'] = trans('Mail Exchange (MX) name record which identifies the name of the server that handles mail for domain (should be a FQDN).');
                                        break;
                                        case 'default_ttl':
                                                $config[$idx]['description'] = trans('The default expiration time of a resource record. Default 86400.');
                                        break;
                                        case 'ttl_refresh':
                                                $config[$idx]['description'] = trans('Time after slave server refreshes records. Default 28800.');
                                        break;
                                        case 'ttl_retry':
                                                $config[$idx]['description'] = trans('Slave server retry time in case of a problem. Default 7200.');
                                        break;
                                        case 'ttl_expire':
                                                $config[$idx]['description'] = trans('Records expiration time. Default 604800.');
                                        break;
                                        case 'ttl_minimum':
                                                $config[$idx]['description'] = trans('Minimum caching time in case of failed lookups. Default 86400.');
                                        break;
                                        case 'default_webserver_ip':
                                                $config[$idx]['description'] = trans('IP address of webserver');
                                        break;
                                        case 'default_mailserver_ip':
                                                $config[$idx]['description'] = trans('IP address of mailserver');
                                        break;
                                        case 'default_spf':
                                                $config[$idx]['description'] = trans('Default SPF record. If you leave the field blank, record will not add. Example: "v=spf1 a mx ip4:ADDRESS_MAILSERVER ~all" (Put in quotes).');
                                        break;
                                } //end: var
                        break;
		    default:
				$config[$idx]['description'] = trans('Unknown option. No description.');
			break;
		} //end: section
		if (!empty($config[$idx]['usercomment']))
			$config[$idx]['usercomment'] = str_replace("\n", '<br>', $config[$idx]['usercomment']);
	} //end: foreach

	$config['total'] = sizeof($config);
	$config['order'] = $order;
	$config['direction'] = $direction;
	$config['section'] = $section;

	return $config;
}

$layout['pagetitle'] = trans('User Interface Configuration');

if(!isset($_GET['o']))
	$SESSION->restore('conlo', $o);
else
	$o = $_GET['o'];
$SESSION->save('conlo', $o);

if(!isset($_GET['s']))
        $SESSION->restore('conls', $s);
else
	$s = $_GET['s'];
$SESSION->save('conls', $s);

if(!isset($_GET['n']))
    $SESSION->restore('conln', $n);
else
	$n = $_GET['n'];
$SESSION->save('conln', $n);

if ($SESSION->is_set('conlp') && !isset($_GET['page']))
	$SESSION->restore('conlp', $_GET['page']);

$configlist = GetConfigList($o, $s, $n);

$listdata['total'] = $configlist['total'];
$listdata['order'] = $configlist['order'];
$listdata['direction'] = $configlist['direction'];
$listdata['section'] = $configlist['section'];
$listdata['search'] = $n;

unset($configlist['total']);
unset($configlist['order']);
unset($configlist['direction']);
unset($configlist['section']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']); 
$pagelimit = ConfigHelper::getConfig('phpui.configlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('conlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('configlist', $configlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('config/configlist.html');

?>
