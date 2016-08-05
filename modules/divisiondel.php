<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if (isset($_GET['is_sure']) && $_GET['is_sure'] == 1 && $id) {
	if ($DB->GetOne('SELECT COUNT(*) FROM divisions', array($id)) != 1) {
		if ($SYSLOG) {
			$countryid = $DB->GetOne('SELECT countryid FROM divisions WHERE id = ?', array($id));
			$args = array(
				SYSLOG::RES_DIV => $id,
				SYSLOG::RES_COUNTRY => $countryid
			);
			$SYSLOG->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_DELETE, $args);
			$assigns = $DB->GetAll('SELECT * FROM numberplanassignments WHERE divisionid = ?', array($id));
			if (!empty($assigns))
				foreach ($assigns as $assign) {
					$args = array(
						SYSLOG::RES_NUMPLANASSIGN => $assign['id'],
						SYSLOG::RES_NUMPLAN => $assign['planid'],
						SYSLOG::RES_DIV => $assign['divisionid'],
					);
					$SYSLOG->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_DELETE, $args);
				}
		}
		$DB->Execute('DELETE FROM divisions WHERE id=?', array($id));
		$DB->Execute('DELETE FROM numberplanassignments WHERE divisionid=?', array($id));
	}
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
