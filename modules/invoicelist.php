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

$layout['pagetitle'] = 'Lista faktur';

$marks = $_POST['marks'];
unset($marked);
if( sizeof($marks) )
	foreach($marks as $marksid => $mark)
		$marked[] = $mark;

$invoicelist = $LMS->GetInvoicesList();

$listdata['startdate'] = $invoicelist['startdate'];
$listdata['enddate'] = $invoicelist['enddate'];
$listdata['startyear'] = date('Y',$listdata['startdate']);
$listdata['endyear'] = date('Y',$listdata['enddate']);

unset($invoicelist['startdate'], $invoicelist['enddate']);

$listdata['totalpos'] = sizeof($invoicelist);

$pagelimit = $LMS->CONFIG['phpui']['invoicelist_pagelimit'];
$page = (! $_POST['page'] ? ceil($listdata['totalpos']/$pagelimit) : $_POST['page']);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('layout',$layout);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('marked',$marked);
$SMARTY->assign('invoicelist',$invoicelist);
$SMARTY->assign('openonload',$_GET['openonload']);
$SMARTY->display('invoicelist.html');

?>
