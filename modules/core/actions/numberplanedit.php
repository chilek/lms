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

	if(!$numberplanedit['isdefault'])
		$numberplanedit['isdefault'] = 0;

	if($numberplanedit['doctype'] == 0)
		$error['doctype'] = trans('Document type is required!');

	if($numberplanedit['period'] == 0)
		$error['period'] = trans('Numbering period is required!');
	
	if($numberplanedit['doctype'] && $numberplanedit['isdefault'])
		if($DB->GetOne('SELECT 1 FROM numberplans WHERE doctype=? AND isdefault=1 AND id!=?', array($numberplanedit['doctype'], $numberplanedit['id'])))
			$error['doctype'] = trans('Selected document type has already defined default plan!');

	if(!$error)
	{
		$DB->Execute('UPDATE numberplans SET template=?, doctype=?, period=?, isdefault=? WHERE id=?',
			    array(
				    $numberplanedit['template'],
				    $numberplanedit['doctype'],
				    $numberplanedit['period'],
				    $numberplanedit['isdefault'],
				    $numberplanedit['id']
				    ));
		
		$SESSION->redirect('?m=numberplanlist');
	}
	$numberplan = $numberplanedit;
}	

$layout['pagetitle'] = trans('Numbering Plan Edit: $0', $template);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('numberplanedit', $numberplan);
$SMARTY->assign('error', $error);
$SMARTY->display('numberplanedit.html');

?>
