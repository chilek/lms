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

if (!isset($_POST['customerid']) || !ctype_digit($_POST['customerid'])
    || !isset($_POST['old-customerid']) || !ctype_digit($_POST['old-customerid'])
    || !isset($_POST['marks']) || !is_array($_POST['marks'])) {
    $SESSION->redirect_to_history_entry();
}

$customerid = intval($_POST['customerid']);
$old_customerid = intval($_POST['old-customerid']);
$marks = Utils::filterIntegers($_POST['marks']);

if (empty($customerid) || empty($old_customerid) || $customerid == $old_customerid || empty($marks)) {
    $SESSION->redirect_to_history_entry();
}

if (!$LMS->CustomerExists($customerid) || !$LMS->CustomerExists($old_customerid)) {
    $SESSION->redirect_to_history_entry();
}

$cashes = $DB->GetAll(
    'SELECT id, comment, value, currency, currencyvalue 
    FROM cash
    WHERE customerid = ?
        AND value > 0
        AND type = 1
        AND id IN ?',
    array(
        $old_customerid,
        $marks,
    )
);

if (!empty($cashes)) {
    $old_customername = $LMS->GetCustomerName($old_customerid);
    $customername = $LMS->GetCustomerName($customerid);

    $userid = Auth::GetCurrentUser();

    $DB->BeginTrans();

    foreach ($cashes as $cash) {
        $DB->Execute(
            'INSERT INTO cash
            (time, userid, customerid, type, comment, value, currency, currencyvalue)
            VALUES (?NOW?, ?, ?, ?, ?, ?, ?, ?)',
            array(
                $userid,
                $old_customerid,
                1,
                trans('transferred to customer $a (#$b): $c', $customername, $customerid, $cash['comment']),
                str_replace(',', '.', $cash['value'] * -1),
                $cash['currency'],
                $cash['currencyvalue'],
            )
        );

        $DB->Execute(
            'INSERT INTO cash
            (time, userid, customerid, type, comment, value, currency, currencyvalue)
            VALUES (?NOW?, ?, ?, ?, ?, ?, ?, ?)',
            array(
                $userid,
                $customerid,
                1,
                trans('transferred from customer $a (#$b): $c', $old_customername, $old_customerid, $cash['comment']),
                $cash['value'],
                $cash['currency'],
                $cash['currencyvalue'],
            )
        );
    }

    $DB->CommitTrans();
}


$SESSION->redirect_to_history_entry();
