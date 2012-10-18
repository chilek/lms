<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

function chkconfig($value, $default = false)
{
    if (is_bool($value)) {
        return $value;
    }

    if ($value == '') {
        return $default;
    }

    if (preg_match('/^(1|y|on|yes|true|tak|t|enabled)$/i', $value)) {
        return true;
    }

    if (preg_match('/^(0|n|no|off|false|nie|disabled)$/i', $value)) {
        return false;
    }

    trigger_error('Incorrect option value: '.$value);
}

function check_conf($name)
{
    global $CONFIG;

    list($section, $name) = explode('.', $name, 2);

    if (empty($name)) {
        return false;
    }

    if ($section == 'privileges' && !empty($CONFIG['privileges']['superuser'])) {
        return preg_match('/^hide/', $name) ? false : true;
    }

    if (!array_key_exists($section, $CONFIG)) {
        return false;
    }

    if (!array_key_exists($name, $CONFIG[$section])) {
        return false;
    }

    return chkconfig($CONFIG[$section][$name]);
}

function get_conf($name, $default = null)
{
    global $CONFIG;

    list($section, $name) = explode('.', $name, 2);

    if (empty($name)) {
        return $default;
    }

    if (!array_key_exists($section, $CONFIG)) {
        return $default;
    }

    if (!array_key_exists($name, $CONFIG[$section])) {
        return $default;
    }

    $value = $CONFIG[$section][$name];

    return $value == '' ? $default : $value;
}

/*
  Default values of some configuration options.

  Warning! Do not change nothing here or LMS will stop working properly!
*/

$DEFAULTS = array(
	'database' => array(
		'type' => 'mysql',
		'host' => 'localhost',
		'user' => 'mysql',
		'database' => 'lms'
	),
	'phpui' => array(
		'lang' => '',
		'allow_from' => '',
		'default_module' => 'welcome',
		'timeout' => 600,
		'customerlist_pagelimit' => 100,
		'nodelist_pagelimit' => 100,
		'balancelist_pagelimit' => 100,
		'invoicelist_pagelimit' => 100,
		'debitnotelist_pagelimit' => 100,
		'ticketlist_pagelimit' => 100,
		'accountlist_pagelimit' => 100,
		'domainlist_pagelimit' => 100,
		'aliaslist_pagelimit' => 100,
		'configlist_pagelimit' => 100,
		'receiptlist_pagelimit' => 100,
		'taxratelist_pagelimit' => 100,
		'numberplanlist_pagelimit' => 100,
		'divisionlist_pagelimit' => 100,
		'documentlist_pagelimit' => 100,
		'voipaccountlist_pagelimit' => 100,
		'networkhosts_pagelimit' => 256,
		'messagelist_pagelimit' => 100,
		'recordlist_pagelimit' => 100,
		'cashreglog_pagelimit' => 100,
		'reload_type' => 'sql',
		'reload_execcmd' => '/bin/true',
		'reload_sqlquery' => '',
		'lastonline_limit' => 600,
		'timetable_days_forward' => 7,
		'gd_translate_to' => 'ISO-8859-2',
		'check_for_updates_period' => 86400,
		'homedir_prefix' => '/home/',
		'default_taxrate' => 23.00,
		'default_zip' => '',
		'default_city' => '',
		'default_address' => '',
		'smarty_debug' => false,
		'force_ssl' => false,
		'allow_mac_sharing' => false,
		'big_networks' => false,
		'short_pagescroller' => false,
		'helpdesk_stats' => true,
		'helpdesk_customerinfo' => true,
		'helpdesk_backend_mode' => false,
		'helpdesk_sender_name' => '',
		'helpdesk_reply_body' => false,
		'use_invoices' => false,
		'ticket_template_file' => 'rtticketprint.html',
		'use_current_payday' => false,
		'default_monthly_payday' => '',
		'newticket_notify' => false,
		'to_words_short_version' => false,
		'ticketlist_status' => '',
		'ewx_support' => false,
		'invoice_check_payment' => false,
		'note_check_payment' => false,
		'radius' => 1,
		'public_ip' => 1,
		'default_assignment_period' => 3,
		'default_assignment_invoice' => 0,
		'display_toolbar' => 1,
	),
	'invoices' => array(
		'template_file' => 'invoice.html',
		'content_type' => 'text/html',
		'cnote_template_file' => 'invoice.html',
		'print_balance_history' => false,
		'print_balance_history_limit' => 10,
		'default_printpage' => 'original,copy',
		'type' => 'html',
		'attachment_name' => '',
		'paytime' => 14,
		'paytype' => 1, // cash
	),
	'finances' => array(
		'suspension_percentage' => 0,
	),
	'receipts' => array(
		'template_file' => 'receipt.html',
		'content_type' => 'text/html',
		'type' => 'html',
		'attachment_name' => '',
	),
	'notes' => array(
		'template_file' => 'note.html',
		'content_type' => 'text/html',
		'type' => 'html',
		'attachment_name' => '',
		'paytime' => 14,
	),
	'mail' => array(
		'debug_email' => '',
		'smtp_host' => '127.0.0.1',
		'smtp_port' => '25'
	),
	'zones' => array(
		'hostmaster_mail' => 'hostmaster.localhost',
		'master_dns' => 'localhost',
		'slave_dns' => 'localhost',
		'default_ttl' => '3600',
		'ttl_refresh' => '28800',
		'ttl_retry' => '7200',
		'ttl_expire' => '604800',
		'ttl_minimum' => '86400',
		'default_webserver_ip' => '127.0.0.1',
		'default_mailserver_ip' => '127.0.0.1',
		'default_mx' => 'localhost'
	),
);

foreach ($DEFAULTS as $section => $values)
    foreach ($values as $key => $val)
        if (!isset($CONFIG[$section][$key]))
            $CONFIG[$section][$key] = $val;
unset($DEFAULTS);

?>
