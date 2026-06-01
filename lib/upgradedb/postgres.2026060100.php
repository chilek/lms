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

const KSEF_INVOICE_DIR_2026060100 = STORAGE_DIR . DIRECTORY_SEPARATOR . 'ksef' . DIRECTORY_SEPARATOR . 'invoice';

function ksefTaxProperties_2026060100(string $ksefTaxRateName): ?array
{
    static $ksefTaxProperties;

    if (empty($ksefTaxProperties)) {
        $ksefTaxProperties = [
            '23' => [
                'rate' => 23,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '22' => [
                'rate' => 22,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '8' => [
                'rate' => 8,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '7' => [
                'rate' => 7,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '5' => [
                'rate' => 5,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '4' => [
                'rate' => 4,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '3' => [
                'rate' => 3,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '0 KR' => [
                'rate' => 0,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            '0 WDT' => [
                'rate' => 0,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => true,
                'export' => false,
            ],
            '0 EX' => [
                'rate' => 0,
                'taxed' => true,
                'reverse_charge' => false,
                'eu' => false,
                'export' => true,
            ],
            'zw' => [
                'rate' => 0,
                'taxed' => false,
                'reverse_charge' => false,
                'eu' => false,
                'export' => false,
            ],
            'oo' => [
                'rate' => 0,
                'taxed' => true,
                'reverse_charge' => true,
                'eu' => false,
                'export' => false,
            ],
            'np I' => [
                'rate' => 0,
                'taxes' => false,
                'reverse_charge' => false,
                'eu' => false,
                'export' => true,
            ],
            'np II' => [
                'rate' => 0,
                'taxes' => false,
                'reverse_charge' => false,
                'eu' => true,
                'export' => false,
            ],
        ];
    }

    return $ksefTaxProperties[$ksefTaxRateName] ?? null;
}

function loadInvoiceFile_2026060100($ten, $ksefNumber)
{
    static $invoiceStorage;

    if (!isset($invoiceStorage)) {
        $invoiceStorage = is_dir(KSEF_INVOICE_DIR_2026060100) && is_readable(KSEF_INVOICE_DIR_2026060100);
    }

    if (!$invoiceStorage) {
        return false;
    }

    [, $date] = explode('-', $ksefNumber);

    $invoiceFilePath = KSEF_INVOICE_DIR_2026060100
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

$purchaseInvoiceUpdateRequired_2026060100 = false;

if (!$this->ResourceExists('ksefinvoiceitems.net_price', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoiceitems ADD COLUMN net_price numeric(24,8) NOT NULL DEFAULT 0");

    $purchaseInvoiceUpdateRequired_2026060100 = true;
}

if (!$this->ResourceExists('ksefinvoiceitems.net_value', LMSDB::RESOURCE_TYPE_COLUMN)) {
    $this->Execute("ALTER TABLE ksefinvoiceitems ADD COLUMN net_value numeric(16,2) NOT NULL DEFAULT 0");

    $purchaseInvoiceUpdateRequired_2026060100 = true;
}

if ($purchaseInvoiceUpdateRequired_2026060100) {
    $purchaseInvoiceItems = $this->GetAll(
        "SELECT
            i.ksef_number,
            i.buyer_identifier_value,
            ii.ksef_invoice_id,
            ii.item_id,
            ii.net_flag,
            ii.price,
            ii.value,
            ii.tax_rate,
            ii.before_state
        FROM ksefinvoices i
        JOIN ksefinvoiceitems ii ON ii.ksef_invoice_id = i.id
        ORDER BY ii.ksef_invoice_id, ii.item_id"
    );
    if (!empty($purchaseInvoiceItems)) {
        $loadedPurchaseInvoices = [];

        foreach ($purchaseInvoiceItems as $purchaseInvoiceItem) {
            $invoiceId = $purchaseInvoiceItem['ksef_invoice_id'];
            $ksefNumber = $purchaseInvoiceItem['ksef_number'];
            $beforeState = empty($purchaseInvoiceItem['before_state']) ? 0 : 1;

            if (!isset($loadedPurchaseInvoices[$ksefNumber])) {
                $xml = loadInvoiceFile_2026060100($purchaseInvoiceItem['buyer_identifier_value'], $ksefNumber);

                if (empty($xml)) {
                    $loadedPurchaseInvoices[$ksefNumber] = false;
                } else {
                    $loadedPurchaseInvoices[$ksefNumber] = true;

                    $xmlPurchaseInvoiceItems = [
                        0 => [],
                        1 => [],
                    ];

                    $itemId = 1;

                    if (empty($xml->Fa->FaWiersz)) {
                        if (!empty($xml->Fa->Zamowienie) && !empty($xml->Fa->Zamowienie->ZamowienieWiersz)) {
                            foreach ($xml->Fa->Zamowienie->ZamowienieWiersz as $item) {
                                $ksefBeforeState = empty($item->StanPrzedZ) ? 0 : 1;
                                $ksefItemId = (int)$item->NrWierszaZam;
                                if ($ksefItemId > 1000) {
                                    $ksefItemId = $itemId;
                                }
                                $count = empty($item->P_8BZ) ? 1 : (float)$item->P_8BZ;
                                $netPrice = (float)($item->P_9AZ ?? 0);
                                $netValue = (float)($item->P_11NettoZ ?? 0);
                                if (isset($item->P_12Z)) {
                                    $taxProperties = ksefTaxProperties_2026060100((string)trim($item->P_12Z));
                                    $taxRate = $taxProperties['rate'];
                                } else {
                                    $taxRate = 0.0;
                                }
                                $xmlPurchaseInvoiceItems[$ksefBeforeState][$ksefItemId] = [
                                    'netprice' => $netPrice,
                                    'price' => round(($netPrice * (100 + $taxRate)) / 100, 8),
                                    'netvalue' => $netValue,
                                    'value' => round(($netValue * (100 + $taxRate)) / 100, 2),
                                ];
                                $itemId++;
                            }
                        }
                    } else {
                        foreach ($xml->Fa->FaWiersz as $item) {
                            $ksefBeforeState = empty($item->StanPrzed) ? 0 : 1;
                            $ksefItemId = (int)$item->NrWierszaFa;
                            if ($ksefItemId > 1000) {
                                $ksefItemId = $itemId;
                            }
                            $count = empty($item->P_8B) || empty((float)$item->P_8B) ? 1 : (float)$item->P_8B;
                            if (isset($item->P_12)) {
                                $taxProperties = ksefTaxProperties_2026060100((string)trim($item->P_12));
                                $taxRate = $taxProperties['rate'];
                            } else {
                                $taxRate = 0.0;
                            }
                            $xmlPurchaseInvoiceItems[$ksefBeforeState][$ksefItemId] = [
                                'netprice' => isset($item->P_9A) ? (float)$item->P_9A : round(((float)$item->P_11) / $count, 8),
                                'price' => isset($item->P_9B) ? (float)$item->P_9B : round(isset($item->P_11A) ? ((float)$item->P_11A) / $count : ((float)$item->P_9A * (100 + $taxRate)) / 100, 8),
                                'netvalue' => isset($item->P_11) ? (float)$item->P_11 : round(((float)$item->P_9A) * $count, 2),
                                'value' => isset($item->P_11A) ? (float)$item->P_11A : round(isset($item->P_9B) ? ((float)$item->P_9B) * $count : ((float)$item->P_11 * (100 + $taxRate)) / 100, 2),
                            ];
                            $itemId++;
                        }
                    }
                }
            }

            $itemId = $purchaseInvoiceItem['item_id'];
            if (!empty($loadedPurchaseInvoices[$ksefNumber]) && isset($xmlPurchaseInvoiceItems[$beforeState][$itemId])) {
                $item = $xmlPurchaseInvoiceItems[$beforeState][$itemId];

                $this->Execute(
                    "UPDATE ksefinvoiceitems
                    SET net_price = ?, price = ?, net_value = ?, value = ?
                    WHERE ksef_invoice_id = ?
                        AND item_id = ?
                        AND before_state = ?",
                    [
                        $item['netprice'],
                        $item['price'],
                        $item['netvalue'],
                        $item['value'],
                        $invoiceId,
                        $itemId,
                        $beforeState,
                    ]
                );
            } else {
                if (empty($purchaseInvoiceItem['net_flag'])) {
                    $this->Execute(
                        "UPDATE ksefinvoiceitems
                        SET net_price = ?, net_value = ?
                        WHERE ksef_invoice_id = ?
                            AND item_id = ?
                            AND before_state = ?",
                        [
                            round(empty($purchaseInvoiceItem['tax_rate']) ? $purchaseInvoiceItem['price'] : (100 * $purchaseInvoiceItem['price']) / (100 + $purchaseInvoiceItem['tax_rate']), 8),
                            round(empty($purchaseInvoiceItem['tax_rate']) ? $purchaseInvoiceItem['value'] : (100 * $purchaseInvoiceItem['value']) / (100 + $purchaseInvoiceItem['tax_rate']), 2),
                            $invoiceId,
                            $itemId,
                            $beforeState,
                        ]
                    );
                } else {
                    $this->Execute(
                        "UPDATE ksefinvoiceitems
                        SET net_price = ?, price = ?, net_value = ?, value = ?
                        WHERE ksef_invoice_id = ?
                            AND item_id = ?
                            AND before_state = ?",
                        [
                            $purchaseInvoiceItem['price'],
                            round(empty($purchaseInvoiceItem['tax_rate']) ? $purchaseInvoiceItem['price'] : ($purchaseInvoiceItem['price'] * (100 + $purchaseInvoiceItem['tax_rate'])) / 100, 8),
                            $purchaseInvoiceItem['value'],
                            round(empty($purchaseInvoiceItem['tax_rate']) ? $purchaseInvoiceItem['value'] : ($purchaseInvoiceItem['value'] * (100 + $purchaseInvoiceItem['tax_rate'])) / 100, 2),
                            $invoiceId,
                            $itemId,
                            $beforeState,
                        ]
                    );
                }
            }
        }
    }
}

$this->Execute("ALTER TABLE ksefinvoiceitems ALTER COLUMN net_price SET NOT NULL");
$this->Execute("ALTER TABLE ksefinvoiceitems ALTER COLUMN net_price DROP DEFAULT");
$this->Execute("ALTER TABLE ksefinvoiceitems ALTER COLUMN net_value SET NOT NULL");
$this->Execute("ALTER TABLE ksefinvoiceitems ALTER COLUMN net_value DROP DEFAULT");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2026060100', 'dbversion'));

$this->CommitTrans();
