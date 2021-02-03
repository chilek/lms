<?php
/**
 * @author Maciej_Wawryk
 */

if (isset($_POST['usergroupadd'])) {
    $usergroupadd = $_POST['usergroupadd'];

    foreach ($usergroupadd as $key => $value) {
        $usergroupadd[$key] = trim($value);
    }

    if ($usergroupadd['name']=='' && $usergroupadd['description']=='') {
        $SESSION->redirect('?m=usergrouplist');
    }

    if ($usergroupadd['name'] == '') {
        $error['name'] = trans('Group name required!');
    } elseif (strlen($usergroupadd['name']) > 255) {
        $error['name'] = trans('Group name is too long!');
    } elseif (!preg_match('/^[._a-z0-9-]+$/i', $usergroupadd['name'])) {
        $error['name'] = trans('Invalid chars in group name!');
    } elseif ($LMS->UsergroupGetId($usergroupadd['name'])) {
        $error['name'] = trans('Group with name $a already exists!', $usergroupadd['name']);
    }

    if (!$error) {
        $SESSION->redirect('?m=usergrouplist&id='.$LMS->UsergroupAdd($usergroupadd));
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('usergroupadd', $usergroupadd);
}

$layout['pagetitle'] = trans('New Group');
$SMARTY->display('user/usergroupadd.html');
