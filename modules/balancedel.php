<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if (!empty($_GET['id']))
	$LMS->DelBalance($_GET['id']);
elseif (count($_POST['marks'])) {
	$ids = array();
	$docitems = array();
	foreach ($_POST['marks'] as $markid => $mark)
		if ($markid == 'proforma')
			foreach ($mark as $docid => $items) {
				$docid = intval($docid);
				if (!isset($docitems[$docid]))
					$docitems[$docid] = array();
				foreach ($items as $item)
					$docitems[$docid][] = $item;
			}
		elseif ($mark)
			$ids[] = $markid;
	sort($ids);
	foreach ($ids as $cashid)
		$LMS->DelBalance($cashid);
	foreach ($docitems as $docid => $items)
		foreach ($items as $itemid)
			$LMS->InvoiceContentDelete($docid, $itemid);
}

header('Location: ?'.$SESSION->get('backto'));

?>
