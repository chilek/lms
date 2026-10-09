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

define('TRANSFER_FILE_EOL', "\r\n");

if (!isset($_POST['id'], $_POST['action'])) {
    header('Content-type: application/json');
    die('[]');
}

$id = is_array($_POST['id']) ? Utils::filterIntegers($_POST['id']) : intval($_POST['id']);
if (empty($id)) {
    header('Content-type: application/json');
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
            header('Content-type: application/json');
            die(json_encode(['error' => 'Tag with given \'id\' does not exist!',]));
        }
        break;
    default:
        if ($DB->GetOne(
            'SELECT
                COUNT(*)
            FROM ksefinvoices i
            JOIN divisions d ON d.id = i.division_id
            JOIN userdivisions ud ON ud.divisionid = d.id
            WHERE i.id ' . (is_array($id) ? 'IN' : '=') . ' ?
                AND ud.userid = ?',
            [
                $id,
                Auth::GetCurrentUser(),
            ]
        ) != (is_array($id) ? count($id) : 1)) {
            header('Content-type: application/json');
            die(json_encode(['error' => 'Permission denied!',]));
        }
        break;
}

switch ($action) {
    case 'restore':
    case 'restore-selected':
    case 'ignore':
    case 'ignore-selected':
        $res = $DB->Execute(
            'UPDATE ksefinvoices
            SET posting = ?
            WHERE id IN ?',
            [
                (int)($action == 'restore' || $action == 'restore-selected'),
                $id,
            ]
        );
        break;
    case 'settle':
    case 'settle-selected':
    case 'unsettle':
    case 'unsettle-selected':
        $res = $DB->Execute(
            'UPDATE ksefinvoices
            SET settled = ?
            WHERE id IN ?',
            [
                (int)($action == 'settle' || $action == 'settle-selected'),
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

        $res = 1;

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

        if (!empty($res)) {
            $DB->Execute(
                'DELETE FROM ksefinvoicetags
                WHERE NOT EXISTS (
                    SELECT 1 FROM ksefinvoicetagassignments a
                    WHERE a.ksef_invoice_tag_id = ksefinvoicetags.id
                )'
            );

            if (!empty($DB->GetErrors())) {
                $res = 0;
            }
        }

        $DB->CommitTrans();

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
    case 'transfer-file':
        $invoices = $DB->GetAll(
            'SELECT
                i.*,
                (CASE WHEN EXISTS (SELECT 1 FROM ksefinvoiceitems ii WHERE ii.ksef_invoice_id = i.id) THEN 1 ELSE 0 END) AS itemcount,
                d.name AS division_name,
                d.shortname AS division_shortname,
                d.label AS division_label,
                d.mainaccount AS division_mainaccount
            FROM ksefinvoices i
            JOIN divisions d ON d.id = i.division_id
            WHERE i.id IN ?',
            [
                $id,
            ]
        );
        if (empty($invoices)) {
            $invoices = [];
        }

        $lines = [];
        foreach ($invoices as $invoice) {
            if ($invoice['gross_amount'] <= 0) {
                continue;
            }

            $buyerBankAccount = preg_replace('/[^0-9a-z]/i', '', $invoice['division_mainaccount']);
            $sellerBankAccount = preg_replace('/[^0-9a-z]/i', '', $invoice['bank_account']);

            if (empty($sellerBankAccount)) {
                continue;
            }

            $lines[] = [
                110,
                date('Ymd'),
                round($invoice['gross_amount'] * 100),
                substr($buyerBankAccount, 2, 8),
                0,
                '"' . $buyerBankAccount . '"',
                '"' . $sellerBankAccount . '"',
                '"' . str_replace('"', '""', $invoice['division_name']) . '"',
                '"' . str_replace('"', '""', $invoice['seller_name']) . '"',
                0,
                substr($sellerBankAccount, 2, 8),
                '"' . trans('<!ksef>invoice no. $a', $invoice['invoice_number']) . '"',
                '""',
                '""',
                '"51"',
                '"' . $invoice['id'] . '"',
            ];
        }

        $encoding = $_POST['encoding'] ?? 'UTF-8';

        $fileName = trans('transfers-$a.csv', date('Ymd-His'));

        $output = iconv(
            'UTF-8',
            $encoding,
            implode(
                TRANSFER_FILE_EOL,
                array_map(
                    function ($line) {
                        return implode(',', $line);
                    },
                    $lines
                )
            )
        ) . TRANSFER_FILE_EOL;

        header('Content-Type: text/csv; charset="' . $encoding . '"');
        header('Content-Disposition: attachment; filename="' . $fileName . '" filename*=UTF-8\'\'' . rawurlencode($fileName));
        header('Content-Length: ' . strlen($output));

        die($output);

        break;
    default:
        die(json_encode([
            'error' => 'Unsupported action!',
        ]));
}

header('Content-type: application/json');

if (empty($res)) {
    die(json_encode([
        'error' => 'SQL error!',
    ]));
}

die('[]');
