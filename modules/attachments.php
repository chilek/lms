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

if (isset($_GET['type']))
	$attachmenttype = $_GET['type'];
if (!preg_match('/^[a-z0-9_]+$/', $attachmenttype))
	die;

switch ($attachmenttype) {
	case 'netdevid':
	case 'netnodeid':
		if (!ConfigHelper::checkPrivilege('network_management'))
			if (isset($_GET['type']))
				access_denied();
			else
				return;
		break;
}

if (isset($_GET['attachmentaction'])) {
	switch ($_GET['attachmentaction']) {
		case 'deletecontainer':
			$LMS->DeleteFileContainer($_GET['id']);
			break;
		case 'viewfile':

			$file = $LMS->GetFile($_GET['fileid']);
			if (empty($file))
				die;

			header('Content-Type: ' . $file['contenttype']);
			if (!preg_match('/^text/i', $file['contenttype'])) {
				$pdf = preg_match('/pdf/i', $file['contenttype']);
				if (!isset($_GET['save']))
					if ($pdf) {
						header('Content-Disposition: inline; filename="'.$file['filename'] . '"');
						header('Content-Transfer-Encoding: binary');
						header('Content-Length: ' . filesize($file['filepath']) . ' bytes');
					} else
						header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
				else
					header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
				header('Pragma: public');
			}
			readfile($file['filepath']);
			die;
			break;

		case 'downloadzippedcontainer':
			$LMS->GetZippedFileContainer($_GET['id']);
			die;
			break;
	}
	$SESSION->redirect('?' . $SESSION->get('backto'));
}

if (isset($_GET['resourceid']))
	$attachmentresourceid = $_GET['resourceid'];
if (!preg_match('/^[0-9]+$/', $attachmentresourceid))
	die;

if (isset($_POST['upload'])) {
	$result = handle_file_uploads('files', $error);
	extract($result);
	$SMARTY->assign('fileupload', $fileupload);

	$upload = $_POST['upload'];

	if (!$error) {
		if (!empty($files)) {
			foreach ($files as &$file)
				$file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
			unset($file);
			$LMS->AddFileContainer(array(
				'description' => $upload['description'],
				'files' => $files,
				'type' => $attachmenttype,
				'resourceid' => $attachmentresourceid,
			));
		}

		// deletes uploaded files
		if (!empty($files))
			rrmdir($tmppath);

		$SESSION->redirect('?' . $SESSION->get('backto'));
	}

	$SMARTY->assign('upload', $upload);
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('attachmenttype', $attachmenttype);
$SMARTY->assign('attachmentresourceid', $attachmentresourceid);
$SMARTY->assign('filecontainers', $LMS->GetFileContainers($attachmenttype, $attachmentresourceid));
