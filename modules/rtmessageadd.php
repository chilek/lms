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

$msg = $_POST['message'];

if(isset($msg))
{
	$msg['ticketid'] = $_GET['ticketid'];
	
	if($msg['subject'] == '')
		$error['subject'] = "Wiadomo¶æ musi mieæ tytu³!";

	if($msg['body'] == '')
		$error['body'] = "Nie poda³e¶ tre¶ci wiadomo¶ci!";

/*
	if($queue['email']!='' && !check_email($queue['email']))
		$error['email'] = 'Podany email nie wydaje siê byæ poprawny!';

*/
	if(!$error)
	{
		$LMS->MessageAdd($msg);
		header("Location: ?m=rtticketinfo&id=".$msg['ticketid']);
		die;
	}
}

$msg['ticketid'] = $_GET['ticketid'];

$layout['pagetitle'] = 'Nowa wiadomo¶æ';

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('message', $msg);
$SMARTY->assign('error', $error);
$SMARTY->display('rtmessageadd.html');

?>
