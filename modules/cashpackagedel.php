<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$name = $DB->GetOne('SELECT name FROM sourcefiles WHERE id = ?', array($_GET['id']));
$layout['pagetitle'] = trans('Removing package "$a"', $name);

if (!$name)
{
	$body = '<P>'.trans('Specified ID is not proper or does not exist!').'</P>';
}
else if ($_GET['is_sure'] != 1) {
    $body = '<P>'.trans('Do you want to remove package "$a"?', $name).'</P>'; 
	$body .= '<P>'.trans('All cash operations from that package will be lost.').'</P>';
	$body .= '<P><A HREF="?m=cashpackagedel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I know what I do.').'</A>&nbsp;';
	$body .= '<A HREF="?'.$SESSION->get('backto').'">'.trans('No, I\'ve changed my mind.').'</A></P>';
}
else {
    $DB->BeginTrans();

	$DB->Execute('DELETE FROM cash WHERE importid IN (
	    SELECT id FROM cashimport WHERE sourcefileid = ?)', array($_GET['id']));
	$DB->Execute('DELETE FROM cashimport WHERE sourcefileid = ?', array($_GET['id']));
	$DB->Execute('DELETE FROM sourcefiles WHERE id = ?', array($_GET['id']));

    $DB->CommitTrans();

	$SESSION->redirect('?m=cashimport');
}

$SMARTY->assign('body', $body);
$SMARTY->display('dialog.html');

?>
