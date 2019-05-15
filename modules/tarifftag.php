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

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'delete') {
    $LMS->TariffAssignmentDelete(array('tariffid' => intval($_GET['id']), 'tarifftagid' => $_GET['tarifftagid']));
} elseif ($action == 'add') {
    $tagid = intval($_POST['tarifftagid']);
    $uid = intval($_GET['id']);
    if ($LMS->TariffTagExists($tagid) && !$LMS->TariffassignmentExist($tagid, $uid) && $LMS->TariffExists($uid)) {
        $LMS->TariffAssignmentAdd(array('tariffid' => $uid, 'tarifftagid' => $tagid));
    }
} elseif (!empty($_POST['tariffassignments']) && $LMS->TariffTagExists($_GET['id'])) {
    $oper = $_POST['oper'];
    $tariffassignments = $_POST['tariffassignments'];

    if (isset($tariffassignments['gmtariffid']) && $oper == '0') {
        $assignment['tarifftagid'] = $_GET['id'];
        foreach ($tariffassignments['gmtariffid'] as $value) {
            $assignment['tariffid'] = $value;
            $LMS->TariffassignmentDelete($assignment);
        }
    } elseif (isset($tariffassignments['mtariffid']) && $oper == '1') {
        $assignment['tarifftagid'] = $_GET['id'];
        foreach ($tariffassignments['mtariffid'] as $value) {
            $assignment['tariffid'] = $value;
            if (!$LMS->TariffassignmentExist($assignment['tarifftagid'], $value)) {
                $LMS->TariffassignmentAdd($assignment);
            }
        }
    }
}

$SESSION->redirect('?' . $SESSION->get('backto'));
