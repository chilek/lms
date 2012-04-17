<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$sourceadd = isset($_POST['sourceadd']) ? $_POST['sourceadd'] : NULL;

if($sourceadd) 
{
	$sourceadd['name'] = trim($sourceadd['name']);
	$sourceadd['description'] = trim($sourceadd['description']);

	if($sourceadd['name']=='' && $sourceadd['description']=='')
	{
		$SESSION->redirect('?m=cashsourcelist');
	}

	if($sourceadd['name'] == '')
		$error['name'] = trans('Source name is required!');
	elseif(mb_strlen($sourceadd['name'])>32)
		$error['name'] = trans('Source name is too long!');
	elseif($DB->GetOne('SELECT 1 FROM cashsources WHERE name = ?', array($sourceadd['name'])))
		$error['name'] = trans('Source with specified name exists!');

	if(!$error)
	{
		$DB->Execute('INSERT INTO cashsources (name, description) VALUES (?,?)',
			array($sourceadd['name'], $sourceadd['description']));
		
		if(!isset($sourceadd['reuse']))
		{
			$SESSION->redirect('?m=cashsourcelist');
		}
		
		unset($sourceadd['name']);
		unset($sourceadd['description']);
	}
}

$layout['pagetitle'] = trans('Cash Import Source New');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('sourceadd', $sourceadd);
$SMARTY->display('cashsourceadd.html');

?>
