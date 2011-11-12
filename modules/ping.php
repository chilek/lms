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
 *  $Id$
 */

function refresh($params)
{
	// xajax response
	$objResponse = new xajaxResponse();

	$ipaddr = $params['ipaddr'];
	$received = $params['received'];
	$transmitted = $params['transmitted'];
	exec('sudo ping '.$ipaddr.' -c 1 -s 1450 -w 1.0', $output);
	$transmitted++;
	$reply = preg_grep('/icmp_[rs]eq/', $output);
	if (count($reply))
	{
		$received++;
		$output = preg_replace('/^([0-9]+).+ttl=([0-9]+).+time=([0-9\.]+.+)$/',
			trans('\1 bytes from $a: icmp_req=$b ttl=\2 time=\3', $ipaddr, $transmitted), current($reply));
	}
	else
		$output = trans('Destination Host Unreachable');
	if (empty($received))
		$received = '0';
	$objResponse->addAppend('data', 'innerHTML', $output.'<br>');
	$objResponse->addAssign('transmitted', 'value', $transmitted);
	$objResponse->addAssign('received', 'value', $received);
	$objResponse->addAssign('summary', 'innerHTML', '<b>'.trans('Total: $a% ($b/$c)', 
		round(($received / $transmitted) * 100), $received, $transmitted).'</b>');
	return $objResponse;
}

$layout['pagetitle'] = trans('Ping');

if (!isset($_GET['id']) || !$DB->GetOne('SELECT id FROM nodes WHERE id=?', array(intval($_GET['id']))))
	$SESSION->redirect('?m=nodelist');

$nodeid = $_GET['id'];

if (isset($_GET['p']) && $_GET['p'] == 'main')
{
	/* Using AJAX for template plugins */
	require(LIB_DIR.'/xajax/xajax.inc.php');

	$xajax = new xajax();
	$xajax->errorHandlerOn();
	$xajax->registerFunction("refresh");
	$xajax->processRequests();

	$SMARTY->assign('xajax', $xajax->getJavascript('img/', 'xajax.js'));
	$SMARTY->assign('part', $_GET['p']);
}

$SMARTY->assign('ipaddr', $LMS->GetNodeIpByID($nodeid));
$SMARTY->assign('nodeid', $nodeid);
$SMARTY->display('ping.html');

?>
