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

    public function CheckOption($var, $value)
    {
        switch ($var) {
            case 'accountlist_pagelimit':
            case 'ticketlist_pagelimit':
            case 'balancelist_pagelimit':
            case 'invoicelist_pagelimit':
            case 'aliaslist_pagelimit':
            case 'domainlist_pagelimit':
            case 'documentlist_pagelimit':
            case 'timeout':
            case 'timetable_days_forward':
            case 'nodepassword_length':
            case 'check_for_updates_period':
            case 'print_balance_list_limit':
            case 'networkhosts_pagelimit':
                if ($value <= 0)
                    return trans('Value of option "$a" must be a number grater than zero!', $var);
                break;
            case 'reload_type':
                if ($value != 'sql' && $value != 'exec')
                    return trans('Incorrect reload type. Valid types are: sql, exec!');
                break;
            case 'force_ssl':
            case 'allow_mac_sharing':
            case 'smarty_debug':
            case 'use_current_payday':
            case 'helpdesk_backend_mode':
            case 'helpdesk_reply_body':
            case 'to_words_short_version':
            case 'newticket_notify':
            case 'print_balance_list':
            case 'short_pagescroller':
            case 'big_networks':
            case 'ewx_support':
            case 'helpdesk_stats':
            case 'helpdesk_customerinfo':
                if (!isboolean($value))
                    return trans('Incorrect value! Valid values are: 1|t|true|y|yes|on and 0|n|no|off|false');
                break;
            case 'debug_email':
                if (!check_email($value))
                    return trans('Incorrect email address!');
                break;
        }
        return NULL;
    }

}
