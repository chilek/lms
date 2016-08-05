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

if ($LMS->CustomergroupExists($from) && $LMS->CustomergroupExists($to) && $_GET['is_sure'] == 1) {
	$DB->BeginTrans();

	if ($SYSLOG)
		$cids = $DB->GetCol('SELECT customerid FROM customerassignments a, customerview c
				WHERE a.customerid = c.id AND a.customergroupid = ?
				AND NOT EXISTS (SELECT 1 FROM customerassignments ca
					WHERE ca.customerid = a.customerid AND ca.customergroupid = ?)',
				array($from, $to));

	$DB->Execute('INSERT INTO customerassignments (customergroupid, customerid)
			SELECT ?, customerid 
			FROM customerassignments a, customerview c
			WHERE a.customerid = c.id AND a.customergroupid = ?
			AND NOT EXISTS (SELECT 1 FROM customerassignments ca
				WHERE ca.customerid = a.customerid AND ca.customergroupid = ?)',
			array($to, $from, $to));

	if ($SYSLOG && $cids) {
		foreach ($cids as $cid) {
			$aid = $DB->GetOne('SELECT a.id FROM customerassignments a, customerview c
				WHERE a.customerid = c.id AND a.customerid = ? AND a.customergroupid = ?', array($cid, $to));
			$args = array(
				SYSLOG::RES_CUSTASSIGN => $aid,
				SYSLOG::RES_CUST => $cid,
				SYSLOG::RES_CUSTGROUP => $to
			);
			$SYSLOG->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_ADD, $args);
		}

		$assigns = $DB->GetAll('SELECT id, customerid FROM customerassignments WHERE customergroupid = ?', array($from));
		if (!empty($assigns))
			foreach ($assigns as $assign) {
				$args = array(
					SYSLOG::RES_CUSTASSIGN => $assign['id'],
					SYSLOG::RES_CUST => $assign['customerid'],
					SYSLOG::RES_CUSTGROUP => $from
				);
				$SYSLOG->AddMessage(SYSLOG::RES_CUSTASSIGN, SYSLOG::OPER_DELETE, $args);
			}
	}

	$DB->Execute('DELETE FROM customerassignments WHERE customergroupid = ?', array($from));

	$DB->CommitTrans();

	$SESSION->redirect('?m=customergroupinfo&id='.$to);
} else
	header('Location: ?'.$SESSION->get('backto'));

?>
