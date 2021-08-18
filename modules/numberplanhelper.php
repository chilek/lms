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

$documentType = intval($_POST['documentType']);
if (isset($_POST['cdate'])) {
    $cdate = strtotime($_POST['cdate']);
} else {
    $cdate = time();
}
$customerID = isset($_POST['customerID']) ? intval($_POST['customerID']) :  null;

$lms = LMS::getInstance();
$db = LMSDB::getInstance();

$args = array(
    'doctype' => $documentType,
    'cdate' => date('Y/m', $cdate),
);
if (!empty($customerID)) {
    $args['customerid'] = $customerID;
    $args['division'] = $db->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($customerID));
}

$numberplanlist = $lms->GetNumberPlans($args);
if (!$numberplanlist) {
    $numberplanlist = $lms->getSystemDefaultNumberPlan($args);
}

foreach ($numberplanlist as &$item) {
    $item['nextNumber'] = docnumber(array(
        'number' => $item['next'],
        'template' => $item['template'],
        'cdate' => $cdate,
        'customerid' => $customerID,
    ));
    $item['period_name'] = $NUM_PERIODS[$item['period']];
}

header('Content-Type: application/json');
die(json_encode($numberplanlist));
