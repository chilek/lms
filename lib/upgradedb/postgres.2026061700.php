<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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
 */

const KSEF_INVOICE_DIR_2026061700 = STORAGE_DIR . DIRECTORY_SEPARATOR . 'ksef' . DIRECTORY_SEPARATOR . 'invoice';

function loadInvoiceFile_2026061700($ten, $ksefNumber)
{
    static $invoiceStorage;

    if (!isset($invoiceStorage)) {
        $invoiceStorage = is_dir(KSEF_INVOICE_DIR_2026061700) && is_readable(KSEF_INVOICE_DIR_2026061700);
    }

    if (!$invoiceStorage) {
        return false;
    }

    [, $date] = explode('-', $ksefNumber);

    $invoiceFilePath = KSEF_INVOICE_DIR_2026061700
        . DIRECTORY_SEPARATOR . $ten
        . DIRECTORY_SEPARATOR . $date
        . DIRECTORY_SEPARATOR . $ksefNumber . '.xml';

    $invoiceContent = file_get_contents($invoiceFilePath);

    if (empty($invoiceContent)) {
        return false;
    }

    $xml = simplexml_load_string($invoiceContent);
    if ($xml === false) {
        return false;
    } else {
        if (empty($xml) || empty($xml->Faktura)) {
            $nameSpaces = $xml->getNamespaces(true);
            if (!empty($nameSpaces)) {
                $nameSpace = reset($nameSpaces);
                $xml = $xml->children($nameSpace);
            }
        }
    }

    return $xml;
}

$this->BeginTrans();

$purchaseInvoiceUpdateRequired_2026061700 = false;

if (!$this->ResourceExists('ksefinvoices.paid', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoices ADD COLUMN paid smallint NOT NULL DEFAULT 0");

    $purchaseInvoiceUpdateRequired_2026061700 = true;
}

if ($purchaseInvoiceUpdateRequired_2026061700) {
    $purchaseInvoices = $this->GetAll(
        "SELECT
            i.id,
            i.ksef_number,
            i.buyer_identifier_value,
            i.pay_type,
            i.pay_date
        FROM ksefinvoices i
        ORDER BY i.id"
    );

    if (!empty($purchaseInvoices)) {
        foreach ($purchaseInvoices as $purchaseInvoice) {
            $invoiceId = $purchaseInvoice['id'];
            $ksefNumber = $purchaseInvoice['ksef_number'];

            $xml = loadInvoiceFile_2026061700($purchaseInvoice['buyer_identifier_value'], $ksefNumber);

            if (empty($xml)) {
                continue;
            }

            if (!empty($xml->Fa->Platnosc->Zaplacono)) {
                $this->Execute(
                    "UPDATE ksefinvoices SET paid = ? WHERE id = ?",
                    [
                        1,
                        $invoiceId,
                    ]
                );
            }
        }
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026061700', 'dbversion'));

$this->CommitTrans();
