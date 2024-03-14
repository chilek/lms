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

$docids = array();

if (!empty($_POST['marks'])) {
    foreach ($_POST['marks'] as $id => $mark) {
        if (is_numeric($mark)) {
            $docids[] = intval($mark);
        }
    }
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    if (!$LMS->DocumentExists($_GET['id'])) {
        access_denied('Document does not exist!');
    }
    $docids[] = intval($_GET['id']);
}

if (!empty($docids)) {
    if ($docs = $DB->GetAllByKey(
        'SELECT
            d.id,
            d.type,
            d.fullnumber
        FROM documents d
        JOIN docrights r ON r.doctype = d.type
        WHERE d.id IN ?
            AND r.userid = ?
            AND (r.rights & ?) > 0',
        'id',
        array(
            $docids,
            Auth::GetCurrentUser(),
            DOCRIGHT_VIEW,
        )
    )) {
        $document_type = strtolower(ConfigHelper::getConfig('documents.type', ConfigHelper::getConfig('phpui.document_type', '', true)));
        $margins = explode(',', ConfigHelper::getConfig('documents.margins', ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5')));

        $attachments = isset($_GET['attachments']) || isset($_POST['attachments']);
        $docTypes = array();
        foreach (Localisation::arraySort($DOCTYPES) as $key => $doctype) {
            if ($key < 0) {
                $docTypes[] = $key;
            }
        }

        $related = ($_GET['related'] ?? ($_POST['related'] ?? null));
        $relatedDocuments = ($_GET['related_documents'] ?? ($_POST['related_documents'] ?? $docTypes));
        $attachmentid = isset($_GET['attachmentid']) && is_numeric($_GET['attachmentid']) ? intval($_GET['attachmentid']) : null;

        if (!empty($related) && !empty($relatedDocuments)) {
            $relatedDocuments = array_combine($relatedDocuments, $relatedDocuments);
            foreach ($docids as $doc) {
                $referencedDocument = $LMS->getReferencedDocument($doc);
                if (!empty($referencedDocument) && isset($relatedDocuments[$referencedDocument['type']])) {
                    $docs[$referencedDocument['id']] = array(
                        'id' => $referencedDocument['id'],
                        'type' => $referencedDocument['type'],
                        'fullnumber' => $referencedDocument['fullnumber'],
                    );
                }

                $referencingDocuments = $LMS->getReferencingDocuments($doc);
                if (!empty($referencingDocuments)) {
                    foreach ($referencingDocuments as $referencingDocument) {
                        if (isset($relatedDocuments[$referencingDocument['type']])) {
                            $docs[$referencingDocument['id']] = array(
                                'id' => $referencingDocument['id'],
                                'type' => $referencingDocument['type'],
                                'fullnumber' => $referencingDocument['fullnumber'],
                            );
                        }
                    }
                }
            }
        }
        $list = $DB->GetAll(
            'SELECT dc.docid, dc.filename, dc.contenttype, dc.md5sum
            FROM documentattachments dc
            JOIN documents d ON d.id = dc.docid
            WHERE dc.docid IN ?'
                . ($attachments || !empty($attachmentid) ? '' : ' AND dc.type = 1')
                . (empty($attachmentid) ? '' : ' AND dc.id = ' . $attachmentid)
            . ' ORDER BY dc.docid ASC, dc.type DESC',
            array(
                Utils::array_column($docs, 'id'),
            )
        );
        if (empty($list)) {
            access_denied('No document attachments meet document view filter settings!');
        }

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

        if ($pdf || $others || count($docs) == 1) {
            $attachment_filename = ConfigHelper::getConfig('documents.attachment_filename', '%filename');

            $docid = $list[0]['docid'];
            $filename = $list[0]['filename'];
            $i = strpos($filename, '.');

            if ($i !== false) {
                $extension = mb_substr($filename, $i + 1);
                if (preg_match('/^htm/', $extension) && $pdf) {
                    $extension = 'pdf';
                }
                $filename = mb_substr($filename, 0, $i);
            } elseif (preg_match('#/\.(?<extension>[[:alnum:]]+)$#i', $list[0]['contenttype'], $m)) {
                $extension = $m['extension'];
            } else {
                $extension = '';
            }

            $filename = preg_replace(
                '/[^[:alnum:]_\.]/iu',
                '_',
                str_replace(
                    array(
                        '%filename',
                        '%type',
                        '%document',
                        '%docid',
                    ),
                    array(
                        $filename,
                        $DOCTYPES[$docs[$docid]['type']],
                        $docs[$docid]['fullnumber'],
                        $docid,
                    ),
                    $attachment_filename
                )
            );

            if ($pdf || $others) {
                header('Content-Disposition: ' . ($pdf && !isset($_GET['save']) ? 'inline' : 'attachment') . '; filename=' . $filename . '.' . $extension);
            } else {
                header('Content-Disposition: ' . (isset($_GET['save']) ? 'attachment' : 'inline') . '; filename=' . $filename . '.' . $extension);
            }
            header('Pragma: public');
        }

        header('Content-Type: ' . ($pdf ? 'application/pdf' : $list[0]['contenttype']));

        if ($pdf && count($list) > 1) {
            $pdf_merge_backend = ConfigHelper::getConfig('documents.pdf_merge_backend', 'fpdi');
            if ($pdf_merge_backend == 'pdfunite') {
                $fpdi = new LMSPdfUniteBackend();
            } else {
                $fpdi = new LMSFpdiBackend();
            }
        }

        $htmlbuffer = '';

        $html2pdf_command = ConfigHelper::getConfig('documents.html2pdf_command', '', true);

        $i = 0;
        foreach ($list as $doc) {
            $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($doc['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $doc['md5sum'];

            $cached_pdf = false;
            if ($pdf && file_exists($filename . '.pdf')) {
                $filename .= '.pdf';
                $cached_pdf = true;
            } elseif (!file_exists($filename)) {
                $filename = null;
            }
            if (!isset($filename)) {
                continue;
            }

            if (!$cached_pdf && $htmls && !$pdfs && $document_type == 'pdf') {
                if (empty($html2pdf_command)) {
                    if ($i) {
                        $htmlbuffer .= "\n<page>\n";
                    }
                    $htmlbuffer .= file_get_contents($filename);
                    if ($i) {
                        $htmlbuffer .= "\n</page>\n";
                    }
                } else {
                    if ($htmls == 1) {
                        $htmlbuffer .= file_get_contents($filename);
                    } else {
                        $htmlbuffer .= "\n<div class=\"document\">\n"
                            . file_get_contents($filename)
                            . "\n</div>\n";
                    }
                }
            } else {
                if ($pdf && count($list) > 1) {
                    $content = file_get_contents($filename);

                    if (!$cached_pdf && preg_match('/html$/i', $doc['contenttype'])) {
                        $content = "
                            <html>
                                <head>
                                    <meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
                                    <style>

                                        @page {
                                            size: A4;
                                            margin: 1cm;
                                        }

                                        .document {
                                             break-after: page;
                                        }

                                    </style>
                                </head>
                                <body>
                                    <div class=\"document\">"
                                    . $content
                                    . "</div>
                                    <script>

                                        let documents = document.querySelectorAll('.document');
                                        if (documents.length) {
                                            documents.forEach(function(document) {
                                                let documentShadow = document.attachShadow({
                                                    mode: \"closed\"
                                                });
                                                let innerHTML = document.innerHTML;
                                                document.innerHTML = '';
                                                documentShadow.innerHTML = innerHTML;
                                            });
                                        }

                                    </script>
                                </body>
                            </html>";

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

                    try {
                        $fpdi->AppendPage($content);
                    } catch (Exception $e) {
                        if ($e->getCode() == 267) {
                            // This PDF document probably uses a compression technique which is not supported by the free parser shipped with FPDI. (See https://www.setasign.com/fpdi-pdf-parser for more details)
                        }
                    }
                } else {
                    if (!$pdf && $htmls) {
                        if ($htmls == 1) {
                            $htmlbuffer .= file_get_contents($filename);
                        } else {
                            $htmlbuffer .= "\n<div class=\"document\">\n"
                                . file_get_contents($filename)
                                . "\n</div>\n";
                        }
                    } else {
                        readfile($filename);
                    }
                }
            }

            $i++;
        }

        if ($htmls && !$pdfs && strlen($htmlbuffer)) {
            if ((!empty($html2pdf_command) || $document_type != 'pdf') && $htmls > 1) {
                $htmlbuffer = "
                    <html>
                        <head>
                            <meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">
                            <style>

                                @page {
                                    size: A4;
                                    margin: 1cm;
                                }

                                .document {
                                     break-after: page;
                                }

                            </style>
                        </head>
                        <body>"
                        . $htmlbuffer
                        . "
                            <script>

                                let documents = document.querySelectorAll('.document');
                                if (documents.length) {
                                    documents.forEach(function(document) {
                                        let documentShadow = document.attachShadow({
                                            mode: \"closed\"
                                        });
                                        let innerHTML = document.innerHTML;
                                        document.innerHTML = '';
                                        documentShadow.innerHTML = innerHTML;
                                    });
                                }

                            </script>
                        </body>
                    </html>";
            }

            if ($document_type == 'pdf') {
                html2pdf(
                    $htmlbuffer,
                    trans('Document'),
                    null,
                    null,
                    null,
                    'P',
                    $margins
                );
            } else {
                echo $htmlbuffer;
            }
        } elseif ($pdf && count($list) > 1) {
            // Output the new PDF
            $fpdi->WriteToBrowser();
        }

        die;
    }
}

access_denied();
