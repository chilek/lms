<?php

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

if(! $_GET['id'])
{
	header('Location: ?m='.$_SESSION['backto']);
	die;
}

$message = $LMS->GetMessage($_GET['id']); 
if($message['adminid'])
	$message['adminname'] = $LMS->GetAdminName($message['adminid']);

if($message['userid'])
	$message['username'] = $LMS->GetUserName($message['userid']);
	
print_r($message);
$layout['pagetitle'] = 'Podgl±d wiadomo¶ci';

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('message', $message);
$SMARTY->display('rtmessageview.html');

?>
