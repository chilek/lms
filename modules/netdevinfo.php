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

if (!$LMS->NetDevExists($id)) {
	$SESSION->redirect('?m=netdevlist');
}

$LMS->InitXajax();
include(MODULES_DIR . DIRECTORY_SEPARATOR . 'netdevxajax.inc.php');
$SMARTY->assign('xajax', $LMS->RunXajax());

if (!isset($_POST['xjxfun'])) {                  // xajax was called and handled by netdevxajax.inc.php

	$attachmenttype = 'netdevid';
	$attachmentresourceid = $id;
	include(MODULES_DIR . DIRECTORY_SEPARATOR . 'attachments.php');

	$netdevinfo = $LMS->GetNetDev($id);
	$netdevconnected = $LMS->GetNetDevConnectedNames($id);
	$netcomplist = $LMS->GetNetdevLinkedNodes($id);
	$netdevlist = $LMS->GetNotConnectedDevices($id);

	if ($netdevinfo['ownerid']) {
		$netdevinfo['owner'] = $LMS->getCustomerName( $netdevinfo['ownerid'] );
	}

	if ($netdevinfo['ports'] > $netdevinfo['takenports'])
		$nodelist = $LMS->GetUnlinkedNodes();
	$netdevips = $LMS->GetNetDevIPs($id);

	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

	$layout['pagetitle'] = trans('Device Info: $a $b $c', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

	$netdevinfo['id'] = $id;

	if ($netdevinfo['netnodeid'])
		$netdevinfo['netnode'] = $LMS->GetNetNode($netdevinfo['netnodeid']);

	$netdevinfo['projectname'] = trans('none');
	if ($netdevinfo['invprojectid']) {
		$prj = $LMS->GetProject($netdevinfo['invprojectid']);
		if ($prj) {
			if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id'])==1) {
				/* inherited */
				if ($netdevinfo['netnodeid']) {
					$prj = $LMS->GetProject($netnode['invprojectid']);
					if ($prj)
						$netdevinfo['projectname'] = trans('$a (from network node $b)', $prj['name'], $netdevinfo['netnode']['name']);
				}
			} else
				$netdevinfo['projectname'] = $prj['name'];
		}
	}
	$queue = $LMS->GetQueueContents(array('ids' => null, 'order' => null, 'state' => null, 'priority' => null,
		'owner' => -1, 'catids' => null, 'removed' => null, 'netdevids' => $id));
	$queue_count = $queue['total'];
	unset($queue['total']);
	unset($queue['state']);
	unset($queue['order']);
	unset($queue['direction']);
	unset($queue['owner']);
	unset($queue['removed']);
	unset($queue['priority']);
	unset($queue['deadline']);

	$start = 0;
	$pagelimit = ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $queue['total']);

	$SMARTY->assign('netdevinfo', $netdevinfo);
	$SMARTY->assign('start', $start);
	$SMARTY->assign('pagelimit', $pagelimit);
	$SMARTY->assign('queue', $queue);
	$SMARTY->assign('queue_count', $queue_count);
	$SMARTY->assign('queue_netdevid', $id);
	$SMARTY->assign('objectid', $netdevinfo['id']);
	$SMARTY->assign('restnetdevlist', $netdevlist);
	$SMARTY->assign('netdevips', $netdevips);
	$SMARTY->assign('nodelist', $nodelist);
	$SMARTY->assign('devlinktype', $SESSION->get('devlinktype'));
	$SMARTY->assign('devlinktechnology', $SESSION->get('devlinktechnology'));
	$SMARTY->assign('devlinkspeed', $SESSION->get('devlinkspeed'));
	$SMARTY->assign('nodelinktype', $SESSION->get('nodelinktype'));
	$SMARTY->assign('nodelinktechnology', $SESSION->get('nodelinktechnology'));
	$SMARTY->assign('nodelinkspeed', $SESSION->get('nodelinkspeed'));

	$hook_data = $LMS->executeHook('netdevinfo_before_display',
		array(
			'netdevconnected' => $netdevconnected,
			'netcomplist' => $netcomplist,
			'smarty' => $SMARTY,
		)
	);
	$netdevconnected = $hook_data['netdevconnected'];
	$netcomplist = $hook_data['netcomplist'];
	$SMARTY->assign('netdevlist', $netdevconnected);
	$SMARTY->assign('netcomplist', $netcomplist);

	if (isset($_GET['ip'])) {
		$nodeipdata = $LMS->GetNodeConnType($_GET['ip']);
		$netdevauthtype = array();
		$authtype = $nodeipdata;
		if ($authtype != 0) {
			$netdevauthtype['dhcp'] = ($authtype & 2);
			$netdevauthtype['eap'] = ($authtype & 4);
		}
		$SMARTY->assign('nodeipdata', $LMS->GetNode($_GET['ip']));
		$SMARTY->assign('nodesessions', $LMS->GetNodeSessions($_GET['ip']));
		$SMARTY->assign('netdevauthtype', $netdevauthtype);
		$SMARTY->display('netdev/netdevipinfo.html');
	} else {
		$SMARTY->assign('netdevinfo_sortable_order', $SESSION->get_persistent_setting('netdevinfo-sortable-order'));
		$SMARTY->display('netdev/netdevinfo.html');
	}
}

?>
