<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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

    public function ConfigOptionExists($params)
    {
        extract($params);
        if (isset($section)) {
            return $this->db->GetOne(
                'SELECT id FROM uiconfig WHERE section = ? AND var = ?',
                array($section, $variable)
            );
        } else {
            return $this->db->GetOne('SELECT id FROM uiconfig WHERE id = ?', array($id));
        }
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

    public function GetConfigVariable($config_id)
    {
        return $this->db->GetRow(
            'SELECT * FROM uiconfig WHERE id = ?',
            array($config_id)
        );
    }

    public function CloneConfigSection($section, $new_section)
    {
        $variables = $this->db->GetAll(
            'SELECT var, value, description, disabled, type FROM uiconfig WHERE section = ?',
            array($section)
        );

        if (!empty($variables)) {
            foreach ($variables as $variable) {
                $args = array_merge(array('section' => $new_section), $variable);
                $this->db->Execute(
                    'INSERT INTO uiconfig (section, var, value, description, disabled, type)
                    VALUES (?, ?, ?, ?, ?, ?)',
                    array_values($args)
                );
                if ($this->syslog) {
                    $args[SYSLOG::RES_UICONF] = $this->db->GetLastInsertID('uiconfig');
                    $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_ADD, $args);
                }
            }
        }
    }

    public function DeleteConfigOption($id)
    {
        $option_deleted = $this->db->Execute('DELETE FROM uiconfig WHERE id = ?', array($id));

        if ($this->syslog && $option_deleted) {
            $args = array(SYSLOG::RES_UICONF => $id);
            $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_DELETE, $args);
        }
    }

    public function getRelatedDivisions($id)
    {
        return $this->db->GetAllByKey(
            'SELECT divisionid
            FROM uiconfig
            WHERE configid = ?
              AND divisionid IS NOT NULL',
            'divisionid',
            array($id)
        );
    }

    public function getRelatedUsers($id, $division = null)
    {
        if (isset($division) && !empty($division)) {
            // user override division conf
            return $this->db->GetAllByKey(
                'SELECT userid 
                FROM uiconfig
                WHERE configid = ?
                  AND userid IS NOT NULL
                  AND divisionid = ?',
                'userid',
                array(
                    $id,
                    $division
                )
            );
        } else {
            // user override global conf
            return $this->db->GetAllByKey(
                'SELECT userid 
                FROM uiconfig
                WHERE configid = ?
                  AND userid IS NOT NULL
                  AND divisionid IS NULL',
                'userid',
                array($id)
            );
        }
    }

    public function getRelatedOptions($id)
    {
        return $this->db->GetAll(
            'SELECT c.*, d.shortname, d.id AS divisionid, u.login, u.name
            FROM uiconfig c
            LEFT JOIN divisions d ON d.id = c.divisionid
            LEFT JOIN vallusers u on c.userid = u.id
            WHERE c.configid = ?',
            array($id)
        );
    }

    public function getOptionHierarchy($id)
    {
        $optionHierarchy = array();
        $parentOption = $this->getParentOption($id);
        $relatedOptions = $this->getRelatedOptions($id);
        $option = $this->GetConfigVariable($id);

        if ($relatedOptions) {
            if (!$parentOption) { //for division
                foreach ($relatedOptions as $idx => $relatedOption) {
                    if (!empty($relatedOption['divisionid'])) {
                        $optionHierarchy['divisions'][$idx] = $relatedOption;
                        $optionHierarchy['ids'][] = $relatedOption['id'];
                        //check if division has related records
                        $divisionrelatedOptions = $this->getRelatedOptions($relatedOption['id']);
                        if ($divisionrelatedOptions) {
                            $optionHierarchy['divisions'][$idx]['users'] = $divisionrelatedOptions;
                            foreach ($divisionrelatedOptions as $divisionrelatedOption) {
                                $optionHierarchy['ids'][] = $divisionrelatedOption['id'];
                            }
                        }
                    } else {
                        $optionHierarchy['users'][] = $relatedOption;
                        $optionHierarchy['ids'][] = $relatedOption['id'];
                    }
                }
            } else {
                $lms = LMS::getInstance();
                $optionHierarchy['divisions'][0] = $lms->GetDivision($option['divisionid']);
                $optionHierarchy['ids'][] = $option['id'];
                $optionHierarchy['divisions'][0]['users'] = $relatedOptions;
                foreach ($relatedOptions as $userrelatedOption) {
                    $optionHierarchy['ids'][] = $userrelatedOption['id'];
                }
            }
        }

        if (isset($optionHierarchy['divisions'])) {
            $optionHierarchy['divisions'] = array_values($optionHierarchy['divisions']);
        }
        if (isset($optionHierarchy['users'])) {
            $optionHierarchy['users'] = array_values($optionHierarchy['users']);
        }
        if (isset($optionHierarchy['ids'])) {
            $optionHierarchy['ids'] = array_combine($optionHierarchy['ids'], $optionHierarchy['ids']);
        }

        return $optionHierarchy;
    }

    public function addConfigOption($option)
    {
        $args = array(
            'section' => $option['section'],
            'var' => $option['var'],
            'value' => $option['value'],
            'description' => $option['description'],
            'disabled' => $option['disabled'],
            'type' => $option['type'],
            'userid' => $option['userid'],
            'divisionid' => $option['divisionid'],
            'configid' => $option['configid']
        );

        $option_inserted = $this->db->Execute(
            'INSERT INTO uiconfig (section, var, value, description, disabled, type, userid, divisionid, configid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($args)
        );

        if ($option_inserted) {
            $configid = $this->db->GetLastInsertID('uiconfig');
            if ($this->syslog) {
                $args[SYSLOG::RES_UICONF] = $configid;
                $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_ADD, $args);
            }

            return $configid;
        } else {
            return false;
        }
    }

    public function editConfigOption($option)
    {
        $args = array(
            'section' => $option['section'],
            'var' => $option['var'],
            'value' => $option['value'],
            'description' => $option['description'],
            'type' => $option['type'],
            'id' => $option['id']
        );

        $id = $option['id'];

        // if 'disabled' flag was changed
        $statuschange = $option['statuschange'];
        if (!empty($statuschange)) {
            $this->toggleConfigOption($id);
        }

        $option_edited = $this->db->Execute(
            'UPDATE uiconfig SET section = ?, var = ?, value = ?, description = ?, type = ? WHERE id = ?',
            array_values($args)
        );
        if ($this->syslog && $option_edited) {
            $args[SYSLOG::RES_UICONF] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_UPDATE, $args);
        }

        $optionHierarchy = $this->getOptionHierarchy($id);
        if (!empty($optionHierarchy)) {
            $optionHierarchyIds = (isset($optionHierarchy['ids']) ? $optionHierarchy['ids'] : null);
            $optionHierarchyIdsSql = implode(',', array_keys($optionHierarchy['ids']));
            $refArgs = array(
                'section' => $option['section'],
                'var' => $option['var'],
                'type' => $option['type']
            );

            $reloptions_edited =$this->db->Execute(
                'UPDATE uiconfig SET section = ?, var = ?, type = ? WHERE id IN (' . $optionHierarchyIdsSql . ')',
                array_values($refArgs)
            );

            if ($this->syslog && $reloptions_edited) {
                foreach ($optionHierarchyIds as $idx => $optionHierarchyId) {
                    $refArgs[SYSLOG::RES_UICONF] = $idx;
                    $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_UPDATE, $refArgs);
                }
            }
        }
    }

    public function getParentOption($id)
    {
        return $this->db->GetRow(
            'SELECT * FROM uiconfig c1 
            WHERE c1.id = (SELECT c2.configid FROM uiconfig c2 WHERE c2.id = ?)',
            array($id)
        );
    }

    public function toggleConfigOption($id)
    {
        $parentOption = $this->getParentOption($id);
        if (!$parentOption || $parentOption['disabled'] == 0) {
            $disabled = $this->db->GetOne('SELECT disabled FROM uiconfig WHERE id = ?', array($id));
            $newdisabled = $disabled ? 0 : 1;
            if ($this->syslog) {
                $args = array(
                    SYSLOG::RES_UICONF => $id,
                    'disabled' => $newdisabled
                );
                $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_UPDATE, $args);
            }
            $this->db->Execute(
                'UPDATE uiconfig SET disabled = CASE disabled WHEN 0 THEN 1 ELSE 0 END WHERE id = ?',
                array($id)
            );

            $optionHierarchy = $this->getOptionHierarchy($id);
            $relatedConfigs = array();
            if ($optionHierarchy) {
                if (isset($optionHierarchy['divisions'])) {
                    foreach ($optionHierarchy['divisions'] as $division) {
                        $relatedConfigs[$division['id']][] = $division['id'];
                        if (isset($division['users'])) {
                            foreach ($division['users'] as $divisionuser) {
                                $relatedConfigs[$divisionuser['id']][] =  $divisionuser['id'];
                            }
                        }
                    }
                }

                if (isset($optionHierarchy['users'])) {
                    foreach ($optionHierarchy['users'] as $user) {
                        $relatedConfigs[$user['id']][] = $user['id'];
                    }
                }

                if (!empty($relatedConfigs)) {
                    $relatedConfigsIds = implode(',', array_keys($relatedConfigs));
                    $this->db->Execute('UPDATE uiconfig SET disabled = ? WHERE id IN ('. $relatedConfigsIds .')', array($newdisabled));

                    if ($this->syslog) {
                        foreach ($relatedConfigs as $idx => $relatedConfig) {
                            $args = array(
                                SYSLOG::RES_UICONF => $idx,
                                'disabled' => $newdisabled
                            );
                            $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_UPDATE, $args);
                        }
                    }
                }
            }
        }
    }
}
