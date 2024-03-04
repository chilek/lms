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

$documentType = empty($_POST['documentType']) ? null : intval($_POST['documentType']);
$cdate = empty($_POST['cdate']) ? time() : strtotime($_POST['cdate']);
$customerID = empty($_POST['customerID']) ? null :  intval($_POST['customerID']);
$numberplanlist = array();

$lms = LMS::getInstance();
$db = LMSDB::getInstance();

if ($documentType) {
    $args = array(
        'doctype' => $documentType,
        'cdate' => $cdate,
    );
    if (!empty($customerID)) {
        $args['customerid'] = $customerID;
        $customer = $db->GetRow('SELECT divisionid, type FROM customers WHERE id = ?', array($customerID));
        $args['division'] = $customer['divisionid'];
        $args['customertype'] = $customer['type'];
    }
    $numberplanlist = $lms->GetNumberPlans($args);
    if (!$numberplanlist) {
        $numberplanlist = $lms->getSystemDefaultNumberPlan($args);
    }
} else {
    $args = array(
        'cdate' => $cdate,
    );
    $numberplanlist = $lms->getSystemDefaultNumberPlan($args);
}

if ($numberplanlist) {
    foreach ($numberplanlist as &$item) {
        $item['nextNumber'] = docnumber(array(
            'number' => $item['next'],
            'template' => $item['template'],
            'cdate' => $cdate,
            'customerid' => $customerID,
        ));
        $item['period_name'] = $NUM_PERIODS[$item['period']];
    }
    $numberplanlist = array_values($numberplanlist);
}

header('Content-Type: application/json');
die(json_encode($numberplanlist));
