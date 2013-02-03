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

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'delete')
{
        $DB->Execute('DELETE FROM nodegroupassignments WHERE nodeid=? AND nodegroupid=?',
                	array(intval($_GET['id']), intval($_GET['nodegroupid'])));
}
elseif($action ==  'add')
{
	if($LMS->NodeExists(intval($_GET['id']))
		&& $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array($_POST['nodegroupid'])))
                $DB->Execute('INSERT INTO nodegroupassignments (nodeid, nodegroupid)
            		VALUES (?, ?)', array(intval($_GET['id']), intval($_POST['nodegroupid'])));
}
elseif(!empty($_POST['marks']) && $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array(intval($_GET['groupid']))))
{
	foreach($_POST['marks'] as $mark)
		if($action == 'unsetgroup')
			$DB->Execute('DELETE FROM nodegroupassignments
					WHERE nodegroupid = ? AND nodeid = ?',
					array(intval($_GET['groupid']), $mark));
		elseif($action == 'setgroup')
		{
			if(!$DB->GetOne('SELECT 1 FROM nodegroupassignments
					WHERE nodegroupid = ? AND nodeid = ?',
					array(intval($_GET['groupid']), $mark)))
				$DB->Execute('INSERT INTO nodegroupassignments 
					(nodegroupid, nodeid) VALUES (?, ?)',
					array(intval($_GET['groupid']), $mark));
		}
}
elseif(isset($_POST['nodeassignments']) && $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array($_GET['id'])))
{
	$oper = $_POST['oper'];
	$nodeassignments = $_POST['nodeassignments'];
	
	if(isset($nodeassignments['gmnodeid']) && $oper=='0')
	{
		foreach($nodeassignments['gmnodeid'] as $nodeid)
		{
			$DB->Execute('DELETE FROM nodegroupassignments 
				WHERE nodegroupid = ? AND nodeid = ?', 
				array($_GET['id'], $nodeid));
		}
	}
	elseif (isset($nodeassignments['mnodeid']) && $oper=='1')
	{
		foreach($nodeassignments['mnodeid'] as $nodeid)
		{
			$DB->Execute('INSERT INTO nodegroupassignments (nodegroupid, nodeid)
				VALUES (?, ?)', array($_GET['id'], $nodeid));
		}
	}
	elseif (isset($nodeassignments['membersnetid']) && $oper=='2')
	{
		$SESSION->redirect('?'.preg_replace('/&membersnetid=[0-9]+/', '', $SESSION->get('backto')).'&membersnetid='.$nodeassignments['membersnetid']);
	}
	elseif (isset($nodeassignments['othersnetid']) && $oper=='3')
	{
		$SESSION->redirect('?'.preg_replace('/&othersnetid=[0-9]+/', '', $SESSION->get('backto')).'&othersnetid='.$nodeassignments['othersnetid']);
	}
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
