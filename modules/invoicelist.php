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

$layout[pagetitle] = 'Lista faktur';
echo '<PRE>';
print_r($_POST);
print_r($_GET);
echo '</PRE>';

if($_GET[action] == 'updatemarks')
{
	if(sizeof($_POST[mark]))
		foreach($_POST[mark] as $markid => $mark)
			if($_SESSION[ilp_marks][$markid])
				unset($_SESSION[ilp_marks][$markid]);
			else
				$_SESSION[ilp_marks][$markid] = TRUE;
//	switch($_POST
}
elseif($_GET[action] == 'clearmarks')
	unset($_SESSION[ilp_marks]);

$invoicelist = $LMS->GetInvoicesList();

$listdata[startdate] = $invoicelist['startdate'];
$listdata[enddate] = $invoicelist['enddate'];
$listdata[startyear] = date('Y',$listdata[startdate]);
$listdata[endyear] = date('Y',$listdata[enddate]);

unset($invoicelist['startdate'], $invoicelist['enddate']);

$listdata[totalpos] = sizeof($invoicelist);

if (isset($_SESSION[blp]) && !isset($_GET[page]))
	$_GET[page] = $_SESSION[ilp];

$pagelimit = (! $_CONFIG[phpui][invoicelist_pagelimit] ? 100 : $_CONFIG[phpui][invoicelist_pagelimit]);
$page = (! $_GET[page] ? ceil($listdata[totalpos]/$pagelimit) : $_GET[page]);
$start = ($page - 1) * $pagelimit;
$_SESSION[ilp] = $page;

$marks = $_SESSION[ilp_marks];

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('layout',$layout);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('invoicelist',$invoicelist);
$SMARTY->display('invoicelist.html');

?>
