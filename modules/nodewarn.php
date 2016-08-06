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

function getMessageTemplate($tmplid) {
	global $DB;

	$result = new xajaxResponse();
	$message = $DB->GetOne('SELECT message FROM templates WHERE id = ?', array($tmplid));
	$result->call('messageTemplateReceived', $message);

	return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getMessageTemplate'));
$SMARTY->assign('xajax', $LMS->RunXajax());

$setwarnings = isset($_POST['setwarnings']) ? $_POST['setwarnings'] : array();

if(isset($setwarnings['mnodeid']))
{
	$message = isset($setwarnings['message']) ? $setwarnings['message'] : '';
	$warnon  = isset($setwarnings['warnon']) ? $setwarnings['warnon'] : FALSE;
	$warnoff = isset($setwarnings['warnoff']) ? $setwarnings['warnoff'] : FALSE;

	$msgtmplid = intval($setwarnings['tmplid']);
	$msgtmploper = intval($setwarnings['tmploper']);
	$msgtmplname = $setwarnings['tmplname'];
	if ($msgtmploper > 1)
		switch ($msgtmploper) {
			case 2:
				if (empty($msgtmplid))
					break;
				$LMS->UpdateMessageTemplate($msgtmplid, TMPL_WARNING, null, '', $setwarnings['message']);
				break;
			case 3:
				if (!strlen($msgtmplname))
					break;
				$LMS->AddMessageTemplate(TMPL_WARNING, $msgtmplname, '', $setwarnings['message']);
				break;
		}

	$nodes = array_filter($setwarnings['mnodeid'], 'is_natural');

	if (!empty($nodes)) {
		$LMS->NodeSetWarn($nodes, $warnon ? 1 : 0);
		if ($message) {
			$cids = $DB->GetCol('SELECT DISTINCT n.ownerid FROM vnodes n WHERE n.id IN (' . implode(',', $nodes) . ')');
			$DB->Execute('UPDATE customers SET message = ? WHERE id IN 
				(' . implode(',', $cids) . ')', array($message));
			if ($SYSLOG) {
				foreach ($cids as $cid) {
					$args = array(
						SYSLOG::RES_CUST => $cid,
						'message' => $message
					);
					$SYSLOG->AddMessage(SYSLOG::RES_CUST, SYSLOG::OPER_UPDATE, $args);
				}
			}
		}
		$data = array('nodes' => $nodes);
		$LMS->ExecHook('node_warn_after', $data);

		$LMS->executeHook('nodewarn_after_submit', $data);
	}

	$SESSION->save('warnmessage', $message);
	$SESSION->save('warnon', $warnon);
	$SESSION->save('warnoff', $warnoff);

	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$warning = isset($_GET['warning']) ? 1 : 0;

if (!empty($_POST['marks']))
{
	$nodes = array_filter($_POST['marks'], 'is_natural');

	$LMS->NodeSetWarn($nodes, $warning);
	if (!empty($nodes)) {
		$data = array('nodes' => $nodes, 'warning' => $warning);
		$LMS->ExecHook('node_warn_after', $data);

		$LMS->executeHook('nodewarn_after_submit', $data);
	}

	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$backid = isset($_GET['ownerid']) ? $_GET['ownerid'] : 0;

if($backid && $LMS->CustomerExists($backid))
{
	$res = $LMS->NodeSetWarnU($backid, $warning);

	if ($res) {
		$data = array('ownerid' => $backid, 'warning' => $warning);
		$LMS->ExecHook('node_warn_after', $data);

		$LMS->executeHook('nodewarn_after_submit', $data);
	}

	$redir = $SESSION->get('backto');
	if($SESSION->get('lastmodule')=='customersearch')
		$redir .= '&search=1';

	$SESSION->redirect('?'.$redir.'#'.$backid);
}

$backid = isset($_GET['id']) ? $_GET['id'] : 0;

if($backid && $LMS->NodeExists($backid))
{
    $res = $LMS->NodeSwitchWarn($backid);

	if ($res) {
		$data = array('nodeid' => $backid);
		$LMS->ExecHook('node_warn_after', $data);

		$LMS->executeHook('nodewarn_after_submit', $data);
	}

	if(!empty($_GET['shortlist'])) {
	    header('Location: ?m=nodelistshort&id='.$LMS->GetNodeOwner($backid));
		die;
	}
	else {
		$SESSION->redirect('?'.$SESSION->get('backto').'#'.$backid);
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Notices');

$nodelist = $LMS->GetNodeList();

unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['direction']);
unset($nodelist['totalon']);
unset($nodelist['totaloff']);

$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates(TMPL_WARNING));
$SMARTY->assign('warnmessage', $SESSION->get('warnmessage'));
$SMARTY->assign('warnon', $SESSION->get('warnon'));
$SMARTY->assign('warnoff', $SESSION->get('warnoff'));
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->display('node/nodewarnings.html');

?>
