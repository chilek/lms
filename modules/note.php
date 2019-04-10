<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

function try_generate_archive_notes($ids) {
	global $LMS, $note_type, $document, $classname, $dontpublish;

	$SMARTY = LMSSmarty::getInstance();

	$archive_stats = $LMS->GetFinancialDocumentArchiveStats($ids);

	if (($note_type == 'pdf' && ($archive_stats['html'] > 0 || $archive_stats['rtype'] == 'html'))
		|| ($note_type == 'html' && ($archive_stats['pdf'] > 0 || $archive_stats['rtype'] == 'pdf')))
		die('Currently you can only print many documents of type text/html or application/pdf!');

	if (!empty($archive_stats) && $archive_stats['archive'] > 0) {
		$attachment_name = 'invoices.' . ($note_type == 'pdf' ? 'pdf' : 'html');
		header('Content-Type: ' . ($note_type == 'pdf' ? 'application/pdf' : 'text/html'));
		header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
		header('Pragma: public');

		if ($note_type == 'pdf') {
			$pdf = new Fpdi();
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}

		foreach ($ids as $idx => $noteid) {
			if ($LMS->isArchiveDocument($noteid)) {
				$file = $LMS->GetArchiveDocument($noteid);
			} else {
				if (!$document)
					if ($note_type == 'pdf')
						$document = new $classname(trans('Notes'));
					else
						$document = new LMSHtmlDebitNote($SMARTY);

				$note = $LMS->GetNoteContent($noteid);
				$note['dontpublish'] = $dontpublish;
				$note['division_header'] = str_replace('%bankaccount',
					format_bankaccount(bankaccount($note['customerid'], $note['account'])), $note['division_header']);
				$document->Draw($note);

				$file['data'] = $document->WriteToString();

				unset($document);
				$document = null;
			}

			if ($note_type == 'pdf') {
				$pageCount = $pdf->setSourceFile(StreamReader::createByString($file['data']));
				for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
					// import a page
					$templateId = $pdf->importPage($pageNo);
					// get the size of the imported page
					$size = $pdf->getTemplateSize($templateId);

					$pdf->AddPage($size['orientation'], $size);

					// use the imported page
					$pdf->useTemplate($templateId);
				}
			} else {
				echo $file['data'];
				if ($idx < count($ids) - 1)
					echo '<div style="page-break-after: always;">&nbsp;</div>';
			}
		}

		if ($note_type == 'pdf')
			$pdf->Output();

		if (!$dontpublish && !empty($ids))
			$LMS->PublishDocuments($ids);

		die;
	}
}

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

	try_generate_archive_notes($ids);

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

	try_generate_archive_notes($ids);

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

	if ($note['archived']) {
		$note = $LMS->GetArchiveDocument($_GET['id']);
		if ($note) {
			header('Content-Type: ' . $invoice['content-type']);
			header('Content-Disposition: inline; filename=' . $note['filename']);
			echo $note['data'];
		}
		$SESSION->close();
		die;
	}

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
	$LMS->PublishDocuments($ids);

?>
