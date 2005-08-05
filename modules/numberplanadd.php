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

$numberplanadd = isset($_POST['numberplanadd']) ? $_POST['numberplanadd'] : NULL;

if(sizeof($numberplanadd)) 
{
	$numberplanadd['template'] = trim($numberplanadd['template']);

	if($numberplanadd['template']=='' && $numberplanadd['doctype']==0 && $numberplanadd['period']==0)
	{
		$SESSION->redirect('?m=numberplanlist');
	}
	
	if($numberplanadd['template'] == '')
		$error['template'] = trans('Number template is required!');
	elseif(strpos($numberplanadd['template'], '%N')===FALSE)
		$error['template'] = trans('Template must consist "%N" specifier!');

	if(!$numberplanadd['isdefault'])
		$numberplanadd['isdefault'] = 0;

	if($numberplanadd['doctype'] == 0)
		$error['doctype'] = trans('Document type is required!');

	if($numberplanadd['period'] == 0)
		$error['period'] = trans('Numbering period is required!');
	
	if($numberplanadd['doctype'] && $numberplanadd['isdefault'])
		if($DB->GetOne('SELECT 1 FROM numberplans WHERE doctype=? AND isdefault=1', array($numberplanadd['doctype'])))
			$error['doctype'] = trans('Selected document type has already defined default plan!');

	if(!$error)
	{
		$DB->Execute('INSERT INTO numberplans (template, doctype, period, isdefault) 
			    VALUES (?,?,?,?)',array(
				    $numberplanadd['template'],
				    $numberplanadd['doctype'],
				    $numberplanadd['period'],
				    $numberplanadd['isdefault']
				    ));
		
		if(!isset($numberplanadd['reuse']))
		{
			$SESSION->redirect('?m=numberplanlist');
		}
		unset($numberplanadd['template']);
		unset($numberplanadd['period']);
		unset($numberplanadd['doctype']);
		unset($numberplanadd['isdefault']);
	}
}	

$layout['pagetitle'] = trans('New Numbering Plan');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('numberplanadd', $numberplanadd);
$SMARTY->assign('error', $error);
$SMARTY->display('numberplanadd.html');

?>
