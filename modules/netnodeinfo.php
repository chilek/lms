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

$id = intval($_GET['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Net Device Node Info: $a', $info['name']);

$result = $LMS->GetNetNode($id);

if (!$result)
	$SESSION->redirect('?m=netnodelist');

if ($nodeinfo['ownerid']) {
	$nodeinfo['owner'] = $LMS->getCustomerName( $nodeinfo['ownerid'] );
}

$SMARTY->assign('nodeinfo', $result);
$SMARTY->assign('objectid', $result['id']);

$attachmenttype = 'netnodeid';
$attachmentresourceid = $id;
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'attachments.php');

$nlist = $DB->GetAll("SELECT * FROM netdevices WHERE netnodeid=? ORDER BY name", array($id));
$SMARTY->assign('netdevlist', $nlist);

$queue = $LMS->GetQueueContents(array('ids' => null, 'order' => null, 'state' => null, 'priority' => null,
	'owner' => -1, 'catids' => null, 'removed' => null, 'netdevids' => null, 'netnodeids' => $id));
$total = $queue['total'];
unset($queue['total']);
unset($queue['state']);
unset($queue['order']);
unset($queue['direction']);
unset($queue['owner']);
unset($queue['removed']);
unset($queue['priority']);
unset($queue['deadline']);
unset($queue['service']);
unset($queue['type']);
unset($queue['unread']);
unset($queue['rights']);

$SMARTY->assign('queue', $queue);

$start = 0;
$pagelimit = ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $total);
$SMARTY->assign('start', $start);
$SMARTY->assign('pagelimit', $pagelimit);

$SMARTY->assign('netnodeinfo_sortable_order', $SESSION->get_persistent_setting('netnodeinfo-sortable-order'));
$SMARTY->display('netnode/netnodeinfo.html');

?>
