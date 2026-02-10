<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

use \Lms\KSeF\KSeF;

header('Content-type: application/json');

if (!isset($_POST['id'], $_POST['action'])) {
    die('[]');
}

$id = intval($_POST['id']);
if (empty($id)) {
    die(json_encode([
        'error' => "'id' parameter validation error!",
    ]));
}

$action = $_POST['action'];

switch ($action) {
    case 'rename-tag':
        if (!$DB->GetOne(
            'SELECT 1 FROM ksefinvoicetags t
            WHERE t.id = ?',
            [
                $id,
            ]
        )) {
            die(json_encode(['error' => 'Tag with given \'id\' does not exist!',]));
        }
        break;
    default:
        if (!$DB->GetOne(
            'SELECT 1 FROM ksefinvoices i
                 JOIN divisions d ON d.id = i.division_id
                 WHERE i.id = ?',
            [
                $id,
            ]
        )) {
            die(json_encode(['error' => 'Permission denied!',]));
        }
        break;
}

switch ($action) {
    case 'restore':
    case 'ignore':
        $res = $DB->Execute(
            'UPDATE ksefinvoices
            SET posting = ?
            WHERE id = ?',
            [
                (int)($_POST['action'] == 'restore'),
                $id,
            ]
        );
        break;
    case 'settle':
    case 'unsettle':
        $res = $DB->Execute(
            'UPDATE ksefinvoices
                SET settled = ?
                WHERE id = ?',
            [
                (int)($_POST['action'] == 'settle'),
                $id,
            ]
        );
        break;
    case 'set-notes':
    case 'clear-notes':
        $res = $DB->Execute(
            'UPDATE ksefinvoices
                SET notes = ?
                WHERE id = ?',
            [
                strlen($_POST['notes']) && $action == 'set-notes' ? $_POST['notes'] : null,
                $id,
            ]
        );
        break;
    case 'set-tags':
        if (empty($_POST['tags'])) {
            $selectedTags = [];
        } else {
            $selectedTags = $_POST['tags'];
        }
        $selectedTags = array_combine($selectedTags, $selectedTags);

        $existingTags = $DB->GetAllByKey(
            'SELECT
                t.id,
                UPPER(t.name) AS name
            FROM ksefinvoicetags t',
            'id'
        );
        if (empty($existingTags)) {
            $existingTags = [];
        }

        $invoiceTags = $DB->GetAllByKey(
            'SELECT
                t.id,
                UPPER(t.name) AS name
            FROM ksefinvoicetags t
            JOIN ksefinvoicetagassignments a ON a.ksef_invoice_tag_id = t.id
            WHERE a.ksef_invoice_id = ?',
            'id',
            [
                $id,
            ]
        );
        if (empty($invoiceTags)) {
            $invoiceTags = [];
        }

        $DB->BeginTrans();

        foreach ($selectedTags as $selectedTag) {
            if (!isset($existingTags[$selectedTag])) {
                $res = $DB->Execute(
                    'INSERT INTO ksefinvoicetags
                    (name)
                    VALUES (?)',
                    [
                        $selectedTag,
                    ]
                );

                if (empty($res)) {
                    break;
                }

                $tagId = $DB->GetLastInsertID('ksefinvoicetags');
            } else {
                $tagId = intval($selectedTag);

                if (empty($tagId)) {
                    $res = 0;
                    break;
                }
            }

            if (!isset($invoiceTags[$tagId])) {
                $res = $DB->Execute(
                    'INSERT INTO ksefinvoicetagassignments
                    (ksef_invoice_id, ksef_invoice_tag_id)
                    VALUES (?, ?)',
                    [
                        $id,
                        $tagId,
                    ]
                );

                if (empty($res)) {
                    break;
                }
            }
        }

        if (!empty($res)) {
            foreach ($invoiceTags as $invoiceTagId => $invoiceTag) {
                if (!isset($selectedTags[$invoiceTagId])) {
                    $res = $DB->Execute(
                        'DELETE FROM ksefinvoicetagassignments
                        WHERE ksef_invoice_id = ?
                            AND ksef_invoice_tag_id = ?',
                        [
                            $id,
                            $invoiceTagId,
                        ]
                    );

                    if (empty($res)) {
                        break;
                    }
                }
            }
        }

        $DB->CommitTrans();

        if (!empty($res)) {
            $DB->Execute(
                'DELETE FROM ksefinvoicetags
                WHERE NOT EXISTS (
                        SELECT 1 FROM ksefinvoicetagassignments a
                        WHERE a.ksef_invoice_tag_id = ksefinvoicetags.id
                    )'
            );

            if (!empty($DB->GetErrors())) {
                $res = false;
            }
        }

        break;
    case 'clear-tags':
        $res = true;

        $DB->Execute(
            'DELETE FROM ksefinvoicetagassignments WHERE ksef_invoice_id = ?',
            [
                $id,
            ]
        );

        if (!empty($DB->GetErrors())) {
            $res = false;
        }

        break;
    case 'rename-tag':
        $res = $DB->Execute(
            'UPDATE ksefinvoicetags
            SET name = ?
            WHERE id = ?',
            [
                $_POST['name'],
                $id,
            ]
        );

        break;
    default:
        die(json_encode([
            'error' => 'Unsupported action!',
        ]));
}

if (empty($res)) {
    die(json_encode([
        'error' => 'SQL error!',
    ]));
}

die('[]');
