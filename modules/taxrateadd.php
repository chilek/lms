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

$taxrateadd = isset($_POST['taxrateadd']) ? $_POST['taxrateadd'] : null;

if (count($taxrateadd)) {
    foreach ($taxrateadd as $idx => $key) {
        $taxrateadd[$idx] = trim($key);
    }

    if ($taxrateadd['label']=='' && $taxrateadd['value']=='') {
        $SESSION->redirect('?m=taxratelist');
    }
    
    if ($taxrateadd['label'] == '') {
        $error['label'] = trans('Tax rate label is required!');
    } elseif (strlen($taxrateadd['label'])>16) {
        $error['label'] = trans('Label is too long (max.16)!');
    }

    $taxrateadd['value'] = str_replace(',', '.', $taxrateadd['value']);
    if (!is_numeric($taxrateadd['value'])) {
        $error['value'] = trans('Tax rate value is not numeric!');
    } elseif ($taxrateadd['value']<0 || $taxrateadd['value']>100) {
        $error['value'] = trans('Incorrect tax rate percentage value (0-100)!');
    }

    if (!$taxrateadd['taxed']) {
        $taxrateadd['taxed'] = 0;
    }
        
    if (!$taxrateadd['taxed'] && $taxrateadd['value']!=0) {
        $error['value'] = trans('Incorrect tax rate percentage value (non-zero value and taxing not checked)!');
    }

    if (!empty($taxrateedit['validfrom'])) {
            $validfrom = date_to_timestamp($taxrateedit['validfrom']);
        if (empty($validfrom)) {
                $error['validfrom'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        }
    } else {
        $validfrom = 0;
    }

    if (!empty($taxrateedit['validto'])) {
            $validto = date_to_timestamp($taxrateedit['validto']);
        if (empty($validto)) {
                $error['validto'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        }
    } else {
        $validto = 0;
    }
    
    if (!$error) {
        $args = array(
            'label' => $taxrateadd['label'],
            'value' => $taxrateadd['value'],
            'taxed' => $taxrateadd['taxed'],
            'reversecharge' => isset($taxrateadd['reversecharge']) ? intval($taxrateadd['reversecharge']) : 0,
            'validfrom' => $validfrom,
            'validto' => $validto,
        );
        $DB->Execute('INSERT INTO taxes (label, value, taxed, reversecharge, validfrom, validto)
				VALUES (?,?,?,?,?,?)', array_values($args));

        if ($SYSLOG) {
            $args[SYSLOG::RES_TAX] = $DB->GetLastInsertID('taxes');
            $SYSLOG->AddMessage(SYSLOG::RES_TAX, SYSLOG::OPER_ADD, $args);
        }

        if (!isset($taxrateadd['reuse'])) {
            $SESSION->redirect('?m=taxratelist');
        }
        unset($taxrateadd['label']);
        unset($taxrateadd['value']);
        unset($taxrateadd['validfrom']);
        unset($taxrateadd['validto']);
    }
}

$layout['pagetitle'] = trans('New Tax Rate');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('taxrateadd', $taxrateadd);
$SMARTY->assign('error', $error);
$SMARTY->display('taxrate/taxrateadd.html');
