<?php

use PragmaRX\Google2FA\Google2FA;

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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$LMS->UserExists($id)) {
    $SESSION->redirect('?m=userlist');
}

// ajax request handlers
if (isset($_GET['oper']) && $_GET['oper'] == 'loadtransactionlist') {
    header('Content-Type: text/html');

    if ($SYSLOG && ConfigHelper::checkPrivilege('transaction_logs')) {
        $trans = $SYSLOG->GetTransactions(array(
            'userid' => $id,
            'limit' => 300,
            'details' => true,
        ));
        $SMARTY->assign('transactions', $trans);
        $SMARTY->assign('userid', $id);
        die($SMARTY->fetch('transactionlist.html'));
    }

    die();
}

if (isset($_GET['fromuser'])) {
    header('Content-Type: application/json');
    $fromuser['rights'] = $LMS->GetUserRights($_GET['fromuser']);
    $fromuser['usergroups'] = array_keys($LMS->getUserAssignments($_GET['fromuser']));
    $fromuser['customergroups'] = array_diff(array_keys($LMS->getAllCustomerGroups()), $LMS->getExcludedCustomerGroups($_GET['fromuser']));
    $fromuser['divisions'] = array_keys($LMS->GetDivisions(array('userid' => $_GET['fromuser'])));
    die(json_encode($fromuser));
}
// end of ajax request handlers

if (isset($_GET['removetrusteddevices'])) {
    $AUTH->removeTrustedDevices($id, $_GET['deviceid'] ?? null);
    $SESSION->redirect($_SERVER['HTTP_REFERER']);
}

if (isset($_GET['forcepasswdchange'])) {
    $LMS->forcePasswordChange($id);
    $SESSION->redirect($_SERVER['HTTP_REFERER']);
}

$divisions = $LMS->GetDivisions();
$user_divisions = array_keys($LMS->GetDivisions(array('userid' => $id)));

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'usercopypermissions.inc.php');

$userinfo = $_POST['userinfo'] ?? array();

