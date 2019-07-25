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

/*use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;*/

if (!empty($_POST['marks'])) {
    $marks = array();
    foreach ($_POST['marks'] as $id => $mark) {
        $marks[] = intval($mark);
    }

    if ($list = $DB->GetCol('SELECT d.id FROM documentcontents c
		JOIN documents d ON (d.id = c.docid)
		JOIN docrights r ON (r.doctype = d.type)
		WHERE c.docid IN ('.implode(',', $marks).')
			AND r.userid = ? AND (r.rights & 1) = 1', array(Auth::GetCurrentUser()))) {
        $list = $DB->GetAll('SELECT filename, contenttype, md5sum FROM documentattachments
			WHERE docid IN (' . implode(',', $list) . ')');

        $html = $pdf = $other = false;
        foreach ($list as $doc) {
            $ctype = $doc['contenttype'];
            if (!$html && !$pdf) {
                if (preg_match('/html$/i', $ctype)) {
                    $html = true;
                } elseif (preg_match('/pdf$/i', $ctype)) {
                    $pdf = true;
                } else {
                    $other = true;
                    break;
                }
            } else if (($html && !preg_match('/html$/i', $ctype))
                    || ($pdf && !preg_match('/pdf$/i', $ctype))) {
                    $other = true;
                    break;
            }
        }

        if ($other && count($list) > 1) {
            die('Currently you can only print many documents of type text/html or application/pdf!');
        }

        $ctype = $list[0]['contenttype'];

        if (!$html) {
            header('Content-Disposition: ' . ($pdf ? 'inline' : 'attachment') . '; filename='.$list[0]['filename']);
            header('Pragma: public');
            if ($pdf) {
                $pdf = new FPDI();
//              $pdf = new Fpdi();
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }
        }
        header('Content-Type: '.$ctype);

        if ($html && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
            $htmlbuffer = null;
        }
        $i = 0;
        foreach ($list as $doc) {
            // we can display only documents with the same content type
//          if ($doc['contenttype'] != $ctype)
//              continue;

            $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($doc['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $doc['md5sum'];
            if (file_exists($filename)) {
                if ($html && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
                    if ($i > 0) {
                        $htmlbuffer .= "\n<page>\n";
                    }
                    ob_start();
                    readfile($filename);
                    $htmlbuffer .= ob_get_contents();
                    ob_end_clean();
                    if ($i > 0) {
                        $htmlbuffer .= "\n</page>\n";
                    }
                } else {
                    if ($i && preg_match('/html/i', $doc['contenttype'])) {
                        echo '<div style="page-break-after: always;">&nbsp;</div>';
                    }

                    if ($pdf) {
                        $pageCount = $pdf->setSourceFile($filename);
                        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                            // import a page
                            $templateId = $pdf->importPage($pageNo);
                            // get the size of the imported page
                            $size = $pdf->getTemplateSize($templateId);

                            // create a page (landscape or portrait depending on the imported page size)
                            if ($size['w'] > $size['h']) {
                                $pdf->AddPage('L', array($size['w'], $size['h']));
                            } else {
                                $pdf->AddPage('P', array($size['w'], $size['h']));
                            }
                            //$pdf->AddPage($size['orientation'], $size);

                            // use the imported page
                            $pdf->useTemplate($templateId);
                        }
                    } else {
                        readfile($filename);
                    }
                }
            }
            $i++;
        }
        if ($html && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
            $margins = explode(",", ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5'));
            html2pdf($htmlbuffer, trans('Document'), null, null, null, 'P', $margins);
        } elseif ($pdf) {
            // Output the new PDF
            $pdf->Output();
        }
        die;
    }
} elseif ($doc = $DB->GetRow('SELECT d.id, d.number, d.cdate, d.type, d.customerid, n.template
	FROM documents d
	LEFT JOIN numberplans n ON (d.numberplanid = n.id)
	JOIN docrights r ON (r.doctype = d.type)
	WHERE d.id = ? AND r.userid = ? AND (r.rights & 1) = 1', array($_GET['id'], Auth::GetCurrentUser()))) {
    $docattachments = $DB->GetAllByKey('SELECT * FROM documentattachments WHERE docid = ?
		ORDER BY main DESC', 'id', array($_GET['id']));
    $attachmentid = intval($_GET['attachmentid']);
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
        } elseif (preg_match('/html/i', $doc['contenttype']) && strtolower(ConfigHelper::getConfig('phpui.document_type')) == 'pdf') {
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
            $margins = explode(",", ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5'));
            if (ConfigHelper::getConfig('phpui.cache_documents')) {
                html2pdf($htmlbuffer, $subject, $title, $doc['type'], $doc['id'], 'P', $margins, ($_GET['save'] == 1) ? true : false, false, $doc['md5sum']);
            } else {
                html2pdf($htmlbuffer, $subject, $title, $doc['type'], $doc['id'], 'P', $margins, ($_GET['save'] == 1) ? true : false);
            }
        } else {
            if (preg_match('#^application/.*pdf#', $doc['contenttype'])) {
                $doc['contenttype'] = 'application/pdf';
            }
            header('Content-Type: ' . $doc['contenttype']);

            if (!preg_match('/^text/i', $doc['contenttype'])) {
                $pdf = preg_match('/pdf/i', $doc['contenttype']);
                if (empty($_GET['save'])) {
                    if ($pdf) {
                        header('Content-Disposition: inline; filename="'.$doc['filename'] . '"');
                        header('Content-Transfer-Encoding: binary');
                        header('Content-Length: ' . filesize($filename) . ' bytes');
                    } else {
                        header('Content-Disposition: attachment; filename="'.$doc['filename'] . '"');
                    }
                } else {
                    header('Content-Disposition: attachment; filename="'.$doc['filename'] . '"');
                }
                header('Pragma: public');
            }

            readfile($filename);
        }
    }
    die;
}

$SMARTY->display('noaccess.html');
