<?php

/*
 * LMS version 1.9-cvs
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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$balancelist = $LMS->GetBalanceList();

$listdata['incomeu'] = $balancelist['incomeu'];
$listdata['income'] = $balancelist['income'];
$listdata['uinvoice'] = $balancelist['uinvoice'];
$listdata['expense'] = $balancelist['expense'];
$listdata['total'] = $balancelist['total'];
unset($balancelist['incomeu']);
unset($balancelist['income']);
unset($balancelist['uinvoice']);
unset($balancelist['expense']);
unset($balancelist['total']);
$listdata['totalpos'] = sizeof($balancelist);

$pagelimit = $LMS->CONFIG['phpui']['balancelist_pagelimit'];
$page = (! $_GET['page'] ? ceil($listdata['totalpos']/$pagelimit) : $_GET['page']); 
$start = ($page - 1) * $pagelimit;

$layout['pagetitle'] = trans('Balance Sheet');

$SMARTY->assign('balancelist',$balancelist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->display('balancelist.html');

?>
