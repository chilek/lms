<?php

/*
 * LMS version 1.2-cvs
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

if(! $LMS->NetDevExists($_GET['id']))
{
	header('Location: ?m=netdevlist');
	die;
}		

$layout['pagetitle'] = _("Deleting Device ID: ").sprintf("%04d",$_GET['id']);
$SMARTY->assign('layout',$layout);
$SMARTY->assign('netdevid',$_GET['id']);

if($LMS->CountNetDevLinks($_GET['id'])>0)
{
	$body = "<H1>".$layout['pagetitle']."</H1>";
	$body .= '<P>'._("Is not allowed to delete device connected to other devices.").'</P>';
}else{
    if($_GET['is_sure']!=1)
    {
	    $body = "<H1>".$layout['pagetitle']."</H1>";
	    $body .= '<P>'._("Are You sure, You want to delete device?").'</P>'; 
	    $body .= "<P><A HREF=\"?m=userdel&id=".$_GET['id']."&is_sure=1\">"._("Yes, I am sure.").'</A></P>';
    }else{
	    header("Location: ?m=netdevlist");
	    $body = "<H1>".$layout['pagetitle']."</H1>";
	    $body .= '<P>'._("Device has been deleted.").'</P>';
	    $LMS->DeleteNetDev($_GET['id']);
    }
}
	

$SMARTY->display('header.html');
$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');
$SMARTY->display('footer.html');

?>
