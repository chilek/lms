<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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
    'SELECT
        s.id, s.name, s.description, s.data, s.disabled, s.deleted, s.length, s.datefrom, s.dateto,
        p.id AS pid, p.name AS promotion,
        COUNT(a.id) AS assignments
    FROM promotionschemas s
    JOIN promotions p ON (p.id = s.promotionid)
    LEFT JOIN assignments a ON a.promotionschemaid = s.id
    WHERE s.id = ?
    GROUP BY s.id, s.name, s.description, s.data, s.disabled, s.deleted, s.length,
        p.id, p.name',
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
        $period = trans('Month $a', $mon);
        $mon++;
    } else {
        $period = trans('Months $a-$b', $mon, $mon + $data-1);
        $mon += $data;
    }
    $schema['periods'][] = $period;
}
$schema['periods'][] = trans('Months $a-$b', $mon, '&hellip;');

$schema['data'] = implode(' &raquo; ', (array)$schema['data']);

$schema['attachments'] = $DB->GetAllByKey(
    'SELECT *
    FROM promotionattachments
    WHERE promotionschemaid = ?',
    'id',
    array(
        $_GET['id'],
    )
);

$schema['tariffs'] = $DB->GetAll(
    'SELECT t.name, t.value, t.netvalue, t.type,
        a.tariffid, a.id, a.data, a.backwardperiod, a.optional, a.label,
        t.flags
    FROM promotionassignments a
    JOIN tariffs t ON (a.tariffid = t.id)
    WHERE a.promotionschemaid = ?
    ORDER BY a.orderid',
    array($schema['id'])
);

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
                    if (isset($users[$userid])) {
                        $user_names[] = $users[$userid]['rname'];
                    }
                }
                sort($user_names, SORT_STRING | SORT_FLAG_CASE);
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

$tariffs = $DB->GetAllByKey(
    'SELECT t.id, t.name, t.value, t.netvalue, t.currency, t.authtype, t.flags,
        datefrom, dateto, (CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid,
        uprate, downrate, upceil, downceil,
        t.type AS tarifftype, ' . $DB->GroupConcat('ta.tarifftagid') . ' AS tags,
        (CASE WHEN t.flags & ' . TARIFF_FLAG_SPLIT_PAYMENT . ' > 0 THEN 1 ELSE 0 END) AS splitpayment
    FROM tariffs t
    LEFT JOIN tariffassignments ta ON ta.tariffid = t.id
    WHERE t.disabled = 0'
        . (ConfigHelper::checkConfig(
            'promotions.tariff_duplicates',
            ConfigHelper::checkConfig('phpui.promotion_tariff_duplicates')
        )
            ? ''
            : ' AND t.id NOT IN (
                SELECT tariffid FROM promotionassignments
                WHERE promotionschemaid = ' . $schema['id'] . ')') . '
    GROUP BY t.id, t.name, t.value, t.netvalue, splitpayment, t.authtype, t.flags, datefrom, dateto, uprate, downrate, upceil, downceil, t.type
    ORDER BY t.name, t.value DESC',
    'id'
);

$layout['pagetitle'] = trans('Schema Info: $a', $schema['name']);

$SESSION->add_history_entry();

$SESSION->restore('psdform', $formdata);
$SESSION->remove('psdform');
$SMARTY->assign('formdata', $formdata);

$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('tags', $LMS->TarifftagGetAll());
$SMARTY->assign('users', $users);
$SMARTY->assign('schema', $schema);
$SMARTY->display('promotion/promotionschemainfo.html');
