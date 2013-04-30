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

if (isset($_GET['is_sure'])) {
	$id = intval($_GET['id']);
	list ($regid, $userid) = array_values($DB->GetRow('SELECT regid, userid FROM cashreglog WHERE id = ?', array($id)));

	if (!$regid)
		$SESSION->redirect('?m=cashreglist');

	if ($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid)) < 256) {
		$SMARTY->display('noaccess.html');
		$SESSION->close();
		die;
	}

	if ($SYSLOG) {
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHREGHIST] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHREG] => $regid,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $userid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_CASHREGHIST, SYSLOG_OPER_DELETE, $args, array_keys($args));
	}
	$DB->Execute('DELETE FROM cashreglog WHERE id = ?', array($id));
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
