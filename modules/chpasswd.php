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

$id = $AUTH->id;

if($LMS->UserExists($id))
{
	if(isset($_POST['passwd']))
	{
		$passwd = $_POST['passwd'];
		
		if($passwd['passwd'] == '' || $passwd['confirm'] == '')
			$error['password'] = trans('Empty passwords are not allowed!');
		elseif($passwd['passwd'] != $passwd['confirm'])
			$error['password'] = trans('Passwords does not match!');
		
		if(!$error)
		{
			$LMS->SetUserPassword($id,$passwd['passwd']);
			header('Location: ?'.$SESSION->get('backto'));
		}
	}

	$passwd['id'] = $id;
	$layout['pagetitle'] = trans('Password Change');

	$SMARTY->assign('passwd', $passwd);
	$SMARTY->assign('error', $error);
	$SMARTY->assign('target', '?m=chpasswd');
	$SMARTY->display('userpasswd.html');
}
else
{
	$SESSION->redirect('?m='.$SESSION->get('lastmodule'));
}

?>
