<?php

/*
 * LMS version 1.7-cvs
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

$layout['pagetitle'] = trans('Invoices List');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$marks = $_POST['marks'];
unset($marked);
if( sizeof($marks) )
	foreach($marks as $marksid => $mark)
		$marked[] = $mark;

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('ils', $s);
$SESSION->save('ils', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('ilo', $o);
$SESSION->save('ilo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('ilc', $c);
$SESSION->save('ilc', $c);

if(isset($_POST['group'])) {
	$g = $_POST['group'];
	$ge = $_POST['groupexclude'];
} else {
	$SESSION->restore('ilg', $g);
	$SESSION->restore('ilge', $ge);
}
$SESSION->save('ilg', $g);
$SESSION->save('ilge', $ge);

if($c == 'cdate' && $s)
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}

$invoicelist = $LMS->GetInvoicesList($s, $c, array('group' => $g, 'exclude'=> $ge), $o);

$SESSION->restore('ilc', $listdata['cat']);
$SESSION->restore('ils', $listdata['search']);
$SESSION->restore('ilg', $listdata['group']);
$SESSION->restore('ilge', $listdata['groupexclude']);
$listdata['order'] = $invoicelist['order'];
$listdata['direction'] = $invoicelist['direction'];
unset($invoicelist['order']);
unset($invoicelist['direction']);

$listdata['totalpos'] = sizeof($invoicelist);

$pagelimit = $CONFIG['phpui']['invoicelist_pagelimit'];
$page = (! $_GET['page'] ? ceil($listdata['totalpos']/$pagelimit) : $_GET['page']);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('marked',$marked);
$SMARTY->assign('grouplist',$LMS->CustomergroupGetAll());
$SMARTY->assign('invoicelist',$invoicelist);
$SMARTY->display('invoicelist.html');

?>
