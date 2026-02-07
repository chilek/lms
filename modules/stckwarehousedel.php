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

if(! $LMSST->WarehouseExists($_GET['id']) || !ctype_digit($_GET['id'])) {
	$SESSION->redirect('?m=stckwarehouselist');
}		

$layout['pagetitle'] = trans('Deleting warehouse with ID: $a',sprintf('%04d',$_GET['id']));
$SMARTY->assign('warehouseid',$_GET['id']);

if($LMSST->WarehouseStockCount($_GET['id'])>0) {
	$body = '<P>'.trans('Warehouse with stock can\'t be deleted.').'</P>';
} else {
    if($_GET['is_sure']!=1) {
	    $body = '<P>'.trans('Are you sure, you want to delete this warehouse?').'</P>'; 
	    $body .= '<P><A HREF="?m=stckwarehousedel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I am sure.').'</A></P>';
    } else {
	    header('Location: ?m=stckwarehouselist');
	    $body = '<P>'.trans('Warehouse has been deleted.').'</P>';
	    $LMSST->WarehouseDel($_GET['id']);
    }
}
	
$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');

?>
