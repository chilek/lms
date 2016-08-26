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

if ($_GET['is_sure'] == '1') {
	if (isset($_POST['marks'])) {
		foreach($_POST['marks'] as $id => $mark) {
			$md5sums = $DB->GetCol('SELECT a.md5sum FROM documentattachments a
				JOIN documents d ON (d.id = a.docid)
				JOIN docrights r ON (r.doctype = d.type)
				WHERE a.docid = ? AND r.userid = ? AND (r.rights & 16) = 16',
				array($id, $AUTH->id));

			if (!$md5sums)
				continue;

			foreach ($md5sums as $md5sum)
				if ($DB->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array((string)$md5sum)) == 1) {
					$filename_pdf = DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum,0,2) . DIRECTORY_SEPARATOR . $md5sum.'.pdf';
					if(file_exists($filename_pdf))
						@unlink($filename_pdf);
					@unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum,0,2) . DIRECTORY_SEPARATOR . $md5sum);
				}

			$DB->BeginTrans();

			$DB->Execute('DELETE FROM documentcontents WHERE docid = ?',array($id));
			$DB->Execute('DELETE FROM documents WHERE id = ?',array($id));

			$DB->CommitTrans();
		}
	} elseif(isset($_GET['id'])) {
		$md5sums = $DB->GetCol('SELECT a.md5sum FROM documentattachments a
			JOIN documents d ON (d.id = a.docid)
			JOIN docrights r ON (r.doctype = d.type)
			WHERE a.docid = ? AND r.userid = ? AND (r.rights & 16) = 16',
			array($_GET['id'], $AUTH->id));

		if (!$md5sums) {
			$SMARTY->display('noaccess.html');
			die;
		}

		foreach ($md5sums as $md5sum)
			if ($DB->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array((string)$md5sum)) == 1)
				@unlink(DOC_DIR.'/'.substr($md5sum,0,2).'/'.$md5sum);

		$DB->BeginTrans();

		$DB->Execute('DELETE FROM documentcontents WHERE docid = ?',array($_GET['id']));
		$DB->Execute('DELETE FROM documents WHERE id = ?',array($_GET['id']));

		$DB->CommitTrans();
	}
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
