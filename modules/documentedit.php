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

$_DOC_DIR = DOC_DIR;

if(isset($_GET['action']) && $_GET['action'] == 'confirm')
{
	if(!empty($_POST['marks']))
	{
	        foreach($_POST['marks'] as $id => $mark)
			$DB->Execute('UPDATE documents SET closed=1 WHERE id=?
				AND EXISTS (SELECT 1 FROM docrights r WHERE r.userid = ?
					AND r.doctype = documents.type AND (r.rights & 4) = 4)',
				array($mark, $AUTH->id));
	}
	else
		$DB->Execute('UPDATE documents SET closed=1 WHERE id=?
			AND EXISTS (SELECT 1 FROM docrights r WHERE r.userid = ?
				AND r.doctype = documents.type AND (r.rights & 4) = 4)',
			array($_GET['id'], $AUTH->id));

	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$document = $DB->GetRow('SELECT documents.id AS id, closed, type, number, template,
	cdate, numberplanid, title, fromdate, todate, description, divisionid
	FROM documents
	LEFT JOIN documentcontents ON (documents.id = docid)
	LEFT JOIN numberplans ON (numberplanid = numberplans.id)
	WHERE documents.id = ?', array($_GET['id']));

if(isset($_POST['document']))
{
	$documentedit = $_POST['document'];
	$documentedit['id'] = $_GET['id'];

	$oldfdate = $documentedit['fromdate'];
	$oldtdate = $documentedit['todate'];

	if(!$documentedit['title'])
		$error['title'] = trans('Document title is required!');

	// check if selected customer can use selected numberplan
        if($documentedit['numberplanid'] && !$DB->GetOne('SELECT 1 FROM numberplanassignments
	        WHERE planid = ? AND divisionid = ?', array($documentedit['numberplanid'], $document['divisionid'])))
	{
		$error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
	}
	elseif(!$documentedit['number'])
	{
		if($document['numberplanid'] != $documentedit['numberplanid'])
		{
			$tmp = $LMS->GetNewDocumentNumber($documentedit['type'], $documentedit['numberplanid']);
			$documentedit['number'] = $tmp ? $tmp : 1;
		}
		else
			$documentedit['number'] = $document['number'];
	}
	elseif(!preg_match('/^[0-9]+$/', $documentedit['number']))
    		$error['number'] = trans('Document number must be an integer!');
	elseif($document['number'] != $documentedit['number'] || $document['numberplanid'] != $documentedit['numberplanid'])
	{
		if($LMS->DocumentExists($documentedit['number'], $documentedit['type'], $documentedit['numberplanid']))
			$error['number'] = trans('Document with specified number exists!');
	}

	if($documentedit['fromdate'])
	{
		$date = explode('/',$documentedit['fromdate']);
		if(checkdate($date[1],$date[2],$date[0]))
			$documentedit['fromdate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
		else
			$error['fromdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	else 
		$documentedit['fromdate'] = 0;

	if($documentedit['todate'])
	{
		$date = explode('/',$documentedit['todate']);
		if(checkdate($date[1],$date[2],$date[0]))
			$documentedit['todate'] = mktime(23,59,59,$date[1],$date[2],$date[0]);
		else
			$error['todate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
	}
	else
		$documentedit['todate'] = 0;

	if($documentedit['fromdate'] > $documentedit['todate'] && $documentedit['todate']!=0)
		$error['todate'] = trans('Start date can\'t be greater than end date!');

/*	if($filename = $_FILES['file']['name'])
	{
		if(is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size'])
		{
			$file = $_FILES['file']['tmp_name'];
			$documentedit['md5sum'] = md5_file($file);
			$documentedit['contenttype'] = $_FILES['file']['type'];
			$documentedit['filename'] = $filename;
		}
		else // upload errors
			switch($_FILES['file']['error'])
			{
				case 1:
				case 2: $error['file'] = trans('File is too large.'); break;
				case 3: $error['file'] = trans('File upload has finished prematurely.'); break;
				case 4: $error['file'] = trans('Path to file was not specified.'); break;
				default: $error['file'] = trans('Problem during file upload.'); break;
			}
	}
	elseif($documentedit['template'])
	{
		include(DOC_DIR.'/templates/'.$documentedit['template'].'/info.php');
		if(file_exists(DOC_DIR.'/templates/'.$engine['engine'].'/engine.php'))
			require_once(DOC_DIR.'/templates/'.$engine['engine'].'/engine.php');
		else
			require_once(DOC_DIR.'/templates/default/engine.php');

		if($output)
		{
			$file = DOC_DIR.'/tmp.file';
			$fh = fopen($file, 'w');
			fwrite($fh, $output);
			fclose($fh);

			$documentedit['md5sum'] = md5_file($file);
			$documentedit['contenttype'] = $engine['content_type'];
			$documentedit['filename'] = $engine['output'];
		}
		else
			$error['template'] = trans('Problem during file generation!');
	}
	else
		$error['file'] = trans('You must to specify file for upload or select document template!');

	if(!$error)
	{
		$path = DOC_DIR.'/'.substr($documentedit['md5sum'],0,2);
		@mkdir($path, 0700);
		$newfile = $path.'/'.$documentedit['md5sum'];
		if(!file_exists($newfile))
		{
			if(!@rename($file, $newfile))
				$error['file'] = trans('Can\'t save file in "$a" directory!', $path);
		}
		else
			$error['file'] = trans('Specified file exists in database!');
	}
*/
	$documentedit['closed'] = isset($documentedit['closed']) ? 1 : 0;

	if(!$error)
	{
		$DB->BeginTrans();

		$DB->Execute('UPDATE documents SET type=?, closed=?, number=?, numberplanid=?
				WHERE id=?',
				array(	$documentedit['type'],
					$documentedit['closed'],
					$documentedit['number'],
					$documentedit['numberplanid'],
					$documentedit['id'],
					));

		$DB->Execute('UPDATE documentcontents SET title=?, fromdate=?, todate=?, description=?
				WHERE docid=?',
				array(	$documentedit['title'],
					$documentedit['fromdate'],
					$documentedit['todate'],
					$documentedit['description'],
					$documentedit['id']
					));

		$DB->CommitTrans();

		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
	else
	{
		$document['title'] = $documentedit['title'];
		$document['type'] = $documentedit['type'];
		$document['description'] = $documentedit['description'];
		$document['closed'] = $documentedit['closed'];
		$document['number'] = $documentedit['number'];
		$document['numberplanid'] = $documentedit['numberplanid'];
		$document['fromdate'] = $oldfdate;
		$document['todate'] = $oldtdate;
	}
}
else
{
	if($document['fromdate']>0)
		$document['fromdate'] = date('Y/m/d', $document['fromdate']);
	if($document['todate']>0)
		$document['todate'] = date('Y/m/d', $document['todate']);
}

$rights = $DB->GetCol('SELECT doctype FROM docrights
	WHERE userid = ? AND (rights & 2) = 2', array($AUTH->id));

if(!$rights || !$DB->GetOne('SELECT 1 FROM docrights
	WHERE userid = ? AND doctype = ? AND (rights & 8) = 8',
	array($AUTH->id, $document['type'])))
{
        $SMARTY->display('noaccess.html');
        die;
}

$allnumberplans = array();
$numberplans = array();

if($templist = $LMS->GetNumberPlans())
        foreach($templist as $item)
	        if($item['doctype']<0)
			$allnumberplans[] = $item;

if(isset($document['numberplanid']))
{
        foreach($allnumberplans as $plan)
                if($plan['doctype'] == $document['numberplanid'])
                        $numberplans[] = $plan;
}

/*
if($dirs = getdir(DOC_DIR.'/templates', '^[a-z0-9_-]+$'))
	foreach($dirs as $dir)
	{
		$infofile = DOC_DIR.'/templates/'.$dir.'/info.php';
		if(file_exists($infofile))
		{
			unset($engine);
			include($infofile);
			$docengines[$dir] = $engine;
		}
	}

if($docengines) ksort($docengines);
*/

$layout['pagetitle'] = trans('Edit Document: $a', docnumber($document['number'], $document['template'], $document['cdate']));

//$SMARTY->assign('docengines', $docengines);
$SMARTY->assign('numberplans', $numberplans);
$SMARTY->assign('docrights', $rights);
$SMARTY->assign('allnumberplans', $allnumberplans);
$SMARTY->assign('error', $error);
$SMARTY->assign('document', $document);
$SMARTY->display('documentedit.html');

?>
