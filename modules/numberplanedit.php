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

$numberplan = $DB->GetRow('SELECT id, period, template, doctype, isdefault
			    FROM numberplans WHERE id=?', array($_GET['id']));

$template = $numberplan['template'];

$numberplanedit = isset($_POST['numberplanedit']) ? $_POST['numberplanedit'] : NULL;

if(sizeof($numberplanedit)) 
{
	$numberplanedit['template'] = trim($numberplanedit['template']);
	$numberplanedit['id'] = $numberplan['id'];

	if($numberplanedit['template'] == '')
		$error['template'] = trans('Number template is required!');
	elseif(!preg_match('/%[1-9]{0,1}N/', $numberplanedit['template']))
		$error['template'] = trans('Template must consist "%N" specifier!');

	if(!isset($numberplanedit['isdefault']))
		$numberplanedit['isdefault'] = 0;

	if($numberplanedit['doctype'] == 0)
		$error['doctype'] = trans('Document type is required!');

	if($numberplanedit['period'] == 0)
		$error['period'] = trans('Numbering period is required!');
	
	if($numberplanedit['doctype'] && $numberplanedit['isdefault'])
		if($DB->GetOne('SELECT 1 FROM numberplans n
			WHERE doctype = ? AND isdefault = 1 AND n.id != ?'
			.(!empty($_POST['selected']) ? ' AND EXISTS (
				SELECT 1 FROM numberplanassignments WHERE planid = n.id
				AND divisionid IN ('.implode(',', array_keys($_POST['selected'])).'))'
			: ' AND NOT EXISTS (SELECT 1 FROM numberplanassignments
			        WHERE planid = n.id)'),					
			array($numberplanedit['doctype'], $numberplanedit['id'])))
		{
			$error['doctype'] = trans('Selected document type has already defined default plan!');
		}

	if (!$error) {
		$DB->BeginTrans();

		$args = array(
			'template' => $numberplanedit['template'],
			'doctype' => $numberplanedit['doctype'],
			'period' => $numberplanedit['period'],
			'isdefault' => $numberplanedit['isdefault'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $numberplanedit['id']
		);
		$DB->Execute('UPDATE numberplans SET template=?, doctype=?, period=?, isdefault=? WHERE id=?',
				array_values($args));

		if ($SYSLOG) {
			$SYSLOG->AddMessage(SYSLOG_RES_NUMPLAN, SYSLOG_OPER_UPDATE,
				$args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN]));
			$assigns = $DB->GetAll('SELECT * FROM numberplanassignments WHERE planid = ?',
				array($numberplanedit['id']));
			if (!empty($assigns))
				foreach ($assigns as $assign) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLANASSIGN] => $assign['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $assign['planid'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $assign['divisionid']
					);
					$SYSLOG->AddMessage(SYSLOG_RES_NUMPLANASSIGN, SYSLOG_OPER_DELETE,
						$args, array_keys($args));
				}
		}

		$DB->Execute('DELETE FROM numberplanassignments WHERE planid = ?', array($numberplanedit['id']));

		if (!empty($_POST['selected']))
			foreach ($_POST['selected'] as $idx => $name) {
				$DB->Execute('INSERT INTO numberplanassignments (planid, divisionid)
					VALUES (?, ?)', array($numberplanedit['id'], intval($idx)));
				if ($SYSLOG) {
					$id = $DB->GetLastInsertID('numberplanassignments');
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLANASSIGN] => $id,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => $numberplanedit['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => intval($idx)
					);
					$SYSLOG->AddMessage(SYSLOG_RES_NUMPLANASSIGN, SYSLOG_OPER_ADD,
						$args, array_keys($args));
				}
			}

		$DB->CommitTrans();
		
		$SESSION->redirect('?m=numberplanlist');
	}
	else
	{
		$numberplanedit['selected'] = array();
	        if(isset($_POST['selected']))
		{
		        foreach($_POST['selected'] as $idx => $name)
		        {
		                $numberplanedit['selected'][$idx]['id'] = $idx;
		                $numberplanedit['selected'][$idx]['name'] = $name;
		        }
		}
	}
	$numberplan = $numberplanedit;
}
else
{
	$numberplan['selected'] = $DB->GetAllByKey('SELECT d.id, d.shortname AS name
		FROM numberplanassignments, divisions d
		WHERE d.id = divisionid AND planid = ?', 'id', array($numberplan['id']));
}

$layout['pagetitle'] = trans('Numbering Plan Edit: $a', $template);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('numberplanedit', $numberplan);
$SMARTY->assign('available', $DB->GetAllByKey('SELECT id, shortname AS name
		FROM divisions WHERE status = 0 '
		.(!empty($numberplan['selected']) ? 'OR id IN ('.implode(',', array_keys($numberplan['selected'])).')' : '')
		.'ORDER BY shortname', 'id', array($numberplan['id'])));
$SMARTY->assign('error', $error);
$SMARTY->display('numberplanedit.html');

?>
