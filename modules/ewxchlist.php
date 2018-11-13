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

function GetChannelsList($order='name,asc')
{
	global $DB, $LMS;

	if($order=='')
        $order='name,asc';

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction=='desc') ? $direction = 'desc' : $direction = 'asc';

	switch($order)
	{
		case 'id':
		case 'devcnt':
		case 'nodecnt':
		case 'downceil':
		case 'upceil':
		case 'downceil_n':
		case 'upceil_n':
		case 'cid':
	        $sqlord = ' ORDER BY '.$order;
		break;
		default:
            $sqlord = ' ORDER BY name';
		break;
	}

	$channels = $DB->GetAll('('
	    .'SELECT c.id, c.name, c.upceil, c.downceil,
	    c.upceil_n, c.downceil_n, c.halfduplex, c2.id AS cid,
		(SELECT COUNT(*) FROM netdevices WHERE channelid = c.id) AS devcnt,
		(SELECT COUNT(*) FROM ewx_stm_nodes n
		    JOIN ewx_stm_channels ch ON (n.channelid = ch.id)
		    WHERE ch.cid = c.id) AS nodecnt
		FROM ewx_channels c
		LEFT JOIN ewx_stm_channels c2 ON (c.id = c2.cid)
		)
		UNION
		(
		SELECT 0 AS id, \''.trans('default').'\' AS name,
		    ch.upceil, ch.downceil, 0 AS upceil_n, 0 AS downceil_n, 0, ch.id AS cid,
		    (SELECT COUNT(DISTINCT netdev) FROM vnodes WHERE netdev IS NOT NULL AND id IN (
		        SELECT nodeid FROM ewx_stm_nodes WHERE channelid = ch.id)) AS devcnt,
		    (SELECT COUNT(*) FROM ewx_stm_nodes WHERE channelid = ch.id) AS nodecnt
		    FROM ewx_stm_channels ch
		    WHERE ch.cid = 0
		)'
		.($sqlord != '' ? $sqlord.' '.$direction : ''));

	$channels['total'] = empty($channels) ? 0 : count($channels);
	$channels['order'] = $order;
	$channels['direction'] = $direction;

	return $channels;
}

if(!isset($_GET['o']))
        $SESSION->restore('eclo', $o);
else
        $o = $_GET['o'];
$SESSION->save('eclo', $o);

if ($SESSION->is_set('eclp') && !isset($_GET['page']))
        $SESSION->restore('eclp', $_GET['page']);

$channels = GetChannelsList($o);

$listdata['total'] = $channels['total'];
$listdata['order'] = $channels['order'];
$listdata['direction'] = $channels['direction'];

unset($channels['total']);
unset($channels['order']);
unset($channels['direction']);

$page = (empty($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.channellist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('eclp', $page);

$layout['pagetitle'] = trans('Channels List');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('channels', $channels);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('ewxch/ewxchlist.html');

?>
