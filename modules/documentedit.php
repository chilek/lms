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

check_file_uploads();

$userid = Auth::GetCurrentUser();

if (isset($_GET['action'])) {
    if (!empty($_POST['marks'])) {
        $ids = $_POST['marks'];
    } else {
        $ids = array($_GET['id']);
    }

    switch ($_GET['action']) {
        case 'confirm':
            $LMS->CommitDocuments($ids);
            break;
        case 'archive':
            $LMS->ArchiveDocuments($ids);
            break;
    }

    $SESSION->redirect('?'.$SESSION->get('backto'));
}

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'document.inc.php');

$document = $DB->GetRow('SELECT documents.id AS id, closed,
		archived, adate, auserid,
		type, number, numberplans.template,
		cdate, sdate, cuserid, numberplanid, title, fromdate, todate, description, divisionid, documents.customerid
	FROM documents
	JOIN docrights r ON (r.doctype = documents.type)
	LEFT JOIN documentcontents ON (documents.id = docid)
	LEFT JOIN numberplans ON (numberplanid = numberplans.id)
	WHERE documents.id = ? AND r.userid = ? AND (r.rights & 8) = 8', array($_GET['id'], $userid));
if (empty($document)) {
    $SMARTY->display('noaccess.html');
    die;
}

$document['attachments'] = $DB->GetAllByKey('SELECT *, 0 AS deleted FROM documentattachments
	WHERE docid = ? AND main = 0', 'id', array($_GET['id']));

if (isset($_POST['document'])) {
    $documentedit = $_POST['document'];
    $documentedit['id'] = $_GET['id'];

    $oldfdate = $documentedit['fromdate'];
    $oldtdate = $documentedit['todate'];

    if (!$documentedit['title']) {
        $error['title'] = trans('Document title is required!');
    }

    // check if selected customer can use selected numberplan
    if ($documentedit['numberplanid'] && !$DB->GetOne('SELECT 1 FROM numberplanassignments
	        WHERE planid = ? AND divisionid = ?', array($documentedit['numberplanid'], $document['divisionid']))) {
        $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
    } elseif (!$documentedit['number']) {
        if ($document['numberplanid'] != $documentedit['numberplanid']) {
            $tmp = $LMS->GetNewDocumentNumber(array(
                'doctype' => $documentedit['type'],
                'planid' => $documentedit['numberplanid'],
                'customerid' => $document['customerid'],
            ));
            $documentedit['number'] = $tmp ? $tmp : 1;
        } else {
            $documentedit['number'] = $document['number'];
        }
    } elseif (!preg_match('/^[0-9]+$/', $documentedit['number'])) {
        $error['number'] = trans('Document number must be an integer!');
    } elseif ($document['number'] != $documentedit['number'] || $document['numberplanid'] != $documentedit['numberplanid']) {
        if ($LMS->DocumentExists(array(
            'number' => $documentedit['number'],
            'doctype' => $documentedit['type'],
            'planid' => $documentedit['numberplanid'],
        ))) {
            $error['number'] = trans('Document with specified number exists!');
        }
    }

    if ($documentedit['fromdate']) {
        $date = explode('/', $documentedit['fromdate']);
        if (checkdate($date[1], $date[2], $date[0])) {
            $documentedit['fromdate'] = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
        } else {
            $error['fromdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        }
    } else {
        $documentedit['fromdate'] = 0;
    }

    if ($documentedit['todate']) {
        $date = explode('/', $documentedit['todate']);
        if (checkdate($date[1], $date[2], $date[0])) {
            $documentedit['todate'] = mktime(23, 59, 59, $date[1], $date[2], $date[0]);
        } else {
            $error['todate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
        }
    } else {
        $documentedit['todate'] = 0;
    }

    if ($documentedit['fromdate'] > $documentedit['todate'] && $documentedit['todate']!=0) {
        $error['todate'] = trans('Start date can\'t be greater than end date!');
    }

    $documentedit['closed'] = isset($documentedit['closed']) ? 1 : 0;
    $documentedit['archived'] = isset($documentedit['archived']) ? 1 : 0;
    if ($documentedit['archived'] && !$documentedit['closed']) {
        $error['closed'] = trans('Cannot undo document confirmation while it is archived!');
    }

    $result = handle_file_uploads('attachments', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    $files = array();
    if (!$error && !empty($attachments)) {
        foreach ($attachments as $attachment) {
            $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
            $attachment['md5sum'] = md5_file($attachment['tmpname']);
            $files[] = $attachment;
        }
    }

    if (!$error) {
        foreach ($files as &$file) {
            $file['path'] = DOC_DIR . DIRECTORY_SEPARATOR . substr($file['md5sum'], 0, 2);
            $file['newfile'] = $file['path'] . DIRECTORY_SEPARATOR . $file['md5sum'];

            // If we have a file with specified md5sum, we assume
            // it's here because of some error. We can replace it with
            // the new document file
            // why? document attachment can be shared between different documents.
            // we should rather use the other message digest in such case!
            if ($DB->GetOne('SELECT docid FROM documentattachments WHERE md5sum = ?', array($file['md5sum']))
                && (filesize($file['newfile']) != filesize($file['tmpname'])
                    || hash_file('sha256', $file['newfile']) != hash_file('sha256', $file['tmpname']))) {
                $error['files'] = trans('Specified file exists in database!');
                break;
            }
        }
        unset($file);
        if (!$error) {
            foreach ($files as $file) {
                @mkdir($file['path'], 0700);
                if (!file_exists($file['newfile']) && !@rename($file['tmpname'], $file['newfile'])) {
                    $error['files'] = trans('Can\'t save file in "$a" directory!', $file['path']);
                    break;
                }
            }
            if (!empty($tmppath)) {
                rrmdir($tmppath);
            }
        }
    }

    if (!$error) {
        $DB->BeginTrans();

        $fullnumber = docnumber(array(
            'number' => $documentedit['number'],
            'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($documentedit['numberplanid'])),
            'cdate' => $document['cdate'],
            'customerid' => $document['customerid'],
        ));

        $DB->Execute(
            'UPDATE documents SET type=?, closed=?, sdate=?, cuserid=?,
			archived = ?, adate = ?, auserid = ?, number=?, numberplanid=?, fullnumber=?
				WHERE id=?',
            array(  $documentedit['type'],
                    $documentedit['closed'],
                    $documentedit['closed'] ? ($document['closed'] ? $document['sdate'] : time()) : 0,
                    $documentedit['closed'] ? ($document['closed'] ? $document['cuserid'] : $userid) : null,
                    $documentedit['archived'],
                    $documentedit['archived'] ? ($document['archived'] ? $document['adate'] : time()) : 0,
                    $documentedit['archived'] ? ($document['archived'] ? $document['auserid'] : $userid) : null,
                    $documentedit['number'],
                    empty($documentedit['numberplanid']) ? null : $documentedit['numberplanid'],
                    $fullnumber,
                    $documentedit['id'],
                    )
        );

        $DB->Execute(
            'UPDATE documentcontents SET title=?, fromdate=?, todate=?, description=?
				WHERE docid=?',
            array(  $documentedit['title'],
                    $documentedit['fromdate'],
                    $documentedit['todate'],
                    $documentedit['description'],
                    $documentedit['id']
                    )
        );

        if (isset($documentedit['attachments']) && is_array($documentedit['attachments'])) {
            foreach ($documentedit['attachments'] as $attachmentid => $attachment) {
                if ($attachment['deleted']) {
                    $md5sum = $document['attachments'][$attachmentid]['md5sum'];
                    if ($DB->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array($md5sum)) <= 1) {
                        @unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2) . DIRECTORY_SEPARATOR . $md5sum);
                    }
                    $DB->Execute('DELETE FROM documentattachments WHERE id = ?', array($attachmentid));
                }
            }
        }

        foreach ($files as $file) {
            if (!$DB->GetOne(
                'SELECT id FROM documentattachments WHERE docid = ? AND md5sum = ?',
                array($documentedit['id'], $file['md5sum'])
            )) {
                $DB->Execute('INSERT INTO documentattachments (docid, filename, contenttype, md5sum, main)
					VALUES (?, ?, ?, ?, ?)', array($documentedit['id'],
                    $file['name'],
                    $file['type'],
                    $file['md5sum'],
                    0,
                ));
            }
        }

        $DB->CommitTrans();

        $SESSION->redirect('?'.$SESSION->get('backto'));
    } else {
        $document['title'] = $documentedit['title'];
        $document['type'] = $documentedit['type'];
        $document['description'] = $documentedit['description'];
        $document['closed'] = $documentedit['closed'];
        $document['number'] = $documentedit['number'];
        $document['numberplanid'] = $documentedit['numberplanid'];
        $document['fromdate'] = $oldfdate;
        $document['todate'] = $oldtdate;
        foreach ($document['attachments'] as $attachmentid => &$attachment) {
            $attachment['deleted'] = $documentedit['attachments'][$attachmentid]['deleted'];
        }
        unset($attachment);
    }
} else {
    if ($document['fromdate']>0) {
        $document['fromdate'] = date('Y/m/d', $document['fromdate']);
    }
    if ($document['todate']>0) {
        $document['todate'] = date('Y/m/d', $document['todate']);
    }
}

$rights = $DB->GetCol('SELECT doctype FROM docrights
	WHERE userid = ? AND (rights & 2) = 2', array($userid));

if (!$rights || !$DB->GetOne(
    'SELECT 1 FROM docrights
	WHERE userid = ? AND doctype = ? AND (rights & 8) = 8',
    array($userid, $document['type'])
)) {
        $SMARTY->display('noaccess.html');
        die;
}

$numberplans = GetDocumentNumberPlans($document['type'], $document['customerid']);
if (empty($numberplans)) {
    $numberplans = array();
}
$SMARTY->assign('numberplans', $numberplans);

/*
if($dirs = getdir(DOC_DIR.'/templates', '^[a-z0-9_-]+$'))
    foreach($dirs as $dir)
    {
        $infofile = DOC_DIR.'/templates/'.$dir.'/info.php';
        if(file_exists($infofile))
        {
            unset($engine);
            include($infofile);
            $docengines[$dir] = $engine;
        }
    }

if($docengines) ksort($docengines);
*/

$layout['pagetitle'] = trans('Edit Document: $a', docnumber(array(
    'number' => $document['number'],
    'template' => $document['template'],
    'cdate' => $document['cdate'],
    'customerid' => $document['customerid'],
)));

//$SMARTY->assign('docengines', $docengines);
$SMARTY->assign('docrights', $rights);
$SMARTY->assign('error', $error);
$SMARTY->assign('document', $document);
$SMARTY->display('document/documentedit.html');
