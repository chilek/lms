<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if (isset($_POST['marks'])) {
	foreach($_POST['marks'] as $id => $mark) {
		$docid = $DB->GetCol('SELECT d.id FROM documents d
			JOIN docrights r ON (r.doctype = d.type)
			WHERE d.id = ? AND r.userid = ? AND (r.rights & 16) = 16',
			array($id, Auth::GetCurrentUser()));
		if (!$docid)
			continue;

		$md5sums = $DB->GetCol('SELECT md5sum FROM documentattachments
			WHERE docid = ?', array($id));
		foreach ($md5sums as $md5sum)
			if ($DB->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array((string)$md5sum)) == 1) {
				$filename_pdf = DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum,0,2) . DIRECTORY_SEPARATOR . $md5sum.'.pdf';
				if(file_exists($filename_pdf))
					@unlink($filename_pdf);
				if (!$LMS->FileExists($md5sum))
					@unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum,0,2) . DIRECTORY_SEPARATOR . $md5sum);
			}

		$DB->Execute('DELETE FROM documents WHERE id = ?', array($id));
	}
} elseif(isset($_GET['id'])) {
	$docid = $DB->GetOne('SELECT d.id FROM documents d
		JOIN docrights r ON (r.doctype = d.type)
		WHERE d.id = ? AND r.userid = ? AND (r.rights & 16) = 16',
		array($_GET['id'], Auth::GetCurrentUser()));
	if (!$docid) {
		$SMARTY->display('noaccess.html');
		die;
	}

	$md5sums = $DB->GetCol('SELECT md5sum FROM documentattachments
		WHERE docid = ?', array($_GET['id']));
	foreach ($md5sums as $md5sum)
		if ($DB->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array((string)$md5sum)) == 1)
			@unlink(DOC_DIR.'/'.substr($md5sum,0,2).'/'.$md5sum);

	$DB->Execute('DELETE FROM documents WHERE id = ?', array($_GET['id']));
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
