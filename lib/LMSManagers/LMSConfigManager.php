<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class LMSConfigManager extends LMSManager implements LMSConfigManagerInterface
{
    private $default_config_types = array(
        'phpui.force_ssl'                   => CONFIG_TYPE_BOOLEAN,
        'phpui.allow_mac_sharing'           => CONFIG_TYPE_BOOLEAN,
        'phpui.smarty_debug'                => CONFIG_TYPE_BOOLEAN,
        'phpui.use_current_payday'          => CONFIG_TYPE_BOOLEAN,
        'phpui.helpdesk_backend_mode'       => CONFIG_TYPE_BOOLEAN,
        'phpui.helpdesk_reply_body'         => CONFIG_TYPE_BOOLEAN,
        'phpui.to_words_short_version'      => CONFIG_TYPE_BOOLEAN,
        'phpui.newticket_notify'            => CONFIG_TYPE_BOOLEAN,
        'phpui.short_pagescroller'          => CONFIG_TYPE_BOOLEAN,
        'phpui.big_networks'                => CONFIG_TYPE_BOOLEAN,
        'phpui.ewx_support'                 => CONFIG_TYPE_BOOLEAN,
        'phpui.helpdesk_stats'              => CONFIG_TYPE_BOOLEAN,
        'phpui.helpdesk_customerinfo'       => CONFIG_TYPE_BOOLEAN,
        'phpui.logging'                     => CONFIG_TYPE_BOOLEAN,
        'phpui.note_check_payment'          => CONFIG_TYPE_BOOLEAN,
        'phpui.public_ip'                   => CONFIG_TYPE_BOOLEAN,
        'phpui.radius'                      => CONFIG_TYPE_BOOLEAN,
        'phpui.hide_summaries'              => CONFIG_TYPE_BOOLEAN,
        'phpui.use_invoices'                => CONFIG_TYPE_BOOLEAN,
        'phpui.hide_toolbar'                => CONFIG_TYPE_BOOLEAN,
        'phpui.default_assignment_invoice'  => CONFIG_TYPE_BOOLEAN,
        'phpui.invoice_check_payment'       => CONFIG_TYPE_BOOLEAN,
        'phpui.logout_confirmation'     => CONFIG_TYPE_BOOLEAN,
        'finances.cashimport_checkinvoices' => CONFIG_TYPE_BOOLEAN,
        'receipts.show_nodes_warning'       => CONFIG_TYPE_BOOLEAN,
        'invoices.customer_bankaccount'     => CONFIG_TYPE_BOOLEAN,
        'invoices.customer_credentials'     => CONFIG_TYPE_BOOLEAN,
        'invoices.print_balance_history'    => CONFIG_TYPE_BOOLEAN,
        'mail.phpmailer_is_html'            => CONFIG_TYPE_BOOLEAN,
        'mail.smtp_persist'                 => CONFIG_TYPE_BOOLEAN,
        'phpui.customerlist_pagelimit'      => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.billinglist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.nodelist_pagelimit'          => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.balancelist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.configlist_pagelimit'        => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.invoicelist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.ticketlist_pagelimit'        => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.accountlist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.domainlist_pagelimit'        => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.aliaslist_pagelimit'         => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.receiptlist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.taxratelist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.numberplanlist_pagelimit'    => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.divisionlist_pagelimit'      => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.documentlist_pagelimit'      => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.recordlist_pagelimit'        => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.voipaccountlist_pagelimit'   => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.networkhosts_pagelimit'      => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.messagelist_pagelimit'       => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.cashreglog_pagelimit'        => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.debitnotelist_pagelimit'     => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.printout_pagelimit'          => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.timeout'                     => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.timetable_days_forward'      => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.nodepassword_length'         => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.check_for_updates_period'    => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.quicksearch_limit'           => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.ping_type'                   => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.autosuggest_max_length'      => CONFIG_TYPE_POSITIVE_INTEGER,
        'phpui.passwordhistory'             => CONFIG_TYPE_POSITIVE_INTEGER,
        'mail.debug_email'                  => CONFIG_TYPE_EMAIL,
        'mail.phpmailer_from'               => CONFIG_TYPE_EMAIL,
        'sendinvoices.debug_email'          => CONFIG_TYPE_EMAIL,
        'sendinvoices.sender_email'         => CONFIG_TYPE_EMAIL,
        'userpanel.debug_email'             => CONFIG_TYPE_EMAIL,
        'zones.hostmaster_mail'             => CONFIG_TYPE_EMAIL,
        'phpui.reload_type'                 => CONFIG_TYPE_RELOADTYPE,
        'notes.type'                        => CONFIG_TYPE_DOCTYPE,
        'receipts.type'                     => CONFIG_TYPE_DOCTYPE,
        'phpui.report_type'                 => CONFIG_TYPE_DOCTYPE,
        'phpui.document_type'               => CONFIG_TYPE_DOCTYPE,
        'invoices.type'                     => CONFIG_TYPE_DOCTYPE,
        'phpui.document_margins'            => CONFIG_TYPE_MARGINS,
        'mail.backend'                      => CONFIG_TYPE_MAIL_BACKEND,
        'mail.smtp_secure'                  => CONFIG_TYPE_MAIL_SECURE,
        'payments.date_format'              => CONFIG_TYPE_DATE_FORMAT,
    );

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
        return array_key_exists($option, $this->default_config_types)
            ? $this->default_config_types[$option] : CONFIG_TYPE_NONE;
    }

    public function CheckOption($option, $value, $type)
    {
        if ($value == '') {
            return trans('Empty option value is not allowed!');
        }

        switch ($type) {
            case CONFIG_TYPE_POSITIVE_INTEGER:
                if (!preg_match('/^[1-9][0-9]*$/', $value)) {
                    return trans('Value of option "$a" must be a number grater than zero!', $option);
                }
                break;

            case CONFIG_TYPE_BOOLEAN:
                if (!isboolean($value)) {
                    return trans('Incorrect value! Valid values are: 1|t|true|y|yes|on and 0|n|no|off|false');
                }
                break;

            case CONFIG_TYPE_RELOADTYPE:
                if ($value != 'sql' && $value != 'exec') {
                    return trans('Incorrect reload type. Valid types are: sql, exec!');
                }
                break;

            case CONFIG_TYPE_DOCTYPE:
                if ($value != 'html' && $value != 'pdf') {
                    return trans('Incorrect value! Valid values are: html, pdf!');
                }
                break;

            case CONFIG_TYPE_EMAIL:
                if (!check_email($value)) {
                    return trans('Incorrect email address!');
                }
                break;

            case CONFIG_TYPE_MARGINS:
                if (!preg_match('/^\d+,\d+,\d+,\d+$/', $value)) {
                    return trans('Margins should consist of 4 numbers separated by commas!');
                }
                break;

            case CONFIG_TYPE_MAIL_BACKEND:
                if ($value != 'pear' && $value != 'phpmailer') {
                    return trans('Incorrect mail backend. Valid types are: pear, phpmailer!');
                }
                break;

            case CONFIG_TYPE_MAIL_SECURE:
                if ($value != 'ssl' && $value != 'tls') {
                    return trans('Incorrect mail security protocol. Valid types are: ssl, tls!');
                }
                break;

            case CONFIG_TYPE_DATE_FORMAT:
                if (!preg_match('/%[aAdejuw]+/', $value) || !preg_match('/%[bBhm]+/', $value) || !preg_match('/%[CgGyY]+/', $value)) {
                    return trans('Incorrect date format! Enter format for day (%a, %A, %d, %e, %j, %u, %w), month (%b, %B, %h, %m) and year (%C, %g, %G, %y, %Y)');
                }
                break;
        }
        return null;
    }
}
