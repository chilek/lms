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

if (isset($_GET['delete'])) {
    if (ConfigHelper::checkPrivilege('customer_call_management')) {
        $LMS->deleteCustomerCall($_GET['cid'], $_GET['id']);
        $SESSION->redirect_to_history_entry();
    } else {
        access_denied();
    }
} elseif (isset($_GET['edit'])) {
    if (ConfigHelper::checkPrivilege('customer_call_management')) {
        if (isset($_POST['callid']) && intval($_POST['callid']) && isset($_POST['notes'])) {
            $LMS->updateCustomerCall(
                intval($_POST['callid']),
                array(
                    'notes' => $_POST['notes'],
                    'added-customers' => !empty($_POST['added-customers'])
                        ? Utils::filterIntegers($_POST['added-customers']):
                        array(),
                    'removed-customers' => !empty($_POST['removed-customers'])
                        ? Utils::filterIntegers($_POST['removed-customers'])
                        : array(),
                )
            );
        }
        header('Contet-Type: application/json');
        die('[]');
    } else {
        access_denied();
    }
} else {
    $LMS->getCustomerCallContent($_GET['id']);
}
