<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

$attachment_name = ConfigHelper::getConfig('notes.attachment_name');
$note_type = ConfigHelper::getConfig('notes.type');
$dontpublish = isset($_GET['dontpublish']);

if ($note_type == 'pdf') {
	$template = ConfigHelper::getConfig('notes.template_file', 'standard');
	if ($template == 'standard')
		$classname = 'LMSTcpdfDebitNote';
	else
		$classname = 'LMS' . ucwords($template) . 'DebitNote';
	$document = new $classname(trans('Debit Notes'));
} else
	$document = new LMSHtmlDebitNote($SMARTY);

if (isset($_GET['print']) && $_GET['print'] == 'cached') {
	$SESSION->restore('ilm', $ilm);
	$SESSION->remove('ilm');

	if (!empty($_POST['marks']))
		foreach($_POST['marks'] as $id => $mark)
			$ilm[$id] = $mark;
	if (count($ilm))
		foreach($ilm as $mark)
			$ids[] = intval($mark);

	if (isset($_GET['cash']) && !empty($ids))
		// we need to check if that document is a debit note
		$ids = $DB->GetCol('SELECT DISTINCT docid FROM cash, documents
			WHERE docid = documents.id AND documents.type = ?
			AND cash.id IN (' . implode(',', $ids) . ')', array(DOC_DNOTE));

	if (empty($ids)) {
		$SESSION->close();
		die;
	}

	$layout['pagetitle'] = trans('Debit Notes');

	sort($ids);

	$count = count($ids);
	$i = 0;
	foreach($ids as $idx => $noteid) {
		$note = $LMS->GetNoteContent($noteid);
		if ($count == 1)
			$docnumber = docnumber(array(
				'number' => $note['number'],
				'template' => $note['template'],
				'cdate' => $note['cdate'],
			));

		$note['dontpublish'] = $dontpublish;
		$i++;
		if ($i == $count)
			$note['last'] = true;
		$note['division_header'] = str_replace('%bankaccount',
			format_bankaccount(bankaccount($note['customerid'], $note['account'])), $note['division_header']);
		$document->Draw($note);
	}
} elseif (isset($_GET['fetchallnotes'])) {
	$layout['pagetitle'] = trans('Debit Notes');

	$ids = $DB->GetCol('SELECT d.id FROM documents d
		WHERE d.cdate >= ? AND d.cdate <= ? AND d.type = ?'
		. (!empty($_GET['customerid']) ? ' AND d.customerid = '.intval($_GET['customerid']) : '')
		. (!empty($_GET['numberplanid']) ? ' AND d.numberplanid = '.intval($_GET['numberplanid']) : '')
		. (!empty($_GET['groupid']) ? 
		' AND '.(!empty($_GET['groupexclude']) ? 'NOT' : '').'
			EXISTS (SELECT 1 FROM customerassignments a
				WHERE a.customergroupid = ' . intval($_GET['groupid']) . '
					AND a.customerid = d.customerid)' : '')
		. ' AND NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'
		. ' ORDER BY CEIL(d.cdate/86400), d.id',
		array($_GET['from'], $_GET['to'], DOC_DNOTE));

	if (!$ids) {
		$SESSION->close();
		die;
	}

	$count = count($ids);
	$i = 0;

	foreach ($ids as $idx => $noteid) {
		$note = $LMS->GetNoteContent($noteid);
		if ($count == 1)
			$docnumber = docnumber(array(
				'number' => $note['number'],
				'template' => $note['template'],
				'cdate' => $note['cdate'],
			));

		$note['dontpublish'] = $dontpublish;
		$note['division_header'] = str_replace('%bankaccount',
			format_bankaccount(bankaccount($note['customerid'], $note['account'])), $note['division_header']);
		$document->Draw($note);
	}
} elseif ($note = $LMS->GetNoteContent($_GET['id'])) {
	$ids = array($_GET['id']);

	$docnumber = $number = docnumber(array(
		'number' => $note['number'],
		'template' => $note['template'],
		'cdate' => $note['cdate'],
	));
	$layout['pagetitle'] = trans('Debit Note No. $a', $number);

	$note['dontpublish'] = $dontpublish;
	$note['last'] = TRUE;
	$note['division_header'] = str_replace('%bankaccount',
		format_bankaccount(bankaccount($note['customerid'], $note['account'])), $note['division_header']);
	$document->Draw($note);
} else
	$SESSION->redirect('?m=notelist');

if (!is_null($attachment_name) && isset($docnumber)) {
	$attachment_name = str_replace('%number', $docnumber, $attachment_name);
	$attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} else
	$attachment_name = 'invoices.' . ($note_type == 'pdf' ? 'pdf' : 'html');

$document->WriteToBrowser($attachment_name);

if (!$dontpublish && isset($ids) && !empty($ids))
	$DB->Execute('UPDATE documents SET published = 1 WHERE id IN (' . implode(',', $ids) . ')');

?>
