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

    public function GetConfigDefaultType($section, $var)
    {
        switch ($section . '.' . $var) {
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
                $type = CONFIG_TYPE_BOOLEAN;
                break;

            case 'phpui.accountlist_pagelimit':
            case 'phpui.ticketlist_pagelimit':
            case 'phpui.balancelist_pagelimit':
            case 'phpui.invoicelist_pagelimit':
            case 'phpui.aliaslist_pagelimit':
            case 'phpui.domainlist_pagelimit':
            case 'phpui.documentlist_pagelimit':
            case 'phpui.networkhosts_pagelimit':
            case 'phpui.timeout':
            case 'phpui.timetable_days_forward':
            case 'phpui.nodepassword_length':
            case 'phpui.check_for_updates_period':
                $type = CONFIG_TYPE_POSITIVE_INTEGER;
                break;

            case 'mail.debug_email':
                $type = CONFIG_TYPE_EMAIL;
                break;

            default:
                $type = CONFIG_TYPE_NONE;
                break;
        }

        return $type;
    }

    public function CheckOption($type, $value)
    {
        switch ($type) {
            case CONFIG_TYPE_POSITIVE_INTEGER:
                if ($value <= 0)
                    return trans('Value of option "$a" must be a number grater than zero!', $var);
                break;

            case CONFIG_TYPE_BOOLEAN:
                if (!isboolean($value))
                    return trans('Incorrect value! Valid values are: 1|t|true|y|yes|on and 0|n|no|off|false');
                break;

            case 'reload_type':
                if ($value != 'sql' && $value != 'exec')
                    return trans('Incorrect reload type. Valid types are: sql, exec!');
                break;

            case CONFIG_TYPE_EMAIL:
                if (!check_email($value))
                    return trans('Incorrect email address!');
                break;
        }
        return NULL;
    }

}
