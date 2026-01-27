<?php

/*
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

namespace Lms\KSeF;

class KSeF
{
    const CERTIFICATE_FORMAT_UNKNOWN = 0;
    const CERTIFICATE_FORMAT_PEM = 1;
    const CERTIFICATE_FORMAT_PKCS12 = 2;

    const ENVIRONMENT_TEST = 1;
    const ENVIRONMENT_PROD = 2;

    private $db;
    private $lms;
    private $divisions = array();
    private $countries;
    private $defaultCurrency;
    private $taxes;

    private $payTypes;

    private $showOnlyAlternativeAccounts;
    private $showAllAccounts;

    public function __construct($db, $lms)
    {
        $this->db = $db;
        $this->lms = $lms;
        $this->countries = $db->GetAllByKey('SELECT * FROM countries', 'id');
        $this->defaultCurrency = \Localisation::getCurrentCurrency();
        $this->taxes = $lms->GetTaxes();

        $this->payTypes = [
            PAYTYPE_CASH => 1,
            PAYTYPE_CARD => 2,
            PAYTYPE_BANK_LOAN => 5,
            PAYTYPE_TRANSFER => 6,
            PAYTYPE_BARTER => 'barter',
            PAYTYPE_CASH_ON_DELIVERY => 'za pobraniem',
            PAYTYPE_COMPENSATION => 'kompensacja',
            PAYTYPE_CONTRACT => 'umowa',
            PAYTYPE_INSTALMENTS => 'raty',
            PAYTYPE_PAID => 'zapłacono',
            PAYTYPE_TRANSFER_CASH => 'przelew/gotówka',
        ];

        $this->showOnlyAlternativeAccounts = \ConfigHelper::checkConfig('invoices.show_only_alternative_accounts');
        $this->showAllAccounts = \ConfigHelper::checkConfig('invoices.show_all_accounts');
    }

    public function updateDelays()
    {
        $divisionDelays = $this->db->GetAllByKey(
            'SELECT
                d.id AS divisionid,
                kd.id AS delayid,
                kd.delay AS delay
            FROM divisions d
            LEFT JOIN ksefdelays kd ON kd.divisionid = d.id
            ORDER BY d.id',
            'divisionid'
        );
        if (empty($divisionDelays)) {
            $divisionDelays = [];
        }

        $uiConfigVariables = $this->db->GetAllByKey(
            'SELECT
                c.value,
                COALESCE(c.divisionid, 0) AS divisionid
            FROM uiconfig c
            WHERE section = ?
                AND var = ?
                AND disabled = ?
            ORDER BY COALESCE(c.divisionid, 0)',
            'divisionid',
            [
                'ksef',
                'delay',
                0,
            ]
        );
        if (empty($uiConfigVariables)) {
            $uiConfigVariables = [
                0 => 3600,
            ];
        }

        foreach ($divisionDelays as $divisionId => $divisionDelay) {
            $delay = isset($uiConfigVariables[$divisionId]) ? $uiConfigVariables[$divisionId]['value'] : $uiConfigVariables[0]['value'];

            if (empty($divisionDelay['delayid'])) {
                $this->db->Execute(
                    'INSERT INTO ksefdelays
                    (divisionid, delay)
                    VALUES (?, ?)',
                    [
                        $divisionId,
                        $delay,
                    ]
                );
                $divisionDelays[$divisionId]['delay'] = $delay;
            } else {
                if ($divisionDelay['delay'] != $delay) {
                    $this->db->Execute(
                        'UPDATE ksefdelays
                        SET delay = ?
                        WHERE id = ?',
                        [
                            $delay,
                            $divisionDelay['delayid'],
                        ]
                    );
                    $divisionDelays[$divisionId]['delay'] = $delay;
                }
            }
        }

        return \Utils::array_column($divisionDelays, 'delay', 'divisionid');
    }

    public static function base64Url(string $base64Data): string
    {
        return rtrim(strtr($base64Data, '+/', '-_'), '=');
    }

    public static function getQrCodeUrl(array $params): string
    {
        $url = isset($params['environment']) && $params['environment'] == self::ENVIRONMENT_TEST
            ? 'https://qr-test.ksef.mf.gov.pl/invoice'
            : 'https://qr.ksef.mf.gov.pl/invoice';
        $url .= '/' . preg_replace('/[^0-9]/', '', $params['ten'])
            . '/' . date('d-m-Y', $params['date'])
            . '/' . self::base64Url($params['hash']);

        return $url;
    }

    public static function formatInternalId($internalId)
    {
        $internalId = preg_replace('/[^0-9]/', '', $internalId);

        return substr($internalId, 0, 10) . '-' . substr($internalId, 10);
    }

    public function getInvoiceXml(array $invoice)
    {
        if (!isset($this->divisions[$invoice['divisionid']])) {
            $this->divisions[$invoice['divisionid']] = $this->lms->GetDivision($invoice['divisionid']);
        }
        $division = $this->divisions[$invoice['divisionid']];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . "<Faktura xmlns=\"http://crd.gov.pl/wzor/2025/06/25/13775/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">" . PHP_EOL
            . "\t<Naglowek>" . PHP_EOL
            . "\t\t<KodFormularza kodSystemowy=\"FA (3)\" wersjaSchemy=\"1-0E\">FA</KodFormularza>" . PHP_EOL
            . "\t\t<WariantFormularza>3</WariantFormularza>" . PHP_EOL
            . "\t\t<DataWytworzeniaFa>" . gmdate('Y-m-d\TH:i:s\Z') . "</DataWytworzeniaFa>" . PHP_EOL
            . "\t\t<SystemInfo>" . \LMS::SOFTWARE_NAME . ' ' . \LMS::SOFTWARE_VERSION . "</SystemInfo>" . PHP_EOL
            . "\t</Naglowek>" . PHP_EOL;

        $xml .= "\t<Podmiot1>" . PHP_EOL;
        $xml .= "\t\t<DaneIdentyfikacyjne>" . PHP_EOL;
        $xml .= "\t\t\t<NIP>" . preg_replace('/[^0-9]/', '', $invoice['division_ten']) . "</NIP>" . PHP_EOL;
        $xml .= "\t\t\t<Nazwa>" . htmlspecialchars($invoice['division_name']) . "</Nazwa>" . PHP_EOL;
        $xml .= "\t\t</DaneIdentyfikacyjne>" . PHP_EOL;
        $xml .= "\t\t<Adres>" . PHP_EOL;
        $xml .= "\t\t\t<KodKraju>PL</KodKraju>" . PHP_EOL;
        $xml .= "\t\t\t<AdresL1>" . htmlspecialchars($invoice['division_address']) . "</AdresL1>" . PHP_EOL;
        $xml .= "\t\t\t<AdresL2>" . htmlspecialchars((empty($invoice['division_zip']) ? '' : $invoice['division_zip'] . ' ') . $invoice['division_city']) . "</AdresL2>" . PHP_EOL;
        $xml .= "\t\t</Adres>" . PHP_EOL;

        if (!empty($division['email']) || !empty($division['phone'])) {
            $xml .= "\t\t<DaneKontaktowe>" . PHP_EOL;
            if (!empty($division['email'])) {
                $xml .= "\t\t\t<Email>" . $division['email'] . "</Email>" . PHP_EOL;
            }
            if (!empty($division['phone'])) {
                $xml .= "\t\t\t<Telefon>" . htmlspecialchars($division['phone']) . "</Telefon>" . PHP_EOL;
            }
            $xml .= "\t\t</DaneKontaktowe>" . PHP_EOL;
        }

        $xml .= "\t</Podmiot1>" . PHP_EOL;

        $ue = $foreign = false;
        $ten = preg_replace('/[\s\-]/', '', $invoice['ten']);
        if (!empty($ten)) {
            if (preg_match('/^(?<country>[A-Z]{2})(?<ten>[A-Z0-9]+)$/', $ten, $m)) {
                if (strpos($ten, 'GB') === false) {
                    $ue = true;
                } else {
                    $foreign = true;
                }
            } elseif (!empty($invoice['countryid']) && !empty($invoice['division_countryid']) && $invoice['countryid'] != $invoice['division_countryid']) {
                $foreign = true;
            }
        }

        $xml .= "\t<Podmiot2>" . PHP_EOL;

        $xml .= "\t\t<DaneIdentyfikacyjne>" . PHP_EOL;
        if ($ue) {
            $xml .= "\t\t\t<KodUE>" . $m['country'] . "</KodUE>\n";
            $xml .= "\t\t<KodVatUE>" . $m['ten'] . "</KodVatUE>\n";
        } elseif ($foreign) {
            $xml .= "\t\t\t<KodKraju>" . $m['country'] . "</KodKraju>\n";
            $xml .= "\t\t<NrID>" . $m['ten'] . "</NrID>\n";
        } elseif (empty($invoice['ten'])) {
            $xml .= "\t\t\t<BrakID>1</BrakID>" . PHP_EOL;
        } else {
            $xml .= "\t\t\t<NIP>" . preg_replace('/[^0-9]/', '', $ten) . "</NIP>" . PHP_EOL;
        }
        $xml .= "\t\t\t<Nazwa>" . htmlspecialchars($invoice['name']) . "</Nazwa>" . PHP_EOL;
        $xml .= "\t\t</DaneIdentyfikacyjne>" . PHP_EOL;

        $xml .= "\t\t<Adres>" . PHP_EOL;
        $xml .= "\t\t\t<KodKraju>" . (empty($m['country']) ? 'PL' : $m['country']) . "</KodKraju>" . PHP_EOL;
        $xml .= "\t\t\t<AdresL1>" . htmlspecialchars($invoice['address']) . "</AdresL1>" . PHP_EOL;
        $xml .= "\t\t\t<AdresL2>" . htmlspecialchars((empty($invoice['zip']) ? '' : $invoice['zip'] . ' ') . $invoice['city']) . "</AdresL2>" . PHP_EOL;
        $xml .= "\t\t</Adres>" . PHP_EOL;

        if (!empty($invoice['post_countryid']) && isset($this->countries[$invoice['post_countryid']])) {
            $countryCode = substr($this->countries[$invoice['post_countryid']]['ccode'], 3);
        } else {
            $countryCode = 'PL';
        }

        $xml .= "\t\t<AdresKoresp>" . PHP_EOL;
        $xml .= "\t\t\t<KodKraju>" . $countryCode . "</KodKraju>" . PHP_EOL;
        $xml .= "\t\t\t<AdresL1>" . htmlspecialchars($invoice['post_address']) . "</AdresL1>" . PHP_EOL;
        $xml .= "\t\t\t<AdresL2>" . htmlspecialchars((empty($invoice['post_zip']) ? '' : $invoice['post_zip'] . ' ') . $invoice['post_city']) . "</AdresL2>" . PHP_EOL;
        $xml .= "\t\t</AdresKoresp>" . PHP_EOL;

        $xml .= "\t\t<NrKlienta>" . $invoice['customerid'] . "</NrKlienta>" . PHP_EOL;

        $xml .= "\t\t<JST>" . (empty($invoice['recipient_address_id']) ? '2' : '1') . "</JST>" . PHP_EOL;
        $xml .= "\t\t<GV>2</GV>" . PHP_EOL;

        $xml .= "\t</Podmiot2>" . PHP_EOL;

        if (!empty($invoice['recipient_address_id'])) {
            $rec_ue = $rec_foreign = false;
            $rec_ten = preg_replace('/[\s\-]/', '', $invoice['recipient_ten']);
            if (!empty($rec_ten)) {
                if (preg_match('/^(?<country>[A-Z]{2})(?<ten>[A-Z0-9]+)$/', $rec_ten, $rec_m)) {
                    if (strpos($rec_ten, 'GB') === false) {
                        $rec_ue = true;
                    } else {
                        $rec_foreign = true;
                    }
                } elseif (!empty($invoice['rec_country_id']) && !empty($invoice['division_countryid']) && $invoice['rec_country_id'] != $invoice['division_countryid']) {
                    $rec_foreign = true;
                }
            }

            $xml .= "\t<Podmiot3>" . PHP_EOL;

            $xml .= "\t\t<DaneIdentyfikacyjne>" . PHP_EOL;
            if ($rec_ue) {
                $xml .= "\t\t\t<KodUE>" . $rec_m['country'] . "</KodUE>\n";
                $xml .= "\t\t<KodVatUE>" . $rec_m['ten'] . "</KodVatUE>\n";
            } elseif ($rec_foreign) {
                $xml .= "\t\t\t<KodKraju>" . $rec_m['country'] . "</KodKraju>\n";
                $xml .= "\t\t<NrID>" . $rec_m['ten'] . "</NrID>\n";
            } elseif (empty($invoice['recipient_ten'])) {
                $xml .= "\t\t\t<BrakID>1</BrakID>" . PHP_EOL;
            } elseif (check_ksef_internal_id($invoice['recipient_ten'])) {
                $xml .= "\t\t\t<IDWew>" . self::formatInternalId($invoice['recipient_ten']) . "</IDWew>" . PHP_EOL;
            } else {
                $xml .= "\t\t\t<NIP>" . preg_replace('/[^0-9]/', '', $rec_ten) . "</NIP>" . PHP_EOL;
            }
            if (!empty($invoice['rec_name'])) {
                $xml .= "\t\t\t<Nazwa>" . htmlspecialchars($invoice['rec_name']) . "</Nazwa>" . PHP_EOL;
            }
            $xml .= "\t\t</DaneIdentyfikacyjne>" . PHP_EOL;

            if (!empty($invoice['rec_country_id']) && isset($this->countries[$invoice['rec_country_id']])) {
                $recCountryCode = substr($this->countries[$invoice['rec_country_id']]['ccode'], 3);
            } else {
                $recCountryCode = 'PL';
            }

            $xml .= "\t\t<Adres>" . PHP_EOL;
            $xml .= "\t\t\t<KodKraju>" . $recCountryCode . "</KodKraju>" . PHP_EOL;
            $xml .= "\t\t\t<AdresL1>" . htmlspecialchars($invoice['rec_address']) . "</AdresL1>" . PHP_EOL;
            $xml .= "\t\t\t<AdresL2>" . htmlspecialchars((empty($invoice['rec_zip']) ? '' : $invoice['rec_zip'] . ' ') . $invoice['rec_city']) . "</AdresL2>" . PHP_EOL;
            $xml .= "\t\t</Adres>" . PHP_EOL;

            $xml .= "\t\t<Rola>8</Rola>" . PHP_EOL;

            $xml .= "\t</Podmiot3>" . PHP_EOL;
        }

        $xml .= "\t<Fa>" . PHP_EOL;

        $xml .= "\t\t<KodWaluty>" . $invoice['currency'] . "</KodWaluty>" . PHP_EOL;
        $xml .= "\t\t<P_1>" . date('Y-m-d', $invoice['cdate']) . "</P_1>" . PHP_EOL;
        //$xml .= "\t\t<P_1M></P_1M>" . PHP_EOL;
        $xml .= "\t\t<P_2>" . $invoice['fullnumber'] . "</P_2>" . PHP_EOL;

        $currency = $invoice['currency'];
        $currencyValue = $invoice['currencyvalue'];

        if ($currency != $this->defaultCurrency) {
            $xml .= "\t\t<KursWalutyZ>" . sprintf('%.6f', $currencyValue) . "</KursWalutyZ>" . PHP_EOL;
        }

        if ($invoice['type'] == DOC_CNOTE) {
            //var_dump($invoice);
        }

        $taxFree = false;
        $diffTotal = 0;

        if ($invoice['type'] == DOC_CNOTE) {
            if (isset($invoice['taxest']['23.00']) || isset($invoice['invoice']['taxest']['23.00'])) {
                $taxRate = '23.00';
            } elseif (isset($invoice['taxest']['22.00']) || isset($invoice['invoice']['taxest']['22.00'])) {
                $taxRate = '22.00';
            } else {
                $taxRate = null;
            }
            if (isset($taxRate)) {
                if (isset($invoice['taxest'][$taxRate])) {
                    $base = round(($invoice['taxest'][$taxRate]['base'] - $invoice['invoice']['taxest'][$taxRate]['base']), 2);
                    $xml .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>" . PHP_EOL;
                    $tax = round(($invoice['taxest'][$taxRate]['tax'] - $invoice['invoice']['taxest'][$taxRate]['tax']), 2);
                    $xml .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>" . PHP_EOL;
                    if ($currency != $this->defaultCurrency) {
                        $xml .= "\t\t<P_14_1W>" . sprintf('%.2f', round($tax * $currencyValue, 2)) . "</P_14_1W>" . PHP_EOL;
                    }
                    $diffTotal += $base + $tax;
                } elseif (isset($invoice['invoice']['taxest'][$taxRate])) {
                    $base = round(-$invoice['invoice']['taxest'][$taxRate]['base'], 2);
                    $xml .= "\t\t<P_13_1>" . sprintf('%.2f', $base) . "</P_13_1>" . PHP_EOL;
                    $tax = round(-$invoice['invoice']['taxest'][$taxRate]['tax'], 2);
                    $xml .= "\t\t<P_14_1>" . sprintf('%.2f', $tax) . "</P_14_1>" . PHP_EOL;
                    if ($currency != $this->defaultCurrency) {
                        $xml .= "\t\t<P_14_1W>" . sprintf('%.2f', round($tax * $currencyValue, 2)) . "</P_14_1W>" . PHP_EOL;
                    }
                    $diffTotal += $base + $tax;
                }
            }
        } else {
            if (isset($invoice['taxest']['23.00'])) {
                $taxRate = '23.00';
            } elseif (isset($invoice['taxes']['22.00'])) {
                $taxRate = '22.00';
            } else {
                $taxRate = null;
            }
            if (isset($taxRate) && isset($invoice['taxest'][$taxRate])) {
                $xml .= "\t\t<P_13_1>" . sprintf('%.2f', $invoice['taxest'][$taxRate]['base']) . "</P_13_1>" . PHP_EOL;
                $xml .= "\t\t<P_14_1>" . sprintf('%.2f', $invoice['taxest'][$taxRate]['tax']) . "</P_14_1>" . PHP_EOL;
                if ($currency != $this->defaultCurrency) {
                    $xml .= "\t\t<P_14_1W>" . sprintf('%.2f', round($invoice['taxest'][$taxRate]['tax'] * $currencyValue, 2)) . "</P_14_1W>" . PHP_EOL;
                }
            }
        }

        if ($invoice['type'] == DOC_CNOTE) {
            if (isset($invoice['taxest']['8.00']) || isset($invoice['invoice']['taxest']['8.00'])) {
                $taxRate = '8.00';
            } elseif (isset($invoice['taxest']['7.00']) || isset($invoice['invoice']['taxest']['7.00'])) {
                $taxRate = '7.00';
            } else {
                $taxRate = null;
            }
            if (isset($taxRate)) {
                if (isset($invoice['taxest'][$taxRate])) {
                    $base = round(($invoice['taxest'][$taxRate]['base'] - $invoice['invoice']['taxest'][$taxRate]['base']), 2);
                    $xml .= "\t\t<P_13_2>" . sprintf('%.2f', $base) . "</P_13_2>" . PHP_EOL;
                    $tax = round(($invoice['taxest'][$taxRate]['tax'] - $invoice['invoice']['taxest'][$taxRate]['tax']), 2);
                    $xml .= "\t\t<P_14_2>" . sprintf('%.2f', $tax) . "</P_14_2>" . PHP_EOL;
                    if ($currency != $this->defaultCurrency) {
                        $xml .= "\t\t<P_14_2W>" . sprintf('%.2f', round($tax * $currencyValue, 2)) . "</P_14_2W>" . PHP_EOL;
                    }
                    $diffTotal += $base + $tax;
                } elseif (isset($invoice['invoice']['taxest'][$taxRate])) {
                    $base = round(-$invoice['invoice']['taxest'][$taxRate]['base'], 2);
                    $xml .= "\t\t<P_13_2>" . sprintf('%.2f', $base) . "</P_13_2>" . PHP_EOL;
                    $tax = round(-$invoice['invoice']['taxest'][$taxRate]['tax'], 2);
                    $xml .= "\t\t<P_14_2>" . sprintf('%.2f', $tax) . "</P_14_2>" . PHP_EOL;
                    if ($currency != $this->defaultCurrency) {
                        $xml .= "\t\t<P_14_2W>" . sprintf('%.2f', round($tax * $currencyValue, 2)) . "</P_14_2W>" . PHP_EOL;
                    }
                    $diffTotal += $base + $tax;
                }
            }
        } else {
            if (isset($invoice['taxest']['8.00'])) {
                $taxRate = '8.00';
            } elseif (isset($invoice['taxes']['7.00'])) {
                $taxRate = '7.00';
            } else {
                $taxRate = null;
            }
            if (isset($taxRate) && isset($invoice['taxest'][$taxRate])) {
                $xml .= "\t\t<P_13_2>" . sprintf('%.2f', $invoice['taxest'][$taxRate]['base']) . "</P_13_2>" . PHP_EOL;
                $xml .= "\t\t<P_14_2>" . sprintf('%.2f', $invoice['taxest'][$taxRate]['tax']) . "</P_14_2>" . PHP_EOL;
                if ($currency != $this->defaultCurrency) {
                    $xml .= "\t\t<P_14_2W>" . sprintf('%.2f', round($invoice['taxest'][$taxRate]['tax'] * $currencyValue, 2)) . "</P_14_2W>" . PHP_EOL;
                }
            }
        }

        if ($invoice['type'] == DOC_CNOTE) {
            if (isset($invoice['taxest']['5.00']) || isset($invoice['invoice']['taxest']['5.00'])) {
                $taxRate = '5.00';
            } else {
                $taxRate = null;
            }
            if (isset($taxRate)) {
                if (isset($invoice['taxest'][$taxRate])) {
                    $base = round(($invoice['taxest'][$taxRate]['base'] - $invoice['invoice']['taxest'][$taxRate]['base']), 2);
                    $xml .= "\t\t<P_13_3>" . sprintf('%.2f', $base) . "</P_13_3>" . PHP_EOL;
                    $tax = round(($invoice['taxest'][$taxRate]['tax'] - $invoice['invoice']['taxest'][$taxRate]['tax']), 2);
                    $xml .= "\t\t<P_14_3>" . sprintf('%.2f', $tax) . "</P_14_3>" . PHP_EOL;
                    if ($currency != $this->defaultCurrency) {
                        $xml .= "\t\t<P_14_3W>" . sprintf('%.2f', round($tax * $currencyValue, 2)) . "</P_14_3W>" . PHP_EOL;
                    }
                    $diffTotal += $base + $tax;
                } elseif (isset($invoice['invoice']['taxest'][$taxRate])) {
                    $base = round(-$invoice['invoice']['taxest'][$taxRate]['base'], 2);
                    $xml .= "\t\t<P_13_3>" . sprintf('%.2f', $base) . "</P_13_3>" . PHP_EOL;
                    $tax = round(-$invoice['invoice']['taxest'][$taxRate]['tax'], 2);
                    $xml .= "\t\t<P_14_3>" . sprintf('%.2f', $tax) . "</P_14_3>" . PHP_EOL;
                    if ($currency != $this->defaultCurrency) {
                        $xml .= "\t\t<P_14_3W>" . sprintf('%.2f', round($tax * $currencyValue, 2)) . "</P_14_3W>" . PHP_EOL;
                    }
                    $diffTotal += $base + $tax;
                }
            }
        } else {
            if (isset($invoice['taxest']['5.00'])) {
                $taxRate = '5.00';
            } else {
                $taxRate = null;
            }
            if (isset($taxRate) && isset($invoice['taxest'][$taxRate])) {
                $xml .= "\t\t<P_13_3>" . sprintf('%.2f', $invoice['taxest'][$taxRate]['base']) . "</P_13_3>" . PHP_EOL;
                $xml .= "\t\t<P_14_3>" . sprintf('%.2f', $invoice['taxest'][$taxRate]['tax']) . "</P_14_3>" . PHP_EOL;
                if ($currency != $this->defaultCurrency) {
                    $xml .= "\t\t<P_14_3W>" . sprintf('%.2f', round($invoice['taxest'][$taxRate]['tax'] * $currencyValue, 2)) . "</P_14_3W>" . PHP_EOL;
                }
            }
        }

        if (!$foreign) {
            if ($invoice['type'] == DOC_CNOTE) {
                if (isset($invoice['taxest']['0.00']) || isset($invoice['invoice']['taxest']['0.00'])) {
                    $taxRate = '0.00';
                    if (isset($invoice['taxest'][$taxRate])) {
                        $base = round(($invoice['taxest'][$taxRate]['base'] - $invoice['invoice']['taxest'][$taxRate]['base']), 2);
                        $xml .= "\t\t<P_13_6_1>" . sprintf('%.2f', $base) . "</P_13_6_1>" . PHP_EOL;
                        $diffTotal += $base;
                    } elseif (isset($invoice['invoice']['taxest'][$taxRate])) {
                        $base = round(-$invoice['invoice']['taxest'][$taxRate]['base'], 2);
                        $xml .= "\t\t<P_13_6_1>" . sprintf('%.2f', $base) . "</P_13_6_1>" . PHP_EOL;
                        $diffTotal += $base;
                    }
                }
            } else {
                if (isset($invoice['taxest']['0.00'])) {
                    $xml .= "\t\t<P_13_6_1>" . sprintf('%.2f', $invoice['taxest']['0.00']['base']) . "</P_13_6_1>" . PHP_EOL;
                }
            }
        }

        if ($invoice['type'] == DOC_CNOTE) {
            if (isset($invoice['taxest']['-1']) || isset($invoice['invoice']['taxest']['-1'])) {
                $taxRate = '-1';
                if (isset($invoice['taxest'][$taxRate])) {
                    $base = round(($invoice['taxest'][$taxRate]['base'] - $invoice['invoice']['taxest'][$taxRate]['base']), 2);
                } elseif (isset($invoice['invoice']['taxest'][$taxRate])) {
                    $base = round(-$invoice['invoice']['taxest'][$taxRate]['base'], 2);
                } else {
                    $base = null;
                }
                if (isset($base)) {
                    if ($ue) {
                        $xml .= "\t\t<P_13_6_2>" . sprintf('%.2f', $base) . "</P_13_6_2>" . PHP_EOL;
                    } elseif ($foreign) {
                        $xml .= "\t\t<P_13_6_3>" . sprintf('%.2f', $base) . "</P_13_6_3>" . PHP_EOL;
                    } else {
                        $xml .= "\t\t<P_13_7>" . sprintf('%.2f', $base) . "</P_13_7>" . PHP_EOL;
                    }
                    $diffTotal += $base;
                }
            }
        } else {
            if (isset($invoice['taxest']['-1'])) {
                if ($ue) {
                    $xml .= "\t\t<P_13_6_2>" . sprintf('%.2f', $invoice['taxest']['-1']['base']) . "</P_13_6_2>" . PHP_EOL;
                } elseif ($foreign) {
                    $xml .= "\t\t<P_13_6_3>" . sprintf('%.2f', $invoice['taxest']['-1']['base']) . "</P_13_6_3>" . PHP_EOL;
                } else {
                    $xml .= "\t\t<P_13_7>" . sprintf('%.2f', $invoice['taxest']['-1']['base']) . "</P_13_7>" . PHP_EOL;
                }
                $taxFree = true;
            }
        }

        if ($invoice['type'] == DOC_CNOTE) {
            if (isset($invoice['taxest']['-2']) || isset($invoice['invoice']['taxest']['-2'])) {
                $taxRate = '-2';
                if (isset($invoice['taxest'][$taxRate])) {
                    $base = round(($invoice['taxest'][$taxRate]['base'] - $invoice['invoice']['taxest'][$taxRate]['base']), 2);
                } elseif (isset($invoice['invoice']['taxest'][$taxRate])) {
                    $base = round(-$invoice['invoice']['taxest'][$taxRate]['base'], 2);
                } else {
                    $base = null;
                }
                if (isset($base)) {
                    $xml .= "\t\t<P_13_10>" . sprintf('%.2f', $base) . "</P_13_10>" . PHP_EOL;
                    $diffTotal += $base;
                }
            }
        } else {
            if (isset($invoice['taxest']['-2'])) {
                $xml .= "\t\t<P_13_10>" . sprintf('%.2f', $invoice['taxest']['-2']['base']) . "</P_13_10>" . PHP_EOL;
            }
        }

        if ($invoice['type'] == DOC_CNOTE) {
            $xml .= "\t\t<P_15>" . sprintf('%.2f', $diffTotal) . "</P_15>" . PHP_EOL;
        } else {
            $xml .= "\t\t<P_15>" . sprintf('%.2f', $invoice['total']) . "</P_15>" . PHP_EOL;
        }

        $xml .= "\t\t<Adnotacje>" . PHP_EOL;
        $xml .= "\t\t\t<P_16>2</P_16>" . PHP_EOL;
        $xml .= "\t\t\t<P_17>2</P_17>" . PHP_EOL;
        $xml .= "\t\t\t<P_18>" . (isset($invoice['taxest']['-2']) ? '1' : '2') . "</P_18>" . PHP_EOL;
        $xml .= "\t\t\t<P_18A>" . ($invoice['total'] * $currencyValue >= 15000 ? '1' : '2') . "</P_18A>" . PHP_EOL;
        $xml .= "\t\t\t<Zwolnienie>" . PHP_EOL;
        if ($taxFree) {
            $xml .= "\t\t\t\t<P_19>1</P_19>" . PHP_EOL;
            if ($ue) {
                $xml .= "\t\t\t\t<P_19B>sprzedaż wewnątrzwspólnotowa</P_19B>" . PHP_EOL;
            } elseif ($foreign) {
                $xml .= "\t\t\t\t<P_19C>eksport</P_19C>" . PHP_EOL;
            } else {
                $xml .= "\t\t\t\t<P_19A>specustawa</P_19A>" . PHP_EOL;
            }
        } else {
            $xml .= "\t\t\t\t<P_19N>1</P_19N>" . PHP_EOL;
        }
        $xml .= "\t\t\t</Zwolnienie>" . PHP_EOL;
        $xml .= "\t\t\t<NoweSrodkiTransportu>" . PHP_EOL;
        $xml .= "\t\t\t\t<P_22N>1</P_22N>" . PHP_EOL;
        $xml .= "\t\t\t</NoweSrodkiTransportu>" . PHP_EOL;
        $xml .= "\t\t\t<P_23>2</P_23>" . PHP_EOL;
        $xml .= "\t\t\t<PMarzy>" . PHP_EOL;
        $xml .= "\t\t\t\t<P_PMarzyN>1</P_PMarzyN>" . PHP_EOL;
        $xml .= "\t\t\t</PMarzy>" . PHP_EOL;
        $xml .= "\t\t</Adnotacje>" . PHP_EOL;

        if ($invoice['type'] == DOC_CNOTE) {
            $xml .= "\t\t<RodzajFaktury>KOR</RodzajFaktury>" . PHP_EOL;
            if (!empty($invoice['reason'])) {
                $xml .= "\t\t<PrzyczynaKorekty>" . htmlspecialchars($invoice['reason']) . "</PrzyczynaKorekty>" . PHP_EOL;
            }
            $xml .= "\t\t<TypKorekty>2</TypKorekty>" . PHP_EOL;

            $xml .= "\t\t<DaneFaKorygowanej>" . PHP_EOL;
            $xml .= "\t\t\t<DataWystFaKorygowanej>" . date('Y-m-d', $invoice['invoice']['cdate']) . "</DataWystFaKorygowanej>" . PHP_EOL;
            $xml .= "\t\t\t<NrFaKorygowanej>" . $invoice['invoice']['fullnumber'] . "</NrFaKorygowanej>" . PHP_EOL;
            if (!empty($invoice['invoice']['ksefnumber'])) {
                $xml .= "\t\t\t<NrKSeF>1</NrKSeF>" . PHP_EOL;
                $xml .= "\t\t\t<NrKSeFFaKorygowanej>" . $invoice['invoice']['ksefnumber'] . "</NrKSeFFaKorygowanej>" . PHP_EOL;
                $xml .= "\t\t\t<OkresFaKorygowanej>" . date('Y-m', $invoice['invoice']['sdate']) . "</OkresFaKorygowanej>" . PHP_EOL;
            } else {
                $xml .= "\t\t\t<NrKSeFN>1</NrKSeFN>" . PHP_EOL;
            }
            $xml .= "\t\t</DaneFaKorygowanej>" . PHP_EOL;
        } else {
            $xml .= "\t\t<RodzajFaktury>VAT</RodzajFaktury>" . PHP_EOL;
        }

        if (!empty($invoice['flags'][DOC_FLAG_RECEIPT])) {
            $xml .= "\t\t<FP>1</FP>" . PHP_EOL;
        }
        if (!empty($invoice['flags'][DOC_FLAG_RELATED_ENTITY])) {
            $xml .= "\t\t<TP>1</TP>" . PHP_EOL;
        }

        foreach ($invoice['content'] as $position) {
            $xml .= "\t\t<FaWiersz>" . PHP_EOL;
            $xml .= "\t\t\t<NrWierszaFa>" . $position['itemid'] . "</NrWierszaFa>" . PHP_EOL;
            $xml .= "\t\t\t<P_7>" . htmlspecialchars($position['description']) . "</P_7>" . PHP_EOL;
            if (!empty($position['tariffid'])) {
                $xml .= "\t\t\t<Indeks>" . $position['tariffid'] . "</Indeks>" . PHP_EOL;
            }
            if (!empty($position['prodid'])) {
                $xml .= "\t\t\t<PKWiU>" . $position['prodid'] . "</PKWiU>" . PHP_EOL;
            }
            if (!empty($position['content'])) {
                $xml .= "\t\t\t<P_8A>" . $position['content'] . "</P_8A>" . PHP_EOL;
            }
            if ($invoice['type'] == DOC_CNOTE) {
                $xml .= "\t\t\t<P_8B>" . sprintf('%.6f', $position['diff_count']) . "</P_8B>" . PHP_EOL;
                if (empty($invoice['netflag'])) {
                    $xml .= "\t\t\t<P_9B>" . sprintf('%.6f', $position['diff_grossprice']) . "</P_9B>" . PHP_EOL;
                } else {
                    $xml .= "\t\t\t<P_9A>" . sprintf('%.6f', $position['diff_netprice']) . "</P_9A>" . PHP_EOL;
                }
                if (empty($invoice['netflag'])) {
                    $xml .= "\t\t\t<P_11A>" . sprintf('%.2f', $position['diff_netvalue']) . "</P_11A>" . PHP_EOL;
                } else {
                    $xml .= "\t\t\t<P_11>" . sprintf('%.2f', $position['diff_grossvalue']) . "</P_11>" . PHP_EOL;
                }
                $xml .= "\t\t\t<P_11Vat>" . sprintf('%.2f', $position['diff_taxvalue']) . "</P_11Vat>" . PHP_EOL;
            } else {
                $xml .= "\t\t\t<P_8B>" . sprintf('%.6f', $position['count']) . "</P_8B>" . PHP_EOL;
                if (empty($invoice['netflag'])) {
                    $xml .= "\t\t\t<P_9B>" . sprintf('%.6f', $position['grossprice']) . "</P_9B>" . PHP_EOL;
                } else {
                    $xml .= "\t\t\t<P_9A>" . sprintf('%.6f', $position['netprice']) . "</P_9A>" . PHP_EOL;
                }
                if (empty($invoice['netflag'])) {
                    $xml .= "\t\t\t<P_11A>" . sprintf('%.2f', $position['total']) . "</P_11A>" . PHP_EOL;
                } else {
                    $xml .= "\t\t\t<P_11>" . sprintf('%.2f', $position['totalbase']) . "</P_11>" . PHP_EOL;
                }
                $xml .= "\t\t\t<P_11Vat>" . sprintf('%.2f', $position['totaltax']) . "</P_11Vat>" . PHP_EOL;
            }

            $tax = $this->taxes[$position['taxid']];
            if (empty($tax['reversecharge'])) {
                if ($tax['value'] > 0) {
                    $taxRate = round($tax['value']);
                } elseif (empty($tax['taxed'])) {
                    if ($ue) {
                        $taxRate = '0 WDT';
                    } elseif ($foreign) {
                        $taxRate = '0 EX';
                    } else {
                        $taxRate = 'zw';
                    }
                } else {
                    $taxRate = '0 KR';
                }
            } else {
                $taxRate = 'oo';
            }
            $xml .= "\t\t\t<P_12>" . $taxRate . "</P_12>" . PHP_EOL;

            if (!empty($position['taxcategory'])) {
                $xml .= "\t\t\t<GTU>GTU_" . sprintf('%02d', $position['taxcategory']) . "</GTU>" . PHP_EOL;
            }

            if ($currency != $this->defaultCurrency) {
                $xml .= "\t\t\t<KursWaluty>" . sprintf('%.6f', $currencyValue) . "</KursWaluty>" . PHP_EOL;
            }

            $xml .= "\t\t</FaWiersz>" . PHP_EOL;
        }

        //$balance = $invoice['customerbalance'];
        $balance = $this->lms->getCustomerBalance($invoice['customerid'], $invoice['cdate']);

        if (!empty($diffTotal)) {
            $total = $diffTotal;
        } else {
            $total = $invoice['total'];
        }

        $xml .= "\t\t<Rozliczenie>" . PHP_EOL;
        if (!empty($balance)) {
            if ($balance < 0) {
                $xml .= "\t\t\t<Obciazenia>" . PHP_EOL;
                $xml .= "\t\t\t\t<Kwota>" . sprintf('%.2f', abs($balance)) . "</Kwota>" . PHP_EOL;
                $xml .= "\t\t\t\t<Powod>dotychczasowa niedopłata</Powod>" . PHP_EOL;
                $xml .= "\t\t\t</Obciazenia>" . PHP_EOL;
                $balance -= $total;
                if ($total > 0) {
                    $xml .= "\t\t\t<Obciazenia>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Kwota>" . sprintf('%.2f', $total) . "</Kwota>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Powod>dokument " . $invoice['fullnumber'] . "</Powod>" . PHP_EOL;
                    $xml .= "\t\t\t</Obciazenia>" . PHP_EOL;
                }
                $xml .= "\t\t\t<SumaObciazen>" . sprintf('%.2f', abs($balance)) . "</SumaObciazen>" . PHP_EOL;
                if ($total < 0) {
                    $xml .= "\t\t\t<Odliczenia>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Kwota>" . sprintf('%.2f', abs($total)) . "</Kwota>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Powod>dokument " . $invoice['fullnumber'] . "</Powod>" . PHP_EOL;
                    $xml .= "\t\t\t</Odliczenia>" . PHP_EOL;
                    $xml .= "\t\t\t<SumaOdliczen>" . sprintf('%.2f', abs($total)) . "</SumaOdliczen>" . PHP_EOL;
                }
            } else {
                $xml .= "\t\t\t<Odliczenia>" . PHP_EOL;
                $xml .= "\t\t\t\t<Kwota>" . sprintf('%.2f', $balance) . "</Kwota>" . PHP_EOL;
                $xml .= "\t\t\t\t<Powod>dotychczasowa nadpłata</Powod>" . PHP_EOL;
                $xml .= "\t\t\t</Odliczenia>" . PHP_EOL;
                $balance -= $total;
                if ($total < 0) {
                    $xml .= "\t\t\t<Odliczenia>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Kwota>" . sprintf('%.2f', abs($total)) . "</Kwota>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Powod>dokument " . $invoice['fullnumber'] . "</Powod>" . PHP_EOL;
                    $xml .= "\t\t\t</Odliczenia>" . PHP_EOL;
                }
                $xml .= "\t\t\t<SumaOdliczen>" . sprintf('%.2f', abs($balance)) . "</SumaOdliczen>" . PHP_EOL;
                if ($total > 0) {
                    $xml .= "\t\t\t<Obciazenia>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Kwota>" . sprintf('%.2f', $total) . "</Kwota>" . PHP_EOL;
                    $xml .= "\t\t\t\t<Powod>dokument " . $invoice['fullnumber'] . "</Powod>" . PHP_EOL;
                    $xml .= "\t\t\t</Obciazenia>" . PHP_EOL;
                    $xml .= "\t\t\t<SumaObciazen>" . sprintf('%.2f', $total) . "</SumaObciazen>" . PHP_EOL;
                }
            }
            if ($balance >= 0) {
                $xml .= "\t\t\t<DoRozliczenia>" . sprintf('%.2f', $balance) . "</DoRozliczenia>" . PHP_EOL;
            } else {
                $xml .= "\t\t\t<DoZaplaty>" . sprintf('%.2f', abs($balance)) . "</DoZaplaty>" . PHP_EOL;
            }
        }
        $xml .= "\t\t</Rozliczenie>" . PHP_EOL;

        $xml .= "\t\t<Platnosc>" . PHP_EOL;
        $xml .= "\t\t\t<TerminPlatnosci>" . PHP_EOL;
        $xml .= "\t\t\t\t<Termin>" . date('Y-m-d', $invoice['pdate']) . "</Termin>" . PHP_EOL;
        $xml .= "\t\t\t</TerminPlatnosci>" . PHP_EOL;
        if (!isset($this->payTypes[$invoice['paytype']]) || !is_int($this->payTypes[$invoice['paytype']])) {
            $xml .= "\t\t\t<PlatnoscInna>1</PlatnoscInna>" . PHP_EOL;
            $xml .= "\t\t\t<OpisPlatnosci>" . ($this->payTypes[$invoice['paytype']] ?? 'inna') . "</OpisPlatnosci>" . PHP_EOL;
        } else {
            $xml .= "\t\t\t<FormaPlatnosci>" . $this->payTypes[$invoice['paytype']] . "</FormaPlatnosci>" . PHP_EOL;
        }

        if (!$this->showOnlyAlternativeAccounts || empty($invoice['bankaccounts'])) {
            $accounts = array(bankaccount($invoice['customerid'], $invoice['account'], $invoice['export']));
        } else {
            $accounts = array();
        }
        if ($this->showAllAccounts || $this->showOnlyAlternativeAccounts) {
            $accounts = array_merge($accounts, $invoice['bankaccounts']);
        }
        foreach ($accounts as $account) {
            $xml .= "\t\t\t<RachunekBankowy>" . PHP_EOL;
            $xml .= "\t\t\t\t<NrRB>". format_bankaccount($account, $invoice['export']) . "</NrRB>" . PHP_EOL;
            $xml .= "\t\t\t</RachunekBankowy>" . PHP_EOL;
        }
        unset($account);

        $xml .= "\t\t</Platnosc>" . PHP_EOL;

        $xml .= "\t</Fa>" . PHP_EOL;

        $footerXml = '';

        if (!empty($invoice['division_footer'])) {
            $tmp = $invoice['division_footer'];

            $tmp = str_replace(
                array(
                    '%bankaccount',
                    '%bankname',
                    '%extid',
                ),
                array(
                    implode("\n", $accounts),
                    $invoice['div_bank'] ?? '',
                    $invoice['extid'] ?? '-',
                ),
                $tmp
            );

            $footerXml .= "\t\t<Informacje>" . PHP_EOL;
            $footerXml .= "\t\t\t<StopkaFaktury>" . htmlspecialchars($tmp) . "</StopkaFaktury>" . PHP_EOL;
            $footerXml .= "\t\t</Informacje>" . PHP_EOL;
        }

        $registryXml = '';
        if (!empty($division['rbe']) && preg_match('/^\d{10}$/', $division['rbe'])) {
            $registryXml .= "\t\t\t<KRS>" . $division['rbe'] . "</KRS>" . PHP_EOL;
        }
        if (!empty($division['regon'])) {
            $registryXml .= "\t\t\t<REGON>" . $division['regon'] . "</REGON>" . PHP_EOL;
        }

        if (!empty($registryXml)) {
            $footerXml .= "\t\t<Rejestry>" . PHP_EOL;
            $footerXml .= $registryXml;
            $footerXml .= "\t\t</Rejestry>" . PHP_EOL;
        }

        if (!empty($footerXml)) {
            $xml .= "\t<Stopka>" . PHP_EOL;
            $xml .= $footerXml;
            $xml .= "\t</Stopka>" . PHP_EOL;
        }

        $xml .= "</Faktura>" . PHP_EOL;

        return $xml;
    }

    private function zipEntryOverheadBytes(string $filename): int
    {
        // Przybliżony narzut ZIP per entry (local header + central dir + name).
        return 160 + strlen($filename);
    }

    /**
     * Buduje ZIP z listy plików XML. Zwraca [zipBinary, zipBytes].
     */
    public function makeZipBinaryFromFiles(array $files, int $idx, ?string $debugZipDir = null): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Brak klasy ZipArchive (ext-zip). Zainstaluj/aktywuj ext-zip.');
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'ksef_zip_');
        if ($tmpBase === false) {
            throw new \RuntimeException('Nie udało się utworzyć pliku tymczasowego dla ZIP.');
        }

        $zipPath = $tmpBase . '.zip';
        @unlink($tmpBase);

        $za = new \ZipArchive();
        if ($za->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Nie udało się otworzyć ZIP do zapisu: {$zipPath}");
        }

        foreach ($files as $p) {
            $za->addFromString($p['name'], $p['xml']);
        }

        $za->close();

        $bytes = filesize($zipPath);
        if ($bytes === false) {
            @unlink($zipPath);
            throw new \RuntimeException("Nie udało się ustalić rozmiaru ZIP: {$zipPath}");
        }

        $bin = file_get_contents($zipPath);
        if ($bin === false || $bin === '') {
            @unlink($zipPath);
            throw new \RuntimeException("Nie udało się wczytać wygenerowanego ZIP: {$zipPath}");
        }

        if ($debugZipDir) {
            @mkdir($debugZipDir, 0775, true);
            $dst = rtrim($debugZipDir, '/') . "/batch-{$idx}.zip";
            file_put_contents($dst, $bin);
        }

        @unlink($zipPath);

        return [$bin, (int)$bytes];
    }

    /**
     * Inteligentne budowanie paczek ZIP <= $maxZipBytes.
     *
     * Etap A: szybkie grupowanie po sumie rozmiarów XML (bez budowania ZIP).
     * Etap B: budowanie ZIP tylko raz na paczkę.
     * Etap C: jeśli ZIP przekroczy limit -> przycięcie paczki przez wyszukiwanie binarne.
     *
     * Zwraca:
     * [
     *   ['zip' => (string), 'files' => [paths...], 'zip_bytes' => int, 'index' => int],
     *   ...
     * ]
     */
    public function buildZipPackagesFromXmlDocuments(array $xmlDocuments, int $maxZipBytes, ?string $debugZipDir = null): array
    {
        if ($maxZipBytes < 1024 * 1024) {
            throw new \RuntimeException('maxZipBytes jest podejrzanie mały (ustaw co najmniej kilka MB).');
        }

        // Bufor bezpieczeństwa na metadane ZIP / gorszą kompresję.
        $zipSafetyMargin = (int) min(8 * 1024 * 1024, max(512 * 1024, $maxZipBytes * 0.05)); // 5% lub max 8MB, min 512KB

        // --- Etap A: wstępne grupowanie O(n) bez ZIP
        $preGroups = [];
        $cur = [];
        $curEstimate = 0;

        foreach ($xmlDocuments as $index => $xmlDocument) {
            $xmlName = 'document-' . ($index + 1) . '.xml';
            $xmlSize = strlen($xmlDocument);

            $est = strlen($xmlDocument) + $this->zipEntryOverheadBytes($xmlName);

            if (empty($cur)) {
                $cur[] = [
                    'xml' => $xmlDocument,
                    'size' => $xmlSize,
                    'name' => $xmlName,
                ];
                $curEstimate = $est;
                continue;
            }

            if (($curEstimate + $est) <= ($maxZipBytes - $zipSafetyMargin)) {
                $cur[] = [
                    'xml' => $xmlDocument,
                    'size' => $xmlSize,
                    'name' => $xmlName,
                ];
                $curEstimate += $est;
            } else {
                $preGroups[] = $cur;
                $cur = [
                    [
                        'xml' => $xmlDocument,
                        'size' => $xmlSize,
                        'name' => $xmlName,
                    ]
                ];
                $curEstimate = $est;
            }
        }
        if (!empty($cur)) {
            $preGroups[] = $cur;
        }

        // --- Etap B/C: buduj ZIP per grupa + binsearch jeśli przekracza limit
        $packages = [];
        $idx = 1;

        for ($g = 0; $g < count($preGroups); $g++) {
            $group = $preGroups[$g];

            [$zipBin, $zipBytes] = $this->makeZipBinaryFromFiles($group, $idx, $debugZipDir);

            if ($zipBytes <= $maxZipBytes) {
                $packages[] = [
                    'zip'       => $zipBin,
                    'documents' => $group,
                    'zip_bytes' => $zipBytes,
                    'index'     => $idx,
                ];
                $idx++;
                continue;
            }

            // sanity: pojedynczy plik przekracza limit
            if (count($group) === 1) {
                throw new \RuntimeException(
                    "Pojedynczy XML po spakowaniu przekracza limit paczki: {$group[0]} (zipBytes={$zipBytes}, limit={$maxZipBytes})"
                );
            }

            // Binsearch: największy prefix, który mieści się w limicie
            $lo = 1;
            $hi = count($group);

            $bestBin = null;
            $bestBytes = null;
            $bestCount = 0;

            while ($lo <= $hi) {
                $mid = intdiv($lo + $hi, 2);
                [$tryBin, $tryBytes] = $this->makeZipBinaryFromFiles(array_slice($group, 0, $mid), $idx, $debugZipDir);

                if ($tryBytes <= $maxZipBytes) {
                    $bestBin = $tryBin;
                    $bestBytes = $tryBytes;
                    $bestCount = $mid;
                    $lo = $mid + 1;
                } else {
                    $hi = $mid - 1;
                }
            }

            if ($bestCount <= 0) {
                throw new \RuntimeException('Nie udało się dopasować paczki ZIP do limitu (nieoczekiwane).');
            }

            $bestDocuments = array_slice($group, 0, $bestCount);
            $packages[] = [
                'zip'       => $bestBin,
                'files'     => $bestDocuments,
                'zip_bytes' => (int)$bestBytes,
                'index'     => $idx,
            ];
            $idx++;

            // Resztę dorzuć jako kolejne grupy (z szybkim cięciem jak w Etapie A)
            $remaining = array_slice($group, $bestCount);
            if ($remaining) {
                $remDocuments = [];
                foreach ($remaining as $rd) {
                    $remDocuments[] = $rd;
                }

                $cur2 = [];
                $curEstimate2 = 0;
                foreach ($remDocuments as $rd) {
                    $est2 = $rd['size'] + $this->zipEntryOverheadBytes($rd['name']);
                    if (empty($cur2)) {
                        $cur2 = [$rd];
                        $curEstimate2 = $est2;
                        continue;
                    }
                    if (($curEstimate2 + $est2) <= ($maxZipBytes - $zipSafetyMargin)) {
                        $cur2[] = $rd;
                        $curEstimate2 += $est2;
                    } else {
                        $preGroups[] = $cur2;
                        $cur2 = [$rd];
                        $curEstimate2 = $est2;
                    }
                }
                if ($cur2) {
                    $preGroups[] = $cur2;
                }
            }
        }

        return $packages;
    }

    public static function detectCertificateFormat($certPath, $certPass)
    {
        $content = file_get_contents($certPath);

        if (preg_match(
            '/-----BEGIN (CERTIFICATE|PRIVATE KEY|RSA PRIVATE KEY|EC PRIVATE KEY)-----/',
            $content
        ) && @openssl_x509_read($content) !== false) {
            return self::CERTIFICATE_FORMAT_PEM;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($certPath);

        if (!in_array($mime, [
            'application/x-pkcs12',
            'application/pkcs12',
            'application/octet-stream',
        ], true)) {
            return self::CERTIFICATE_FORMAT_UNKNOWN;
        }

        if (empty($certPass)) {
            return self::CERTIFICATE_FORMAT_UNKNOWN;
        }

        $certs = null;
        if (!openssl_pkcs12_read(file_get_contents($certPath), $certs, $certPass)) {
            return self::CERTIFICATE_FORMAT_UNKNOWN;
        }

        return self::CERTIFICATE_FORMAT_PKCS12;
    }

    /** ECDSA sign (SHA-256) -> DER signature bytes (ASN.1 DER SEQUENCE of r,s) */
    private static function ecdsaSignDerSha256(string $rawDataToSign, \OpenSSLAsymmetricKey $privateKey): string
    {
        $der = '';
        if (!openssl_sign($rawDataToSign, $der, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException("openssl_sign() failed: " . (openssl_error_string() ?: 'unknown error'));
        }
        return $der;
    }

    /**
     * Parse DER ECDSA signature (SEQUENCE { INTEGER r; INTEGER s })
     * Returns [rBytes, sBytes] as unsigned big-endian (variable length, no leading sign byte).
     */
    private static function derEcdsaToRS(string $derSig): array
    {
        $i = 0;

        $readByte = function () use (&$derSig, &$i): int {
            if ($i >= strlen($derSig)) {
                throw new \RuntimeException("DER parse: unexpected EOF");
            }
            return ord($derSig[$i++]);
        };

        $readLen = function () use ($readByte): int {
            $len = $readByte();
            if (($len & 0x80) === 0) {
                return $len;
            }
            $num = $len & 0x7F;
            if ($num === 0 || $num > 4) {
                throw new \RuntimeException("DER parse: invalid length");
            }
            $val = 0;
            for ($k = 0; $k < $num; $k++) {
                $val = ($val << 8) | $readByte();
            }
            return $val;
        };

        $expectTag = function (int $tag) use ($readByte): void {
            $t = $readByte();
            if ($t !== $tag) {
                throw new \RuntimeException(sprintf("DER parse: expected tag 0x%02X, got 0x%02X", $tag, $t));
            }
        };

        // SEQUENCE
        $expectTag(0x30);
        $seqLen = $readLen();
        if ($seqLen > (strlen($derSig) - $i)) {
            throw new \RuntimeException("DER parse: sequence length out of range");
        }

        // INTEGER r
        $expectTag(0x02);
        $rLen = $readLen();
        $r = substr($derSig, $i, $rLen);
        $i += $rLen;

        // INTEGER s
        $expectTag(0x02);
        $sLen = $readLen();
        $s = substr($derSig, $i, $sLen);
        $i += $sLen;

        // Drop possible leading 0x00 that forces a positive INTEGER
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        if ($r === '' || $s === '') {
            throw new \RuntimeException("DER parse: empty r or s");
        }

        return [$r, $s];
    }

    /** DER -> IEEE P1363 fixed field concat (R||S). For P-256 => 32+32 bytes. */
    private static function derEcdsaToP1363(string $derSig, int $fieldBytes = 32): string
    {
        [$r, $s] = self::derEcdsaToRS($derSig);

        if (strlen($r) > $fieldBytes || strlen($s) > $fieldBytes) {
            throw new \RuntimeException("r or s longer than {$fieldBytes} bytes; wrong curve?");
        }

        $rFixed = str_pad($r, $fieldBytes, "\x00", STR_PAD_LEFT);
        $sFixed = str_pad($s, $fieldBytes, "\x00", STR_PAD_LEFT);

        return $rFixed . $sFixed; // 64 bytes for P-256
    }

    public static function getCertificateQrCodeUrl(array $params): string
    {
        static $certs = [];

        $divisionId = $params['divisionid'];

        if (!isset($certs[$divisionId])) {
            $certFile = \ConfigHelper::getConfig('ksef.certificate');
            $certFile = preg_replace('/\.[^.]+$/', '', $certFile);
            $certFile .= '-offline.pem';

            if (!is_readable($certFile)) {
                return '';
            }

            $cert = file_get_contents($certFile);

            $privKey = openssl_pkey_get_private($cert);
            if ($privKey === false) {
                throw new \RuntimeException("openssl_pkey_get_private() failed: " . (openssl_error_string() ?: 'unknown error'));
            }

            $privKeyDetails = openssl_pkey_get_details($privKey);
            if (!$privKeyDetails || ($privKeyDetails['type'] ?? null) !== OPENSSL_KEYTYPE_EC) {
                throw new \RuntimeException("Private key is not EC (ECDSA).");
            }

            $pubKey = openssl_x509_read($cert);
            if ($pubKey === false) {
                throw new \RuntimeException("openssl_x509_read() failed: " . (openssl_error_string() ?: 'unknown error'));
            }

            $pubKeyDetails = openssl_x509_parse($pubKey);
            if (!$pubKeyDetails) {
                throw new \RuntimeException("openssl_x509_parse() failed: " . (openssl_error_string() ?: 'unknown error'));
            }

            $certs[$divisionId] = [
                'serialNumber' => $pubKeyDetails['serialNumberHex'],
                'privKey' => $privKey,
            ];
        }

        extract($certs[$divisionId]);

        $ten = preg_replace('/[^0-9]/', '', $params['ten']);

        $url = isset($params['environment']) && $params['environment'] == self::ENVIRONMENT_TEST
            ? 'qr-test.ksef.mf.gov.pl/certificate/Nip'
            : 'qr.ksef.mf.gov.pl/certificate/Nip';
        $url .= '/' . $ten . '/' . $ten
            . '/' . $serialNumber
            . '/' . self::base64Url($params['hash']);

        $signature = self::ecdsaSignDerSha256($url, $privKey);

        // IEEE P1363 (R||S) formatted signature
        $p1363 = self::derEcdsaToP1363($signature, 32);

        return 'https://' . $url . '/' . self::base64Url(base64_encode($p1363));
    }
}
