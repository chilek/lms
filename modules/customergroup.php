<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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
    if (isset($_GET['customergroupid'])) {
        $customergroupids = array($_GET['customergroupid']);
    } elseif (isset($_POST['markedcustomergroupid'])) {
        $customergroupids = $_POST['markedcustomergroupid'];
    }
    if (isset($customergroupids) && !empty($customergroupids)) {
        foreach ($customergroupids as $customergroupid) {
            $LMS->CustomerAssignmentDelete(
                array('customerid' => intval($_GET['id']),
                'customergroupid' => $customergroupid)
            );
        }
    }
} elseif ($action == 'add') {
    $groupids = $_POST['customergroupid'];
    if (!is_array($groupids)) {
        $groupids = array($groupids);
    }
    $uid = intval($_GET['id']);

    if (!empty($groupids)) {
        foreach ($groupids as $groupid) {
            if ($LMS->CustomerGroupExists($groupid)
            && !$LMS->CustomerassignmentExist($groupid, $uid)
            && $LMS->CustomerExists($uid)) {
                $LMS->CustomerAssignmentAdd(
                    array('customerid' => $uid, 'customergroupid' => $groupid)
                );
            }
        }
    }
} elseif (!empty($_POST['setwarnings'])) {
    $setwarnings = $_POST['setwarnings'];
    $oper = isset($_GET['oper']) ? $_GET['oper'] : '';

    if (isset($setwarnings['customergroup'])) {
        if (is_array($setwarnings['customergroup'])) {
            $groups = $setwarnings['customergroup'];
        } else {
            $groups = array($setwarnings['customergroup']);
        }
        $groups = Utils::filterIntegers($groups);
    } elseif (isset($setwarnings['newcustomergroup']) && !empty($setwarnings['newcustomergroup'])) {
        $groups = array($LMS->CustomergroupAdd(array(
            'name' => $setwarnings['newcustomergroup'],
            'description' => '',
        )));
    } else {
        $groups = null;
    }

    if ($oper != '' && !empty($groups)) {
        foreach ($setwarnings['mcustomerid'] as $cid) {
            $customerassignmentdata['customerid'] = $cid;
            switch ($oper) {
                case 'addtogroups':
                    foreach ($groups as $groupid) {
                        if (!$LMS->CustomerassignmentExist($groupid, $cid)) {
                            $customerassignmentdata['customergroupid'] = $groupid;
                            $LMS->CustomerassignmentAdd($customerassignmentdata);
                        }
                    }
                    break;
                case 'removefromgroups':
                    foreach ($groups as $groupid) {
                        $customerassignmentdata['customergroupid'] = $groupid;
                        $LMS->CustomerassignmentDelete($customerassignmentdata);
                    }
                    break;
                case 'changegroups':
                    unset($customerassignmentdata['customergroupid']);
                    $LMS->CustomerassignmentDelete($customerassignmentdata);
                    foreach ($groups as $groupid) {
                        $customerassignmentdata['customergroupid'] = $groupid;
                        $LMS->CustomerassignmentAdd($customerassignmentdata);
                    }
                    break;
            }
        }
    }
} elseif (!empty($_POST['customerassignments']) && $LMS->CustomerGroupExists($_GET['id'])) {
    $oper = $_POST['oper'];
    $customerassignments = $_POST['customerassignments'];

    if (isset($customerassignments['gmcustomerid']) && $oper=='0') {
        $assignment['customergroupid'] = $_GET['id'];
        foreach ($customerassignments['gmcustomerid'] as $value) {
            $assignment['customerid'] = $value;
            $LMS->CustomerassignmentDelete($assignment);
        }
    } elseif (isset($customerassignments['mcustomerid']) && $oper=='1') {
        $assignment['customergroupid'] = $_GET['id'];
        foreach ($customerassignments['mcustomerid'] as $value) {
            $assignment['customerid'] = $value;
            if (! $LMS->CustomerassignmentExist($assignment['customergroupid'], $value)) {
                $LMS->CustomerassignmentAdd($assignment);
            }
        }
    } elseif ($oper=='2' || $oper=='3') {
        $SESSION->redirect('?'.preg_replace('/&[a-z]*id=[0-9]+/i', '', $SESSION->get('backto')).'&id='.$_GET['id']
            .(isset($customerassignments['membersnetid']) && $customerassignments['membersnetid'] != '0' ? '&membersnetid='.$customerassignments['membersnetid'] : '')
            .(isset($customerassignments['othersnetid']) && $customerassignments['othersnetid'] != '0' ? '&othersnetid='.$customerassignments['othersnetid'] : ''));
    }
}

$SESSION->redirect('?'.$SESSION->get('backto'));
