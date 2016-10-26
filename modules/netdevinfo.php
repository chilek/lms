<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

if (!$LMS->NetDevExists($_GET['id'])) {
	$SESSION->redirect('?m=netdevlist');
}

include(MODULES_DIR . '/netdevxajax.inc.php');

if (! array_key_exists('xjxfun', $_POST)) {                  // xajax was called and handled by netdevxajax.inc.php
	$netdevinfo = $LMS->GetNetDev($_GET['id']);
	$netdevconnected = $LMS->GetNetDevConnectedNames($_GET['id']);
	$netcomplist = $LMS->GetNetdevLinkedNodes($_GET['id']);
	$netdevlist = $LMS->GetNotConnectedDevices($_GET['id']);

	if ($netdevinfo['ownerid']) {
		$netdevinfo['owner'] = $LMS->getCustomerName( $netdevinfo['ownerid'] );
	}

	$nodelist = $LMS->GetUnlinkedNodes();
	$netdevips = $LMS->GetNetDevIPs($_GET['id']);

	$SESSION->save('backto', $_SERVER['QUERY_STRING']);

	$layout['pagetitle'] = trans('Device Info: $a $b $c', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

	$netdevinfo['id'] = $_GET['id'];

	if ($netdevinfo['netnodeid']) {
		$netnode = $DB->GetRow("SELECT * FROM netnodes WHERE id=".$netdevinfo['netnodeid']);
		if ($netnode) {
			$netdevinfo['nodename'] = $netnode['name'];
		}
	}

	$netdevinfo['projectname'] = trans('none');
	if ($netdevinfo['invprojectid']) {
		$prj = $DB->GetRow("SELECT * FROM invprojects WHERE id = ?", array($netdevinfo['invprojectid']));
		if ($prj) {
			if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id'])==1) {
				/* inherited */
				if ($netnode) {
					$prj = $DB->GetRow("SELECT * FROM invprojects WHERE id=?",
						array($netnode['invprojectid']));
					if ($prj)
						$netdevinfo['projectname'] = trans('$a (from network node $b)', $prj['name'], $netnode['name']);
				}
			} else
				$netdevinfo['projectname'] = $prj['name'];
		}
	}
	$SMARTY->assign('netdevinfo', $netdevinfo);
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
		$SMARTY->assign('netdevauthtype', $netdevauthtype);
		$SMARTY->display('netdev/netdevipinfo.html');
	} else {
		$SMARTY->display('netdev/netdevinfo.html');
	}
}
?>
