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

if(!empty($_POST['marks']))
{
	$marks = array();
        foreach($_POST['marks'] as $id => $mark)
    		$marks[] = intval($mark);

        if ($list = $DB->GetAll('SELECT c.filename, c.md5sum, c.contenttype
		FROM documentcontents c
		JOIN documents d ON (d.id = c.docid)
		JOIN docrights r ON (r.doctype = d.type)
		WHERE c.docid IN ('.implode(',', $marks).')
			AND r.userid = ? AND (r.rights & 1) = 1', array($AUTH->id)))
	{
		$ctype = $list[0]['contenttype'];

		if (!preg_match('/^text/i', $ctype))
		{
			if (sizeof($list))
				    die('Currently you can only print many documents of type text/html!');

			header('Content-Disposition: attachment; filename='.$list[0]['filename']);
			header('Pragma: public');
		}
		header('Content-Type: '.$ctype);

		if (strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
			$htmlbuffer = NULL;
		}
		$i = 0;
		foreach ($list as $doc)
		{
			// we can display only documents with the same content type
			if($doc['contenttype'] != $ctype)
				continue;

			$filename = DOC_DIR.'/'.substr($doc['md5sum'],0,2).'/'.$doc['md5sum'];
			if(file_exists($filename))
			{
				if ($ctype != 'pdf' && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
					if($i > 0)
						$htmlbuffer .= "\n<page>\n";
					ob_start();
					readfile($filename);
					$htmlbuffer .= ob_get_contents();
					ob_end_clean();
					if($i > 0)
						$htmlbuffer .= "\n</page>\n";
				} else {
					if ($i && preg_match('/html/i', $doc['contenttype']))
						echo '<div style="page-break-after: always;">&nbsp;</div>';

					readfile($filename);
				}
			}
			$i++;
		}
		if ($ctype != 'pdf' && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
			html2pdf($htmlbuffer, trans('Document'), NULL, NULL, NULL, 'P', array(10, 5, 15, 5));
		}
		die;
	}
} elseif($doc = $DB->GetRow('SELECT c.filename, c.md5sum, c.contenttype, d.id, d.number, d.cdate, d.type, n.template
	FROM documentcontents c
	JOIN documents d ON (d.id = c.docid)
	LEFT JOIN numberplans n ON (d.numberplanid = n.id)
	JOIN docrights r ON (r.doctype = d.type)
	WHERE c.docid = ? AND r.userid = ? AND (r.rights & 1) = 1', array($_GET['id'], $AUTH->id)))
{
	$docnumber = docnumber($doc['number'], $doc['template'], $doc['cdate']);
	$filename = DOC_DIR.'/'.substr($doc['md5sum'],0,2).'/'.$doc['md5sum'];
	if(file_exists($filename))
	{
		if ($doc['contenttype'] != 'pdf' && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
			if($doc['type'] == DOC_CONTRACT) {
				$subject = trans('Contract');
				$title = trans('Contract No. $a', $docnumber);
			} elseif($doc['type'] == DOC_ANNEX) {
				$subject = trans('Annex');
				$title = trans('Annex No. $a', $docnumber);
			} else {
				$subject = trans('Document');
				$title = $docnumber;
			}

			ob_start();
			readfile($filename);
			$htmlbuffer = ob_get_contents();
			ob_end_clean();
			html2pdf($htmlbuffer, $subject, $title, $doc['type'], $doc['id'], 'P', array(10, 5, 15, 5), ($_GET['save'] == 1) ? true : false);
		} else {
			header('Content-Type: '.$doc['contenttype']);

			if(!preg_match('/^text/i', $doc['contenttype']) || !empty($_GET['save']))
			{
				header('Content-Disposition: attachment; filename='.$doc['filename']);
				header('Pragma: public');
			}

			readfile($filename);
		}
	}
	die;
}

$SMARTY->display('noaccess.html');

?>
