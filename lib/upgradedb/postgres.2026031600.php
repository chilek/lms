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

const KSEF_INVOICE_DIR_2026031600 = STORAGE_DIR . DIRECTORY_SEPARATOR . 'ksef' . DIRECTORY_SEPARATOR . 'invoice';

function loadInvoiceFile_20260316($ten, $ksefNumber)
{
    static $invoiceStorage;

    if (!isset($invoiceStorage)) {
        $invoiceStorage = is_dir(KSEF_INVOICE_DIR_2026031600) && is_readable(KSEF_INVOICE_DIR_2026031600);
    }

    if (!$invoiceStorage) {
        return false;
    }

    [, $date] = explode('-', $ksefNumber);

    $invoiceFilePath = KSEF_INVOICE_DIR_2026031600
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

if (!$this->ResourceExists('ksefinvoicesummaries', LMSDB::RESOURCE_TYPE_TABLE)) {
    $this->Execute(
        "CREATE TABLE ksefinvoicesummaries (
            ksef_invoice_id integer NOT NULL
                CONSTRAINT ksefinvoicesummaries_ksef_invoice_id_fkey REFERENCES ksefinvoices (id) ON UPDATE CASCADE ON DELETE CASCADE,
            net_amount numeric(12,5) NOT NULL,
            gross_amount numeric(12,5) NOT NULL,
            vat_amount numeric(12,5) NOT NULL,
            tax_rate numeric(4,2),
            taxed smallint DEFAULT 1,
            reverse_charge smallint DEFAULT 0,
            eu smallint DEFAULT 0,
            export smallint DEFAULT 0
        )"
    );

    $purchaseInvoices = $this->GetAll(
        "SELECT * FROM ksefinvoices"
    );
    if (!empty($purchaseInvoices)) {
        foreach ($purchaseInvoices as $purchaseInvoice) {
            $xml = loadInvoiceFile_20260316($purchaseInvoice['buyer_identifier_value'], $purchaseInvoice['ksef_number']);

            if (empty($xml)) {
                die('Fatal error: could not load invoice XML file!');
            }

            if (!empty($xml->Fa->P_13_1)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_1,
                        (float)$xml->Fa->P_14_1 + (float)$xml->Fa->P_13_1,
                        (float)$xml->Fa->P_14_1,
                        23.00,
                        1,
                        0,
                        0,
                        0,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_2)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_2,
                        (float)$xml->Fa->P_14_2 + (float)$xml->Fa->P_13_2,
                        (float)$xml->Fa->P_14_2,
                        8.00,
                        1,
                        0,
                        0,
                        0,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_3)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_3,
                        (float)$xml->Fa->P_14_3 + (float)$xml->Fa->P_13_3,
                        (float)$xml->Fa->P_14_3,
                        5.00,
                        1,
                        0,
                        0,
                        0,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_6_1)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_6_1,
                        (float)$xml->Fa->P_13_6_1,
                        0.0,
                        0.00,
                        1,
                        0,
                        0,
                        0,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_6_2)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_6_2,
                        (float)$xml->Fa->P_13_6_2,
                        0.0,
                        0.00,
                        1,
                        0,
                        1,
                        0,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_6_3)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_6_3,
                        (float)$xml->Fa->P_13_6_3,
                        0.0,
                        0.00,
                        1,
                        0,
                        0,
                        1,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_7)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_7,
                        (float)$xml->Fa->P_13_7,
                        0.0,
                        0.00,
                        0,
                        0,
                        0,
                        0,
                    ]
                );
            }

            if (!empty($xml->Fa->P_13_10)) {
                $this->Execute(
                    "INSERT INTO ksefinvoicesummaries (
                        ksef_invoice_id,
                        net_amount,
                        gross_amount,
                        vat_amount,
                        tax_rate,
                        taxed,
                        reverse_charge,
                        eu,
                        export
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $purchaseInvoice['id'],
                        (float)$xml->Fa->P_13_10,
                        (float)$xml->Fa->P_13_10,
                        0.0,
                        0.00,
                        1,
                        1,
                        0,
                        0,
                    ]
                );
            }
        }
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026031600', 'dbversion'));

$this->CommitTrans();
