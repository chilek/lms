<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$suspensionId = $sid = isset($_GET['sid']) ? intval($_GET['sid']) : null;
$cid = isset($_GET['cid']) ? intval($_GET['cid']) : null;

if (!empty($suspensionId)) {
    if (!empty($cid)) {
        $customerId = $DB->GetOne('SELECT id FROM customerview WHERE id = ?', array($cid));
    }
    if (!$customerId) {
        $SESSION->redirect_to_history_entry();
    }

    $DB->BeginTrans();
    $DB->LockTables(array('suspensions', 'assignmentsuspensions'));

    $LMS->deleteSuspension($suspensionId);

    $DB->UnLockTables();
    $DB->CommitTrans();

    $backto = $SESSION->get_history_entry();
    // infinite loop prevention
    if (preg_match('/customerassignmentedit/', $backto)) {
        $backto = 'm=customerinfo&id=' . $customer;
    }
    $SESSION->redirect('?' . $backto);
}
