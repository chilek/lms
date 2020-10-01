<?php

use PragmaRX\Google2FA\Google2FA;

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

if (isset($_GET['fromuser'])) {
    header('Content-Type: application/json');
    $fromuser['rights'] = $LMS->GetUserRights($_GET['fromuser']);
    $fromuser['usergroups'] = array_keys($LMS->getUserAssignments($_GET['fromuser']));
    $fromuser['customergroups'] = array_diff(array_keys($LMS->getAllCustomerGroups()), $LMS->getExcludedCustomerGroups($_GET['fromuser']));
    $fromuser['divisions'] = array_keys($LMS->GetDivisions(array('userid' => $_GET['fromuser'])));
    die(json_encode($fromuser));
}

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'usercopypermissions.inc.php');

$acl = isset($_POST['acl']) ? $_POST['acl'] : array();
$useradd = isset($_POST['useradd']) ? $_POST['useradd'] : array();

if (count($useradd)) {
    $error = array();

    foreach ($useradd as $key => $value) {
        if (!is_array($value)) {
            $useradd[$key] = trim($value);
        }
    }

    if ($useradd['login'] == '' && $useradd['firstname'] == '' && $useradd['lastname'] == '' && $useradd['password'] == '' && $useradd['confirm'] == '' && !isset($useradd['divisions'])) {
        $SESSION->redirect('?m=useradd');
    }

    if ($useradd['login'] == '') {
        $error['login'] = trans('Login can\'t be empty!');
    } elseif (!preg_match('/^[a-z0-9.-_]+$/i', $useradd['login'])) {
        $error['login'] = trans('Login contains forbidden characters!');
    } elseif ($LMS->GetUserIDByLogin($useradd['login'])) {
        $error['login'] = trans('User with specified login exists or that login was used in the past!');
    }

    if ($useradd['email'] != '' && !check_email($useradd['email'])) {
        $error['email'] = trans('E-mail isn\'t correct!');
    }

    if ($useradd['firstname'] == '') {
        $error['firstname'] = trans('You have to enter first and lastname!');
    }
    if ($useradd['lastname'] == '') {
        $error['lastname'] = trans('You have to enter first and lastname!');
    }

    if (!isset($useradd['divisions'])) {
        $error['division'] = trans('You have to choose division!');
    }

    if ($useradd['password'] == '') {
        $error['password'] = trans('Empty passwords are not allowed!');
    } elseif ($useradd['password'] != $useradd['confirm']) {
        $error['password'] = trans('Passwords does not match!');
    } elseif (!check_password_strength($useradd['password'])) {
        $error['password'] = trans('The password should contain at least one capital letter, one lower case letter, one digit and should consist of at least 8 characters!');
    }

    if (!empty($useradd['accessfrom'])) {
        $accessfrom = date_to_timestamp($useradd['accessfrom']);
        if (empty($accessfrom)) {
            $error['accessfrom'] = trans('Incorrect charging time!');
        }
    } else {
        $accessfrom = 0;
    }

    if (!empty($useradd['accessto'])) {
        $accessto = date_to_timestamp($useradd['accessto']);
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
    $useradd['rights'] = implode(',', $rights);

    if (!empty($useradd['ntype'])) {
        $useradd['ntype'] = array_sum(Utils::filterIntegers($useradd['ntype']));
    }

    if ($useradd['twofactorauth'] == 1) {
        if (strlen($useradd['twofactorauthsecretkey']) != 16) {
            $error['twofactorauthsecretkey'] = trans('Incorrect secret key format!');
        } else {
            $google2fa = new Google2FA();
            if ($google2fa->removeInvalidChars($useradd['twofactorauthsecretkey']) != $useradd['twofactorauthsecretkey']) {
                $error['twofactorauthsecretkey'] = trans('Secret key contains invalid characters!');
            }
        }
    }

    $hook_data = $LMS->executeHook('useradd_validation_before_submit', array(
        'useradd' => $useradd,
        'error' => $error
    ));
    $useradd = $hook_data['useradd'];
    $error = $hook_data['error'];

    if (!$error) {
        if ($useradd['twofactorauth'] == -1) {
            $useradd['twofactorauth'] = 1;
            $google2fa = new Google2FA();
            $useradd['twofactorauthsecretkey'] = $google2fa->generateSecretKey();
        } elseif (empty($useradd['twofactorauth'])) {
            $useradd['twofactorauthsecretkey'] = null;
        }

        $useradd['accessfrom'] = $accessfrom;
        $useradd['accessto'] = $accessto;
        $id = $LMS->UserAdd($useradd);

        if (isset($useradd['copy-permissions']) && !empty($useradd['src_userid'])) {
            $LMS->CopyPermissions($useradd['src_userid'], $id, array_flip($useradd['copy-permissions']));
            $LMS->executeHook(
                'user_modify_copy_permissions',
                array(
                    'src-userid' => $useradd['src_userid'],
                    'dst-userid' => $id,
                    'copy-permissions' => $useradd['copy-permissions'],
                )
            );
        }

        $LMS->executeHook('useradd_after_submit', $id);
        $SESSION->redirect('?m=userinfo&id=' . $id);
    } else {
        $SMARTY->assign('selectedusergroups', array_flip(isset($useradd['usergroups']) ? $useradd['usergroups'] : array()));
        $SMARTY->assign('selectedgroups', array_flip(isset($useradd['customergroups']) ? $useradd['customergroups'] : array()));
    }
} else {
    $useradd['ntype'] = MSG_MAIL | MSG_SMS;
}

$rights = isset($acl) ? array_keys($acl) : array();
$access = AccessRights::getInstance();
$accesslist = $access->getArray($rights);

if ($AUTH->nousers == true) {           // if there is no users
    $accesslist['full_access']['enabled'] = 1;       // then new users should have "full privileges" checked to make new installation more human error proof.
}

$layout['pagetitle'] = trans('New User');

$SMARTY->assign('useradd', $useradd);
$SMARTY->assign('error', $error);
$SMARTY->assign('usergroups', $LMS->getAllUserGroups());
$SMARTY->assign('customergroups', $LMS->getAllCustomerGroups());
$SMARTY->assign('accesslist', $accesslist);
$SMARTY->assign('users', $LMS->GetUserNames());
$SMARTY->assign('available', $DB->GetAllByKey('SELECT id, name FROM customergroups ORDER BY name', 'id'));
$SMARTY->assign('divisions', $LMS->GetDivisions());
$SMARTY->display('user/useradd.html');
