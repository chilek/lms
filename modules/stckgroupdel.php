<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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
 *  $Id: netdevdel.php,v 1.28 2011/01/18 08:12:23 alec Exp $
 */

if(! $LMSST->GroupExists($_GET['id']) || !ctype_digit($_GET['id'])) {
	$SESSION->redirect('?m=stckgrouplist');
}		

$layout['pagetitle'] = trans('Deletion of Group with ID: $0',sprintf('%04d',$_GET['id']));
$SMARTY->assign('groupid',$_GET['id']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if($LMSST->GroupStockCount($_GET['id'])>0)
{
	$body = '<P>'.trans('Group with stock can\'t be deleted.').'</P>';
}else{
    if($_GET['is_sure']!=1)
    {
	    $body = '<P>'.trans('Are you sure, you want to delete this group?').'</P>'; 
	    $body .= '<P><A HREF="?m=stckgroupdel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A></P>';
    }else{
	    header('Location: ?m=stckgrouplist');
	    $body = '<P>'.trans('Group has been deleted.').'</P>';
	    $LMSST->GroupDel($_GET['id']);
    }
}
	
$SMARTY->assign('body',$body);
$SMARTY->display('stck/dialog.html');

?>
