<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
 *  $Id: dc7c85b02cd54effe9bd3ae833edc6970d0ed30a $
 */

if (!empty($_GET['id']))
	$LMS->DelBalance($_GET['id']);
elseif (sizeof($_POST['marks'])) {
	$ids = array();
	$docids = array();
	foreach ($_POST['marks'] as $markid => $junk)
		if (strpos($markid, 'proforma') !== false)
			$docids[] = intval($junk);
		elseif ($junk)
			$ids[] = $markid;
	sort($ids);
	foreach ($ids as $cashid)
		$LMS->DelBalance($cashid);
	foreach ($docids as $docid)
		$LMS->InvoiceDelete($docid);
}

header('Location: ?'.$SESSION->get('backto'));

?>
