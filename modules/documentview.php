<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (!empty($_POST['marks'])) {
    $document_type = strtolower(ConfigHelper::getConfig('documents.type', ConfigHelper::getConfig('phpui.document_type')));
    $margins = explode(',', ConfigHelper::getConfig('documents.margins', ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5')));

    $marks = array();
    foreach ($_POST['marks'] as $id => $mark) {
        $marks[] = intval($mark);
    }

    if ($list = $DB->GetCol(
        'SELECT d.id FROM documentcontents c
            JOIN documents d ON (d.id = c.docid)
            JOIN docrights r ON (r.doctype = d.type)
            WHERE c.docid IN ?
                AND r.userid = ?
                AND (r.rights & ?) > 0',
        array(
            $marks,
            Auth::GetCurrentUser(),
            DOCRIGHT_VIEW,
        )
    )) {
        $list = $DB->GetAll(
            'SELECT filename, contenttype, md5sum
            FROM documentattachments
            WHERE docid IN ?
            ORDER BY docid ASC, type DESC',
            array(
                $list,
            )
        );

        $htmls = $pdfs = $others = 0;
        foreach ($list as $doc) {
            $ctype = $doc['contenttype'];

            if (preg_match('/html$/i', $ctype)) {
                $htmls++;
            } elseif (preg_match('/pdf$/i', $ctype)) {
                $pdfs++;
            } else {
                $others++;
            }
        }

        if ($others && count($list) > 1 || $htmls && $pdfs && $document_type != 'pdf') {
            die('Currently you can only print many documents of type text/html or application/pdf!');
        }

        $pdf = $pdfs || $htmls && $document_type == 'pdf';

        if ($pdf || $others) {
            header('Content-Disposition: ' . ($pdf ? 'inline' : 'attachment') . '; filename=' . $list[0]['filename']);
            header('Pragma: public');
        }

        header('Content-Type: ' . ($pdf ? 'application/pdf' : $list[0]['contenttype']));

        if ($pdf && count($list) > 1) {
            $fpdi = new LMSFpdiBackend();
        }

        if ($htmls && !$pdfs && $document_type == 'pdf') {
            $htmlbuffer = '';
        }

        $i = 0;
        foreach ($list as $doc) {
            $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($doc['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $doc['md5sum'];
            if (file_exists($filename)) {
                if ($htmls && !$pdfs && $document_type == 'pdf') {
                    if ($i) {
                        $htmlbuffer .= "\n<page>\n";
                    }
                    $htmlbuffer .= file_get_contents($filename);
                    if ($i) {
                        $htmlbuffer .= "\n</page>\n";
                    }
                } else {
                    if ($htmls && !$pdfs && $i) {
                        echo '<div style="page-break-after: always;">&nbsp;</div>';
                    }

                    if ($pdf && count($list) > 1) {
                        $content = file_get_contents($filename);

                        if (preg_match('/html$/i', $doc['contenttype'])) {
                            $content = html2pdf(
                                $content,
                                trans('Document'),
                                null,
                                null,
                                null,
                                'P',
                                $margins,
                                'S'
                            );
                        }

                        $fpdi->AppendPage($content);
                    } else {
                        readfile($filename);
                    }
                }
            }
            $i++;
        }

        if ($htmls && !$pdfs && $document_type == 'pdf') {
            html2pdf(
                $htmlbuffer,
                trans('Document'),
                null,
                null,
                null,
                'P',
                $margins
            );
        } elseif ($pdf && count($list) > 1) {
            // Output the new PDF
            $fpdi->WriteToBrowser();
        }
        die;
    }
} elseif ($doc = $DB->GetRow('SELECT d.id, d.number, d.cdate, d.type, d.customerid, n.template
	FROM documents d
	LEFT JOIN numberplans n ON (d.numberplanid = n.id)
	JOIN docrights r ON (r.doctype = d.type)
	WHERE d.id = ? AND r.userid = ? AND (r.rights & ?) > 0', array(intval($_GET['id']), Auth::GetCurrentUser(), DOCRIGHT_VIEW))) {
    $docattachments = $DB->GetAllByKey('SELECT * FROM documentattachments WHERE docid = ?
		ORDER BY type DESC', 'id', array($_GET['id']));
    $attachmentid = isset($_GET['attachmentid']) ? intval($_GET['attachmentid']) : null;
    if ($attachmentid) {
        $docattach = $docattachments[$attachmentid];
    } else {
        $docattach = reset($docattachments);
    }
    $doc['md5sum'] = $docattach['md5sum'];
    $doc['filename'] = $docattach['filename'];
    $doc['contenttype'] = $docattach['contenttype'];

    $docnumber = docnumber(array(
        'number' => $doc['number'],
        'template' => $doc['template'],
        'cdate' => $doc['cdate'],
        'customerid' => $doc['customerid'],
    ));
    $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($doc['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $doc['md5sum'];
    if (file_exists($filename)) {
        $filename_pdf = DOC_DIR . DIRECTORY_SEPARATOR . substr($doc['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $doc['md5sum'].'.pdf';
        if (file_exists($filename_pdf)) {
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $docnumber . '.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($filename_pdf));
            header('Accept-Ranges: bytes');
            readfile($filename_pdf);
        } elseif (preg_match('/html/i', $doc['contenttype']) && strtolower(ConfigHelper::getConfig('documents.type', ConfigHelper::getConfig('phpui.document_type', '', true))) == 'pdf') {
            if ($doc['type'] == DOC_CONTRACT) {
                $subject = trans('Contract');
                $title = trans('Contract No. $a', $docnumber);
            } elseif ($doc['type'] == DOC_ANNEX) {
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
            $margins = explode(",", ConfigHelper::getConfig('documents.margins', ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5')));
            if (ConfigHelper::checkConfig('documents.cache', ConfigHelper::checkConfig('phpui.cache_documents'))) {
                html2pdf($htmlbuffer, $subject, $title, $doc['type'], $doc['id'], 'P', $margins, !empty($_GET['save']), false, $doc['md5sum']);
            } else {
                html2pdf($htmlbuffer, $subject, $title, $doc['type'], $doc['id'], 'P', $margins, !empty($_GET['save']));
            }
        } else {
            header('Content-Type: ' . $doc['contenttype']);

            $pdf = preg_match('/pdf/i', $doc['contenttype']);
            if (isset($_GET['save'])) {
                header('Content-Disposition: attachment; filename="'.$doc['filename'] . '"');
            } else {
                if (!preg_match('/^text/i', $doc['contenttype'])) {
                    if ($pdf) {
                        header('Content-Disposition: inline; filename="' . $doc['filename'] . '"');
                        header('Content-Transfer-Encoding: binary');
                        header('Content-Length: ' . filesize($filename) . ' bytes');
                    } else {
                        header('Content-Disposition: attachment; filename="' . $doc['filename'] . '"');
                    }
                    header('Pragma: public');
                }
            }

            readfile($filename);
        }
    }
    die;
} elseif (!$LMS->DocumentExists(!$_GET['id'])) {
    $SMARTY->assign('message', trans('Document does not exist!'));
}

$SMARTY->display('noaccess.html');
