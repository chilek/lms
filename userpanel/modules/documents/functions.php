<?php

/*
 *  LMS version 1.11-git
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

function module_main()
{
    global $SESSION;

    $LMS = LMS::getInstance();
    $SMARTY = LMSSmarty::getInstance();

    if (isset($_POST['documentid']) && ($documentid = intval($_POST['documentid'])) > 0) {
        if ($LMS->DB->GetOne(
            'SELECT 1 FROM documents WHERE id = ? AND customerid = ? AND closed = 0 AND confirmdate > 0 AND confirmdate > ?NOW?',
            array($documentid, $SESSION->id)
        )) {
            $files = array();
            $error = null;

            if (isset($_FILES['files'])) {
                foreach ($_FILES['files']['name'] as $fileidx => $filename) {
                    if (!empty($filename)) {
                        if (is_uploaded_file($_FILES['files']['tmp_name'][$fileidx]) && $_FILES['files']['size'][$fileidx]) {
                            $files[] = array(
                                'tmpname' => null,
                                'filename' => $filename,
                                'name' => $_FILES['files']['tmp_name'][$fileidx],
                                'type' => $_FILES['files']['type'][$fileidx],
                                'md5sum' => md5($_FILES['files']['tmp_name'][$fileidx]),
                                'attachmenttype' => -1,
                            );
                        } else { // upload errors
                            if (isset($error['files'])) {
                                $error['files'] .= "\n";
                            } else {
                                $error['files'] = '';
                            }
                            switch ($_FILES['files']['error'][$fileidx]) {
                                case 1:
                                case 2:
                                    $error['files'] .= trans('File is too large: $a', $filename);
                                    break;
                                case 3:
                                    $error['files'] .= trans('File upload has finished prematurely: $a', $filename);
                                    break;
                                case 4:
                                    $error['files'] .= trans('Path to file was not specified: $a', $filename);
                                    break;
                                default:
                                    $error['files'] .= trans('Problem during file upload: $a', $filename);
                                    break;
                            }
                        }
                    }
                }
                if (!$error) {
                    $error = $LMS->AddDocumentFileAttachments($files);
                    if (!$error) {
                        $LMS->AddDocumentAttachments($documentid, $files);
                        $LMS->AddDocumentScans($documentid, $files);
                    } else {
                        $SMARTY->assign('error', $error);
                    }
                } else {
                    $SMARTY->assign('error', $error);
                }
            }
        }
    }

    $documents = $LMS->DB->GetAll('SELECT d.id, d.number, d.type, c.title, c.fromdate, c.todate, 
		    c.description, n.template, d.closed, d.cdate, d.confirmdate
		FROM documentcontents c
		JOIN documents d ON (c.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		WHERE d.customerid = ?'
            . (ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only')
                ? ' AND (d.closed > 0 OR d.confirmdate >= ?NOW? OR d.confirmdate = -1)': '')
            . (ConfigHelper::checkConfig('userpanel.hide_archived_documents') ? ' AND d.archived = 0': '')
            . ' ORDER BY cdate', array($SESSION->id));

    if (!empty($documents)) {
        foreach ($documents as &$doc) {
            $doc['attachments'] = $LMS->DB->GetAllBykey('SELECT * FROM documentattachments WHERE docid = ?
				ORDER BY type DESC, filename', 'id', array($doc['id']));
        }
    }

    $unit_multipliers = array(
        'K' => 1024,
        'M' => 1024 * 1024,
        'G' => 1024 * 1024 * 1024,
        'T' => 1024 * 1024 * 1024 * 1024,
    );
    foreach (array('post_max_size', 'upload_max_filesize') as $var) {
        preg_match('/^(?<number>[0-9]+)(?<unit>[kKmMgGtT]?)$/', ini_get($var), $m);
        $unit_multiplier = isset($m['unit']) ? $unit_multipliers[strtoupper($m['unit'])] : 1;
        if ($var == 'post_max_size') {
            $unit_multiplier *= 1/1.33;
        }
        if (empty($m['number'])) {
            $val['bytes'] = 0;
            $val['text'] = trans('(unlimited)');
        } else {
            $val['bytes'] = round($m['number'] * $unit_multiplier);
            $res = setunits($val['bytes']);
            $val['text'] = round($res[0]) . ' ' . $res[1];
        }
        $SMARTY->assign($var, $val);
    }

    $SMARTY->assign('documents', $documents);
    $SMARTY->display('module:documents.html');
}

function module_docview()
{
    include 'docview.php';
}

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        $SMARTY = LMSSmarty::getInstance();

        $SMARTY->assign('hide_documentbox', ConfigHelper::getConfig('userpanel.hide_documentbox'));
        $SMARTY->assign('show_confirmed_documents_only', ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only'));
        $SMARTY->assign('hide_archived_documents', ConfigHelper::checkConfig('userpanel.hide_archived_documents'));

        $SMARTY->display('module:documents:setup.html');
    }

    function module_submit_setup()
    {
        $DB = LMSDB::getInstance();

        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
            array(isset($_POST['hide_documentbox']) ? 1 : 0, 'userpanel', 'hide_documentbox')
        );
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
            array(isset($_POST['show_confirmed_documents_only']) ? 'true' : 'false', 'userpanel', 'show_confirmed_documents_only')
        );
        $DB->Execute(
            'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
            array(
                isset($_POST['hide_archived_documents']) ? 'true' : 'false',
                'userpanel',
                'hide_archived_documents'
            )
        );

        header('Location: ?m=userpanel&module=documents');
    }
}
