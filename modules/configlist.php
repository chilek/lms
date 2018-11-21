<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function GetConfigList() {
	$configuration_variables = array(
		'phpui' => array(
			'autosuggest_max_length' => 'Max length of auto suggest proposal, further characters will be dotted.',
			'default_autosuggest_placement' => 'Default placement of suggestion window (left/right/top/bottom)',
			'allow_from2' => 'List of networks and IP addresses, with access to LMS. If empty, every IP address has access to LMS. When you write list of addresses or address pools here, LMS will dismiss every unwanted user with HTTP 403 error.',
			'allow_from' => 'List of networks and IP addresses, with access to LMS. If empty, every IP address has access to LMS. When you write list of addresses or address pools here, LMS will dismiss every unwanted user with HTTP 403 error.',
			'lang' => 'System language code. If not set, language will be determined on browser settings. Default: en.',
			'timeout' => 'WWW session timeout. After that time (in seconds) user will be logged out if action has been made. Default: 600.',
			'customerlist_pagelimit' => 'Limit of records displayed on one page in customers list. Default: 100.',
			'nodelist_pagelimit' => 'Limit of records displayed on one page in nodes list. Default: 100.',
			'voipaccountlist_pagelimit' => 'Limit of records displayed on one page in voip accounts list. Default: 100.',
			'voipaccountbilling_pagelimit' => 'Limit of records displayed on one page in voip billings list. Default: 100.',
			'balancelist_pagelimit' => 'Limit of records displayed on one page in customer\'s balance. Default: 100.',
			'configlist_pagelimit' => 'Limit of records displayed on one page in UI config options list. Default: 100.',
			'invoicelist_pagelimit' => 'Limit of records displayed on one page in invoices list. Default: 100.',
			'ticketlist_pagelimit' => 'Limit of records displayed on one page in tickets (requests) list. Default: 100.',
			'accountlist_pagelimit' => 'Limit of records displayed on one page in accounts list. Default: 100.',
			'domainlist_pagelimit' => 'Limit of records displayed on one page in domains list. Default: 100.',
			'aliaslist_pagelimit' => 'Limit of records displayed on one page in aliases list. Default: 100.',
			'receiptlist_pagelimit' => 'Limit of records displayed on one page in cash receipts list. Default: 100.',
			'taxratelist_pagelimit' => 'Limit of records displayed on one page in tax rates list. Default: 100.',
			'numberplanlist_pagelimit' => 'Limit of records displayed on one page in numbering plans list. Default: 100.',
			'billinglist_pagelimit' => 'Limit of billings displayed on one page. Default: 100.',
			'divisionlist_pagelimit' => 'Limit of records displayed on one page in divisions list. Default: 100.',
			'documentlist_pagelimit' => 'Limit of records displayed on one page in documents list. Default: 100.',
			'networkhosts_pagelimit' => 'Limit of nodes displayed on one page in Network Information. Default: 256. With 0, this information is omitted (page is displaying faster).',
			'force_ssl' => 'SSL Enforcing. Setting this option to 1 will effect with that LMS will enforce SSL connection with redirect to \'https://\'.$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI] at every request without SSL. Default: 0 (off).',
			'reload_timer' => 'Reload timer. If set to true it will display remaining time to configuration reload. If using more than one host, remember to sync time between them.',
			'reload_type' => 'Reload type. Allowed values: exec - call some command (most often with sudo, some script or something else, configurable below); sql - writes down to SQL (multiple queries separated with semicolon may be setup).',
			'reload_execcmd' => 'Command to run during reload, if reload_type is set to \'exec\'. By default /bin/true. That string is sent to command system(), so I propose you to think what you do and how :) Altogether, semicolons should be parsed by bash, but LMS splits that string and execute commands separately.',
			'reload_sqlquery' => 'SQL query executed while reload, if reload_type = sql. Default: empty. You can use \'%TIME%\' as replacement to current unix timestamp. WARNING! Semicolon is handled as query separator, which means that you can enter couple of SQL queries separated by semicolon sign.',
			'allow_mac_sharing' => 'Allow nodes addition with duplicated MAC address (not checking that some computer have that MAC yet). Default: 0 (off).',
			'default_zip' => 'Default zip code, city, street, used while inserting new customer. Useful if you add majority of customers with the same street.',
			'default_city' => 'Default zip code, city, street, used while inserting new customer. Useful if you add majority of customers with the same street.',
			'default_address' => 'Default zip code, city, street, used while inserting new customer. Useful if you add majority of customers with the same street.',
			'lastonline_limit' => 'Specify time (in seconds), after which node will be marked offline. It should match with frequency of running nodes activity script (i.e. lms-fping). Default: 600.',
			'use_current_payday' => 'Qualify to use current day of month for payment day. Default: 0 (off).',
			'default_monthly_payday' => 'Qualify the day of month for payment day. Default: 0 (undefined).',
			'smarty_debug' => 'Enable Smarty\'s debug console. Useful for tracking values passed from PHP to Smarty. Default: 0 (off).',
			'arpd_servers' => 'List of arpd servers for MAC addresses retrieval from remote networks. That list should include IP[:port] items separated with spaces. Default: empty.',
			'helpdesk_backend_mode' => 'When enabled, all messages in helpdesk system (except those sent to requester) will be sent to mail server corresponding queue address. lms-rtparser script should be running on server. Messages won\'t be written directly to database, but on solely responsibility of rtparser script. Default: disabled.',
			'helpdesk_sender_name' => 'Name of messages sender or predefined variables: "queue" - queue name, "user" - logged user name. Default: none.',
			'newticket_notify' => 'When enabled, system will sent notification to all users with rights for current queue after new ticket creation. Default: disabled.',
			'to_words_short_version' => 'Specify format of verbal amounts representation (on invoices). e.g. for value "1" verbal expand of 123,15 will be "one two thr 15/100". Default: 0.',
			'timetable_days_forward' => 'Number of days (including current day) on timetable. Default: 7.',
			'gd_translate_to' => 'Charset of data gd library expects (useful if gd library needs ISO-8859-2 instead of UTF-8 to feed imagetext() function).',
			'nodepassword_length' => 'Length of (auto-generated) node password. Max.32. Default: 16.',
			'custom_accesstable' => 'PHP file with user-defined access rules in "lib" directory. Default: empty.',
			'check_for_updates_period' => 'How often to check for LMS updates (in seconds). Default: 86400.',
			'map_type' => 'Network map type. Use "flash" if you have Ming library or "gd" if your PHP supports gdlib. By default LMS will try to generate flash map, with fallback to GD if it fails.',
			'homedir_prefix' => 'Prefix for account home directory. Default: /home/',
			'default_taxrate' => 'Value of tax rate which will be selected by default on tax rates lists. Default: 22.0',
			'default_prodid' => 'Value of product ID. Default: empty',
			'helpdesk_reply_body' => 'Adds body of message in ticket reply. Default: false',
			'big_networks' => 'Support for big ISPs e.g. hidding long customers selection dropdowns. Default: false',
			'short_pagescroller' => 'Enables page scroller designed for lists with very big number of pages. Default: false',
			'ewx_support' => 'Support for EtherWerX devices. Default: false',
			'helpdesk_stats' => 'Adds helpdesk requests causes stats on ticket view and print pages. Default: true',
			'helpdesk_customerinfo' => 'Adds customer basic information on ticket view and in notifications. Default: true',
			'ticket_template_file' => 'Helpdesk ticket printout template file. Default: rtticketprint.html',
			'ticketlist_status' => 'Default status filter setting on tickets list. For allowed values see html source code. Default: not set',
			'use_invoices' => 'Makes option "with invoice" checked by default. Default: false',
			'default_module' => 'Start-up module (filename from /modules without .php). Default: welcome',
			'default_assignment_period' => 'Default period value for assignment. Default: 0',
			'arp_table_backend' => 'Command which returns IP-MAC bindings. Default: internal backend',
			'report_type' => 'Documents type. You can use "html" or "pdf". Default: html.',
			'hide_toolbar' => 'Hide toolbar from user interface. Default: false.',
			'logging' => 'Does this LMS have transaction log support (not opensource). Default: false.',
			'add_customer_group_required' => 'If isset "true" when adding new customer select group is required. Default "false"',
			'event_max_userlist_size' => 'Automatically adjusts the size of the selection list to the number of users when set to 0.',
			'ping_type' => 'Default ping type. You can use "1" for ping or "2" for arping. Default: 1.',
			'default_teryt_city' => 'Default City in TERYT. Set city id in TERYT.',
			'logout_confirmation' => 'If set to "true" then logout confirmation is required. Default "false"',
			'helpdesk_notification_mail_subject' => 'Template for user notice relevant to ticket in Helpdesk. %status - ticket status ; %cat - ticket categories ; %tid - ticket id ; %cid - customer id ; %subject - ticket subject ; %body - ticket body ; %url - ticket url ; %customerinfo - customer information',
			'helpdesk_notification_mail_body' => 'Template for user notice relevant to ticket in Helpdesk. %status - ticket status ; %cat - ticket categories ; %tid - ticket id ; %cid - customer id ; %subject - ticket subject ; %body - ticket body ; %url - ticket url ; %customerinfo - customer information',
			'helpdesk_notification_sms_body' => 'Template for user notice relevant to ticket in Helpdesk. %status - ticket status ; %cat - ticket categories ; %tid - ticket id ; %cid - customer id ; %subject - ticket subject ; %body - ticket body ; %url - ticket url ; %customerinfo - customer information',
			'helpdesk_customerinfo_mail_body' => 'Template for user email notice relevant to customer info in ticket in Helpdesk. %custname - customer name ; %cid  - customer id ; %address - address ; %email - e-mails ; %phone - phones',
			'helpdesk_customerinfo_sms_body' => 'Template for user sms notice relevant to customer info in ticket in Helpdesk. %custname - customer name ; %cid  - customer id ; %address - address ; %email - e-mails ; %phone - phones',
		),
		'payments' => array(
			'date_format' => 'Define date format for variable: %period, %aligned_period, %current_month used in payments.comment and payments.settlement_comment',
			'default_unit_name' => 'Unit name on invoice, default: "pcs."',
		),
		'finances' => array(
			'suspension_percentage' => 'Percentage of suspended liabilities. Default: 0',
			'cashimport_checkinvoices' => 'Check invoices as accounted when importing cash operations. Default: false',
		),
		'invoices' => array(
			'header' => 'This is a seller data. A new line replacement is "\n" sign, e.g. SuperNet ISP\n00-950 Warsaw\nWiosenna 52\n0 49 3883838\n\naccounting@supernet.pl\n\nNIP: 123-123-12-23',
			'footer' => 'Small font text appearing in selected (in template) place of the invoice, e.g. Our Bank: SNETISP, 828823917293871928371\nPhone number 555 123 123',
			'default_author' => 'Default invoice issuer',
			'cplace' => 'Invoice draw-up place.',
			'template_file' => 'Invoice template file. Default: "invoice.html". Should be placed in templates directory.',
			'cnote_template_file' => 'Credit note template file. Default: "invoice.html". Should be placed in templates directory.',
			'content_type' => 'Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".',
			'attachment_name' => 'File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk. Default: empty.',
			'type' => 'Documents type. You can use "html" or "pdf". Default: html.',
			'print_balance_history' => 'If true on invoice (html) will be printed history of financial operations on customer account. Default: not set.',
			'print_balance_history_limit' => 'Number of Records on customer balance list on invoice. Specify last x records. Default: 10.',
			'default_printpage' => 'Coma-separated list of default invoice printout pages. You can use "original", "copy", "duplicate". Default: "original,copy".',
			'radius' => 'Enable RADIUS support. Default: 1',
			'public_ip' => 'Enable public IP address fields. Default: 1',
			'paytime' => 'Default documents paytime in days. Default: 14',
			'paytype' => 'Default invoices paytype. Default: "1" (cash)',
			'customer_bankaccount' => 'Show bankaccount on invoice. Default: 0',
		),
		'notes' => array(
			'template_file' => 'Debit note template file. Default: "note.html". Should be placed in templates directory.',
			'content_type' => 'Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".',
			'attachment_name' => 'File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk. Default: empty.',
			'type' => 'Documents type. You can use "html" or "pdf". Default: html.',
			'paytime' => 'Default documents paytime in days. Default: 14',
		),
		'receipts' => array(
			'template_file' => 'Cash receipt template file. Default: "receipt.html". Should be placed in templates directory.',
			'content_type' => 'Content-type for document. If you enter "application/octet-stream", browser will send file to save on disk, instead of displaying it. It\'s useful if you use your own template which generate e.g. rtf or xls file. Default: "text/html".',
			'attachment_name' => 'File name for saving document printout. WARNING: Setting attachment_name with default content_type will (in case of MSIE) print document, and prompt for save on disk. Default: empty.',
			'type' => 'Documents type. You can use "html" or "pdf". Default: html.',
		),
		'mail' => array(
			'debug_email' => 'E-mail address for debugging - messages from \'Mailing\' module will be sent at this address, instead to real users.',
			'smtp_port' => 'SMTP settings.',
			'smtp_host' => 'SMTP settings.',
			'smtp_username' => 'SMTP settings.',
			'smtp_password' => 'SMTP settings.',
			'smtp_auth_type' => 'SMTP settings.',
			'SMTP settings.' => 'SMTP settings.',
			'backend' => 'Mail backend settings. Available options: pear or phpmailer.',
			'phpmailer_from' => 'E-mail address from which we send mail.',
			'phpmailer_from_name' => 'E-mail address name from which we send mail.',
			'phpmailer_is_html' => 'Email message in html format.',
			'smtp_secure' => 'Security protocol. Available options: ssl or tls.',
		),
		'sms' => array(
			'service' => 'Default service type for sending text messages.',
			'prefix' => 'Country prefix code, needed for number validation. Default: 48',
			'from' => 'Default sender of a text message.',
			'username' => 'Username for smscenter service',
			'password' => 'Password for smscenter service',
			'smscenter_type' => 'Type of account you have at smscenter service. LMS will add sender at the end of message, when static type has been set. Correct values are: static and dynamic',
		),
		'zones' => array(
			'hostmaster_mail' => 'Domain admin e-mail address.',
			'master_dns' => 'Primary Name Server name (should be a FQDN).',
			'slave_dns' => 'Secondary Name Server name (should be a FQDN).',
			'default_mx' => 'Mail Exchange (MX) name record which identifies the name of the server that handles mail for domain (should be a FQDN).',
			'default_ttl' => 'The default expiration time of a resource record. Default 86400.',
			'ttl_refresh' => 'Time after slave server refreshes records. Default 28800.',
			'ttl_retry' => 'Slave server retry time in case of a problem. Default 7200.',
			'ttl_expire' => 'Records expiration time. Default 604800.',
			'ttl_minimum' => 'Minimum caching time in case of failed lookups. Default 86400.',
			'default_webserver_ip' => 'IP address of webserver',
			'default_mailserver_ip' => 'IP address of mailserver',
			'default_spf' => 'Default SPF record. If you leave the field blank, record will not add. Example: "v=spf1 a mx ip4:ADDRESS_MAILSERVER ~all" (Put in quotes).',
		),
	);

	$DB = LMSDB::getInstance();

	$config = $DB->GetAll('SELECT id, section, var, value, description as usercomment, disabled 
			FROM uiconfig WHERE section != \'userpanel\'');

	$md_dir = SYS_DIR . DIRECTORY_SEPARATOR . 'doc' . DIRECTORY_SEPARATOR . 'configuration-variables';

	if ($config) {
		foreach ($config as $idx => &$item) {
			$filename = $md_dir . DIRECTORY_SEPARATOR . $item['section'] . '.' . $item['var'];
			if (file_exists($filename))
				$item['description'] = file_get_contents($filename);
			else
				if (isset($configuration_variables[$item['section']][$item['var']]))
					$item['description'] = trans($configuration_variables[$item['section']][$item['var']]);
				else
					$item['description'] = trans('Unknown option. No description.');

			if (!empty($item['usercomment']))
				$item['usercomment'] = str_replace("\n", '<br>', $item['usercomment']);
		} //end: foreach

		unset($item);
	}
	return $config;
}

$layout['pagetitle'] = trans('User Interface Configuration');

$configlist = GetConfigList();

$pagelimit = ConfigHelper::getConfig('phpui.configlist_pagelimit', count($configlist));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('sections', $LMS->GetConfigSections());
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('configlist', $configlist);
$SMARTY->assign('section', isset($_GET['s']) ? $_GET['s'] : '');
$SMARTY->display('config/configlist.html');

?>
