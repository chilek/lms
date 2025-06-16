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
        JOIN customerview c ON c.id = d.customerid
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
        if (isset($_GET['html2pdf']) && empty($_GET['html2pdf']) || isset($_GET['office2pdf']) && empty($_GET['office2pdf'])) {
            $document_type = '';
        }
        $margins = explode(',', ConfigHelper::getConfig('documents.margins', ConfigHelper::getConfig('phpui.document_margins', '10,5,15,5')));
        $cache_pdf = ConfigHelper::checkConfig('documents.cache', ConfigHelper::checkConfig('phpui.cache_documents'));

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
            'SELECT dc.docid, dc.filename, dc.contenttype, dc.md5sum, dc.type
            FROM documentattachments dc
            JOIN documents d ON d.id = dc.docid
            WHERE dc.docid IN ?'
                . ($attachments || !empty($attachmentid) || isset($_GET['save']) ? '' : ' AND dc.type = 1')
                . (empty($attachmentid) ? '' : ' AND dc.id = ' . $attachmentid)
            . ' ORDER BY dc.docid ASC, dc.type DESC',
            array(
                Utils::array_column($docs, 'id'),
            )
        );
        if (empty($list)) {
            access_denied('No document attachments meet document view filter settings!');
        }

        $attachment_filename = ConfigHelper::getConfig('documents.attachment_filename', '%filename');
        $html2pdf_command = ConfigHelper::getConfig('documents.html2pdf_command', '', true);
        $office2dpf_command = ConfigHelper::getConfig('documents.office2pdf_command', '', true);

        if (isset($_GET['save'])) {
            $docid = $list[0]['docid'];

            $pdf_on_output = $document_type == 'pdf';

            foreach ($list as $idx => $file) {
                $contenttype = $file['contenttype'];

                $html_on_input = preg_match('/html$/i', $contenttype);
                $html2pdf = $html_on_input && $pdf_on_output;

                $office_on_input = preg_match('#^application/(rtf|.+(oasis|opendocument|openxml).+)$#i', $contenttype);
                $office2pdf = $office_on_input && $pdf_on_output;

                $filename = $file['filename'];
                $document_filename = DOC_DIR . DIRECTORY_SEPARATOR
                    . substr($file['md5sum'], 0, 2)
                    . DIRECTORY_SEPARATOR . $file['md5sum'];

                $cached_pdf = false;
                if ($html_on_input && $pdf_on_output && file_exists($document_filename . '.pdf')) {
                    $document_filename .= '.pdf';
                    $cached_pdf = true;
                } elseif ($office_on_input && $pdf_on_output && file_exists($document_filename . '.pdf')) {
                    $document_filename .= '.pdf';
                    $cached_pdf = true;
                } elseif (!file_exists($document_filename)) {
                    $document_filename = null;
                }
                if (!isset($document_filename)) {
                    continue;
                }

                $i = strpos($filename, '.');

                if ($i !== false) {
                    $extension = mb_substr($filename, $i + 1);
                    if ($pdf_on_output && preg_match('/^(htm|(odt|docx|rtf)$)/i', $extension)) {
                        $extension = 'pdf';
                    }
                    $filename = mb_substr($filename, 0, $i);
                } elseif (preg_match('#/\.(?<extension>[[:alnum:]]+)$#i', $file['contenttype'], $m)) {
                    $extension = $m['extension'];
                } else {
                    $extension = '';
                }

                if (empty($idx) && count($list) > 1) {
                    $zip_filename = ConfigHelper::getConfig('documents.zip_filename', 'document-%document.zip');

                    $zip_filename = preg_replace(
                        '/[^[:alnum:]_\.\-]/iu',
                        '_',
                        str_replace(
                            array(
                                '%filename',
                                '%type',
                                '%document',
                                '%docid',
                            ),
                            array(
                                $filename . '.zip',
                                $DOCTYPES[$docs[$docid]['type']],
                                $docs[$docid]['fullnumber'],
                                $docid,
                            ),
                            $zip_filename
                        )
                    );

                    if (!class_exists('ZipArchive')) {
                        die('Error: ZipArchive class not found!');
                    }

                    $zip_temp_filename = tempnam(sys_get_temp_dir(), 'lms-documentview');

                    $zip = new ZipArchive;
                    $zip->open($zip_temp_filename, ZipArchive::CREATE);
                    if (empty($zip)) {
                        die('Error: cannot create temporary ZipArchive: \'' . $zip_temp_filename . '\'!');
                    }
                }

                if ($file['type'] == 1 || count($list) == 1) {
                    $filename = preg_replace(
                        '/[^[:alnum:]_\.\-]/iu',
                        '_',
                        str_replace(
                            array(
                                '%filename',
                                '%type',
                                '%document',
                                '%docid',
                            ),
                            array(
                                $filename . (strlen($extension) ? '.' . $extension : ''),
                                $DOCTYPES[$docs[$docid]['type']],
                                $docs[$docid]['fullnumber'],
                                $docid,
                            ),
                            $attachment_filename
                        )
                    );
                    $i = strpos($filename, '.');
                    if ($i === false && strlen($extension)) {
                        $filename .= '.' . $extension;
                    }
                } else {
                    $filename = $file['filename'];
                }

                $output_filename = $filename;

                if ($cached_pdf || !$html2pdf && !$office2pdf) {
                    if (count($list) > 1) {
                        $zip->addFile($document_filename, $output_filename);
                    } else {
                        $content = file_get_contents($document_filename);
                    }
                } else {
                    if ($office2pdf) {
                        $content = Utils::office2pdf(array(
                            'content' => file_get_contents($document_filename),
                            'subject' => trans('Document'),
                            'doctype' => Utils::docTypeByMimeType($contenttype),
                            'dest' => 'S',
                            'md5sum' => $cache_pdf ? $file['md5sum'] : null,
                        ));
                    } else {
                        $content = Utils::html2pdf(array(
                            'content' => file_get_contents($document_filename),
                            'subject' => trans('Document'),
                            'margins' => $margins,
                            'dest' => 'S',
                            'md5sum' => $cache_pdf ? $file['md5sum'] : null,
                        ));
                    }
                    if (count($list) > 1) {
                        $zip->addFromString($output_filename, $content);
                    }
                }
            }

            if (count($list) > 1) {
                $zip->close();

                header('Content-Disposition: attachment; filename=' . $zip_filename);
                header('Pragma: public');
                header('Content-Type: application/x-zip');

                readfile($zip_temp_filename);
                unlink($zip_temp_filename);
            } else {
                header('Content-Disposition: attachment; filename=' . $output_filename);
                header('Pragma: public');
                header('Content-Type: ' . $contenttype);

                die($content);
            }
        } else {
            $htmls = $offices = $pdfs = $others = 0;
            foreach ($list as $doc) {
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
            }

            if ($others && count($list) > 1 || $htmls && ($offices || $pdfs) && $document_type != 'pdf') {
                die(
                    'Currently you can only print many documents of type text/html, application/pdf, application/rtf, '
                        . 'application/vnd.oasis.opendocument.text and application/vnd.openxmlformats-officedocument.wordprocessingml.document!'
                );
            }

            $pdf = $pdfs || ($htmls || $offices) && $document_type == 'pdf';

            if ($pdf || $others || count($docs) == 1) {
                $docid = $list[0]['docid'];
                $filename = $list[0]['filename'];
                $i = strpos($filename, '.');

                if ($i !== false) {
                    $extension = mb_substr($filename, $i + 1);
                    if (preg_match('/^(htm|(odt|docx|rtf)$)/i', $extension) && $pdf) {
                        $extension = 'pdf';
                    }
                    $filename = mb_substr($filename, 0, $i);
                } elseif (preg_match('#/\.(?<extension>[[:alnum:]]+)$#i', $list[0]['contenttype'], $m)) {
                    $extension = $m['extension'];
                } else {
                    $extension = '';
                }

                $filename = preg_replace(
                    '/[^[:alnum:]_\.\-]/iu',
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

            $i = 0;
            foreach ($list as $doc) {
                $filename = DOC_DIR . DIRECTORY_SEPARATOR
                    . substr($doc['md5sum'], 0, 2)
                    . DIRECTORY_SEPARATOR . $doc['md5sum'];

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

                if (!$cached_pdf && $htmls && !$pdfs && !$offices && $document_type == 'pdf') {
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

                        if (!$cached_pdf) {
                            if (preg_match('/html$/i', $doc['contenttype'])) {
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

                                $content = Utils::html2pdf(array(
                                    'content' => $content,
                                    'subject' => trans('Document'),
                                    'margins' => $margins,
                                    'dest' => 'S',
                                    'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                                ));
                            } elseif (preg_match('#^application/(rtf|.+(oasis|opendocument|openxml).+)$#i', $doc['contenttype'])) {
                                $content = Utils::office2pdf(array(
                                    'content' => $content,
                                    'subject' => trans('Document'),
                                    'doctype' => Utils::docTypeByMimeType($doc['contenttype']),
                                    'dest' => 'S',
                                    'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                                ));
                            }
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
                        } elseif ($pdf) {
                            if ($cached_pdf) {
                                echo file_get_contents($filename);
                            } else {
                                $content = file_get_contents($filename);

                                if (preg_match('/html$/i', $doc['contenttype'])) {
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

                                    $content = Utils::html2pdf(array(
                                        'content' => $content,
                                        'subject' => trans('Document'),
                                        'margins' => $margins,
                                        'dest' => 'S',
                                        'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                                    ));
                                } elseif (preg_match('#^application/(rtf|.+(oasis|opendocument|openxml).+)$#i', $doc['contenttype'])) {
                                    $content = Utils::office2pdf(array(
                                        'content' => $content,
                                        'subject' => trans('Document'),
                                        'doctype' => Utils::docTypeByMimeType($doc['contenttype']),
                                        'dest' => 'S',
                                        'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                                    ));
                                }

                                echo $content;
                            }
                        }
                    }
                }

                $i++;
            }

            if ($htmls && !$offices && !$pdfs && strlen($htmlbuffer)) {
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
                    Utils::html2pdf(array(
                        'content' => $htmlbuffer,
                        'subject' => trans('Document'),
                        'margins' => $margins,
                        'md5sum' => $cache_pdf ? $doc['md5sum'] : null,
                    ));
                } else {
                    echo $htmlbuffer;
                }
            } elseif ($pdf && count($list) > 1) {
                // Output the new PDF
                $fpdi->WriteToBrowser();
            }
        }

        die;
    }
}

access_denied();
