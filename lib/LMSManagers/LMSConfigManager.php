<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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

/**
 * LMSConfigManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSConfigManager extends LMSManager implements LMSConfigManagerInterface
{

    public function GetConfigSections()
    {
        $sections = $this->db->GetCol('SELECT DISTINCT section FROM uiconfig WHERE section!=? ORDER BY section', array('userpanel'));
        $sections = array_unique(array_merge($sections, array('phpui', 'finances', 'invoices', 'receipts', 'mail', 'sms', 'zones', 'tarifftypes')));
        sort($sections);
        return $sections;
    }

    public function GetConfigOptionId($var, $section)
    {
        return $this->db->GetOne('SELECT id FROM uiconfig WHERE section = ? AND var = ?', array($section, $var));
    }

    public function GetConfigDefaultType($option)
    {
        switch ($option) {
            case 'phpui.force_ssl':
            case 'phpui.allow_mac_sharing':
            case 'phpui.smarty_debug':
            case 'phpui.use_current_payday':
            case 'phpui.helpdesk_backend_mode':
            case 'phpui.helpdesk_reply_body':
            case 'phpui.to_words_short_version':
            case 'phpui.newticket_notify':
            case 'phpui.short_pagescroller':
            case 'phpui.big_networks':
            case 'phpui.ewx_support':
            case 'phpui.helpdesk_stats':
            case 'phpui.helpdesk_customerinfo':
            case 'phpui.logging':
            case 'phpui.note_check_payment':
            case 'phpui.public_ip':
            case 'phpui.radius':
            case 'phpui.hide_summaries':
            case 'phpui.use_invoices':
            case 'phpui.hide_toolbar':
            case 'phpui.default_assignment_invoice':
            case 'phpui.invoice_check_payment':
            case 'finances.cashimport_checkinvoices':
            case 'receipts.show_nodes_warning':
            case 'invoices.customer_bankaccount':
            case 'invoices.customer_credentials':
            case 'invoices.print_balance_history':
            case 'mail.phpmailer_is_html':
            case 'mail.smtp_persist':
                $type = CONFIG_TYPE_BOOLEAN;
                break;

            case 'phpui.customerlist_pagelimit':
            case 'phpui.nodelist_pagelimit':
            case 'phpui.balancelist_pagelimit':
            case 'phpui.configlist_pagelimit':
            case 'phpui.invoicelist_pagelimit':
            case 'phpui.ticketlist_pagelimit':
            case 'phpui.accountlist_pagelimit':
            case 'phpui.domainlist_pagelimit':
            case 'phpui.aliaslist_pagelimit':
            case 'phpui.receiptlist_pagelimit':
            case 'phpui.taxratelist_pagelimit':
            case 'phpui.numberplanlist_pagelimit':
            case 'phpui.divisionlist_pagelimit':
            case 'phpui.documentlist_pagelimit':
            case 'phpui.recordlist_pagelimit':
            case 'phpui.voipaccountlist_pagelimit':
            case 'phpui.networkhosts_pagelimit':
            case 'phpui.messagelist_pagelimit':
            case 'phpui.cashreglog_pagelimit':
            case 'phpui.debitnotelist_pagelimit':
            case 'phpui.printout_pagelimit':
            case 'phpui.timeout':
            case 'phpui.timetable_days_forward':
            case 'phpui.nodepassword_length':
            case 'phpui.check_for_updates_period':
            case 'phpui.quicksearch_limit':
                $type = CONFIG_TYPE_POSITIVE_INTEGER;
                break;

            case 'mail.debug_email':
            case 'mail.phpmailer_from':
            case 'sendinvoices.debug_email':
            case 'sendinvoices.sender_email':
            case 'userpanel.debug_email':
            case 'zones.hostmaster_mail':
                $type = CONFIG_TYPE_EMAIL;
                break;

            case 'phpui.reload_type':
                $type = CONFIG_TYPE_RELOADTYPE;
                break;

            case 'notes.type':
            case 'receipts.type':
            case 'phpui.report_type':
            case 'phpui.document_type':
            case 'invoices.type':
                $type = CONFIG_TYPE_DOCTYPE;
                break;

            case 'phpui.document_margins':
                $type = CONFIG_TYPE_MARGINS;
                break;

            case 'mail.backend':
                $type = CONFIG_TYPE_MAIL_BACKEND;
                break;

            case 'mail.smtp_secure':
                $type = CONFIG_TYPE_MAIL_SECURE;
                break;

            case 'payments.date_format':
                $type = CONFIG_TYPE_DATE_FORMAT;
                break;

            default:
                $type = CONFIG_TYPE_NONE;
                break;
        }

        return $type;
    }

    public function CheckOption($option, $value, $type)
    {
        if($value == '')
            return trans('Empty option value is not allowed!');

        switch ($type) {
            case CONFIG_TYPE_POSITIVE_INTEGER:
                if ($value <= 0)
                    return trans('Value of option "$a" must be a number grater than zero!', $option);
                break;

            case CONFIG_TYPE_BOOLEAN:
                if (!isboolean($value))
                    return trans('Incorrect value! Valid values are: 1|t|true|y|yes|on and 0|n|no|off|false');
                break;

            case CONFIG_TYPE_RELOADTYPE:
                if ($value != 'sql' && $value != 'exec')
                    return trans('Incorrect reload type. Valid types are: sql, exec!');
                break;

            case CONFIG_TYPE_DOCTYPE:
                if ($value != 'html' && $value != 'pdf')
                    return trans('Incorrect value! Valid values are: html, pdf!');
                break;

            case CONFIG_TYPE_EMAIL:
                if (!check_email($value))
                    return trans('Incorrect email address!');
                break;

            case CONFIG_TYPE_MARGINS:
                if (!preg_match('/^\d,\d,\d,\d$/', $value))
                    return trans('Margins should consist of 4 numbers separated by commas!');
                break;

            case CONFIG_TYPE_MAIL_BACKEND:
                if ($value != 'pear' && $value != 'phpmailer')
                    return trans('Incorrect mail backend. Valid types are: pear, phpmailer!');
                break;

            case CONFIG_TYPE_MAIL_SECURE:
                if ($value != 'ssl' && $value != 'tls')
                    return trans('Incorrect mail security protocol. Valid types are: ssl, tls!');
                break;

            case CONFIG_TYPE_DATE_FORMAT:
                if (!preg_match('/%[aAdejuw]+/', $value) || !preg_match('/%[bBhm]+/', $value) || !preg_match('/%[CgGyY]+/', $value))
                    return trans('Incorrect date format! Enter format for day (%a, %A, %d, %e, %j, %u, %w), month (%b, %B, %h, %m) and year (%C, %g, %G, %y, %Y)');
                break;

        }
        return NULL;
    }

}
