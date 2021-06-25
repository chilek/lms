<?php

$event = $LMS->GetEvent($_GET['id']);

$event['date'] = sprintf('%04d/%02d/%02d', date('Y', $event['date']), date('n', $event['date']), date('j', $event['date']));

$eventuserlist = $DB->GetAll('SELECT userid AS id, vusers.name
	FROM vusers, eventassignments
	WHERE vusers.id = userid AND eventid = ?', array($event['id']));

if ($SESSION->is_set('backto', true)) {
    $backto = $SESSION->get('backto', true);
} elseif ($SESSION->is_set('backto')) {
    $backto = $SESSION->get('backto');
} else {
    $backto = 'm=eventlist';
}
$backid = $SESSION->get('backid');
$backurl = '?' . $backto . (empty($backid) ? '' : '#' . $backid);

if (isset($_POST['event'])) {
    $event = $_POST['event'];
    $event['id'] = $_GET['id'];
    $DB->Execute('UPDATE events SET note=? WHERE id=?', array($event['note'], $event['id']));
    $SESSION->redirect($backurl);
}

$SMARTY->assign('backurl', $backurl);

$event['userlist'] = ($eventuserlist) ? $eventuserlist : array();
$layout['pagetitle'] = trans('Add Note');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SMARTY->assign('event', $event);
$SMARTY->display('event/eventnote.html');
