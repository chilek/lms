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

if (isset($_GET['mid']))
	$msgid = intval($_GET['mid']);

if (isset($_GET['tid']))
	$ticketid = intval($_GET['tid']);

if (isset($ticketid) && isset($msgid) && isset($_GET['file'])) {
	$filename = $_GET['file'];
	if (isset($_GET['img'])) {
		if ($attach = $DB->GetRow('SELECT * FROM rtattachments WHERE messageid = ? AND filename = ?', array($msgid, $filename))) {
			$file = ConfigHelper::getConfig('rt.mail_dir') . sprintf("/%06d/%06d/%s", $ticketid, $msgid, $filename);
			if (file_exists($file)) {
				$size = @filesize($file);
				header('Content-Length: ' . $size . ' bytes');
				header('Content-Type: '. $attach['contenttype']);
				header('Cache-Control: private');
				header('Content-Disposition: attachment; filename=' . $filename);
				@readfile($file);
			}
			//$SESSION->close();
			die;
		}
	} else
		$SMARTY->assign(array(
			'tid' => $ticketid,
			'mid' => $msgid,
			'file' => $filename,
		));
}

$SMARTY->display('rtattachmentview.html');

?>
