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
	$regid = $DB->GetOne('SELECT DISTINCT regid FROM receiptcontents WHERE docid=?', array($id));
	if ($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid)) < 256) {
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	$customerid = $DB->GetOne('SELECT customerid FROM documents WHERE id = ?', array($id));
	if ($DB->Execute('DELETE FROM documents WHERE id = ?', array($id))) {
		if ($SYSLOG) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
			);
			$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_DELETE, $args, array_keys($args));
			$items = $DB->GetCol('SELECT itemid FROM receiptcontents WHERE docid = ?', array($id));
			foreach ($items as $item) {
				$args['itemid'] = $item;
				$SYSLOG->AddMessage(SYSLOG_RES_RECEIPTCONT, SYSLOG_OPER_DELETE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
			}
			$cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($id));
			foreach ($cashids as $cashid) {
				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $cashid,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $id,
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customerid,
				);
				$SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
			}
		}
		$DB->Execute('DELETE FROM receiptcontents WHERE docid = ?', array($id));
		$DB->Execute('DELETE FROM cash WHERE docid = ?', array($id));
	}
}

header('Location: ?m=receiptlist');

?>
