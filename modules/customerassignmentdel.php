<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2014 LMS Developers
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

if (isset($_GET['id']))
	$id = intval($_GET['id']);
if (isset($_GET['cid']))
	$cid = intval($_GET['cid']);

if ($_GET['is_sure'] == '1' && (isset($id) || isset($cid))) {
	if (isset($id)) {
		$customer = $DB->GetOne('SELECT a.customerid
			FROM assignments a
			JOIN customerview c ON (c.id = a.customerid)
			WHERE a.id = ?', array($id));
		$ids = array($id);
	} else {
		$customer = $DB->GetOne('SELECT id FROM customerview
			WHERE id = ?', array($cid));
		$ids = $DB->GetCol('SELECT id FROM assignments
			WHERE customerid = ?', array($cid));
	}

	if (!$customer)
		$SESSION->redirect('?'.$SESSION->get('backto'));

	if (!empty($ids))
		foreach ($ids as $id)
			$LMS->DeleteAssignment($id);

	$backto = $SESSION->get('backto');
	// infinite loop prevention
	if (preg_match('/customerassignmentedit/', $backto))
		$backto = 'm=customerinfo&id=' . $customer;
	$SESSION->redirect('?' . $backto);
}

?>
