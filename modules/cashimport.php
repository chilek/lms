<?php

/*
 * LMS version 1.5-cvs
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

$layout['pagetitle'] = trans('Import cash operations');
$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$op = $_GET['op'];

switch ($op) {
    case "del":
	$LMS->DB->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($_GET['id']));
	header('Location: ?m=cashimport');
	break;
    case "assign":
	if ($_POST['customer']!=0) {
	    $data = $LMS->DB->GetRow('SELECT * FROM cashimport WHERE id = ?', array($_GET['id']));
	    $LMS->DB->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($_GET['id']));
	    $balance['time'] = $data['date'];
	    $balance['type'] = 3;
	    $balance['value'] = $data['value'];
	    $balance['userid'] = $_POST['customer'];
	    $balance['comment'] = $data['description'];
	    $LMS->AddBalance($balance);
	    header('Location: ?m=cashimport');
	    break;
	} else {
	    $error[$_GET['id']] = trans('No customer selected!');
	}
    default:	
	$userlist = $LMS->GetUserNames();
	$importlist = $LMS->DB->GetAll('SELECT * FROM cashimport WHERE closed = 0 AND value > 0');
	$importlist['total'] = sizeof($importlist);
	$SMARTY->assign('importlist',$importlist);
	$SMARTY->assign('userlist',$userlist);
	$SMARTY->assign('error',$error);
	$SMARTY->display('cashimport.html');
}
?>
