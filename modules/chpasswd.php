<?php

/*
 * LMS version 1.4-cvs
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

$passwd = $_POST['passwd'];

$id = $SESSION->id;

if($LMS->AdminExists($id))
{
	if(isset($passwd))
	{
		if($passwd['passwd'] == "" || $passwd['confirm'] == "")
			$error['password'] .= "Has³o nie mo¿e byæ puste!<BR>";
		
		if($passwd['passwd'] != $passwd['confirm'])
			$error['password'] .= "Podane has³a siê ró¿ni±!";
		
		if(!$error)
		{
			$LMS->SetAdminPassword($id,$passwd['passwd']);
			header("Location: ?m=welcome");
		}
	}

	$passwd['realname'] = $LMS->GetAdminName($id);
	$passwd['id'] = $id;
	$layout['pagetitle'] = "Zmiana has³a";
	$SMARTY->assign('error',$error);
	$SMARTY->assign('passwd',$passwd);
	$SMARTY->assign('target',"?m=chpasswd");
	$SMARTY->display('adminpasswd.html');

}
else
{
	header("Location: ?m=".$_SESSION['lastmodule']);
	die;
}

?>