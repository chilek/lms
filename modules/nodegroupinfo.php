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

if( !($id = $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array(intval($_GET['id'])))))
{
	$SESSION->redirect('?m=nodegrouplist');
}

if (isset($_GET['membersnetid']) && $membersnetid = $_GET['membersnetid'])
{
	if (!$LMS->NetworkExists($membersnetid))
	{
		$SESSION->redirect('?m=nodegrouplist');
	}
}

if (isset($_GET['othersnetid']) && $othersnetid = $_GET['othersnetid'])
{
	if (!$LMS->NetworkExists($othersnetid))
	{
		$SESSION->redirect('?m=nodegrouplist');
	}
}

$nodegroup = $LMS->GetNodeGroup($id, isset($membersnetid) ? $membersnetid : 0);
$nodes = $LMS->GetNodesWithoutGroup($id, isset($othersnetid) ? $othersnetid : 0);
$nodescount = count($nodes);

$layout['pagetitle'] = trans('Group Info: $a',$nodegroup['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('nodegroup',$nodegroup);
$SMARTY->assign('nodes', $nodes);
$SMARTY->assign('nodescount', $nodescount);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('membersnetid', isset($membersnetid) ? $membersnetid : 0);
$SMARTY->assign('othersnetid', isset($othersnetid) ? $othersnetid : 0);

$SMARTY->display('node/nodegroupinfo.html');

?>
