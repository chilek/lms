<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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
    $allowed_document_types = ConfigHelper::getConfig('userpanel.allowed_document_types');
    if (!empty($allowed_document_types)) {
        $allowed_document_types = Utils::filterIntegers(explode(',', $allowed_document_types));
    }

    $doc = $LMS->DB->GetRow(
        'SELECT
            d.id,
            d.number,
            d.cdate,
            d.type,
            d.customerid,
            d.closed,
            d.confirmdate,
            n.template
        FROM documents d
        LEFT JOIN numberplans n ON d.numberplanid = n.id
        LEFT JOIN divisions ds ON ds.id = d.divisionid
        WHERE d.id = ?'
            . (
                ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only')
                    ? ' AND (d.closed > 0 OR d.confirmdate >= ?NOW? OR d.confirmdate = -1)'
                    : ''
            ) . (ConfigHelper::checkConfig('userpanel.hide_archived_documents') ? ' AND d.archived = 0': '')
            . ($allowed_document_types ? ' AND d.type IN (' . implode(',', $allowed_document_types) . ')' : ''),
        array(
            intval($_GET['id']),
        )
    );

    if (empty($doc) || $doc['customerid'] != $SESSION->id) {
        die;
    }

    $show_unapproved_document_attachments = ConfigHelper::checkConfig('userpanel.show_unapproved_document_attachments');

    if (empty($doc['closed'])
        && !$show_unapproved_document_attachments
        && (empty($doc['confirmdate']) || $doc['confirmdate'] < time())) {
        die;
    }

    $docattachments = $LMS->DB->GetAllByKey(
        'SELECT *
        FROM documentattachments
        WHERE docid = ?
        ORDER BY type DESC, filename',
        'id',
        array(
            $_GET['id'],
        )
    );

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
    ));
    $filename = DOC_DIR. DIRECTORY_SEPARATOR .substr($doc['md5sum'], 0, 2). DIRECTORY_SEPARATOR .$doc['md5sum'];
    if (file_exists($filename)) {
        $cache_pdf = ConfigHelper::checkConfig('documents.cache', ConfigHelper::checkConfig('phpui.cache_documents'));

        $filename_pdf = DOC_DIR . DIRECTORY_SEPARATOR . substr($doc['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $doc['md5sum'].'.pdf';

        $attachment_filename = ConfigHelper::getConfig('documents.attachment_filename', '%filename');
        $html2pdf_command = ConfigHelper::getConfig('documents.html2pdf_command', '', true);
        $office2pdf_command = ConfigHelper::getConfig('documents.office2pdf_command', '', true);
        $document_type = strtolower(ConfigHelper::getConfig('documents.type', ConfigHelper::getConfig('phpui.document_type', '', true)));

        $htmls = $offices = $pdfs = $others = 0;

        $ctype = $doc['contenttype'];

        if (preg_match('/html$/i', $ctype)) {
            $htmls++;
        } elseif (preg_match('/pdf$/i', $ctype)) {
            $pdfs++;
        } elseif (preg_match('#^application/(rtf|.+(oasis|opendocument|openxml).+)$#i', $ctype)) {
            $offices++;
        } else {
            $others++;
        }

        if (file_exists($filename_pdf)) {
            if ($doc['type'] == DOC_CONTRACT) {
                $title = trans('Contract No. $a', $docnumber);
            } elseif ($doc['type'] == DOC_ANNEX) {
                $title = trans('Annex No. $a', $docnumber);
            } else {
                $title = $docnumber;
            }

            $title = preg_replace(
                '/[^[:alnum:]_\.\-]/iu',
                '_',
                $title
            );

            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $title . '.pdf"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . filesize($filename_pdf));
            header('Accept-Ranges: bytes');
            readfile($filename_pdf);
        } elseif (($htmls || $offices) && $document_type == 'pdf') {
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

            $margins = explode(',', ConfigHelper::getConfig('documents.margins', ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5')));

            $title = preg_replace(
                '/[^[:alnum:]_\.\-]/iu',
                '_',
                $title
            );

            if ($offices) {
                if (!empty($office2pdf_command)) {
                    header('Content-type: application/pdf');
                    header('Content-Disposition: inline; filename="' . $title . '.pdf"');
                    header('Content-Transfer-Encoding: binary');

                    $content = Utils::office2pdf(array(
                        'content' => file_get_contents($filename),
                        'subject' => $subject,
                        'title' => $title,
                        'doctype' => Utils::docTypeByMimeType($ctype),
                        'dest' => 'S',
                        'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                    ));

                    echo $content;
                }
            } else {
                Utils::html2pdf(array(
                    'content' => file_get_contents($filename),
                    'subject' => $subject,
                    'title' => $title . '.pdf',
                    'type' => $doc['type'],
                    'id' => $doc['id'],
                    'margins' => $margins,
                    'dest' => !empty($_GET['save']),
                    'copy' => $copy,
                    'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                ));
            }
        } else {
            header('Content-Type: '.$doc['contenttype']);

            if (!preg_match('/(^text|pdf)/i', $doc['contenttype']) || !empty($_GET['save'])) {
                header('Content-Disposition: attachment; filename='.$doc['filename']);
                header('Pragma: public');
            } else {
                header('Content-Disposition: inline; filename="' . $doc['filename'] . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($filename));
            }

            readfile($filename);
        }
    }
    die;
}
