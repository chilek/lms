<?php
/**
 * @author Maciej_Wawryk
 */

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'delete') {
    $LMS->UserAssignmentDelete(array('userid' => intval($_GET['id']),'usergroupid' => $_GET['usergroupid']));
} elseif ($action == 'add') {
    $groupid = intval($_POST['usergroupid']);
    $uid = intval($_GET['id']);
    if ($LMS->UserGroupExists($groupid) && !$LMS->UserassignmentExist($groupid, $uid) && $LMS->UserExists($uid)) {
        $LMS->UserAssignmentAdd(array('userid' => $uid, 'usergroupid' => $groupid));
    }
} elseif (!empty($_POST['userassignments']) && $LMS->UserGroupExists($_GET['id'])) {
    $oper = $_POST['oper'];
    $userassignments = $_POST['userassignments'];
    
    if (isset($userassignments['gmuserid']) && $oper=='0') {
        $assignment['usergroupid'] = $_GET['id'];
        foreach ($userassignments['gmuserid'] as $value) {
            $assignment['userid'] = $value;
            $LMS->UserassignmentDelete($assignment);
        }
    } elseif (isset($userassignments['muserid']) && $oper=='1') {
        $assignment['usergroupid'] = $_GET['id'];
        foreach ($userassignments['muserid'] as $value) {
            $assignment['userid'] = $value;
            if (! $LMS->UserassignmentExist($assignment['usergroupid'], $value)) {
                $LMS->UserassignmentAdd($assignment);
            }
        }
    }
}

$SESSION->redirect('?'.$SESSION->get('backto'));
