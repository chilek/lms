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

$from = !empty($_GET['from']) ? intval($_GET['from']) : 0;
$to = !empty($_GET['to']) ? intval($_GET['to']) : 0;

if($DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array($from)) 
	&& $DB->GetOne('SELECT id FROM nodegroups WHERE id = ?', array($to)) 
	&& $_GET['is_sure'] == 1)
{
	$DB->BeginTrans();
	
	if ($SYSLOG)
		$nids = $DB->GetCol('SELECT noderid FROM nodegroupassignments a
				WHERE a.nodegroupid = ?
				AND NOT EXISTS (SELECT 1 FROM nodegroupassignments nga
					WHERE nga.nodegroupid = a.nodegroupid AND nga.nodegroupid = ?)',
				array($from, $to));

	$DB->Execute('INSERT INTO nodegroupassignments (nodegroupid, nodeid)
			SELECT ?, nodeid 
			FROM nodegroupassignments a
			JOIN nodes n ON (a.nodeid = n.id)
			JOIN customerview c (n.ownerid = c.id)
			WHERE a.nodegroupid = ?
			AND NOT EXISTS (SELECT 1 FROM nodegroupassignments na
				WHERE na.nodeid = a.nodeid AND na.nodegroupid = ?)',
			array($to, $from, $to));

	if ($SYSLOG && $nids) {
		foreach ($nids as $nid) {
			$aid = $DB->GetOne('SELECT a.id FROM nodegroupassignments a
				WHERE a.nodeid = ? AND a.nodegroupid = ?', array($nid, $to));
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEGROUPASSIGN] => $aid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $nid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEGROUP] => $to
			);
			$SYSLOG->AddMessage(SYSLOG_RES_NODEGROUPASSIGN, SYSLOG_OPER_ADD, $args, array_keys($args));
		}

		$assigns = $DB->GetAll('SELECT id, nodeid FROM nodegroupassignments WHERE nodegroupid = ?', array($from));
		if (!empty($assigns))
			foreach ($assigns as $assign) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEGROUPASSIGN] => $assign['id'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODE] => $assign['nodeid'],
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NODEGROUP] => $from
				);
				$SYSLOG->AddMessage(SYSLOG_RES_NODEGROUPASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
			}
	}
	
	$DB->Execute('DELETE FROM nodegroupassignments WHERE nodegroupid = ?', array($from));

        $DB->CommitTrans();
	
	$SESSION->redirect('?m=nodegroupinfo&id='.$to);
}
else
	header('Location: ?'.$SESSION->get('backto'));

?>
