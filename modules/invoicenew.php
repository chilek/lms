<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

$layout[pagetitle] = 'Nowa faktura';
$contents = $_SESSION[invoicecontents];
$itemdata = r_trim($_POST);

if($_GET['deletepos'] != '')
	if(sizeof($contents))
		foreach($contents as $idx => $row)
			if($row['posuid'] == $_GET['deletepos'])
				unset($contents[$idx]);

foreach(array('count', 'valuenetto', 'taxvalue', 'valuebrutto') as $key)
	$itemdata[$key] = sprintf('%01.2f',$itemdata[$key]);

if($itemdata['count'] > 0 && $itemdata['name'] != '')
{
	if($itemdata['taxvalue'] < 0 || $itemdata['taxvalue'] > 100)
		$error['taxvalue'] = 'Niepoprawna wysoko¶æ podatku!';
		
	// warto¶æ netto ma priorytet
	if($itemdata['valuenetto'] != 0)
		$itemdata['valuebrutto'] = round($itemdata['valuenetto'] * ($itemdata['taxvalue'] / 100 + 1),2);
	elseif($itemdata['valuebrutto'] != 0)
		$itemdata['valuenetto'] = round($itemdata['valuebrutto'] / ($itemdata['taxvalue'] + 100) * 100, 2);

	$itemdata['s_valuenetto'] = $itemdata['valuenetto'] * $itemdata['count'];
	$itemdata['s_valuebrutto'] = $itemdata['valuebrutto'] * $itemdata['count'];
	$itemdata['posuid'] = getmicrotime();
	$contents[] = $itemdata;
}

$_SESSION[invoicecontents] = $contents;
$SMARTY->assign('contents',$contents);
$SMARTY->assign('users',$users);
$SMARTY->assign('layout',$layout);
$SMARTY->display('invoicenew.html');

?>
