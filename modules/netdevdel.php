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
 *  $Id$
 */

$id = intval($_GET['id']);

if ($api) {
	if (!$LMS->NetDevExists($id))
		die;
} elseif (!$LMS->NetDevExists($id))
	$SESSION->redirect('?m=netdevlist');

if (!$api) {
	$layout['pagetitle'] = trans('Deletion of Device with ID: $a', sprintf('%04d', $id));
	$SMARTY->assign('netdevid', $id);
}

if ($LMS->CountNetDevLinks($id) > 0) {
	if ($api)
		$error['general'] = trans('Device connected to other device or node can\'t be deleted.');
	else
		$body = '<P>' . trans('Device connected to other device or node can\'t be deleted.') . '</P>';
} else
	if (!$api && $_GET['is_sure'] != 1) {
		$body = '<P>' . trans('Are you sure, you want to delete that device?') . '</P>'; 
		$body .= '<P><A HREF="?m=netdevdel&id=' . $id . '&is_sure=1">' . trans('Yes, I am sure.') . '</A></P>';
	} else {
		if (!$api) {
			header('Location: ?m=netdevlist');
			$body = '<P>' . trans('Device has been deleted.') . '</P>';
		}

		$hook_data = $LMS->executeHook(
			'netdevedel_validation_before_submit',
			array(
				'id' => $id,
				'body' => $body,
			)
		);
		if (!isset($hook_data['abort']) || empty($hook_data['abort'])) {
			$result = $LMS->DeleteNetDev($id);

			$hook_data = $LMS->executeHook('netdevdel_after_submit',
				array(
					'id' => $id,
				)
			);

			$LMS->CleanupProjects();
			if ($api) {
				if ($result) {
					header('Content-Type: application/json');
					echo json_encode(array('id' => $id));
				}
				die;
			}
		} else
			$body = $hook_data['body'];
	}

if ($api) {
	if (isset($error['general'])) {
		header('Content-Type: application/json');
		echo json_encode($error);
	}
	die;
}

$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');

?>
