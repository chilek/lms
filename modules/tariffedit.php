<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

if (!$LMS->TariffExists($_GET['id'])) {
    $SESSION->redirect('?m=tarifflist');
}

if (isset($_GET['set'])) {
    $LMS->TariffSet($_GET['id']);
    $SESSION->redirect('?m=tarifflist');
}

if (isset($_POST['tariff'])) {
    if (!$LMS->isTariffEditable($_GET['id'])) {
        return;
    }

    $tariff = $_POST['tariff'];
    $limit = $_POST['limit'] ?? array();

    foreach ($tariff as $key => $value) {
        if ($key != 'authtype' && $key != 'tags' && $key != 'flags') {
            $tariff[$key] = trim($value);
        }
    }

    $tariff['id'] = $_GET['id'];
    $tariff['netvalue'] = str_replace(',', '.', $tariff['netvalue']);
    $tariff['value'] = str_replace(',', '.', $tariff['value']);

    if (!preg_match('/^[-]?[0-9.,]+$/', $tariff['value'])) {
        $error['value'] = trans('Incorrect value!');
    }

    if ($tariff['name'] == '') {
        $error['name'] = trans('Subscription name required!');
    } else if (!$error) {
        if ($DB->GetOne(
            'SELECT id FROM tariffs WHERE name = ? AND value = ?
	        AND period = ? AND id <> ?',
            array($tariff['name'], str_replace(',', '.', $tariff['value']),
            $tariff['period'] == '' ? null : $tariff['period'],
             $tariff['id'])
        )
        ) {
            $error['name'] = trans('Subscription with specified name and value already exists!');
        }
    }

    if (empty($tariff['datefrom'])) {
        $tariff['from'] = 0;
    } else {
        $tariff['from'] = date_to_timestamp($tariff['datefrom']);
        if (empty($tariff['from'])) {
            $error['datefrom'] = trans('Incorrect effective start time!');
        }
    }

    if (empty($tariff['dateto'])) {
            $tariff['to'] = 0;
    } else {
        $tariff['to'] = date_to_timestamp($tariff['dateto']);
        if (empty($tariff['to'])) {
            $error['dateto'] = trans('Incorrect effective start time!');
        }
    }

    if ($tariff['to'] != 0 && $tariff['from'] != 0 && $tariff['to'] < $tariff['from']) {
        $error['dateto'] = trans('Incorrect date range!');
    }

    $items = array('uprate', 'downrate', 'upceil', 'downceil',
        'up_burst_time', 'up_burst_threshold', 'up_burst_limit',
        'down_burst_time', 'down_burst_threshold', 'down_burst_limit',
        'climit', 'plimit', 'dlimit');

    foreach ($items as $item) {
        if ($tariff[$item] == '') {
            $tariff[$item] = 0;
        } elseif (!preg_match('/^[0-9]+$/', $tariff[$item])) {
            $error[$item] = trans('Integer value expected!');
        }
    }

    if ($tariff['uprate'] < 8 && $tariff['uprate'] != 0) {
        $error['uprate'] = trans('This field must be greater than 8');
    }
    if ($tariff['downrate'] < 8 && $tariff['downrate'] != 0) {
        $error['downrate'] = trans('This field must be greater than 8');
    }
    if (($tariff['upceil'] < 8 || $tariff['upceil'] < $tariff['uprate']) && $tariff['upceil'] != 0) {
        $error['upceil'] = trans('This field must be greater than 8 and greater than upload rate');
    }
    if (($tariff['downceil'] < 8 || $tariff['downceil'] < $tariff['downrate']) && $tariff['downceil'] != 0) {
        $error['downceil'] = trans('This field must be greater than 8 and greater than download rate');
    }

    $validated = 0;
    foreach (array('down_burst_time', 'down_burst_threshold', 'down_burst_limit') as $item) {
        if ($tariff[$item]) {
            $validated++;
        }
    }
    if ($validated && $validated < 3) {
        $error['down_burst_time'] = $error['down_burst_threshold'] = $error['down_burst_limit'] =
            trans('Burst time, threshold and limit should not be empty values!');
    }
    if ($validated == 3) {
        if ($tariff['downceil']) {
            if ($tariff['down_burst_threshold'] && $tariff['down_burst_threshold'] > $tariff['downceil']) {
                $error['down_burst_threshold'] = trans('This field must be less than download ceil!');
            }
            if ($tariff['down_burst_limit'] && $tariff['down_burst_limit'] < $tariff['downceil']) {
                $error['down_burst_limit'] = trans('This field must be greater then download ceil!');
            }
        } else {
            $error['downceil'] = trans('This field must be greater than 8');
        }
    }

    $validated = 0;
    foreach (array('up_burst_time', 'up_burst_threshold', 'up_burst_limit') as $item) {
        if ($tariff[$item]) {
            $validated++;
        }
    }
    if ($validated && $validated < 3) {
        $error['up_burst_time'] = $error['up_burst_threshold'] = $error['up_burst_limit'] =
            trans('Burst time, threshold and limit should not be empty values!');
    }
    if ($validated == 3) {
        if ($tariff['upceil']) {
            if ($tariff['up_burst_threshold'] && $tariff['up_burst_threshold'] > $tariff['upceil']) {
                $error['up_burst_threshold'] = trans('This field must be less than upload ceil!');
            }
            if ($tariff['up_burst_limit'] && $tariff['up_burst_limit'] < $tariff['upceil']) {
                $error['up_burst_limit'] = trans('This field must be greater then upload ceil!');
            }
        } else {
            $error['upceil'] = trans('This field must be greater than 8');
        }
    }

    $items = array('uprate_n', 'downrate_n', 'upceil_n', 'downceil_n',
        'up_burst_time_n', 'up_burst_threshold_n', 'up_burst_limit_n',
        'down_burst_time_n', 'down_burst_threshold_n', 'down_burst_limit_n',
        'climit_n', 'plimit_n');

    foreach ($items as $item) {
        if ($tariff[$item] == '') {
            $tariff[$item] = null;
        } elseif (!preg_match('/^[0-9]+$/', $tariff[$item])) {
            $error[$item] = trans('Integer value expected!');
        }
    }

    if ($tariff['uprate_n'] < 8 && $tariff['uprate_n']) {
        $error['uprate_n'] = trans('This field must be greater than 8');
    }
    if ($tariff['downrate_n'] < 8 && $tariff['downrate_n']) {
        $error['downrate_n'] = trans('This field must be greater than 8');
    }
    if (($tariff['upceil_n'] < 8 || $tariff['upceil_n'] < $tariff['uprate']) && $tariff['upceil_n']) {
        $error['upceil_n'] = trans('This field must contain number greater than 8 and greater than upload rate');
    }
    if (($tariff['downceil_n'] < 8 || $tariff['downceil_n'] < $tariff['downrate']) && $tariff['downceil_n']) {
        $error['downceil_n'] = trans('This field must contain number greater than 8 and greater than download rate');
    }

    $validated = 0;
    foreach (array('down_burst_time_n', 'down_burst_threshold_n', 'down_burst_limit_n') as $item) {
        if ($tariff[$item]) {
            $validated++;
        }
    }
    if ($validated && $validated < 3) {
        $error['down_burst_time_n'] = $error['down_burst_threshold_n'] = $error['down_burst_limit_n'] =
            trans('Burst time, threshold and limit should not be empty values!');
    }
    if ($validated == 3) {
        if ($tariff['downceil_n']) {
            if ($tariff['down_burst_threshold_n'] && $tariff['down_burst_threshold_n'] > $tariff['downceil_n']) {
                $error['down_burst_threshold_n'] = trans('This field must be less than download ceil!');
            }
            if ($tariff['down_burst_limit_n'] && $tariff['down_burst_limit_n'] < $tariff['downceil_n']) {
                $error['down_burst_limit_n'] = trans('This field must be greater then download ceil!');
            }
        } else {
            $error['downceil_n'] = trans('This field must be greater than 8');
        }
    }

    $validated = 0;
    foreach (array('up_burst_time_n', 'up_burst_threshold_n', 'up_burst_limit_n') as $item) {
        if ($tariff[$item]) {
            $validated++;
        }
    }
    if ($validated && $validated < 3) {
        $error['up_burst_time_n'] = $error['up_burst_threshold_n'] = $error['up_burst_limit_n'] =
            trans('Burst time, threshold and limit should not be empty values!');
    }
    if ($validated == 3) {
        if ($tariff['upceil_n']) {
            if ($tariff['up_burst_threshold_n'] && $tariff['up_burst_threshold_n'] > $tariff['upceil_n']) {
                $error['up_burst_threshold_n'] = trans('This field must be less than upload ceil!');
            }
            if ($tariff['up_burst_limit_n'] && $tariff['up_burst_limit_n'] < $tariff['upceil_n']) {
                $error['up_burst_limit_n'] = trans('This field must be greater then upload ceil!');
            }
        } else {
            $error['upceil_n'] = trans('This field must be greater than 8');
        }
    }

    if (!isset($tariff['taxid'])) {
        $tariff['taxid'] = 0;
    }

    $authtype = 0;
    if (isset($tariff['authtype'])) {
        foreach ($tariff['authtype'] as $val) {
            $authtype |= intval($val);
        }
    }
    $tariff['authtype'] = $authtype;

    $items = array('domain_limit', 'alias_limit');
    foreach ($ACCOUNTTYPES as $typeidx => $type) {
        $items[] = $type['alias'] . '_limit';
        $items[] = 'quota_' . $type['alias'] . '_limit';
    }

    foreach ($items as $item) {
        if (isset($limit[$item])) {
            $tariff[$item] = null;
        } elseif (!preg_match('/^[0-9]+$/', $tariff[$item])) {
            $error[$item] = trans('Integer value expected!');
        }
    }

    if (!isset($CURRENCIES[$tariff['currency']])) {
        $error['currency'] = trans('Invalid currency selection!');
    }

    if (ConfigHelper::checkConfig('phpui.tax_category_required')
        && empty($tariff['taxcategory'])) {
        $error['taxcategory'] = trans('Tax category selection is required!');
    }

    if (!$error) {
        $LMS->TariffUpdate($tariff);

        $SESSION->restore('tariff_netflag', $netflag, true);
        $SESSION->restore('tariff_taxid', $taxid, true);
        if (!isset($tariff['netflag'])) {
            $tariff['netflag'] = 0;
        }
        if ($netflag != $tariff['netflag'] || $taxid != $tariff['taxid']) {
            $calculation_method = !empty($tariff['netflag']) ? 'from_net' : 'from_gross';
            $LMS->recalculateTariffPriceVariants($tariff, $calculation_method);
        }
        $SESSION->redirect('?m=tariffinfo&id='.$tariff['id']);
    }

    if (!empty($tariff['tags'])) {
        $tariff['tags'] = array_flip($tariff['tags']);
    }
} else {
    $tariff = $LMS->GetTariff($_GET['id']);

    if (!empty($tariff['customers']) && !ConfigHelper::checkPrivilege('used_tariff_edit')) {
        access_denied('You can\'t edit tariff which is assigned to customers through assignments!');
    }

    if ($tariff['dateto']) {
        $tariff['dateto'] = date('Y/m/d', $tariff['dateto']);
    }

    if ($tariff['datefrom']) {
        $tariff['datefrom'] = date('Y/m/d', $tariff['datefrom']);
    }

    $SESSION->save('tariff_netflag', $tariff['netflag'], true);
    $SESSION->save('tariff_taxid', $tariff['taxid'], true);
}

$layout['pagetitle'] = trans('Subscription Edit: $a', $tariff['name']);

$SMARTY->assign('voip_tariffs', $LMS->getVoipTariffs());
$SMARTY->assign('voip_tariffrules', $LMS->getVoipTariffRuleGroups());
$SMARTY->assign('tariff', $tariff);
$SMARTY->assign('tarifftags', $LMS->TarifftagGetAll());
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(array(
    'doctype' => DOC_INVOICE,
    'next' => false,
)));
$SMARTY->assign('error', $error);
$SMARTY->display('tariff/tariffedit.html');
