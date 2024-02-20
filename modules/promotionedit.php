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

$promotion = $_POST['promotion'] ?? null;
$action = $_GET['action'] ?? null;

$promotionid = intval($_GET['id']);
if (!$promotionid) {
    $SESSION->redirect('?m=promotionlist');
}

if ($action == 'tariffdel' && ($tariffid = intval($_GET['tid']))) {
    $args = array(
        SYSLOG::RES_TARIFF => $tariffid,
        SYSLOG::RES_PROMO => $promotionid
    );
    if ($SYSLOG) {
        $assigns = $DB->GetAll('SELECT id, tariffid, promotionschemaid
			FROM promotionassignments WHERE tariffid = ?
			AND promotionschemaid IN (SELECT id FROM promotionschemas
				WHERE promotionid = ?)', array_values($args));
        if (!empty($assigns)) {
            foreach ($assigns as $assign) {
                $args[SYSLOG::RES_PROMOASSIGN] = $assign['id'];
                $args[SYSLOG::RES_PROMOSCHEMA] = $assign['promotionschemaid'];
                $SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
            }
            unset($args[SYSLOG::RES_PROMOASSIGN]);
        }
    }

    $DB->Execute('DELETE FROM promotionassignments WHERE tariffid = ?
		AND promotionschemaid IN (SELECT id FROM promotionschemas
		WHERE promotionid = ?)', array_values($args));

    $SESSION->redirect('?m=promotioninfo&id=' . $promotionid);
}

if ($promotion) {
    foreach ($promotion as $key => $value) {
        if ($key != 'attachments') {
            $promotion[$key] = trim($value);
        }
    }

    if ($promotion['name']=='' && $promotion['description']=='') {
        $SESSION->redirect('?m=promotionlist');
    }

    $promotion['id'] = $promotionid;

    $oldpromotion = $LMS->getPromotion($promotionid);

    if (empty($oldpromotion['assignmentcount']) || ConfigHelper::checkPrivilege('superuser')) {
        if ($promotion['name'] == '') {
            $error['name'] = trans('Promotion name is required!');
        } else if ($DB->GetOne(
            'SELECT id FROM promotions WHERE name = ? AND id <> ?',
            array($promotion['name'], $promotion['id'])
        )) {
            $error['name'] = trans('Specified name is in use!');
        } elseif (!empty($oldpromotion['assignmentcount']) && $oldpromotion['name'] != $promotion['name']
                && ConfigHelper::checkPrivilege('superuser') && !isset($warnings['promotion-name-'])) {
            $warning['promotion[name]'] = trans('Promotion is indirectly assigned to liabilities, change its name can have impact on existing assignments!');
        }
    } else {
        $promotion['name'] = $oldpromotion['name'];
    }

    if (!empty($promotion['dateto']) && !empty($promotion['datefrom']) && $promotion['dateto'] < $promotion['datefrom']) {
        $error['dateto'] = trans('Incorrect date range!');
    }

    $result = handle_file_uploads('attachments', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    $files = array();
    if (!$error && !empty($attachments)) {
        foreach ($attachments as $attachment) {
            $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
            $attachment['filename'] = $attachment['name'];
            $files[] = $attachment;
        }
    }

    $attachments = $DB->GetAllByKey(
        'SELECT *, 0 AS deleted
        FROM promotionattachments
        WHERE promotionid = ?',
        'id',
        array($promotionid)
    );

    if (!$error && !$warning) {
        $args = array(
            'name' => $promotion['name'],
            'description' => $promotion['description'],
            'datefrom' => $promotion['datefrom'] ?: 0,
            'dateto' => $promotion['dateto'] ? strtotime('tomorrow', $promotion['dateto']) - 1 : 0,
            SYSLOG::RES_PROMO => $promotion['id']
        );
        $DB->Execute(
            'UPDATE promotions
            SET name = ?, description = ?, datefrom = ?, dateto = ?
            WHERE id = ?',
            array_values($args)
        );

        $promo_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'promotions';
        $stat = stat($promo_dir);
        $promo_dir .= DIRECTORY_SEPARATOR . $promotionid;

        if (isset($promotion['attachments']) && is_array($promotion['attachments'])) {
            foreach ($promotion['attachments'] as $attachmentid => $attachment) {
                if ($attachment['deleted']) {
                    $filename = $promo_dir . DIRECTORY_SEPARATOR . $attachments[$attachmentid]['filename'];
                    @unlink($filename);
                    $DB->Execute('DELETE FROM promotionattachments WHERE id = ?', array($attachmentid));
                } else {
                    $DB->Execute(
                        'UPDATE promotionattachments SET label = ?, checked = ? WHERE id = ?',
                        array(
                            $attachment['label'],
                            isset($attachment['checked']) ? 1 : 0,
                            $attachmentid,
                        )
                    );
                }
            }
        }

        if (!is_dir($promo_dir)) {
            @mkdir($promo_dir, 0700);
        }

        foreach ($files as $file) {
            $filename = $promo_dir . DIRECTORY_SEPARATOR . $file['filename'];

            if (!file_exists($filename) && !@rename($file['tmpname'], $filename)) {
                die(trans('Can\'t save file in "$a" directory!', $filename));
            }

            $DB->Execute(
                'INSERT INTO promotionattachments (promotionid, filename, contenttype, label, checked)
                VALUES (?, ?, ?, ?, ?)',
                array(
                    $promotionid,
                    $file['filename'],
                    $file['type'],
                    $file['label'],
                    empty($file['checked']) ? 0 : 1,
                )
            );
        }

        if ($SYSLOG) {
            $SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_UPDATE, $args);
        }

        $SESSION->redirect('?m=promotioninfo&id=' . $promotion['id']);
    } else {
        foreach ($attachments as $attachmentid => &$attachment) {
            $attachment['deleted'] = $promotion['attachments'][$attachmentid]['deleted'];
            $attachment['checked'] = isset($promotion['attachments'][$attachmentid]['checked']) ? 1 : 0;
            $attachment['label'] = $promotion['attachments'][$attachmentid]['label'];
        }
        unset($attachment);
        $promotion['attachments'] = $attachments;
    }
} else {
    $promotion = $LMS->getPromotion($promotionid);
}

$layout['pagetitle'] = trans('Promotion Edit: $a', $promotion['name']);

$SMARTY->assign('error', $error);
$SMARTY->assign('warning', $warning);
$SMARTY->assign('promotion', $promotion);
$SMARTY->display('promotion/promotionedit.html');
