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

if(!eregi('^[0-9]+$',$_GET['id']))
{
	$SESSION->redirect('?m=userlist');
}

if($LMS->UserExists($_GET['id']) == 0)
{
	$SESSION->redirect('?m=userlist');
}

$userinfo = $LMS->GetUser($_GET['id']);
$assignments = $LMS->GetUserAssignments($_GET['id']);
$usernodes = $LMS->GetUserNodes($_GET['id']);
$tariffs = $LMS->GetTariffs();
$userinfo['username'] = ucwords(strtolower($userinfo['username']));
$usernodes['ownerid'] = $_GET['id'];

$SMARTY->assign(
		array(
			'usernodes' => $usernodes,
			'assignments' => $assignments,
			'userinfo' => $userinfo,
			'tariffs' => $tariffs
		     )
		);

if($_POST['templ'])
	$SMARTY->display($_POST['templ']);
else
	$SMARTY->display($LMS->CONFIG['phpui']['contract_template']);

?>