if ($userinfo) {
    $acl = $_POST['acl'];
    $userinfo['id'] = $id;

    foreach ($userinfo as $key => $value) {
        if (!is_array($value)) {
            $userinfo[$key] = trim($value);
        }
    }

    if ($userinfo['login'] == '') {
        $error['login'] = trans('Login can\'t be empty!');
    } elseif (!preg_match('/^[a-z0-9._-]+$/i', $userinfo['login'])) {
        $error['login'] = trans('Login contains forbidden characters!');
    } elseif ($LMS->GetUserIDByLogin($userinfo['login']) && $LMS->GetUserIDByLogin($userinfo['login']) != $id) {
        $error['login'] = trans('User with specified login exists or that login was used in the past!');
    }

    if ($userinfo['firstname'] == '') {
        $error['firstname'] = trans('You have to enter first and lastname!');
    }
    if ($userinfo['lastname'] == '') {
        $error['lastname'] = trans('You have to enter first and lastname!');
    }

    if (!isset($userinfo['divisions'])) {
        $error['division'] = trans('You have to choose division!');
    }

    if ($userinfo['email']!='' && !check_email($userinfo['email'])) {
        $error['email'] = trans('E-mail isn\'t correct!');
    }

    if (!empty($userinfo['accessfrom'])) {
        $accessfrom=date_to_timestamp($userinfo['accessfrom']);
        if (empty($accessfrom)) {
            $error['accessfrom'] = trans('Incorrect charging time!');
        }
    } else {
        $accessfrom = 0;
    }

    if (!empty($userinfo['accessto'])) {
        $accessto=date_to_timestamp($userinfo['accessto']);
        if (empty($accessto)) {
            $error['accessto'] = trans('Incorrect charging time!');
        }
    } else {
        $accessto = 0;
    }

    if ($accessto < $accessfrom && $accessto != 0 && $accessfrom != 0) {
        $error['accessto'] = trans('Incorrect date range!');
    }

    $rights = isset($acl) ? array_keys($acl) : array();
    $userinfo['rights'] = implode(',', $rights);

    if (!empty($userinfo['ntype'])) {
        $userinfo['ntype'] = array_sum(Utils::filterIntegers($userinfo['ntype']));
    }

    if ($userinfo['twofactorauth'] == 1) {
        if (strlen($userinfo['twofactorauthsecretkey']) != 16) {
            $error['twofactorauthsecretkey'] = trans('Incorrect secret key format!');
        } else {
            $google2fa = new Google2FA();
            if ($google2fa->removeInvalidChars($userinfo['twofactorauthsecretkey']) != $userinfo['twofactorauthsecretkey']) {
                $error['twofactorauthsecretkey'] = trans('Secret key contains invalid characters!');
            }
        }
    }

    foreach (array('hosts', 'trustedhosts') as $field) {
        if ($userinfo[$field]) {
            $hosts = preg_split("/([\s]+|[\s]*,[\s]*)/", $userinfo[$field], -1, PREG_SPLIT_NO_EMPTY);
            if (!empty($hosts)) {
                foreach ($hosts as $host) {
                    if (!check_ip($host) && !check_cidr($host)) {
                        $error[$field] = trans('Invalid address list format!');
                        break;
                    }
                }
            }
        }
    }

    if (!$error) {
        if ($userinfo['twofactorauth'] == -1) {
            $userinfo['twofactorauth'] = 1;
            $google2fa = new Google2FA();
            $userinfo['twofactorauthsecretkey'] = $google2fa->generateSecretKey();
        }

        $diffDivisionAdd = array();
        $diffDivisionDel = array();
        // check if user divisions were changed
        foreach ($user_divisions as $user_division) {
            if (in_array(intval($user_division), $userinfo['divisions'])) {
                continue;
            } else {
                $diffDivisionDel[] = intval($user_division);
            }
        }
        foreach ($userinfo['divisions'] as $userinfo_division) {
            if (in_array(intval($userinfo_division), $user_divisions)) {
                continue;
            } else {
                $diffDivisionAdd[] = intval($userinfo_division);
            }
        }
        $userinfo['diff_division_del'] = $diffDivisionDel;
        $userinfo['diff_division_add'] = $diffDivisionAdd;

        $userinfo['accessfrom'] = $accessfrom;
        $userinfo['accessto'] = $accessto;
        $LMS->UserUpdate($userinfo);

        if (isset($userinfo['copy-permissions']) && !empty($userinfo['src_userid'])) {
            $LMS->CopyPermissions($userinfo['src_userid'], $userinfo['id'], array_flip($userinfo['copy-permissions']));
            $LMS->executeHook(
                'user_modify_copy_permissions',
                array(
                    'src-userid' => $userinfo['src_userid'],
                    'dst-userid' => $userinfo['id'],
                    'copy-permissions' => $userinfo['copy-permissions'],
                )
            );
        }

        $SESSION->redirect('?m=userinfo&id='.$userinfo['id']);
    } else {
        $SMARTY->assign('selectedusergroups', !empty($userinfo['usergroups']) ? array_flip($userinfo['usergroups']) : array());

        $customergroups = $LMS->getAllCustomerGroups();
        $SMARTY->assign('selectedgroups', array_flip($userinfo['customergroups'] ?? array()));

        $access = AccessRights::getInstance();
        $accesslist = $access->getArray(array_keys($acl));
    }
} else {
    $rights = $LMS->GetUserRights($id);

    $access = AccessRights::getInstance();
    $accesslist = $access->getArray($rights);

    $selectedusergroups = $LMS->getUserAssignments($id);
    $SMARTY->assign('selectedusergroups', $selectedusergroups);

    $customergroups = $LMS->getAllCustomerGroups();
    $excludedgroups = $LMS->getExcludedCustomerGroups($id);
    $SMARTY->assign('selectedgroups', array_flip(array_diff(array_keys($customergroups), $excludedgroups)));
}

foreach ($LMS->GetUserInfo($id) as $key => $value) {
    if (!isset($userinfo[$key])) {
        $userinfo[$key] = $value;
    }
}

$layout['pagetitle'] = trans('User Edit: $a', $userinfo['login']);

$SESSION->add_history_entry();

$customercalls = $LMS->getCustomerCalls(array(
    'userid' => $userinfo['id'],
    'limit' => -1,
));

$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('customergroups', $customergroups);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('usergroups', $LMS->getAlluserGroups());
$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('customercalls', $customercalls);
$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('user_divisions', $user_divisions);
$SMARTY->assign('error', $error);

$SMARTY->display('user/useredit.html');
