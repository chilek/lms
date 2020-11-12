<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$schema = $DB->GetRow(
    'SELECT s.*, p.id AS pid, p.name AS promotion
    FROM promotionschemas s
    JOIN promotions p ON (p.id = s.promotionid)
    WHERE s.id = ? AND s.deleted = 0 AND p.deleted = 0',
    array(intval($_GET['id']))
);

if (!$schema) {
    $SESSION->redirect('?m=promotionlist');
}

$schema['data'] = explode(';', $schema['data']);
$schema['periods'] = array(0 => trans('Activation'));

$mon = 1;
foreach ($schema['data'] as $idx => $data) {
    $period = '';
    if (!$data) {
        // unlimited
        break;
    } else if ($data == 1) {
        $period = trans('Month $a', $data);
        $mon++;
    } else {
        $period = trans('Months $a-$b', $mon, $mon + $data-1);
        $mon += $data;
    }
    $schema['periods'][] = $period;
}
$schema['periods'][] = trans('Months $a-', $mon);

$schema['data'] = implode(' &raquo; ', (array)$schema['data']);

$schema['tariffs'] = $DB->GetAll('SELECT t.name, t.value,
    a.tariffid, a.id, a.data, a.backwardperiod, a.optional, a.label
    FROM promotionassignments a
    JOIN tariffs t ON (a.tariffid = t.id)
    WHERE a.promotionschemaid = ?
    ORDER BY a.orderid', array($schema['id']));

$users = $LMS->GetUserNamesIndexedById();

if (!empty($schema['tariffs'])) {
    $schema['selections'] = array();
    foreach ($schema['tariffs'] as $idx => $value) {
        $tmp = explode(';', $value['data']);
        $data = array();
        foreach ($tmp as $didx => $d) {
            $cols = explode(':', $d);
            $data['value'][$didx] = $cols[0];
            $data['period'][$didx] = (count($cols) > 1 ? $cols[1] : null);
            $data['users'][$didx] = (count($cols) > 2 && !empty($cols[2]) ? explode(',', $cols[2]) : array());

            if (!empty($data['users'][$didx])) {
                $user_names = array();
                foreach ($data['users'][$didx] as $userid) {
                    $user_names[] = $users[$userid]['rname'];
                }
                $data['users_text'][$didx] = implode('<br>', $user_names);
            }
        }
        $schema['tariffs'][$idx]['data'] = $data;
        if (!empty($value['label'])) {
            $schema['selections'][] = $value['label'];
        }
    }
    $schema['selections'] = array_unique($schema['selections']);
}

$tariffs = $DB->GetAllByKey('SELECT t.id, t.name, t.value, t.currency, t.authtype,
				datefrom, dateto, (CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid,
				uprate, downrate, upceil, downceil,
				t.type AS tarifftype, ' . $DB->GroupConcat('ta.tarifftagid') . ' AS tags
				FROM tariffs t
				LEFT JOIN tariffassignments ta ON ta.tariffid = t.id
				WHERE t.disabled = 0' . (ConfigHelper::checkConfig('phpui.promotion_tariff_duplicates') ? '' : ' AND t.id NOT IN (
                    SELECT tariffid FROM promotionassignments
                    WHERE promotionschemaid = ' . $schema['id'] . ')') . '
				GROUP BY t.id, t.name, t.value, t.splitpayment, t.authtype, datefrom, dateto, uprate, downrate, upceil, downceil,
					t.type
				ORDER BY t.name, t.value DESC', 'id');

$layout['pagetitle'] = trans('Schema Info: $a', $schema['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('psdform', $formdata);
$SESSION->remove('psdform');
$SMARTY->assign('formdata', $formdata);

$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('tags', $LMS->TarifftagGetAll());
$SMARTY->assign('users', $users);
$SMARTY->assign('schema', $schema);
$SMARTY->display('promotion/promotionschemainfo.html');
