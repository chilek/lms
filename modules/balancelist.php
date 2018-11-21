<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

if(isset($_POST['search']))
        $s = $_POST['search'];
else
	$SESSION->restore('bls', $s);

if(isset($_POST['cat']))
        $c = $_POST['cat'];
else
	$SESSION->restore('blc', $c);
if (!isset($c))
{
$c="cdate";
}
$SESSION->save('blc', $c);

if(isset($_POST['group']))
{
        $g = $_POST['group'];
	$ge = isset($_POST['groupexclude']) ? 1 : 0;
} else {
        $SESSION->restore('blg', $g);
        $SESSION->restore('blge', $ge);
}
$SESSION->save('blg', $g);
$SESSION->save('blge', $ge);

$SESSION->save('bls', $s);

if($c == 'cdate' && $s)
{
	$date = date_to_timestamp($s);
	if (empty($date))
		$s = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
	else
		$s = $date;
}

if(!empty($_POST['from']))
{
	$from = datetime_to_timestamp($_POST['from']);
}
elseif($SESSION->is_set('blf'))
	$SESSION->restore('blf', $from);
else
	$from = '';

if(!empty($_POST['to']))
{
    $to = datetime_to_timestamp($_POST['to']);
}
elseif($SESSION->is_set('blt'))
	$SESSION->restore('blt', $to);
else
	$to = '';

if(!empty($from) && !empty($to)) {
	if($from < $to) {
    $SESSION->save('blf', $from);
    $SESSION->save('blt', $to);
}
}
elseif(!empty($from))
	$SESSION->save('blf', $from);
elseif(!empty($to))
    $SESSION->save('blt', $to);

$pagelimit = ConfigHelper::getConfig('phpui.balancelist_pagelimit');
$page = (empty($_GET['page']) ? 0 : intval($_GET['page']));

if (isset($_GET['sourcefileid'])) {
	$s = $DB->GetOne('SELECT name FROM sourcefiles WHERE id = ?', array($_GET['sourcefileid']));
	$c = 'cashimport';
	$SESSION->save('bls', $s);
	$SESSION->save('blc', $c);
}

$summary = $LMS->GetBalanceList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude'=> $ge,
	'from' => $from, 'to' => $to, 'count' => true));
$total = intval($summary['total']);

$limit = intval(ConfigHelper::getConfig('phpui.balancelist_pagelimit', 100));
$page = !isset($_GET['page']) ? ceil($total / $limit) : $_GET['page'];
if (empty($page))
	$page = 1;
$page = intval($page);
$offset = ($page - 1) * $limit;

$balancelist = $LMS->GetBalanceList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude'=> $ge,
	'limit' => $limit, 'offset' => $offset, 'from' => $from, 'to' => $to, 'count' =>  false));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$listdata['liability'] = $summary['liability'];
$listdata['income'] = $summary['income'];
$listdata['expense'] = $summary['expense'];
$listdata['totalval'] = $summary['income'] - $summary['expense'];
$listdata['total'] = $total;

$SESSION->restore('blc', $listdata['cat']);
$SESSION->restore('bls', $listdata['search']);
$SESSION->restore('blg', $listdata['group']);
$SESSION->restore('blge', $listdata['groupexclude']);
$SESSION->restore('blf', $listdata['from']);
$SESSION->restore('blt', $listdata['to']);

$layout['pagetitle'] = trans('Balance Sheet');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('grouplist',$LMS->CustomergroupGetAll());
$SMARTY->display('balance/balancelist.html');

?>
