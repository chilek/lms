<?php
/**
 * @author Maciej_Wawryk
 */

$id = !empty($_GET['id']) ? $_GET['id'] : null;

if (!$id || !$LMS->UsergroupExists($id)) {
    $SESSION->redirect('?m=usergrouplist');
}

$usergroup = $LMS->UsergroupGet($id);
$users = $LMS->GetUserWithoutGroupNames($id);
$userscount = count($users);

$layout['pagetitle'] = trans('Group Info: $a', $usergroup['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('usergroup', $usergroup);
$SMARTY->assign('users', $users);
$SMARTY->assign('userscount', $userscount);
$SMARTY->assign('membersnetid', isset($membersnetid) ? $membersnetid : 0);
$SMARTY->assign('othersnetid', isset($othersnetid) ? $othersnetid : 0);
$SMARTY->display('user/usergroupinfo.html');
