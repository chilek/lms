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

$id = intval($_GET['id']);

if ($id && $_GET['is_sure'] == '1') {
	$DB->BeginTrans();
	if ($SYSLOG) {
		$customerid = $DB->GetOne('SELECT customerid FROM documents WHERE id = ?', array($id));
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
		$dnoteitems = $DB->GetCol('SELECT id FROM debitnotecontents WHERE docid = ?', array($id));
		foreach ($dnoteitems as $item) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT] => $item,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
			);
			$SYSLOG->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
		}
		$cashitems = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($id));
		foreach ($cashitems as $item) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $item,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
			);
			$SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
		}
	}
	$DB->Execute('DELETE FROM documents WHERE id = ?', array($id));
	$DB->Execute('DELETE FROM debitnotecontents WHERE docid = ?', array($id));
	$DB->Execute('DELETE FROM cash WHERE docid = ?', array($id));
	$DB->CommitTrans();
}

$SESSION->redirect('?m=notelist');

?>
