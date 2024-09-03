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

    $SESSION->redirect_to_history_entry();
}

include(MODULES_DIR . DIRECTORY_SEPARATOR . 'document.inc.php');

$document = $DB->GetRow(
    'SELECT documents.id AS id, closed,
		archived, confirmdate, adate, auserid,
		type, number, numberplans.template,
		cdate, sdate, cuserid, numberplanid, title, fromdate, todate, description, divisionid, documents.customerid,
		r.rights AS docrights
	FROM documents
	JOIN docrights r ON (r.doctype = documents.type)
	LEFT JOIN documentcontents ON (documents.id = docid)
	LEFT JOIN numberplans ON (numberplanid = numberplans.id)
	WHERE documents.id = ? AND r.userid = ? AND (r.rights & ?) > 0',
    array($_GET['id'], $userid, DOCRIGHT_EDIT)
);
if (empty($document)) {
    $SMARTY->display('noaccess.html');
    die;
}

$document['customer_name'] = $LMS->GetCustomerName($document['customerid']);

$document['attachments'] = $DB->GetAllByKey('SELECT *, 0 AS deleted FROM documentattachments
	WHERE docid = ? ORDER BY type DESC', 'id', array($_GET['id']));

if (isset($_POST['document'])) {
    $documentedit = $_POST['document'];
    $documentedit['id'] = $_GET['id'];

    $oldfdate = $documentedit['fromdate'];
    $oldtdate = $documentedit['todate'];
    $oldconfirmdate = $documentedit['confirmdate'] ?? 0;

    if (!$documentedit['title']) {
        $error['title'] = trans('Document title is required!');
    }

    if (($documentedit['numberplanid'] && !$LMS->checkNumberPlanAccess($documentedit['numberplanid']))
        || ($document['numberplanid'] && !$LMS->checkNumberPlanAccess($document['numberplanid']))) {
        $documentedit['numberplanid'] = $document['numberplanid'];
        $error['numberplanid'] = trans('Persmission denied!');
    }

    if (!isset($error['numberplanid'])) {
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
                $documentedit['number'] = $tmp ?: 1;
            } else {
                $documentedit['number'] = $document['number'];
            }
        } elseif (!preg_match('/^[0-9]+$/', $documentedit['number'])) {
            $error['number'] = trans('Document number must be an integer!');
        } elseif ($document['number'] != $documentedit['number'] || $document['numberplanid'] != intval($documentedit['numberplanid'])) {
            if (($docid = $LMS->DocumentExists(array(
                'number' => $documentedit['number'],
                'doctype' => $documentedit['type'],
                'planid' => $documentedit['numberplanid'],
                'customerid' => $document['customerid'] ?? null,
            ))) > 0 && $docid != $documentedit['id']) {
                $error['number'] = trans('Document with specified number exists!');
            }
        }
    }

    $currtime = time();

    if (ConfigHelper::checkPrivilege('document_consent_date')) {
        if ($documentedit['cdate']) {
            [$year, $month, $day] = explode('/', $documentedit['cdate']);
            if (checkdate($month, $day, $year)) {
                $documentedit['cdate'] = mktime(
                    date('G', $currtime),
                    date('i', $currtime),
                    date('s', $currtime),
                    $month,
                    $day,
                    $year
                );
                $currmonth = $month;
            } else {
                $error['cdate'] = trans('Incorrect date format!');
                $documentedit['cdate'] = $currtime;
            }
        }
    } else {
        $documentedit['cdate'] = $document['cdate'];
    }

    if (ConfigHelper::checkPrivilege('document_consent_date') && $documentedit['cdate'] && !isset($warnings['document-cdate-'])) {
        if ($documentedit['type'] && strtotime(date('Y/m/d', $document['cdate'])) != strtotime(date('Y/m/d', $documentedit['cdate']))) {
            if (empty($documentedit['numberplanid'])) {
                $maxdate = $DB->GetOne(
                    'SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid IS NULL AND id <> ?',
                    array($documentedit['type'], $documentedit['id'])
                );
            } else {
                $maxdate = $DB->GetOne(
                    'SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ? AND id <> ?',
                    array($documentedit['type'], $documentedit['numberplanid'], $documentedit['id'])
                );
            }

            if ($documentedit['cdate'] < $maxdate) {
                $warning['document[cdate]'] = trans(
                    'Last date of document settlement is $a. If sure, you want to write document with date of $b, then click "Submit" again.',
                    date('Y/m/d H:i', $maxdate),
                    date('Y/m/d H:i', $documentedit['cdate'])
                );
            }
        }
    } elseif (!$documentedit['cdate']) {
        $documentedit['cdate'] = $document['cdate'];
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

    $documentedit['closed'] = isset($documentedit['closed']) ? DOC_CLOSED : DOC_OPEN;
    $documentedit['archived'] = isset($documentedit['archived']) ? 1 : 0;
    if ($documentedit['archived'] && !$documentedit['closed']) {
        $error['closed'] = trans('Cannot undo document confirmation while it is archived!');
    }

    if (isset($documentedit['confirmdate']) && $documentedit['confirmdate'] && !$documentedit['closed']) {
        $date = explode('/', $documentedit['confirmdate']);
        if (checkdate($date[1], $date[2], $date[0])) {
            $documentedit['confirmdate'] = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
        } else {
            $error['confirmdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
            $documentedit['confirmdate'] = 0;
        }
    } else {
        $documentedit['confirmdate'] = 0;
    }

    $result = handle_file_uploads('attachments', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    $files = array();
    if (!$error && !empty($attachments)) {
        foreach ($attachments as $attachment) {
            $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
            $attachment['filename'] = $attachment['name'];
            $attachment['md5sum'] = md5_file($attachment['tmpname']);
            $files[] = $attachment;
        }
    }

    if (!$error) {
        $error = $LMS->AddDocumentFileAttachments($files);
        if (empty($error) && !empty($tmppath)) {
            rrmdir($tmppath);
        }
    }

    if (!$error && !$warning) {
        $DB->BeginTrans();

        $fullnumber = docnumber(array(
            'number' => $documentedit['number'],
            'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($documentedit['numberplanid'])),
            'cdate' => $document['cdate'],
            'customerid' => $document['customerid'],
        ));

        if ($documentedit['closed']) {
            if ($document['confirmdate'] == -1 && $document['closed'] < DOC_CLOSED_AFTER_CUSTOMER_SMS) {
                $closed = DOC_CLOSED_AFTER_CUSTOMER_SMS;
            } elseif ($document['closed'] == DOC_CLOSED_AFTER_CUSTOMER_SCAN) {
                $closed = DOC_CLOSED_AFTER_CUSTOMER_SCAN;
            } else {
                $closed = DOC_CLOSED;
            }
        } else {
            $closed = DOC_OPEN;
        }

        $allowed_archiving = ($document['docrights'] & DOCRIGHT_ARCHIVE) > 0;
        $DB->Execute(
            'UPDATE documents SET type=?, closed=?, cdate = ?, sdate=?, cuserid=?, confirmdate = ?,
			        archived = ?, adate = ?, auserid = ?, number=?, numberplanid=?, fullnumber=?
				WHERE id=?',
            array(  $documentedit['type'],
                    ($document['docrights'] & DOCRIGHT_CONFIRM)
                        ? ($closed == DOC_OPEN && !$document['closed'] ? DOC_OPEN : $closed)
                        : $document['closed'],
                    $documentedit['cdate'],
                    ($document['docrights'] & DOCRIGHT_CONFIRM)
                        ? $documentedit['closed'] ? ($document['closed'] ? $document['sdate'] : time()) : 0
                        : $document['sdate'],
                    ($document['docrights'] & DOCRIGHT_CONFIRM)
                        ? ($documentedit['closed'] ? ($document['closed'] ? $document['cuserid'] : $userid) : null)
                        : $document['cuserid'],
                    !$document['closed'] && $documentedit['closed'] && $document['confirmdate'] == -1 ? 0 : ($documentedit['closed'] || !$documentedit['confirmdate'] ? 0 : strtotime('tomorrow', $documentedit['confirmdate']) - 1),
                    $allowed_archiving ? $documentedit['archived'] : $document['archived'],
                    $allowed_archiving
                        ? ($documentedit['archived'] ? ($document['archived'] ? $document['adate'] : time()) : 0)
                        : $document['adate'],
                    $allowed_archiving
                        ? ($documentedit['archived'] ? ($document['archived'] ? $document['auserid'] : $userid) : null)
                        : $document['auserid'],
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
                if ($attachment['deleted'] && $document['attachments'][$attachmentid]['type'] < 1) {
                    $md5sum = $document['attachments'][$attachmentid]['md5sum'];
                    if ($DB->GetOne('SELECT COUNT(*) FROM documentattachments WHERE md5sum = ?', array($md5sum)) <= 1) {
                        @unlink(DOC_DIR . DIRECTORY_SEPARATOR . substr($md5sum, 0, 2) . DIRECTORY_SEPARATOR . $md5sum);
                    }
                    $DB->Execute('DELETE FROM documentattachments WHERE id = ?', array($attachmentid));
                }
            }
        }

        $LMS->AddDocumentAttachments($documentedit['id'], $files);

        $DB->CommitTrans();

        if ($closed > DOC_OPEN && !$document['closed']) {
            $LMS->CommitDocuments(array($documentedit['id']), false, false);
        }

        $SESSION->redirect_to_history_entry();
    } else {
        $document['title'] = $documentedit['title'];
        $document['type'] = $documentedit['type'];
        $document['description'] = $documentedit['description'];
        $document['cdate'] = $documentedit['cdate'];
        $document['closed'] = $documentedit['closed'];
        $document['number'] = $documentedit['number'];
        $document['numberplanid'] = $documentedit['numberplanid'];
        $document['fromdate'] = $oldfdate;
        $document['todate'] = $oldtdate;
        $document['confirmdate'] = $documentedit['confirmdate'];
        $document['confirmdatestring'] = $oldconfirmdate;
        foreach ($document['attachments'] as $attachmentid => &$attachment) {
            $attachment['deleted'] = $documentedit['attachments'][$attachmentid]['deleted'];
        }
        unset($attachment);
    }
} else {
    if ($document['fromdate'] > 0) {
        $document['fromdate'] = date('Y/m/d', $document['fromdate']);
    }
    if ($document['todate'] > 0) {
        $document['todate'] = date('Y/m/d', $document['todate']);
    }
    if ($document['confirmdate'] > 0) {
        $document['confirmdatestring'] = date('Y/m/d', $document['confirmdate']);
    }
}

$rights = $DB->GetCol(
    'SELECT doctype FROM docrights
	WHERE userid = ? AND (rights & ?) > 0',
    array($userid, DOCRIGHT_CREATE)
);

if (!$rights || !$DB->GetOne(
    'SELECT 1 FROM docrights
	WHERE userid = ? AND doctype = ? AND (rights & ?) > 0',
    array($userid, $document['type'], DOCRIGHT_EDIT)
)) {
        $SMARTY->display('noaccess.html');
        die;
}

$numberplans = GetDocumentNumberPlans($document['type'], $document['customerid']);
if (empty($numberplans)) {
    $numberplans = $LMS->getSystemDefaultNumberPlan(array('doctype' => $document['type']));
}
$SMARTY->assign('numberplans', $numberplans);
$SMARTY->assign('planDocumentType', $document['type']);

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
