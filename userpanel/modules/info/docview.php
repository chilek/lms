<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if (!empty($_GET['id'])) {
    $doc = $LMS->DB->GetRow('SELECT d.id, d.number, d.cdate, d.type, d.customerid, n.template
		FROM documents d
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		LEFT JOIN divisions ds ON (ds.id = d.divisionid)
		WHERE d.id = ?', array(intval($_GET['id'])));

    $docattachments = $LMS->DB->GetAllByKey('SELECT * FROM documentattachments WHERE docid = ?
		ORDER BY main DESC, filename', 'id', array($_GET['id']));
    $attachmentid = intval($_GET['attachmentid']);
    if ($attachmentid) {
        $docattach = $docattachments[$attachmentid];
    } else {
        $docattach = reset($docattachments);
    }
    $doc['md5sum'] = $docattach['md5sum'];
    $doc['filename'] = $docattach['filename'];
    $doc['contenttype'] = $docattach['contenttype'];

    if ($doc['customerid'] != $SESSION->id) {
        die;
    }

    $docnumber = docnumber(array(
        'number' => $doc['number'],
        'template' => $doc['template'],
        'cdate' => $doc['cdate'],
    ));
    $filename = DOC_DIR.'/'.substr($doc['md5sum'], 0, 2).'/'.$doc['md5sum'];
    if (file_exists($filename)) {
        if (preg_match('/html/i', $doc['contenttype']) && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
            if ($doc['type'] == DOC_CONTRACT) {
                $subject = trans('Contract');
                $title = trans('Contract No. $a', $docnumber);
                $copy = true;
            } elseif ($doc['type'] == DOC_ANNEX) {
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

            if (!preg_match('/(^text|pdf)/i', $doc['contenttype']) || !empty($_GET['save'])) {
                header('Content-Disposition: attachment; filename='.$doc['filename']);
                header('Pragma: public');
            } else {
                header('Content-Disposition: inline; filename="' . $doc['filename'] . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($filename) . ' bytes');
            }

            readfile($filename);
        }
    }
    die;
}
