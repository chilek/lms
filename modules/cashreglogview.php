<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function GetCashLog($order = 'time,asc', $regid = 0)
{
    global $DB;

    list($order,$direction) = sscanf($order, '%[^,],%s');

    ($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

    switch ($order) {
        case 'value':
            $sqlord = " ORDER BY value $direction";
            break;
        case 'snapshot':
            $sqlord = " ORDER BY snapshot $direction";
            break;
        case 'description':
            $sqlord = " ORDER BY description $direction";
            break;
        case 'username':
            $sqlord = " ORDER BY username $direction";
            break;
        default:
            $sqlord = " ORDER BY time $direction";
            break;
    }

    $list = $DB->GetAll(
        'SELECT cashreglog.id, time, value, description,
				    snapshot, userid, vusers.name AS username
			    FROM cashreglog
			    LEFT JOIN vusers ON (userid = vusers.id)
			    WHERE regid = ?
			    '.($sqlord != '' ? $sqlord : ''),
        array($regid)
    );

    $list['total'] = count($list);
    $list['order'] = $order;
    $list['direction'] = $direction;

    return $list;
}

if (!isset($_GET['o'])) {
    $SESSION->restore('crlo', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('crlo', $o);

if (!isset($_GET['regid'])) {
    $SESSION->restore('crlr', $regid);
} else {
    $regid = $_GET['regid'];
}
$SESSION->save('crlr', $regid);

if (!$regid) {
        $SESSION->redirect('?m=cashreglist');
}

if (! $DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $regid))) {
        $SMARTY->display('noaccess.html');
        $SESSION->close();
        die;
}

$cashreglog = GetCashLog($o, $regid);

$listdata['total'] = $cashreglog['total'];
$listdata['order'] = $cashreglog['order'];
$listdata['direction'] = $cashreglog['direction'];
$listdata['regid'] = $regid;

unset($cashreglog['total']);
unset($cashreglog['order']);
unset($cashreglog['direction']);

if ($SESSION->is_set('crlp') && !isset($_GET['page'])) {
    $SESSION->restore('crlp', $_GET['page']);
}

$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.cashreglog_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('crlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Cash History of Registry:').
        ' <A href="?m=receiptlist&regid='.$regid.'">'.$DB->GetOne('SELECT name FROM cashregs WHERE id = ?', array($regid)).'</A>';

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('cashreglog', $cashreglog);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('cash/cashreglogview.html');
