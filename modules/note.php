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

/*
if(strtolower(ConfigHelper::getConfig('notes.type')) == 'pdf')
{
    include('notee_pdf.php');
    $SESSION->close();
    die;
}
*/

header('Content-Type: '.ConfigHelper::getConfig('notes.content_type'));
$attachment_name = ConfigHelper::getConfig('notes.attachment_name');
if(!empty($attachment_name))
	header('Content-Disposition: attachment; filename='.$attachment_name);

$SMARTY->assign('css', file('img/style_print.css')); 

if(isset($_GET['print']) && $_GET['print'] == 'cached')
{
	$SESSION->restore('ilm', $ilm);
	$SESSION->remove('ilm');

	if(!empty($_POST['marks']))
		foreach($_POST['marks'] as $id => $mark)
			$ilm[$id] = $mark;
	if(sizeof($ilm))
		foreach($ilm as $mark)
			$ids[] = intval($mark);

	if(isset($_GET['cash']) && !empty($ids))
	{
		// we need to check if that document is a debit note
		$ids = $DB->GetCol('SELECT DISTINCT docid FROM cash, documents
			WHERE docid = documents.id AND documents.type='.DOC_DNOTE.'
			AND cash.id IN ('.implode(',', $ids).')');
	}

	if(empty($ids))
	{
		$SESSION->close();
		die;
	}

	$layout['pagetitle'] = trans('Debit Notes');
	$SMARTY->display('noteheader.html');


	sort($ids);

	$count = sizeof($ids);
	$i=0;
	foreach($ids as $idx => $noteid)
	{
		$note = $LMS->GetNoteContent($noteid);

		$i++;
		if($i == $count) $note['last'] = TRUE;
		$SMARTY->assign('note', $note);
		$SMARTY->display(ConfigHelper::getConfig('notes.template_file'));
	}
	$SMARTY->display('clearfooter.html');
}
elseif(isset($_GET['fetchallnotes']))
{
	$layout['pagetitle'] = trans('Debit Notes');

	$ids = $DB->GetCol('SELECT d.id FROM documents d
		WHERE d.cdate >= ? AND d.cdate <= ? AND d.type = ?'
		.(!empty($_GET['customerid']) ? ' AND d.customerid = '.intval($_GET['customerid']) : '')
		.(!empty($_GET['numberplanid']) ? ' AND d.numberplanid = '.intval($_GET['numberplanid']) : '')
		.(!empty($_GET['groupid']) ? 
		' AND '.(!empty($_GET['groupexclude']) ? 'NOT' : '').'
		        EXISTS (SELECT 1 FROM customerassignments a
			        WHERE a.customergroupid = '.intval($_GET['groupid']).'
				AND a.customerid = d.customerid)' : '')
		.' AND NOT EXISTS (
			SELECT 1 FROM customerassignments a
		        JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)' 
		.' ORDER BY CEIL(d.cdate/86400), d.id',
		array($_GET['from'], $_GET['to'], DOC_DNOTE));

	if(!$ids)
	{
		$SESSION->close();
		die;
	}

	$count = sizeof($ids);
	$i=0;

	$SMARTY->display('noteheader.html');

	foreach($ids as $idx => $noteid)
	{
		$note = $LMS->GetNoteContent($noteid);

		$SMARTY->assign('note',$note);
		$SMARTY->display(ConfigHelper::getConfig('notes.template_file'));
	}
	$SMARTY->display('clearfooter.html');
}
elseif($note = $LMS->GetNoteContent($_GET['id']))
{
	$number = docnumber($note['number'], $note['template'], $note['cdate']);
	$layout['pagetitle'] = trans('Debit Note No. $a', $number);

	$SMARTY->display('noteheader.html');

	$note['last'] = TRUE;
	$SMARTY->assign('note',$note);
	$SMARTY->display(ConfigHelper::getConfig('notes.template_file'));
	$SMARTY->display('clearfooter.html');
}
else
{
	$SESSION->redirect('?m=notelist');
}

?>
