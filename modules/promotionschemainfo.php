<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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


$schema = $DB->GetRow('SELECT s.*, p.id AS pid, p.name AS promotion,
    t.name AS tariff, t.value
    FROM promotionschemas s
    JOIN promotions p ON (p.id = s.promotionid)
    LEFT JOIN tariffs t ON (t.id = s.ctariffid)
    WHERE s.id = ?',
    array(intval($_GET['id'])));

if(!$schema)
{
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
    }
    else if ($data == 1) {
        $period = trans('Month $a', $data);
        $mon++;
    }
    else {
        $period = trans('Months $a-$b', $mon, $mon + $data-1);
        $mon += $data;
    }
    $schema['periods'][] = $period;
}

$schema['data'] = implode(' &raquo; ', (array)$schema['data']);

$schema['tariffs'] = $DB->GetAll('SELECT t.name, t.value,
    a.tariffid, a.id, a.data
    FROM promotionassignments a
    JOIN tariffs t ON (a.tariffid = t.id)
    WHERE a.promotionschemaid = ?
    ORDER BY t.name, t.value DESC', array($schema['id']));

if (!empty($schema['tariffs'])) {
    foreach ($schema['tariffs'] as $idx => $value) {
        $tmp = explode(';', $value['data']);
        $data = array();
        foreach ($tmp as $didx => $d) {
            list($data['value'][$didx], $data['period'][$didx]) = explode(':', $d);
        }
        $schema['tariffs'][$idx]['data'] = $data;
    }
}

$tariffs = $DB->GetAll('SELECT t.name, t.value, t.id, t.upceil, t.downceil
    FROM tariffs t
    WHERE t.id NOT IN (
        SELECT tariffid FROM promotionassignments
        WHERE promotionschemaid = ?)
    ORDER BY t.name, t.value DESC', array($schema['id']));

$layout['pagetitle'] = trans('Schema Info: $a', $schema['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('schema', $schema);
$SMARTY->display('promotionschemainfo.html');

?>
