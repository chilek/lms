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

$schema = $_POST['schema'] ?? null;

if ($schema) {
    foreach ($schema as $key => $value) {
        if (!is_array($value)) {
            $schema[$key] = trim($value);
        }
    }

    $schema['promotionid'] = intval($_GET['id']);

    if ($schema['name'] == '') {
        $error['name'] = trans('Schema name is required!');
    } else if ($DB->GetOne('SELECT id FROM promotionschemas
		WHERE name = ? AND promotionid = ?', array($schema['name'], $schema['promotionid']))) {
        $error['name'] = trans('Specified name is in use!');
    }

    if (!empty($schema['dateto']) && !empty($schema['datefrom']) && $schema['dateto'] < $schema['datefrom']) {
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

    if (!$error) {
        $length = 0;
        $data = array();
        foreach ($schema['periods'] as $period) {
            if ($period = intval($period)) {
                $data[] = $period;
                $length += intval($period);
            } else {
                break;
            }
        }
        $data = implode(';', $data);

        $args = array(
            SYSLOG::RES_PROMO => $schema['promotionid'],
            'name' => $schema['name'],
            'description' => $schema['description'],
            'data' => $data,
            'length' => $length,
            'datefrom' => $schema['datefrom'] ?: 0,
            'dateto' => $schema['dateto'] ? strtotime('tomorrow', $schema['dateto']) - 1 : 0,
        );
        $DB->Execute('INSERT INTO promotionschemas (promotionid, name,
			description, data, length, datefrom, dateto)
			VALUES (?, ?, ?, ?, ?, ?, ?)', array_values($args));

        $sid = $DB->GetLastInsertId('promotionschemas');

        $schema_dir = STORAGE_DIR . DIRECTORY_SEPARATOR . 'promotionschemas';
        $stat = stat($schema_dir);
        $schema_dir .= DIRECTORY_SEPARATOR . $sid;
        if (!is_dir($schema_dir)) {
            @mkdir($schema_dir, 0700);
        }

        foreach ($files as $file) {
            $filename = $schema_dir . DIRECTORY_SEPARATOR . $file['filename'];

            if (!file_exists($filename) && !@rename($file['tmpname'], $filename)) {
                die(trans('Can\'t save file in "$a" directory!', $filename));
            }

            $DB->Execute(
                'INSERT INTO promotionattachments (promotionschemaid, filename, contenttype, label, checked)
                VALUES (?, ?, ?, ?, ?)',
                array(
                    $sid,
                    $file['filename'],
                    $file['type'],
                    $file['label'],
                    empty($file['checked']) ? 0 : 1,
                )
            );
        }

        if ($SYSLOG) {
            $args[SYSLOG::RES_PROMOSCHEMA] = $sid;
            $SYSLOG->AddMessage(SYSLOG::RES_PROMOSCHEMA, SYSLOG::OPER_ADD, $args);
        }

        // pre-fill promotionassignments with all tariffs in specified promotion
/*
        $tariffs = $DB->GetCol('SELECT DISTINCT tariffid FROM promotionassignments
            WHERE promotionschemaid IN (SELECT id FROM promotionschemas WHERE promotionid = ?)
            GROUP BY tariffid', array($schema['promotionid']));
        if (!empty($tariffs)) {
            $args = array(
                SYSLOG::RES_PROMO => $schema['promotionid'],
                SYSLOG::RES_PROMOSCHEMA => $sid
            );
            foreach ($tariffs as $tariff) {
                $DB->Execute('INSERT INTO promotionassignments (promotionschemaid, tariffid)
                    VALUES (?, ?)', array($sid, $tariff));
                if ($SYSLOG) {
                    $args[SYSLOG::RES_TARIFF] = $tariff;
                    $args[SYSLOG::RES_PROMOASSIGN] =
                        $DB->GetLastInsertID('promotionassignments');
                    $SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_ADD, $args);
                }
            }
        }
*/

        if (empty($schema['reuse'])) {
            $SESSION->redirect('?m=promotionschemainfo&id=' . $sid);
        }

        unset($schema['name']);
        unset($schema['description']);
        $schema['reuse'] = '1';
    }
} else {
    $schema['promotionid'] = $_GET['id'];
    $schema['promotionname'] = $LMS->GetPromotionNameByID($schema['promotionid']);
    $schema['periods'] = array(0);
}

$schema['selection'] = array(1,3,6,9,12,18,24,30,36,42,48,60);

$layout['pagetitle'] = trans('New Schema');

$SMARTY->assign('error', $error);
$SMARTY->assign('schema', $schema);
$SMARTY->display('promotion/promotionschemaadd.html');
