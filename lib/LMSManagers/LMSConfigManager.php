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

    public function DeleteConfigOption($id)
    {
        $option_deleted = $this->db->Execute('DELETE FROM uiconfig WHERE id = ?', array($id));

        if ($this->syslog && $option_deleted) {
            $args = array(SYSLOG::RES_UICONF => $id);
            $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_DELETE, $args);
        }
    }

    private function globalConfigOptionExists($params)
    {
        extract($params);
        if (isset($section) && isset($variable)) {
            return $this->db->GetOne(
                'SELECT id FROM uiconfig WHERE section = ? AND var = ? AND divisionid IS NULL AND userid IS NULL',
                array($section, $variable)
            );
        }

        return false;
    }

    private function divisionConfigOptionExists($params)
    {
        extract($params);
        if (isset($section) && isset($variable) && isset($divisionid)) {
            return $this->db->GetOne(
                'SELECT id FROM uiconfig WHERE section = ? AND var = ? AND divisionid = ? AND userid IS NULL',
                array($section, $variable, $divisionid)
            );
        }

        return false;
    }

    private function divisionUserConfigOptionExists($params)
    {
        extract($params);
        if (isset($section) && isset($variable) && isset($divisionid) && isset($userid)) {
            return $this->db->GetOne(
                'SELECT id FROM uiconfig WHERE section = ? AND var = ? AND divisionid = ? AND userid = ?',
                array($section, $variable, $divisionid, $userid)
            );
        }

        return false;
    }

    private function userConfigOptionExists($params)
    {
        extract($params);
        if (isset($section) && isset($variable) && isset($userid)) {
            return $this->db->GetOne(
                'SELECT id FROM uiconfig WHERE section = ? AND var = ? AND userid = ? AND divisionid IS NULL',
                array($section, $variable, $userid)
            );
        }

        return false;
    }

    private function addDivisionConfig($params)
    {
        extract($params);

        if (isset($variableinfo) && !empty($variableinfo)
            && isset($divArgs) && !empty($divArgs)
            && isset($config_id) && !empty($config_id)) {
            $addedDivisionConfig = $this->addConfigOption($divArgs);

            if (isset($withchildbindings) && !empty($withchildbindings) && !empty($addedDivisionConfig)) { // clone child bindings
                $optionHierarchy = $this->getOptionHierarchy($config_id);
                if ($optionHierarchy) {
                    if (isset($optionHierarchy['divisions']) && $optionHierarchy['divisions'][0]['id'] == $variableinfo['divisionid']) {
                        $division = $optionHierarchy['divisions'][0];
                        if (isset($division['users'])) {
                            $lms = LMS::getInstance();
                            foreach ($division['users'] as $divisionuser) {
                                // clone divisionuser config if user is binded to division
                                $userDivisionAccess = $lms->checkDivisionsAccess(array('divisions' => $divArgs['divisionid'], 'userid' => $divisionuser['userid']));
                                if ($userDivisionAccess) {
                                    $divuserArgs = array(
                                        'section' => $divArgs['section'],
                                        'var' => $divArgs['var'],
                                        'value' => $divisionuser['value'],
                                        'description' => $divisionuser['description'],
                                        'disabled' => $divisionuser['disabled'],
                                        'type' => $divArgs['type'],
                                        'userid' => $divisionuser['userid'],
                                        'divisionid' => $divArgs['divisionid'],
                                        'configid' => $addedDivisionConfig
                                    );
                                    $this->addConfigOption($divuserArgs);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function cloneGlobalConfig($params)
    {
        extract($params);

        if (isset($config_id) && !empty($config_id) && isset($targetSection) && !empty($targetSection)) {
            $variableinfo = $this->GetConfigVariable($config_id);
            $optionExist = $this->globalConfigOptionExists(array('section' => $targetSection, 'variable' => $variableinfo['var']));

            if (!$optionExist) {
                // clone to the new section
                $globalArgs = array(
                    'section' => $targetSection,
                    'var' => $variableinfo['var'],
                    'value' => $variableinfo['value'],
                    'description' => $variableinfo['description'],
                    'disabled' => $variableinfo['disabled'],
                    'type' => $variableinfo['type'],
                    'userid' => $variableinfo['userid'],
                    'divisionid' => $variableinfo['divisionid'],
                    'configid' => $variableinfo['configid']
                );
                $addedGlobalConfig = $this->addConfigOption($globalArgs);

                if (!empty($addedGlobalConfig)) {
                    if (isset($withchildbindings) && !empty($withchildbindings)) { // clone child bindings
                        $optionHierarchy = $this->getOptionHierarchy($config_id);
                        if ($optionHierarchy) {
                            if (isset($optionHierarchy['divisions'])) {
                                foreach ($optionHierarchy['divisions'] as $division) {
                                    // clone division donfig
                                    $divArgs = array(
                                        'section' => $targetSection,
                                        'var' => $variableinfo['var'],
                                        'value' => $division['value'],
                                        'description' => $division['description'],
                                        'disabled' => $division['disabled'],
                                        'type' => $variableinfo['type'],
                                        'userid' => $division['userid'],
                                        'divisionid' => $division['divisionid'],
                                        'configid' => $addedGlobalConfig
                                    );
                                    $addedDivisionConfig = $this->addConfigOption($divArgs);

                                    if (isset($division['users'])) {
                                        foreach ($division['users'] as $divisionuser) {
                                            // clone divisionuser config
                                            $divuserArgs = array(
                                                'section' => $targetSection,
                                                'var' => $variableinfo['var'],
                                                'value' => $divisionuser['value'],
                                                'description' => $divisionuser['description'],
                                                'disabled' => $divisionuser['disabled'],
                                                'type' => $variableinfo['type'],
                                                'userid' => $divisionuser['userid'],
                                                'divisionid' => $divisionuser['divisionid'],
                                                'configid' => $addedDivisionConfig
                                            );

                                            $this->addConfigOption($divuserArgs);
                                        }
                                    }
                                }
                            }

                            if (isset($optionHierarchy['users'])) {
                                foreach ($optionHierarchy['users'] as $user) {
                                    $userArgs = array(
                                        'section' => $targetSection,
                                        'var' => $variableinfo['var'],
                                        'value' => $user['value'],
                                        'description' => $user['description'],
                                        'disabled' => $user['disabled'],
                                        'type' => $variableinfo['type'],
                                        'userid' => $user['userid'],
                                        'divisionid' => $user['divisionid'],
                                        'configid' => $addedGlobalConfig
                                    );

                                    $this->addConfigOption($userArgs);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function cloneDivisionConfig($params)
    {
        extract($params);

        if (isset($config_id) && isset($targetSection) && isset($targetDivision)
            && !empty($config_id) && !empty($targetSection) && !empty($targetDivision)) {
            $variableinfo = $this->GetConfigVariable($config_id);
            $optionExist = $this->globalConfigOptionExists(array('section' => $targetSection, 'variable' => $variableinfo['var']));

            $divisionSourceArgs = array(
                'var' => $variableinfo['var'],
                'value' => $variableinfo['value'],
                'description' => $variableinfo['description'],
                'disabled' => $variableinfo['disabled'],
                'type' => $variableinfo['type'],
                'userid' => null,
                'divisionid' => $targetDivision
            );

            if ($targetSection == 'samesection') { // source section is the same as target section
                if ($variableinfo['divisionid'] != $targetDivision) { // source division is different from target division
                    $divisionOptionExist = $this->divisionConfigOptionExists(array('section' => $variableinfo['section'], 'variable' => $variableinfo['var'], 'divisionid' => $targetDivision));
                    if ($divisionOptionExist) { // if target division exists
                        if (isset($override) && !empty($override)) {
                            // delete old target division config
                            $this->DeleteConfigOption($divisionOptionExist);
                            // clone source division data to the target division config
                            $divisionTargetArgs = array(
                                'section' => $variableinfo['section'],
                                'configid' => $variableinfo['configid']
                            );
                            $params['variableinfo'] = $variableinfo;
                            $divArgs = array_merge($divisionSourceArgs, $divisionTargetArgs);
                            $params['divArgs'] = $divArgs;
                            $this->addDivisionConfig($params);
                        } else {
                            return; // skip due to denied override
                        }
                    } else { // if target division does not exist
                        // clone source division data to the target division config
                        $divisionTargetArgs = array(
                            'section' => $variableinfo['section'],
                            'configid' => $variableinfo['configid']
                        );
                        $params['variableinfo'] = $variableinfo;
                        $divArgs = array_merge($divisionSourceArgs, $divisionTargetArgs);
                        $params['divArgs'] = $divArgs;
                        $this->addDivisionConfig($params);
                    }
                }
            } else { // if section is new one or exists
                if (!empty($optionExist)) { // clone to the another existing section
                    $divisionOptionExist = $this->divisionConfigOptionExists(array('section' => $targetSection, 'variable' => $variableinfo['var'], 'divisionid' => $targetDivision));
                    if (!empty($divisionOptionExist)) { // if target division option exists
                        if (isset($override) && !empty($override)) {
                            // delete old target division config
                            $this->DeleteConfigOption($divisionOptionExist);
                            // clone source division config data to the target division config
                            $divisionTargetArgs = array(
                                'section' => $targetSection,
                                'configid' => $optionExist
                            );
                            $params['variableinfo'] = $variableinfo;
                            $divArgs = array_merge($divisionSourceArgs, $divisionTargetArgs);
                            $params['divArgs'] = $divArgs;
                            $this->addDivisionConfig($params);
                        } else {
                            return; // skip due to denied override
                        }
                    } else { // if target division option does not exist
                        // clone source division config data to the target division config
                        $divisionTargetArgs = array(
                            'section' => $targetSection,
                            'configid' => $optionExist
                        );
                        $params['variableinfo'] = $variableinfo;
                        $divArgs = array_merge($divisionSourceArgs, $divisionTargetArgs);
                        $params['divArgs'] = $divArgs;
                        $this->addDivisionConfig($params);
                    }
                } else { // clone to the new section - parent dependency is requiered
                    if (isset($withparentbindings) && !empty($withparentbindings)) {
                        // create parent (global) option with data from target division option data
                        $globalArgs = array(
                            'section' => $targetSection,
                            'var' => $variableinfo['var'],
                            'value' => $variableinfo['value'],
                            'description' => $variableinfo['description'],
                            'disabled' => $variableinfo['disabled'],
                            'type' => $variableinfo['type'],
                            'userid' => null,
                            'divisionid' => null,
                            'configid' => null
                        );
                        $addedGlobalConfig = $this->addConfigOption($globalArgs);

                        // bind target division to previously added global option
                        if (!empty($addedGlobalConfig)) {
                            $divisionTargetArgs = array(
                                'section' => $targetSection,
                                'configid' => $addedGlobalConfig
                            );
                            $params['variableinfo'] = $variableinfo;
                            $divArgs = array_merge($divisionSourceArgs, $divisionTargetArgs);
                            $params['divArgs'] = $divArgs;
                            $this->addDivisionConfig($params);
                        }
                    } else {
                        return; // skip due to denied parent dependecy
                    }
                }
            }
        }
    }

    private function cloneDivisionUserConfig($params)
    {
        extract($params);

        if (isset($config_id) && isset($targetSection) && isset($targetDivision) && isset($targetUser)
            && !empty($config_id) && !empty($targetSection) && !empty($targetDivision) && !empty($targetUser)) {
            $lms = LMS::getInstance();
            $userDivisionAccess = $lms->checkDivisionsAccess(array('divisions' => $targetDivision, 'userid' => $targetUser));
            if ($userDivisionAccess) {
                $variableinfo = $this->GetConfigVariable($config_id);
                $optionExist = $this->globalConfigOptionExists(array(
                    'section' => $targetSection,
                    'variable' => $variableinfo['var']
                ));

                $sourceArgs = array(
                    'var' => $variableinfo['var'],
                    'value' => $variableinfo['value'],
                    'description' => $variableinfo['description'],
                    'disabled' => $variableinfo['disabled'],
                    'type' => $variableinfo['type']
                );

                if ($targetSection == 'samesection') { // source section is the same as target section
                    if ($variableinfo['divisionid'] != $targetDivision) { // clone user to another user in the other division
                        $divisionOptionExist = $this->divisionConfigOptionExists(array(
                            'section' => $variableinfo['section'],
                            'variable' => $variableinfo['var'],
                            'divisionid' => $targetDivision
                        ));
                        if (!empty($divisionOptionExist)) { // if parent target division for target user exists
                            $divisionUserOptionExist = $this->divisionUserConfigOptionExists(array(
                                'section' => $variableinfo['section'],
                                'variable' => $variableinfo['var'],
                                'divisionid' => $targetDivision,
                                'userid' => $targetUser
                            ));
                            if (!empty($divisionUserOptionExist)) { // if user exists in target division
                                if (isset($override) && !empty($override)) {
                                    $this->DeleteConfigOption($divisionUserOptionExist);
                                    // clone source division config to the target division config in the same option
                                    $userTargetArgs = array(
                                        'section' => $variableinfo['section'],
                                        'userid' => $targetUser,
                                        'divisionid' => $targetDivision,
                                        'configid' => $divisionOptionExist
                                    );
                                    $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                    $this->addConfigOption($userArgs);
                                } else {
                                    return;
                                }
                            } else { // if user option does not exist in target division
                                $userTargetArgs = array(
                                    'section' => $variableinfo['section'],
                                    'userid' => $targetUser,
                                    'divisionid' => $targetDivision,
                                    'configid' => $divisionOptionExist
                                );
                                $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                $this->addConfigOption($userArgs);
                            }
                        } else { // if parent target division does not exist - parent dependency is requiered
                            if (isset($withparentbindings) && !empty($withparentbindings)) {
                                // create parent division option with the same settings as target user option data
                                // and right after that bind new division user settings
                                $globalOptionExists = $this->globalConfigOptionExists(array(
                                    'section' => $variableinfo['section'],
                                    'variable' => $variableinfo['var']
                                ));
                                if ($globalOptionExists) {
                                    $divTargetArgs = array(
                                        'section' => $variableinfo['section'],
                                        'userid' => null,
                                        'divisionid' => $targetDivision,
                                        'configid' => $globalOptionExists
                                    );
                                    $divArgs = array_merge($sourceArgs, $divTargetArgs);
                                    $addedDivisiononfig = $this->addConfigOption($divArgs);

                                    if (!empty($addedDivisiononfig)) {
                                        $userTargetArgs = array(
                                            'section' => $variableinfo['section'],
                                            'userid' => $targetUser,
                                            'divisionid' => $targetDivision,
                                            'configid' => $addedDivisiononfig
                                        );
                                        $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                        $this->addConfigOption($userArgs);
                                    }
                                } else {
                                    return;
                                }
                            } else {
                                return; // skip because creation of parent dependecy is denied
                            }
                        }
                    } else { // clone source user to target user in the same division in the same section
                        if ($variableinfo['userid'] != $targetUser) {
                            $divisionUserOptionExist = $this->divisionUserConfigOptionExists(array(
                                'section' => $variableinfo['section'],
                                'variable' => $variableinfo['var'],
                                'divisionid' => $variableinfo['divisionid'],
                                'userid' => $targetUser
                            ));
                            if (!empty($divisionUserOptionExist)) {
                                if (isset($override) && !empty($override)) {
                                    $this->DeleteConfigOption($divisionUserOptionExist);
                                    // clone source division config to the target division config in the same option
                                    $userTargetArgs = array(
                                        'section' => $variableinfo['section'],
                                        'userid' => $targetUser,
                                        'divisionid' => $variableinfo['divisionid'],
                                        'configid' => $variableinfo['configid']
                                    );
                                    $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                    $this->addConfigOption($userArgs);
                                } else {
                                    return;
                                }
                            } else { // if user not exists in target division
                                $userTargetArgs = array(
                                    'section' => $variableinfo['section'],
                                    'userid' => $targetUser,
                                    'divisionid' => $variableinfo['divisionid'],
                                    'configid' => $variableinfo['configid']
                                );
                                $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                $this->addConfigOption($userArgs);
                            }
                        } else {
                            return; // skip if source user is the same as target user
                        }
                    }
                } else { // check if section is new one or exists
                    if (!empty($optionExist)) { // clone to the another existing section
                        $divisionOptionExist = $this->divisionConfigOptionExists(array(
                            'section' => $targetSection,
                            'variable' => $variableinfo['var'],
                            'divisionid' => $targetDivision
                        ));
                        if (!empty($divisionOptionExist)) { // if exists parent target division option for target user
                            $divisionUserOptionExist = $this->divisionUserConfigOptionExists(array(
                                'section' => $variableinfo['section'],
                                'variable' => $variableinfo['var'],
                                'divisionid' => $targetDivision,
                                'userid' => $targetUser
                            ));
                            if (!empty($divisionUserOptionExist)) { // if user exists in target division
                                if (isset($override) && !empty($override)) {
                                    $this->DeleteConfigOption($divisionUserOptionExist);
                                    // clone source user config data to the target user config
                                    $userTargetArgs = array(
                                        'section' => $targetSection,
                                        'userid' => $targetUser,
                                        'divisionid' => $targetDivision,
                                        'configid' => $divisionOptionExist
                                    );
                                    $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                    $this->addConfigOption($userArgs);
                                } else {
                                    return; // skip because override is denied
                                }
                            } else { // if user not exists in target division add new one
                                $userTargetArgs = array(
                                    'section' => $targetSection,
                                    'userid' => $targetUser,
                                    'divisionid' => $targetDivision,
                                    'configid' => $divisionOptionExist
                                );
                                $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                $this->addConfigOption($userArgs);
                            }
                        } else { // if parent target division option for target user does not exist
                            // create parent division option with the same settings as target user option data
                            // and right after that bind new division user settings
                            if (isset($withparentbindings) && !empty($withparentbindings)) {
                                $divTargetArgs = array(
                                    'section' => $targetSection,
                                    'userid' => null,
                                    'divisionid' => $targetDivision,
                                    'configid' => $optionExist
                                );
                                $divArgs = array_merge($sourceArgs, $divTargetArgs);
                                $addedDivisiononfig = $this->addConfigOption($divArgs);

                                if (!empty($addedDivisiononfig)) {
                                    $userTargetArgs = array(
                                        'section' => $targetSection,
                                        'userid' => $targetUser,
                                        'divisionid' => $targetDivision,
                                        'configid' => $addedDivisiononfig
                                    );
                                    $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                    $this->addConfigOption($userArgs);
                                }
                            } else {
                                return; // skip because creation of parent dependecy is denied
                            }
                        }
                    } else { // clone to the new section - parent dependency is requiered
                        if (isset($withparentbindings) && !empty($withparentbindings)) {
                            // create parent global and division option with the same settings as target user
                            // and right after that bind new user settings
                            $globalTargetArgs = array(
                                'section' => $targetSection,
                                'userid' => null,
                                'divisionid' => null,
                                'configid' => null
                            );
                            $globalArgs = array_merge($sourceArgs, $globalTargetArgs);
                            $addedGlobalConfig = $this->addConfigOption($globalArgs);

                            // clone source division config to the target division config
                            if (!empty($addedGlobalConfig)) {
                                $divTargetArgs = array(
                                    'section' => $targetSection,
                                    'userid' => null,
                                    'divisionid' => $targetDivision,
                                    'configid' => $addedGlobalConfig
                                );
                                $divArgs = array_merge($sourceArgs, $divTargetArgs);
                                $addedDivisiononfig = $this->addConfigOption($divArgs);
                            }

                            if (!empty($addedDivisiononfig)) {
                                $userTargetArgs = array(
                                    'section' => $targetSection,
                                    'userid' => $targetUser,
                                    'divisionid' => $targetDivision,
                                    'configid' => $addedDivisiononfig
                                );
                                $userArgs = array_merge($sourceArgs, $userTargetArgs);
                                $this->addConfigOption($userArgs);
                            }
                        } else {
                            return; // skip because creation of parent dependecy is denied
                        }
                    }
                }
            }
        }
    }

    private function cloneUserConfig($params)
    {
        extract($params);

        if (isset($config_id) && isset($targetSection) && isset($targetUser)
            && !empty($config_id) && !empty($targetSection) && !empty($targetUser)) {
            $variableinfo = $this->GetConfigVariable($config_id);
            $optionExist = $this->globalConfigOptionExists(array('section' => $targetSection, 'variable' => $variableinfo['var']));

            $userSourceArgs = array(
                'var' => $variableinfo['var'],
                'value' => $variableinfo['value'],
                'description' => $variableinfo['description'],
                'disabled' => $variableinfo['disabled'],
                'type' => $variableinfo['type'],
                'userid' => $targetUser,
                'divisionid' => null
            );
            if ($targetSection == 'samesection') { // source section is the same as target section
                if ($variableinfo['userid'] != $targetUser) { // source user is different from target user
                    $userOptionExist = $this->userConfigOptionExists(array('section' => $variableinfo['section'], 'variable' => $variableinfo['var'], 'userid' => $targetUser));
                    if (!empty($userOptionExist)) { // if target user exists
                        if (isset($override) && !empty($override)) {
                            // delete old target user config
                            $this->DeleteConfigOption($userOptionExist);
                            // clone source user data to the target user config
                            $userTargetArgs = array(
                                'section' => $variableinfo['section'],
                                'configid' => $variableinfo['configid']
                            );
                            $userArgs = array_merge($userSourceArgs, $userTargetArgs);
                            $this->addConfigOption($userArgs);
                        } else {
                            return; // skip because override is denied
                        }
                    } else { // if target user does not exist
                        // clone source user data to the target user config
                        $userTargetArgs = array(
                            'section' => $variableinfo['section'],
                            'configid' => $variableinfo['configid']
                        );
                        $userArgs = array_merge($userSourceArgs, $userTargetArgs);
                        $this->addConfigOption($userArgs);
                    }
                }
            } else { // check if section is new one or exists
                if (!empty($optionExist)) { // clone to the another existing section
                    $userOptionExist = $this->userConfigOptionExists(array('section' => $targetSection, 'variable' => $variableinfo['var'], 'userid' => $targetUser));
                    if (!empty($userOptionExist)) { // if target user option exists
                        if (isset($override) && !empty($override)) {
                            // delete old target user config
                            $this->DeleteConfigOption($userOptionExist);
                            // clone source user config data to the target user config
                            $userTargetArgs = array(
                                'section' => $targetSection,
                                'configid' => $optionExist
                            );
                            $userArgs = array_merge($userSourceArgs, $userTargetArgs);
                            $this->addConfigOption($userArgs);
                        } else {
                            return; // skip because override is denied
                        }
                    } else { // if target user option does not exist
                        // clone source user config data to the target user config
                        $userTargetArgs = array(
                            'section' => $targetSection,
                            'configid' => $optionExist
                        );
                        $userArgs = array_merge($userSourceArgs, $userTargetArgs);
                        $this->addConfigOption($userArgs);
                    }
                } else { // clone to the new section - parent dependency is requiered
                    if (isset($withparentbindings) && !empty($withparentbindings)) {
                        // create parent (global) option with data from target user option data
                        $globalArgs = array(
                            'section' => $targetSection,
                            'var' => $variableinfo['var'],
                            'value' => $variableinfo['value'],
                            'description' => $variableinfo['description'],
                            'disabled' => $variableinfo['disabled'],
                            'type' => $variableinfo['type'],
                            'userid' => null,
                            'divisionid' => null,
                            'configid' => null
                        );
                        $addedGlobalConfig = $this->addConfigOption($globalArgs);

                        // bind target division to previously added global option
                        if (!empty($addedGlobalConfig)) {
                            $userTargetArgs = array(
                                'section' => $targetSection,
                                'configid' => $addedGlobalConfig
                            );
                            $userArgs = array_merge($userSourceArgs, $userTargetArgs);
                            $this->addConfigOption($userArgs);
                        }
                    } else {
                        return; // skip because parent dependecy is denied
                    }
                }
            }
        }
    }

    private function cloneConfig($params)
    {
        extract($params);
        if (!empty($config_id)) {
            $variableinfo = $this->GetConfigVariable($config_id);
            $optionType = '';

            if (empty($variableinfo['configid'])) {
                $optionType = 'global';
            } else {
                if (!empty($variableinfo['divisionid']) && empty($variableinfo['userid'])) {
                    $optionType = 'division';
                }

                if (!empty($variableinfo['divisionid']) && !empty($variableinfo['userid'])) {
                    $optionType = 'divisionuser';
                }

                if (empty($variableinfo['divisionid']) && !empty($variableinfo['userid'])) {
                    $optionType = 'user';
                }
            }

            switch ($optionType) {
                case 'global':
                    $this->cloneGlobalConfig($params);
                    break;
                case 'division':
                    $this->cloneDivisionConfig($params);
                    break;
                case 'divisionuser':
                    $this->cloneDivisionUserConfig($params);
                    break;
                case 'user':
                    $this->cloneUserConfig($params);
                    break;
                default:
                    break;
            }
        }
    }

    public function cloneConfigs($params)
    {
        extract($params);
        if (!empty($variables)) {
            foreach ($variables as $idx => $variable) {
                // add option
                $params['config_id'] = $idx;
                $this->cloneConfig($params);
            }
        }
    }

    private function importAsGlobalConfig($params)
    {
        extract($params);

        if (isset($targetSection) && !empty($targetSection)
            && isset($targetVariable) && !empty($targetVariable)
            && isset($targetValue) && !empty($targetValue)) {
            $optionExist = $this->globalConfigOptionExists(array('section' => $targetSection, 'variable' => $targetVariable));

            if (!$optionExist) {
                // set option type
                $option = $targetSection . '.' . $targetVariable;
                $optionType = $this->GetConfigDefaultType($option);

                // create new variable
                $globalArgs = array(
                    'section' => $targetSection,
                    'var' => $targetVariable,
                    'value' => $targetValue,
                    'description' => '',
                    'disabled' => 0,
                    'type' => $optionType,
                    'userid' => null,
                    'divisionid' => null,
                    'configid' => null
                );
                $this->addConfigOption($globalArgs);
            } else {
                // override existing
                if (isset($override) && !empty($override)) {
                    $globalArgs = array(
                        'value' => $targetValue,
                        'id' => $optionExist
                    );
                    $this->overrideConfigOption($globalArgs);
                }
            }
        }
    }

    private function importAsDivisionConfig($params)
    {
        extract($params);

        if (isset($targetSection) && !empty($targetSection)
            && isset($targetVariable) && !empty($targetVariable)
            && isset($targetValue) && !empty($targetValue)
            && isset($targetDivision) && !empty($targetDivision)) {
            $divisionOptionExist = $this->divisionConfigOptionExists(array('section' => $targetSection, 'variable' => $targetVariable, 'divisionid' => $targetDivision));

            if (!$divisionOptionExist) {  // create division config
                $globalOptionExist = $this->globalConfigOptionExists(array('section' => $targetSection, 'variable' => $targetVariable));
                if ($globalOptionExist) { // global parent option exists
                    // get global option data
                    $globalOption = $this->GetConfigVariable($globalOptionExist);
                    $divisionTargetArgs = array(
                        'section' => $globalOption['section'],
                        'var' => $globalOption['var'],
                        'value' => $targetValue,
                        'description' => $globalOption['description'],
                        'disabled' => $globalOption['disabled'],
                        'type' => $globalOption['type'],
                        'userid' => null,
                        'divisionid' => $targetDivision,
                        'configid' => $globalOption['id']
                    );
                    $this->addConfigOption($divisionTargetArgs);
                } else { // global parent option does not exist - parent dependency is requiered
                    if (isset($withparentbindings) && !empty($withparentbindings)) {
                        // set option type
                        $option = $targetSection . '.' . $targetVariable;
                        $optionType = $this->GetConfigDefaultType($option);
                        $globalArgs = array(
                            'section' => $targetSection,
                            'var' => $targetVariable,
                            'value' => $targetValue,
                            'description' => '',
                            'disabled' => 0,
                            'type' => $optionType,
                            'userid' => null,
                            'divisionid' => null,
                            'configid' => null
                        );
                        $addedGlobalConfig = $this->addConfigOption($globalArgs);

                        if (!empty($addedGlobalConfig)) { // bind target division to previously added global option
                            $divisionTargetArgs = array(
                                'section' => $targetSection,
                                'var' => $targetVariable,
                                'value' => $targetValue,
                                'description' => '',
                                'disabled' => 0,
                                'type' => $optionType,
                                'userid' => null,
                                'divisionid' => $targetDivision,
                                'configid' => $addedGlobalConfig
                            );
                            $this->addConfigOption($divisionTargetArgs);
                        }
                    }
                }
            } else { // override existing division config
                if (isset($override) && !empty($override)) {
                    $divisionTargetArgs = array(
                        'value' => $targetValue,
                        'id' => $divisionOptionExist
                    );
                    $this->overrideConfigOption($divisionTargetArgs);
                }
            }
        }
    }

    private function importAsDivisionUserConfig($params)
    {
        extract($params);

        if (isset($targetSection) && !empty($targetSection)
            && isset($targetVariable) && !empty($targetVariable)
            && isset($targetValue) && !empty($targetValue)
            && isset($targetUser) && !empty($targetUser)
            && isset($targetDivision) && !empty($targetDivision)) {
            $lms = LMS::getInstance();
            $userDivisionAccess = $lms->checkDivisionsAccess(array('divisions' => $targetDivision, 'userid' => $targetUser));

            if ($userDivisionAccess) {
                $divisionUserOptionExist = $this->divisionUserConfigOptionExists(array(
                    'section' => $targetSection,
                    'variable' => $targetVariable,
                    'divisionid' => $targetDivision,
                    'userid' => $targetUser
                ));
                if (!$divisionUserOptionExist) {  // create divisionuser config
                    $divisionOptionExist = $this->divisionConfigOptionExists(array(
                        'section' => $targetSection,
                        'variable' => $targetVariable,
                        'divisionid' => $targetDivision
                    ));
                    if ($divisionOptionExist) { // division parent option exists
                        // get division option data
                        $divisionOption = $this->GetConfigVariable($divisionOptionExist);
                        // add user option
                        $divisionUserTargetArgs = array(
                            'section' => $divisionOption['section'],
                            'var' => $divisionOption['var'],
                            'value' => $targetValue,
                            'description' => $divisionOption['description'],
                            'disabled' => $divisionOption['disabled'],
                            'type' => $divisionOption['type'],
                            'userid' => $targetUser,
                            'divisionid' => $targetDivision,
                            'configid' => $divisionOption['id']
                        );
                        $this->addConfigOption($divisionUserTargetArgs);
                    } else { // division parent option does not exist - parent dependency is requiered
                        if (isset($withparentbindings) && !empty($withparentbindings)) {
                            $globalOptionExist = $this->globalConfigOptionExists(array(
                                'section' => $targetSection,
                                'variable' => $targetVariable
                            ));
                            if ($globalOptionExist) { // global parent option exists
                                // get global option data
                                $globalOption = $this->GetConfigVariable($globalOptionExist);
                                $divisionTargetArgs = array(
                                    'section' => $globalOption['section'],
                                    'var' => $globalOption['var'],
                                    'value' => $targetValue,
                                    'description' => $globalOption['description'],
                                    'disabled' => $globalOption['disabled'],
                                    'type' => $globalOption['type'],
                                    'userid' => null,
                                    'divisionid' => $targetDivision,
                                    'configid' => $globalOption['id']
                                );
                                $addedDivisionConfig = $this->addConfigOption($divisionTargetArgs);

                                if (!empty($addedDivisionConfig)) {
                                    $divisionUserTargetArgs = array(
                                        'section' => $globalOption['section'],
                                        'var' => $globalOption['var'],
                                        'value' => $targetValue,
                                        'description' => $globalOption['description'],
                                        'disabled' => $globalOption['disabled'],
                                        'type' => $globalOption['type'],
                                        'userid' => $targetUser,
                                        'divisionid' => $targetDivision,
                                        'configid' => $addedDivisionConfig
                                    );
                                    $this->addConfigOption($divisionUserTargetArgs);
                                }
                            } else { // global parent option does not exist - parent dependency is requiered
                                // set option type
                                $option = $targetSection . '.' . $targetVariable;
                                $optionType = $this->GetConfigDefaultType($option);
                                $globalArgs = array(
                                    'section' => $targetSection,
                                    'var' => $targetVariable,
                                    'value' => $targetValue,
                                    'description' => '',
                                    'disabled' => 0,
                                    'type' => $optionType,
                                    'userid' => null,
                                    'divisionid' => null,
                                    'configid' => null
                                );
                                $addedGlobalConfig = $this->addConfigOption($globalArgs);

                                if (!empty($addedGlobalConfig)) { // bind target division to previously added global option
                                    $divisionTargetArgs = array(
                                        'section' => $targetSection,
                                        'var' => $targetVariable,
                                        'value' => $targetValue,
                                        'description' => '',
                                        'disabled' => 0,
                                        'type' => $optionType,
                                        'userid' => null,
                                        'divisionid' => $targetDivision,
                                        'configid' => $addedGlobalConfig
                                    );
                                    $addedDivisionConfig = $this->addConfigOption($divisionTargetArgs);
                                }

                                if (!empty($addedDivisionConfig)) {
                                    $divisionUserTargetArgs = array(
                                        'section' => $targetSection,
                                        'var' => $targetVariable,
                                        'value' => $targetValue,
                                        'description' => '',
                                        'disabled' => 0,
                                        'type' => $optionType,
                                        'userid' => $targetUser,
                                        'divisionid' => $targetDivision,
                                        'configid' => $addedDivisionConfig
                                    );
                                    $this->addConfigOption($divisionUserTargetArgs);
                                }
                            }
                        }
                    }
                } else { // override existing division config
                    if (isset($override) && !empty($override)) {
                        $divisionUserTargetArgs = array(
                            'value' => $targetValue,
                            'id' => $divisionUserOptionExist
                        );
                        $this->overrideConfigOption($divisionUserTargetArgs);
                    }
                }
            }
        }
    }

    private function importAsUserConfig($params)
    {
        extract($params);

        if (isset($targetSection) && !empty($targetSection)
            && isset($targetVariable) && !empty($targetVariable)
            && isset($targetValue) && !empty($targetValue)
            && isset($targetUser) && !empty($targetUser)) {
            $userOptionExist = $this->userConfigOptionExists(array('section' => $targetSection, 'variable' => $targetVariable, 'userid' => $targetUser));

            if (!$userOptionExist) {  // create user config
                $globalOptionExist = $this->globalConfigOptionExists(array('section' => $targetSection, 'variable' => $targetVariable));
                if ($globalOptionExist) { // global parent option exists
                    // get global option data
                    $globalOption = $this->GetConfigVariable($globalOptionExist);
                    $userTargetArgs = array(
                        'section' => $globalOption['section'],
                        'var' => $globalOption['var'],
                        'value' => $targetValue,
                        'description' => $globalOption['description'],
                        'disabled' => $globalOption['disabled'],
                        'type' => $globalOption['type'],
                        'userid' => $targetUser,
                        'divisionid' => null,
                        'configid' => $globalOption['id']
                    );
                    $this->addConfigOption($userTargetArgs);
                } else { // global parent option does not exist - parent dependency is requiered
                    if (isset($withparentbindings) && !empty($withparentbindings)) {
                        // set option type
                        $option = $targetSection . '.' . $targetVariable;
                        $optionType = $this->GetConfigDefaultType($option);
                        $globalArgs = array(
                            'section' => $targetSection,
                            'var' => $targetVariable,
                            'value' => $targetValue,
                            'description' => '',
                            'disabled' => 0,
                            'type' => $optionType,
                            'userid' => null,
                            'divisionid' => null,
                            'configid' => null
                        );
                        $addedGlobalConfig = $this->addConfigOption($globalArgs);

                        if (!empty($addedGlobalConfig)) { // bind target user to previously added global option
                            $userTargetArgs = array(
                                'section' => $targetSection,
                                'var' => $targetVariable,
                                'value' => $targetValue,
                                'description' => '',
                                'disabled' => 0,
                                'type' => $optionType,
                                'userid' => $targetUser,
                                'divisionid' => null,
                                'configid' => $addedGlobalConfig
                            );
                            $this->addConfigOption($userTargetArgs);
                        }
                    }
                }
            } else { // override existing division config
                if (isset($override) && !empty($override)) {
                    $userTargetArgs = array(
                        'value' => $targetValue,
                        'id' => $userOptionExist
                    );
                    $this->overrideConfigOption($userTargetArgs);
                }
            }
        }
    }

    private function importConfig($params)
    {
        extract($params);
        if (isset($targetType)) {
            switch ($targetType) {
                case 'global':
                    $this->importAsGlobalConfig($params);
                    break;
                case 'division':
                    $this->importAsDivisionConfig($params);
                    break;
                case 'divisionuser':
                    $this->importAsDivisionUserConfig($params);
                    break;
                case 'user':
                    $this->importAsUserConfig($params);
                    break;
                default:
                    break;
            }
        }
    }

    public function importConfigs($params)
    {
        extract($params);
        if (isset($file) && !empty($file) && isset($targetType) && !empty($targetType)) {
            $configs = (array) parse_ini_file($file, true);
        }

        if (!empty($configs)) {
            foreach ($configs as $section => $variables) {
                foreach ($variables as $variable => $value) {
                    $params['targetSection'] = $section;
                    $params['targetVariable'] = $variable;
                    $params['targetValue'] = $value;
                    $this->importConfig($params);
                }
            }
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

    private function overrideConfigOption($option)
    {
        $args = array(
            'value' => $option['value'],
            'id' => $option['id']
        );

        $id = $option['id'];

        $option_edited = $this->db->Execute(
            'UPDATE uiconfig SET value = ? WHERE id = ?',
            array_values($args)
        );
        if ($this->syslog && $option_edited) {
            $args[SYSLOG::RES_UICONF] = $id;
            $this->syslog->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_UPDATE, $args);
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
