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

$taxrate = $DB->GetRow('SELECT * FROM taxes WHERE id=?', array($_GET['id']));

if (!$taxrate) {
    $SESSION->redirect('?m=taxratelist');
}

$label = $taxrate['label'];

if (!$taxrate['validfrom']) {
    $taxrate['validfrom'] = '';
} else {
    $taxrate['validfrom'] = date('Y/m/d', $taxrate['validfrom']);
}
if (!$taxrate['validto']) {
    $taxrate['validto'] = '';
} else {
    $taxrate['validto'] = date('Y/m/d', $taxrate['validto']);
}

$taxrateedit = isset($_POST['taxrateedit']) ? $_POST['taxrateedit'] : null;

if (is_array($taxrateedit) && count($taxrateedit)) {
    foreach ($taxrateedit as $idx => $key) {
        $taxrateedit[$idx] = trim($key);
    }

    $taxrateedit['id'] = $taxrate['id'];

    if ($taxrateedit['label'] == '') {
        $error['label'] = trans('Tax rate label is required!');
    } elseif (strlen($taxrateedit['label'])>16) {
        $error['label'] = trans('Label is too long (max.16)!');
    }

    $taxrateedit['value'] = str_replace(',', '.', $taxrateedit['value']);
    if (!is_numeric($taxrateedit['value'])) {
        $error['value'] = trans('Tax rate value is not numeric!');
    } elseif ($taxrateedit['value']<0 || $taxrateedit['value']>100) {
        $error['value'] = trans('Incorrect tax rate percentage value (0-100)!');
    } elseif ($taxrateedit['value'] != $taxrate['value']) {
        if ($DB->GetOne('SELECT COUNT(*) FROM cash WHERE taxid=?', array($taxrateedit['id'])) +
            $DB->GetOne('SELECT COUNT(*) FROM invoicecontents WHERE taxid=?', array($taxrateedit['id'])) > 0) {
            $error['value'] = trans('Can\'t change value of tax rate which was used in the past!');
        }
    }

    if (!$taxrateedit['taxed']) {
        $taxrateedit['taxed'] = 0;
    }
        
    if (!$taxrateedit['taxed'] && $taxrateedit['value']!=0) {
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
            'label' => $taxrateedit['label'],
            'value' => $taxrateedit['value'],
            'taxed' => $taxrateedit['taxed'],
            'reversecharge' => isset($taxrateedit['reversecharge']) ? intval($taxrateedit['reversecharge']) : 0,
            'validfrom' => $validfrom,
            'validto' => $validto,
            SYSLOG::RES_TAX => $taxrateedit['id']
        );
        $DB->Execute(
            'UPDATE taxes SET label=?, value=?, taxed=?, reversecharge=?, validfrom=?,validto=? WHERE id=?',
            array_values($args)
        );

        if ($SYSLOG) {
            $SYSLOG->AddMessage(SYSLOG::RES_TAX, SYSLOG::OPER_UPDATE, $args);
        }

        $SESSION->redirect('?m=taxratelist');
    } else {
        $taxrate = $taxrateedit;
    }
}

$layout['pagetitle'] = trans('Tax Rate Edit: $a', $label);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('taxrateedit', $taxrate);
$SMARTY->assign('error', $error);
$SMARTY->display('taxrate/taxrateedit.html');

?>
<?php
