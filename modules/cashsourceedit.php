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

$source = $DB->GetRow('SELECT id, name, description FROM cashsources WHERE id=?', array($_GET['id']));

if(!$source)
{
	$SESSION->redirect('?m=cashsourcelist');
}

$layout['pagetitle'] = trans('Cash Import Source Edit: $a', $source['name']);

if(isset($_POST['sourceedit']))
{
	$sourceedit = $_POST['sourceedit'];
	$sourceedit['name'] = trim($sourceedit['name']);
	$sourceedit['description'] = trim($sourceedit['description']);
	
	if($sourceedit['name'] == '')
		$error['name'] = trans('Source name is required!');
	elseif(mb_strlen($sourceadd['name'])>32)
		$error['name'] = trans('Source name is too long!');
	elseif($source['name'] != $sourceedit['name'])
		if($DB->GetOne('SELECT 1 FROM cashsources WHERE name = ?', array($sourceedit['name'])))
			$error['name'] = trans('Source with specified name exists!');

	if (!$error) {
		$args = array(
			'name' => $sourceedit['name'],
			'description' => $sourceedit['description'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE] => $_GET['id']
		);
		$DB->Execute('UPDATE cashsources SET name=?, description=? WHERE id=?', array_values($args));

		if ($SYSLOG)
			$SYSLOG->AddMessage(SYSLOG_RES_CASHSOURCE, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASHSOURCE]));

		$SESSION->redirect('?m=cashsourcelist');
	}

	$source['name'] = $sourceedit['name'];
	$source['description'] = $sourceedit['description'];
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('sourceedit', $source);
$SMARTY->assign('error', $error);
$SMARTY->display('cashsourceedit.html');

?>
