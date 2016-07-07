<?php

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
            if (!$LMS->TariffassignmentExist($assignment['tarifftagid'], $value))
                $LMS->TariffassignmentAdd($assignment);
        }
    }
}

$SESSION->redirect('?' . $SESSION->get('backto'));
