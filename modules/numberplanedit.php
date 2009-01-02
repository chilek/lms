<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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
			.(!empty($numberplanedit['divisions']) ? ' AND EXISTS (
				SELECT 1 FROM numberplanassignments WHERE planid = n.id
				AND divisionid IN ('.implode(',', $numberplanedit['divisions']).'))'
			: ' AND NOT EXISTS (SELECT 1 FROM numberplanassignments
			        WHERE planid = n.id)'),					
			array($numberplanedit['doctype'], $numberplanedit['id'])))
		{
			$error['doctype'] = trans('Selected document type has already defined default plan!');
		}
	
	if(!$error)
	{
		$DB->BeginTrans();
		
		$DB->Execute('UPDATE numberplans SET template=?, doctype=?, period=?, isdefault=? WHERE id=?',
			    array(
				    $numberplanedit['template'],
				    $numberplanedit['doctype'],
				    $numberplanedit['period'],
				    $numberplanedit['isdefault'],
				    $numberplanedit['id']
			    ));
		
		$DB->Execute('DELETE FROM numberplanassignments WHERE planid = ?', array($numberplanedit['id']));
	
		if(!empty($numberplanedit['divisions']))
			foreach($numberplanedit['divisions'] as $div)
				$DB->Execute('INSERT INTO numberplanassignments (planid, divisionid)
					VALUES (?, ?)', array($numberplanedit['id'], intval($div)));

		$DB->CommitTrans();
		
		$SESSION->redirect('?m=numberplanlist');
	}
	$numberplan = $numberplanedit;
}
else
	$numberplan['divisions'] = $DB->GetCol('SELECT divisionid
			FROM numberplanassignments WHERE planid = ?', array($numberplan['id']));

$layout['pagetitle'] = trans('Numbering Plan Edit: $0', $template);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('numberplanedit', $numberplan);
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions
		WHERE status = 0 '
		.(!empty($numberplan['divisions']) ? 'OR id IN ('.implode(',', $numberplan['divisions']).')' : '')
		.'ORDER BY shortname', array($numberplan['id'])));
$SMARTY->assign('error', $error);
$SMARTY->display('numberplanedit.html');

?>
