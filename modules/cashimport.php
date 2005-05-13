<?php

/*
 * LMS version 1.6-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$layout['pagetitle'] = trans('Cash Operations Import');

if($_GET['action']=='delete')
{
	if($marks = $_POST['marks'])
		foreach($marks as $id)
			$LMS->DB->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($id));
}
elseif($marks = $_POST['marks'])
{
	$customers = $_POST['customer'];
	foreach($marks as $id)
	{
		if($customers[$id])
		{
			$import = $LMS->DB->GetRow('SELECT * FROM cashimport WHERE id = ?', array($id));
			$LMS->DB->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($id));
			$balance['time'] = $import['date'];
			$balance['type'] = 3;
			$balance['value'] = $import['value'];
			$balance['userid'] = $customers[$id];
			$balance['comment'] = $import['description'];
			$LMS->AddBalance($balance);
		}
		else
			$error[$id] = trans('Customer not selected!');
	}
}

$importlist = $LMS->DB->GetAll('SELECT * FROM cashimport WHERE closed = 0 AND value > 0');
$listdata['total'] = sizeof($importlist);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('importlist', $importlist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('error', $error);
$SMARTY->assign('userlist', $LMS->GetUserNames());
$SMARTY->display('cashimport.html');

?>
