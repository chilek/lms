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

global $LMS, $SESSION;

if(!empty($_GET['id'])) {
	$doc = $LMS->DB->GetRow('SELECT c.filename, c.md5sum, c.contenttype, d.id, d.number, d.cdate, d.type, d.customerid, n.template
		FROM documentcontents c
		JOIN documents d ON (d.id = c.docid)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN divisions ds ON (ds.id = d.divisionid)
		WHERE c.docid = ?', array(intval($_GET['id'])));

	if($doc['customerid'] != $SESSION->id)
	{
		die;
	}

	$docnumber = docnumber($doc['number'], $doc['template'], $doc['cdate']);
	$filename = DOC_DIR.'/'.substr($doc['md5sum'],0,2).'/'.$doc['md5sum'];
	if(file_exists($filename))
	{
		if (strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
			if($doc['type'] == DOC_CONTRACT) {
				$subject = trans('Contract');
				$title = trans('Contract No. $a', $docnumber);
				$copy = true;
			} elseif($doc['type'] == DOC_ANNEX) {
				$subject = trans('Annex');
				$title = trans('Annex No. $a', $docnumber);
				$copy = true;
			} else {
				$subject = trans('Document');
				$title = $docnumber;
				$copy = false;
			}

			ob_start();
			readfile($filename);
			$htmlbuffer = ob_get_contents();
			ob_end_clean();
			$margins = explode(",", ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5'));
			html2pdf($htmlbuffer, $subject, $title, $doc['type'], $doc['id'], 'P', $margins, ($_GET['save'] == 1) ? true : false, $copy);
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

?>
