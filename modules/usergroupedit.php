<?php
/**
 * @author Maciej_Wawryk
 */

if (!$LMS->UsergroupExists($_GET['id'])) {
    $SESSION->redirect('?m=usergrouplist');
}
if (isset($_POST['userassignments'])) {
    $oper = $_POST['oper'];
    $userassignments = $_POST['userassignments'];
}
$usergroup = $LMS->UsergroupGet($_GET['id']);
$users = $LMS->GetUserWithoutGroupNames($_GET['id']);

$layout['pagetitle'] = trans('Group Edit: $a', $usergroup['name']);

if (isset($_POST['usergroup'])) {
    $usergroupedit = $_POST['usergroup'];
    foreach ($usergroupedit as $key => $value) {
        $usergroupedit[$key] = trim($value);
    }

    $usergroupedit['id'] = $_GET['id'];

    if ($usergroupedit['name'] == '') {
        $error['name'] = trans('Group name required!');
    } elseif (strlen($usergroupedit['name']) > 255) {
        $error['name'] = trans('Group name is too long!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $usergroupedit['name'])) {
        $error['name'] = trans('Invalid chars in group name!');
    } elseif (($id = $LMS->UsergroupGetId($usergroupedit['name'])) && $id != $usergroupedit['id']) {
        $error['name'] = trans('Group with name $a already exists!', $usergroupedit['name']);
    }

    if (!$error) {
        $LMS->UsergroupUpdate($usergroupedit);
        $SESSION->redirect('?m=usergroupinfo&id='.$usergroup['id']);
    }

    $usergroup['description'] = $usergroupedit['description'];
    $usergroup['name'] = $usergroupedit['name'];
}


$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('usergroup', $usergroup);
$SMARTY->assign('error', $error);
$SMARTY->assign('users', $users);
$SMARTY->assign('userscount', count($users));
$SMARTY->assign('usergroups', $LMS->getAllUserGroups());
$SMARTY->display('user/usergroupedit.html');
