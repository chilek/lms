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

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

function invoice_body($document, $invoice)
{
    if (ConfigHelper::checkValue(ConfigHelper::getConfig('invoices.use_customer_lang', true))) {
        Localisation::setUiLanguage($invoice['lang']);
    }
    $document->Draw($invoice);
    if (!isset($invoice['last'])) {
        $document->NewPage();
    }
}

function parse_address($address)
{
    $address = trim($address);
    if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*(?:\/[0-9][0-9a-z]*)?)(?:\s+|\s*(?:\/|m\.?|lok\.?)\s*)(?<flat>[0-9a-z]+)$/i', $address, $m))) {
        if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*)$/i', $address, $m))) {
            $res = preg_match('/^(?<street>.+)$/i', $address, $m);
            if (!$res) {
                return null;
            }
        }
    }

    // ARRAY_FILTER_USE_KEY flag is only for php 5.6 and above
    $m = array_filter($m, 'is_string');

    foreach ($m as $k => $v) {
        if (is_numeric($k)) {
            unset($m[$k]);
        }
    }

    return $m;
}

function try_generate_archive_invoices($ids)
{
    global $LMS, $invoice_type, $which, $document, $classname, $dontpublish, $DOCENTITIES;

    $SMARTY = LMSSmarty::getInstance();

    $archive_stats = $LMS->GetTradeDocumentArchiveStats($ids);

    if (($invoice_type == 'pdf' && ($archive_stats['html'] > 0 || $archive_stats['rtype'] == 'html'))
        || ($invoice_type == 'html' && ($archive_stats['pdf'] > 0 || $archive_stats['rtype'] == 'pdf'))) {
        die('Currently you can only print many documents of type text/html or application/pdf!');
    }

    if (!empty($archive_stats) && $archive_stats['archive'] > 0 && !($which & DOC_ENTITY_DUPLICATE)) {
        if ($archive_stats['rtype'] && $archive_stats['rtype'] != $invoice_type) {
            $invoice_type = $archive_stats['rtype'];
        }

        $attachment_name = 'invoices.' . ($invoice_type == 'pdf' ? 'pdf' : 'html');
        header('Content-Type: ' . ($invoice_type == 'pdf' ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
        header('Pragma: public');

        if ($invoice_type == 'pdf') {
            $pdf = new Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        foreach ($ids as $idx => $invoiceid) {
            if ($LMS->isArchiveDocument($invoiceid)) {
                $file = $LMS->GetArchiveDocument($invoiceid);
            } else {
                $count = Utils::docEntityCount($which);
                $i = 0;

                if (!$document) {
                    if ($invoice_type == 'pdf') {
                        $document = new $classname(trans('Invoices'));
                    } else {
                        $document = new LMSHtmlInvoice($SMARTY);
                    }
                }

                $invoice = $LMS->GetInvoiceContent($invoiceid);
                $invoice['dontpublish'] = $dontpublish;
                foreach (array_keys($DOCENTITIES) as $type) {
                    if ($which & $type) {
                        $i++;
                        if ($i == $count) {
                            $invoice['last'] = true;
                        }
                        $invoice['type'] = $type;
                        invoice_body($document, $invoice);
                    }
                }
                $file['data'] = $document->WriteToString();

                unset($document);
                $document = null;
            }

            if ($invoice_type == 'pdf') {
                $pageCount = $pdf->setSourceFile(StreamReader::createByString($file['data']));
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    // import a page
                    $templateId = $pdf->importPage($pageNo);
                    // get the size of the imported page
                    $size = $pdf->getTemplateSize($templateId);

                    // create a page (landscape or portrait depending on the imported page size)
                    $pdf->AddPage($size['orientation'], $size);

                    // use the imported page
                    $pdf->useTemplate($templateId);
                }
            } else {
                echo $file['data'];
                if ($idx < count($ids) - 1) {
                    echo '<div style="page-break-after: always;">&nbsp;</div>';
                }
            }
        }

        if ($invoice_type == 'pdf') {
            $pdf->Output();
        }

        if (!$dontpublish && !empty($ids)) {
            $LMS->PublishDocuments($ids);
        }

        die;
    }
}

switch (intval($_GET['customertype'])) {
    case CTYPES_PRIVATE:
    case CTYPES_COMPANY:
        $ctype = $_GET['customertype'];
        break;

    default:
        $ctype = -1; //all
}

$attachment_name = ConfigHelper::getConfig('invoices.attachment_name');
$invoice_type = strtolower(ConfigHelper::getConfig('invoices.type'));
$dontpublish = isset($_GET['dontpublish']);
$jpk = isset($_GET['jpk']);
if ($jpk) {
    $jpk_type = $_GET['jpk'];
    if ($jpk_type != 'fa' && $jpk_type != 'vat') {
        $jpk = false;
        unset($jpk_type);
    }
    $jpk_data = '';

    if ($jpk) {
        if (isset($_GET['jpk_format'])) {
            $jpk_format = $_GET['jpk_format'];
            if ($jpk_format != 'xml' && $jpk_format != 'csv') {
                $jpk_format = 'xml';
            }
        } else {
            $jpk_format = 'xml';
        }
    }
}

if ($invoice_type == 'pdf') {
    $pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
    $pdf_type = ucwords($pdf_type);
    $classname = 'LMS' . $pdf_type . 'Invoice';
    $document = new $classname(trans('Invoices'));
} else {
    $document = new LMSHtmlInvoice($SMARTY);
}

if (isset($_GET['print']) && $_GET['print'] == 'cached') {
    $SESSION->restore('ilm', $ilm);
    $SESSION->remove('ilm');

    if (isset($_POST['marks'])) {
        if (isset($_POST['marks']['invoice'])) {
            $marks = $_POST['marks']['invoice'];
        } else {
            $marks = $_POST['marks'];
        }
    } else {
        $marks = array();
    }

    $ids = Utils::filterIntegers($marks);

    if (empty($ids)) {
        $SESSION->close();
        die;
    }

    if (isset($_GET['cash'])) {
        $ids = $LMS->GetDocumentsForBalanceRecords($ids, array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO));
    }

    $layout['pagetitle'] = trans('Invoices');

    $which = isset($_GET['which']) ? intval($_GET['which']) : 0;
    if ($which & DOC_ENTITY_DUPLICATE) {
        $duplicate_date = isset($_GET['duplicate-date']) ? intval($_GET['duplicate-date']) : 0;
    } else {
        $duplicate_date = 0;
    }
    if (!$which) {
        $which = DOC_ENTITY_ORIGINAL;
    }

    try_generate_archive_invoices($ids);

    $count = count($ids) * Utils::docEntityCount($which);
    $i = 0;

    foreach ($ids as $idx => $invoiceid) {
        $invoice = $LMS->GetInvoiceContent($invoiceid);
        if (count($ids) == 1) {
            $docnumber = docnumber(array(
                'number' => $invoice['number'],
                'template' => $invoice['template'],
                'cdate' => $invoice['cdate'],
                'customerid' => $invoice['customerid'],
            ));
        }

        $invoice['dontpublish'] = $dontpublish;
        foreach (array_keys($DOCENTITIES) as $type) {
            if ($which & $type) {
                $i++;
                if ($i == $count) {
                    $invoice['last'] = true;
                }
                $invoice['type'] = $type;
                $invoice['duplicate-date'] = $duplicate_date;

                invoice_body($document, $invoice);
            }
        }
    }
} elseif (isset($_GET['fetchallinvoices'])) {
    $layout['pagetitle'] = trans('Invoices');

    $datefrom = intval($_GET['from']);
    $dateto = intval($_GET['to']);
    $einvoice = intval($_GET['einvoice']);
    $documents = $DB->GetAllByKey(
        'SELECT
            d.id, d.type,
            cn.name AS country, n.template,
            a.state AS rec_state, a.state_id AS rec_state_id,
            a.city as rec_city, a.city_id AS rec_city_id,
            a.street AS rec_street, a.street_id AS rec_street_id,
            a.zip as rec_zip, a.postoffice AS rec_postoffice,
            a.name as rec_name, a.address AS rec_address,
            a.house AS rec_house, a.flat AS rec_flat, a.country_id AS rec_country_id,
            c.pin AS customerpin, c.divisionid AS current_divisionid,
            c.street, c.building, c.apartment,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_street ELSE a2.street END) AS post_street,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_building ELSE a2.house END) AS post_building,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_apartment ELSE a2.flat END) AS post_apartment,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_name ELSE a2.name END) AS post_name,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_address ELSE a2.address END) AS post_address,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_zip ELSE a2.zip END) AS post_zip,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_city ELSE a2.city END) AS post_city,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_postoffice ELSE a2.postoffice END) AS post_postoffice,
            (CASE WHEN d.post_address_id IS NULL THEN c.post_countryid ELSE a2.country_id END) AS post_countryid,
            cp.name AS post_country,
            (CASE WHEN d.div_countryid IS NOT NULL
                THEN (CASE WHEN d.countryid IS NULL
                    THEN cdv.ccode
                    ELSE cn.ccode
                END)
                ELSE NULL
            END) AS lang
        FROM documents d
        JOIN customeraddressview c ON (c.id = d.customerid)
        LEFT JOIN countries cn ON (cn.id = d.countryid)
        LEFT JOIN countries cdv ON cdv.id = d.div_countryid
        LEFT JOIN numberplans n ON (d.numberplanid = n.id)
        LEFT JOIN vaddresses a ON d.recipient_address_id = a.id
        LEFT JOIN vaddresses a2 ON d.post_address_id = a2.id
        LEFT JOIN countries cp ON (d.post_address_id IS NOT NULL AND cp.id = a2.country_id) OR (d.post_address_id IS NULL AND cp.id = c.post_countryid)
        WHERE d.cdate >= ? AND d.cdate <= ? AND (d.type = ? OR d.type = ?) AND d.cancelled = 0'
        .($einvoice ? ' AND d.customerid IN (SELECT id FROM customeraddressview WHERE ' . ($einvoice == 1 ? 'einvoice = 1' : 'einvoice = 0 OR einvoice IS NULL') . ')' : '')
        .($ctype !=  -1 ? ' AND d.customerid IN (SELECT id FROM customers WHERE type = ' . intval($ctype) .')' : '')
        .(!empty($_GET['divisionid']) ? ' AND d.divisionid = ' . intval($_GET['divisionid']) : '')
        .(!empty($_GET['customerid']) ? ' AND d.customerid = '.intval($_GET['customerid']) : '')
        .(!empty($_GET['numberplanid']) ? ' AND d.numberplanid' . (is_array($_GET['numberplanid'])
                ? ' IN (' . implode(',', Utils::filterIntegers($_GET['numberplanid'])) . ')'
                : ' = ' . intval($_GET['numberplanid']))
            : '')
        .(!empty($_GET['autoissued']) ? ' AND d.userid IS NULL' : '')
        .(!empty($_GET['manualissued']) ? ' AND d.userid IS NOT NULL' : '')
        .(!empty($_GET['groupid']) ?
        ' AND ' . (!empty($_GET['groupexclude']) ? 'NOT' : '') . '
            EXISTS (SELECT 1 FROM customerassignments a
            WHERE a.customerid = d.customerid AND a.customergroupid' . (is_array($_GET['groupid'])
                ? ' IN (' . implode(',', Utils::filterIntegers($_GET['groupid'])) . ')'
                : ' = ' . intval($_GET['groupid'])) . ')'
            : '')
        .' AND NOT EXISTS (
            SELECT 1 FROM customerassignments a
            JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
            WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'
        .' ORDER BY CEIL(cdate/86400), id',
        'id',
        array($datefrom, $dateto, DOC_INVOICE, DOC_CNOTE)
    );
    if (empty($documents)) {
        if ($jpk) {
            echo trans('No documents to JPK export!');
        }
        $SESSION->close();
        die;
    }
    $ids = array_keys($documents);

    $which = isset($_GET['which']) ? intval($_GET['which']) : 0;
    if ($which & DOC_ENTITY_DUPLICATE) {
        $duplicate_date = isset($_GET['duplicate-date']) ? intval($_GET['duplicate-date']) : 0;
    } else {
        $duplicate_date = 0;
    }
    if (!$which) {
        $which = DOC_ENTITY_ORIGINAL;
    }

    if ($jpk) {
        if ($jpk_type == 'vat') {
            // if date from for report is earlier than 1 I 2018
            //$jpk_vat_version = $datefrom < mktime(0, 0, 0, 1, 1, 2018) ? 2 : 3;
            // if current date is earlier than 1 I 2018
            //$jpk_vat_version = time() < mktime(0, 0, 0, 1, 1, 2018) ? 2 : 3;
            $jpk_vat_version = (time() < mktime(0, 0, 0, 11, 1, 2020) ? 3 : 4);
        } else {
            // if date from for report is earlier than 2 XII 2019
            //$jpk_fa_version = $datefrom < mktime(0, 0, 0, 12, 2, 2019) ? 2 : 3;
            // if current date is earlier than 1 I 2018
            $jpk_fa_version = time() < mktime(0, 0, 0, 12, 2, 2019) ? 2 : 3;
        }

        $jpk_data .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if ($jpk_type == 'fa') {
            $jpk_data .= "<JPK xmlns=\"" . ($jpk_fa_version == 2 ? 'http://jpk.mf.gov.pl/wzor/2019/03/21/03211/' : 'http://jpk.mf.gov.pl/wzor/2019/09/27/09271/')
                . "\" xmlns:etd=\"http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2018/08/24/eD/DefinicjeTypy/\">\n";
        } else {
            if ($jpk_vat_version == 3) {
                $jpk_data .= "<JPK xmlns=\"http://jpk.mf.gov.pl/wzor/2017/11/13/1113/\""
                    . " xmlns:etd=\"http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/\">\n";
            } else {
                $jpk_data .= "<JPK xmlns=\"http://crd.gov.pl/wzor/2020/05/08/9393/\""
                    . " xmlns:etd=\"http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2020/03/11/eD/DefinicjeTypy/\">\n";
            }
        }

        $divisionid = intval($_GET['divisionid']);
        $division = $DB->GetRow("SELECT d.name, shortname, d.email, va.address, va.city,
				va.zip, va.countryid, ten, regon,
				account, inv_header, inv_footer, inv_author, inv_cplace, va.location_city,
				va.location_street, tax_office_code,
				lb.name AS borough, ld.name AS district, ls.name AS state FROM divisions d
				JOIN vaddresses va ON va.id = d.address_id
				LEFT JOIN location_cities lc ON lc.id = va.location_city
				LEFT JOIN location_boroughs lb ON lb.id = lc.boroughid
				LEFT JOIN location_districts ld ON ld.id = lb.districtid
				LEFT JOIN location_states ls ON ls.id = ld.stateid
				WHERE d.id = ?", array($divisionid));

        if ($jpk_type == 'vat' && $jpk_vat_version == 4 && empty($division['email'])) {
            die(trans('Please define email address in division properties!'));
        }

        // JPK header
        $jpk_data .= "\t<Naglowek>\n";
        if ($jpk_type == 'vat') {
            if ($jpk_vat_version == 3) {
                $jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_VAT (3)\" wersjaSchemy=\"1-1\">JPK_VAT</KodFormularza>\n";
                $jpk_data .= "\t\t<WariantFormularza>3</WariantFormularza>\n";
                $jpk_data .= "\t\t<CelZlozenia>0</CelZlozenia>\n";
                $jpk_data .= "\t\t<DataWytworzeniaJPK>" . strftime('%Y-%m-%dT%H:%M:%S') . "</DataWytworzeniaJPK>\n";
                $jpk_data .= "\t\t<DataOd>" . strftime('%Y-%m-%d', $datefrom) . "</DataOd>\n";
                $jpk_data .= "\t\t<DataDo>" . strftime('%Y-%m-%d', $dateto) . "</DataDo>\n";
                $jpk_data .= "\t\t<NazwaSystemu>LMS</NazwaSystemu>\n";
            } else {
                $jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_V7M (1)\" wersjaSchemy=\"1-2E\">JPK_VAT</KodFormularza>\n";
                $jpk_data .= "\t\t<WariantFormularza>1</WariantFormularza>\n";
                $jpk_data .= "\t\t<DataWytworzeniaJPK>" . strftime('%Y-%m-%dT%H:%M:%S') . "</DataWytworzeniaJPK>\n";
                $jpk_data .= "\t\t<NazwaSystemu>LMS</NazwaSystemu>\n";
                $jpk_data .= "\t\t<CelZlozenia poz=\"P_7\">1</CelZlozenia>\n";
                $jpk_data .= "\t\t<KodUrzedu>" . (!empty($division['tax_office_code']) ? $division['tax_office_code']
                        : ConfigHelper::getConfig('jpk.tax_office_code', '', true)) . "</KodUrzedu>\n";
                $jpk_data .= "\t\t<Rok>" . strftime('%Y', $datefrom) . "</Rok>\n";
                $jpk_data .= "\t\t<Miesiac>" . strftime('%m', $datefrom) . "</Miesiac>\n";
            }
            $tns = '';
        } else {
            if ($jpk_fa_version == 2) {
                $jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_FA (2)\" wersjaSchemy=\"1-0\">JPK_FA</KodFormularza>\n";
                $jpk_data .= "\t\t<WariantFormularza>2</WariantFormularza>\n";
                $jpk_data .= "\t\t<DomyslnyKodWaluty>PLN</DomyslnyKodWaluty>\n";
            } else {
                $jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_FA (3)\" wersjaSchemy=\"1-0\">JPK_FA</KodFormularza>\n";
                $jpk_data .= "\t\t<WariantFormularza>3</WariantFormularza>\n";
            }
            $tns = 'etd:';

            $jpk_data .= "\t\t<CelZlozenia>1</CelZlozenia>\n";
            $jpk_data .= "\t\t<DataWytworzeniaJPK>" . strftime('%Y-%m-%dT%H:%M:%S') . "</DataWytworzeniaJPK>\n";
            $jpk_data .= "\t\t<DataOd>" . strftime('%Y-%m-%d', $datefrom) . "</DataOd>\n";
            $jpk_data .= "\t\t<DataDo>" . strftime('%Y-%m-%d', $dateto) . "</DataDo>\n";
            $jpk_data .= "\t\t<KodUrzedu>" . (!empty($division['tax_office_code']) ? $division['tax_office_code']
                    : ConfigHelper::getConfig('jpk.tax_office_code', '', true)) . "</KodUrzedu>\n";
        }

        $jpk_data .= "\t</Naglowek>\n";

        if ($jpk_type == 'fa') {
            $jpk_data .= "\t<Podmiot1>\n";

            $jpk_data .= "\t\t<IdentyfikatorPodmiotu>\n";
            $jpk_data .= "\t\t\t<etd:NIP>" . preg_replace('/[\s\-]/', '', $division['ten']) . "</etd:NIP>\n";
            $jpk_data .= "\t\t\t<etd:PelnaNazwa>" . htmlspecialchars($division['name']) . "</etd:PelnaNazwa>\n";
            if ($jpk_fa_version == 2) {
                $jpk_data .= "\t\t\t<etd:REGON>" . $division['regon'] . "</etd:REGON>\n";
            }
            $jpk_data .= "\t\t</IdentyfikatorPodmiotu>\n";
            $jpk_data .= "\t\t<AdresPodmiotu>\n";
            $jpk_data .= "\t\t\t<${tns}KodKraju>PL</${tns}KodKraju>\n";
            $jpk_data .= "\t\t\t<${tns}Wojewodztwo>" . (!empty($division['state']) ? $division['state']
                    : ConfigHelper::getConfig('jpk.division_state', '', true)) . "</${tns}Wojewodztwo>\n";
            $jpk_data .= "\t\t\t<${tns}Powiat>" . (!empty($division['district']) ? $division['district']
                    : ConfigHelper::getConfig('jpk.division_district', '', true)) . "</${tns}Powiat>\n";
            $jpk_data .= "\t\t\t<${tns}Gmina>" . (!empty($division['borough']) ? $division['borough']
                    : ConfigHelper::getConfig('jpk.division_borough', '', true)) . "</${tns}Gmina>\n";
            $address = parse_address($division['address']);
            $jpk_data .= "\t\t\t<${tns}Ulica>" . $address['street'] . "</${tns}Ulica>\n";
            $jpk_data .= "\t\t\t<${tns}NrDomu>" . $address['house'] . "</${tns}NrDomu>\n";
            if (isset($address['flat'])) {
                $jpk_data .= "\t\t\t<${tns}NrLokalu>" . $address['flat'] . "</${tns}NrLokalu>\n";
            }
            $jpk_data .= "\t\t\t<${tns}Miejscowosc>" . $division['city'] . "</${tns}Miejscowosc>\n";
            $jpk_data .= "\t\t\t<${tns}KodPocztowy>" . $division['zip'] . "</${tns}KodPocztowy>\n";
            if ($jpk_type == 'vat' || $jpk_fa_version == 2) {
                $jpk_data .= "\t\t\t<${tns}Poczta>" . ConfigHelper::getConfig(
                    'jpk.division_postal_city',
                    $division['city']
                ) . "</${tns}Poczta>\n";
            }
            $jpk_data .= "\t\t</AdresPodmiotu>\n";

            $jpk_data .= "\t</Podmiot1>\n";
        } else {
            if ($jpk_vat_version == 3) {
                $jpk_data .= "\t<Podmiot1>\n";
                $jpk_data .= "\t\t<NIP>" . preg_replace('/[\s\-]/', '', $division['ten']) . "</NIP>\n";
                $jpk_data .= "\t\t<PelnaNazwa>" . htmlspecialchars($division['name']) . "</PelnaNazwa>\n";
                $jpk_data .= "\t</Podmiot1>\n";
            } else {
                $jpk_data .= "\t<Podmiot1 rola=\"Podatnik\">\n";
                $jpk_data .= "\t\t<OsobaNiefizyczna>\n";
                $jpk_data .= "\t\t\t<NIP>" . preg_replace('/[\s\-]/', '', $division['ten']) . "</NIP>\n";
                $jpk_data .= "\t\t\t<PelnaNazwa>" . htmlspecialchars($division['name']) . "</PelnaNazwa>\n";
                $jpk_data .= "\t\t\t<Email>" . $division['email'] . "</Email>\n";
                $jpk_data .= "\t\t</OsobaNiefizyczna>\n";
                $jpk_data .= "\t</Podmiot1>\n";
                $jpk_data .= "\t<Ewidencja>\n";
            }
        }

        $totalvalue = 0;
        $totaltax = 0;
        $jpkrow = 1;
    } else {
        try_generate_archive_invoices($ids);
    }

    $count = count($ids) * Utils::docEntityCount($which);
    $i = 0;

    $invoices = array();
    foreach ($documents as $invoiceid => $invoice) {
        $invoice = array_merge($invoice, $LMS->GetInvoiceContent(
            $invoiceid,
            $jpk ? LMSFinanceManager::INVOICE_CONTENT_DETAIL_GENERAL : LMSFinanceManager::INVOICE_CONTENT_DETAIL_MORE
        ));
        if (count($ids) == 1) {
            $docnumber = docnumber(array(
                'number' => $invoice['number'],
                'template' => $invoice['template'],
                'cdate' => $invoice['cdate'],
                'customerid' => $invoice['customerid'],
            ));
        }
        $currencyvalue = $invoice['currencyvalue'];

        $invoice['dontpublish'] = $dontpublish;
        if ($jpk) {
            if (isset($docnumber)) {
                $invoice['fullnumber'] = $docnumber;
            } else {
                $invoice['fullnumber'] = docnumber(array(
                    'number' => $invoice['number'],
                    'template' => $invoice['template'],
                    'cdate' => $invoice['cdate'],
                    'customerid' => $invoice['customerid'],
                ));
            }

            if ($jpk_type == 'vat') {
                // JPK body positions (sale)
                $jpk_data .= "\t<SprzedazWiersz>\n";

                $jpk_data .= "\t\t<LpSprzedazy>" . $jpkrow . "</LpSprzedazy>\n";
                $jpkrow++;
                $ten = empty($invoice['ten']) ? 'brak' : preg_replace('/[\s\-]/', '', $invoice['ten']);
                $jpk_data .= "\t\t<NrKontrahenta>" . $ten . "</NrKontrahenta>\n";
                $jpk_data .= "\t\t<NazwaKontrahenta><![CDATA[" . $invoice['name'] . "]]></NazwaKontrahenta>\n";
                if ($jpk_vat_version == 3) {
                    $jpk_data .= "\t\t<AdresKontrahenta>" . ($invoice['postoffice'] && $invoice['postoffice'] != $invoice['city'] && $invoice['street'] ? $invoice['city'] . ', ' : '')
                        . $invoice['address'] . ', ' . (empty($invoice['zip']) ? '' : $invoice['zip'] . ' ') . ($invoice['postoffice'] ? $invoice['postoffice'] : $invoice['city']) . "</AdresKontrahenta>\n";
                }
                $jpk_data .= "\t\t<DowodSprzedazy>" . $invoice['fullnumber'] . "</DowodSprzedazy>\n";
                $jpk_data .= "\t\t<DataWystawienia>" . strftime('%Y-%m-%d', $invoice['cdate']) . "</DataWystawienia>\n";
                //if ($invoice['cdate'] != $invoice['sdate'])
                $jpk_data .= "\t\t<DataSprzedazy>" . strftime('%Y-%m-%d', $invoice['sdate']) . "</DataSprzedazy>\n";

                if ($jpk_vat_version == 4) {
                    if (!empty($invoice['flags'][DOC_FLAG_RECEIPT])) {
                        $jpk_data .= "\t\t<TypDokumentu>FP</TypDokumentu>\n";
                    }

                    $tax_categories = array();
                    foreach ($invoice['content'] as $item) {
                        if (!empty($item['taxcategory'])) {
                            $tax_categories[] = $item['taxcategory'];
                        }
                    }
                    $tax_categories = array_unique($tax_categories);
                    sort($tax_categories, SORT_NUMERIC);
                    if (!empty($tax_categories)) {
                        foreach ($tax_categories as $idx => $tax_category) {
                            $jpk_data .= "\t\t<GTU_" . sprintf("%02d", $tax_category)
                                . ">1</GTU_" . sprintf("%02d", $tax_category) . ">\n";
                        }
                    }

                    if (!empty($invoice['flags'][DOC_FLAG_TELECOM_SERVICE])) {
                        $jpk_data .= "\t\t<EE>1</EE>\n";
                    }

                    if (!empty($invoice['flags'][DOC_FLAG_RELATED_ENTITY])) {
                        $jpk_data .= "\t\t<TP>1</TP>\n";
                    }

                    $splitpayment = isset($invoice['splitpayment']) && !empty($invoice['splitpayment']);
                    if ($splitpayment) {
                        $jpk_data .= "\t\t<MPP>1</MPP>\n";
                    }
                }

                $ue = $foreign = false;
                if (!empty($invoice['ten'])) {
                    $ten = str_replace('-', '', $invoice['ten']);
                    if (preg_match('/^[A-Z]{2}[0-9]+$/', $ten)) {
                        $ue = true;
                    } elseif (!empty($invoice['countryid']) && !empty($invoice['division_countryid']) && $invoice['countryid'] != $invoice['division_countryid']) {
                        $foreign = true;
                    }
                }

                if (isset($invoice['invoice'])) {
                    if (isset($invoice['taxest']['-1'])) {
                        $base = ($invoice['taxest']['-1']['base'] - $invoice['invoice']['taxest']['-1']['base']) * $currencyvalue;
                        if ($ue || $foreign) {
                            $jpk_data .= "\t\t<K_11>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_11>\n";
                            $jpk_data .= "\t\t<K_12>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_12>\n";
                        } else {
                            $jpk_data .= "\t\t<K_10>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_10>\n";
                        }
                    } elseif (isset($invoice['invoice']['taxest']['-1'])) {
                        $base = -$invoice['invoice']['taxest']['-1']['base'] * $currencyvalue;
                        if ($ue || $foreign) {
                            $jpk_data .= "\t\t<K_11>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_11>\n";
                            $jpk_data .= "\t\t<K_12>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_12>\n";
                        } else {
                            $jpk_data .= "\t\t<K_10>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_10>\n";
                        }
                    }

                    if (!$foreign && isset($invoice['taxest']['0.00'])) {
                        $base = ($invoice['taxest']['0.00']['base'] - $invoice['invoice']['taxest']['0.00']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_13>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_13>\n";
                    } elseif (isset($invoice['invoice']['taxest']['0.00'])) {
                        $base = -$invoice['invoice']['taxest']['0.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_13>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_13>\n";
                    }

                    if (isset($invoice['taxest']['5.00'])) {
                        $base = ($invoice['taxest']['5.00']['base'] - $invoice['invoice']['taxest']['5.00']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_15>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_15>\n";
                        $tax = ($invoice['taxest']['5.00']['tax'] - $invoice['invoice']['taxest']['5.00']['tax']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_16>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_16>\n";
                        $totaltax += $tax;
                    } elseif (isset($invoice['invoice']['taxest']['5.00'])) {
                        $base = -$invoice['invoice']['taxest']['5.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_15>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_15>\n";
                        $tax = -$invoice['invoice']['taxest']['5.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_16>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_16>\n";
                        $totaltax += $tax;
                    }

                    if (isset($invoice['taxest']['7.00'])) {
                        $base = ($invoice['taxest']['7.00']['base'] - $invoice['invoice']['taxest']['7.00']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
                        $tax = ($invoice['taxest']['7.00']['tax'] - $invoice['invoice']['taxest']['7.00']['tax']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
                        $totaltax += $tax;
                    } elseif (isset($invoice['invoice']['taxest']['7.00'])) {
                        $base = -$invoice['invoice']['taxest']['7.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
                        $tax = -$invoice['invoice']['taxest']['7.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
                        $totaltax += $tax;
                    }

                    if (isset($invoice['taxest']['8.00'])) {
                        $base = ($invoice['taxest']['8.00']['base'] - $invoice['invoice']['taxest']['8.00']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
                        $tax = ($invoice['taxest']['8.00']['tax'] - $invoice['invoice']['taxest']['8.00']['tax']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
                        $totaltax += $tax;
                    } elseif (isset($invoice['invoice']['taxest']['8.00'])) {
                        $base = -$invoice['invoice']['taxest']['8.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
                        $tax = -$invoice['invoice']['taxest']['8.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
                        $totaltax += $tax;
                    }

                    if (isset($invoice['taxest']['22.00'])) {
                        $base = ($invoice['taxest']['22.00']['base'] - $invoice['invoice']['taxest']['22.00']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
                        $tax = ($invoice['taxest']['22.00']['tax'] - $invoice['invoice']['taxest']['22.00']['tax']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
                        $totaltax += $tax;
                    } elseif (isset($invoice['invoice']['taxest']['22.00'])) {
                        $base = -$invoice['invoice']['taxest']['22.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
                        $tax = -$invoice['invoice']['taxest']['22.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
                        $totaltax += $tax;
                    }

                    if (isset($invoice['taxest']['23.00'])) {
                        $base = ($invoice['taxest']['23.00']['base'] - $invoice['invoice']['taxest']['23.00']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
                        $tax = ($invoice['taxest']['23.00']['tax'] - $invoice['invoice']['taxest']['23.00']['tax']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
                        $totaltax += $tax;
                    } elseif (isset($invoice['invoice']['taxest']['23.00'])) {
                        $base = -$invoice['invoice']['taxest']['23.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
                        $tax = -$invoice['invoice']['taxest']['23.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
                        $totaltax += $tax;
                    }

                    if (isset($invoice['taxest']['-2'])) {
                        $base = ($invoice['taxest']['-2']['base'] - $invoice['invoice']['taxest']['-2']['base']) * $currencyvalue;
                        $jpk_data .= "\t\t<K_31>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_31>\n";
                    } elseif (isset($invoice['invoice']['taxest']['-2'])) {
                        $base = -$invoice['invoice']['taxest']['-2']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_31>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_31>\n";
                    }
                } else {
                    if (isset($invoice['taxest']['-1'])) {
                        $base = $invoice['taxest']['-1']['base'] * $currencyvalue;
                        if ($ue || $foreign) {
                            $jpk_data .= "\t\t<K_11>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_11>\n";
                            $jpk_data .= "\t\t<K_12>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_12>\n";
                        } else {
                            $jpk_data .= "\t\t<K_10>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_10>\n";
                        }
                    }

                    if (!$foreign && isset($invoice['taxest']['0.00'])) {
                        $base = $invoice['taxest']['0.00']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_13>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_13>\n";
                    }

                    if (isset($invoice['taxest']['5.00'])) {
                        $base = $invoice['taxest']['5.00']['base'] * $currencyvalue;
                        $tax = $invoice['taxest']['5.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_15>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_15>\n";
                        $jpk_data .= "\t\t<K_16>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_16>\n";
                    }

                    if (isset($invoice['taxest']['7.00'])) {
                        $base = $invoice['taxest']['7.00']['base'] * $currencyvalue;
                        $tax = $invoice['taxest']['7.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
                        $jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
                    }

                    if (isset($invoice['taxest']['8.00'])) {
                        $base = $invoice['taxest']['8.00']['base'] * $currencyvalue;
                        $tax = $invoice['taxest']['8.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
                        $jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
                    }

                    if (isset($invoice['taxest']['22.00'])) {
                        $base = $invoice['taxest']['22.00']['base'] * $currencyvalue;
                        $tax = $invoice['taxest']['22.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
                        $jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
                    }

                    if (isset($invoice['taxest']['23.00'])) {
                        $base = $invoice['taxest']['23.00']['base'] * $currencyvalue;
                        $tax = $invoice['taxest']['23.00']['tax'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
                        $jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
                    }

                    if (isset($invoice['taxest']['-2'])) {
                        $base = $invoice['taxest']['-2']['base'] * $currencyvalue;
                        $jpk_data .= "\t\t<K_31>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_31>\n";
                    }

                    $totaltax += $invoice['totaltax'] * $currencyvalue;
                }

                /*
                // sale of goods to eu or other countries
                if (isset($invoice['taxest']['-1'])) {
                    if ($ue)
                        $jpk_data .= "\t\t<K_21>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-1']['base'])) . "</K_21>\n";
                    elseif ($foreign)
                        $jpk_data .= "\t\t<K_22>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-1']['base'])) . "</K_22>\n";
                */

                $jpk_data .= "\t</SprzedazWiersz>\n";
            } else {
                // JPK body positions (invoices)
                $jpk_data .= "\t<Faktura" . ($jpk_fa_version == 2 ? ' typ="G"' : '') . ">\n";
                if ($jpk_fa_version == 3) {
                    $jpk_data .= "\t\t<KodWaluty>" . (isset($invoice['currency']) ? $invoice['currency'] : 'PLN') . "</KodWaluty>\n";
                }
                $jpk_data .= "\t\t<P_1>" . strftime('%Y-%m-%d', $invoice['cdate']) . "</P_1>\n";
                $invoices[$invoiceid] = $invoice;
                $jpk_data .= "\t\t<P_2A>" . $invoice['fullnumber'] . "</P_2A>\n";
                $jpk_data .= "\t\t<P_3A>" . htmlspecialchars($invoice['name']) . "</P_3A>\n";
                $jpk_data .= "\t\t<P_3B>" . ($invoice['postoffice'] && $invoice['postoffice'] != $invoice['city'] && $invoice['street'] ? $invoice['city'] . ', ' : '')
                    . $invoice['address'] . ', ' . (empty($invoice['zip']) ? '' : $invoice['zip'] . ' ') . ($invoice['postoffice'] ? $invoice['postoffice'] : $invoice['city']) . "</P_3B>\n";
                $jpk_data .= "\t\t<P_3C>" . htmlspecialchars($invoice['division_name']) . "</P_3C>\n";
                $jpk_data .= "\t\t<P_3D>" . $invoice['division_address'] . ', '
                    . (empty($invoice['division_zip']) ? $invoice['division_city'] : $invoice['division_zip'] . ' ' . $invoice['division_city']) . "</P_3D>\n";
                if (preg_match('/^(?<country>[A-Z]{2})(?<ten>[0-9]+)$/', $invoice['division_ten'], $m)) {
                    $jpk_data .= "\t\t<P_4A>" . $m['country'] . "</P_4A>\n";
                    $jpk_data .= "\t\t<P_4B>" . $m['ten'] . "</P_4B>\n";
                } else {
                    $jpk_data .= "\t\t<P_4B>" . preg_replace('/[\s\-]/', '', $invoice['division_ten']) . "</P_4B>\n";
                }
                if (!empty($invoice['ten'])) {
                    if (preg_match('/^(?<country>[A-Z]{2})(?<ten>[0-9]+)$/', $invoice['ten'], $m)) {
                        if (preg_match('/^[1-9]((\d[1-9])|([1-9]\d))\d{7}$/', $m['ten'])) {
                            $jpk_data .= "\t\t<P_5A>" . $m['country'] . "</P_5A>\n";
                            $jpk_data .= "\t\t<P_5B>" . $m['ten'] . "</P_5B>\n";
                        }
                    } else {
                        $jpk_data .= "\t\t<P_5B>" . preg_replace('/[\s\-]/', '', $invoice['ten']) . "</P_5B>\n";
                    }
                } elseif ($jpk_type != 'fa') {
                    $jpk_data .= "\t\t<P_5B>brak</P_5B>\n";
                }

                if (isset($invoice['invoice'])) {
                    if (isset($invoice['taxest']['23.00'])) {
                        $base = ($invoice['taxest']['23.00']['base'] - $invoice['invoice']['taxest']['23.00']['base']);
                        $jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
                        $tax = ($invoice['taxest']['23.00']['tax'] - $invoice['invoice']['taxest']['23.00']['tax']);
                        $jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_1W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_1W>\n";
                        }
                    } elseif (isset($invoice['invoice']['taxest']['23.00'])) {
                        $base = -$invoice['invoice']['taxest']['23.00']['base'];
                        $jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
                        $tax = -$invoice['invoice']['taxest']['23.00']['tax'];
                        $jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_1W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_1W>\n";
                        }
                    } else {
                        $base = $tax = 0;
                    }
                    $totalvalue += $base + $tax;

                    if (isset($invoice['taxest']['22.00'])) {
                        $base = ($invoice['taxest']['22.00']['base'] - $invoice['invoice']['taxest']['22.00']['base']);
                        $jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
                        $tax = ($invoice['taxest']['22.00']['tax'] - $invoice['invoice']['taxest']['22.00']['tax']);
                        $jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_1W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_1W>\n";
                        }
                    } elseif (isset($invoice['invoice']['taxest']['22.00'])) {
                        $base = -$invoice['invoice']['taxest']['22.00']['base'];
                        $jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
                        $tax = -$invoice['invoice']['taxest']['22.00']['tax'];
                        $jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_1W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_1W>\n";
                        }
                    } else {
                        $base = $tax = 0;
                    }
                    $totalvalue += $base + $tax;

                    if (isset($invoice['taxest']['8.00'])) {
                        $base = ($invoice['taxest']['8.00']['base'] - $invoice['invoice']['taxest']['8.00']['base']);
                        $jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
                        $tax = ($invoice['taxest']['8.00']['tax'] - $invoice['invoice']['taxest']['8.00']['tax']);
                        $jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_2W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_2W>\n";
                        }
                    } elseif (isset($invoice['invoice']['taxest']['8.00'])) {
                        $base = -$invoice['invoice']['taxest']['8.00']['base'];
                        $jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
                        $tax = -$invoice['invoice']['taxest']['8.00']['tax'];
                        $jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_2W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_2W>\n";
                        }
                    } else {
                        $base = $tax = 0;
                    }
                    $totalvalue += $base + $tax;

                    if (isset($invoice['taxest']['7.00'])) {
                        $base = ($invoice['taxest']['7.00']['base'] - $invoice['invoice']['taxest']['7.00']['base']);
                        $jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
                        $tax = ($invoice['taxest']['7.00']['tax'] - $invoice['invoice']['taxest']['7.00']['tax']);
                        $jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_2W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_2W>\n";
                        }
                    } elseif (isset($invoice['invoice']['taxest']['7.00'])) {
                        $base = -$invoice['invoice']['taxest']['7.00']['base'];
                        $jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
                        $tax = -$invoice['invoice']['taxest']['7.00']['tax'];
                        $jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_2W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_2W>\n";
                        }
                    } else {
                        $base = $tax = 0;
                    }
                    $totalvalue += $base + $tax;

                    if (isset($invoice['taxest']['5.00'])) {
                        $base = ($invoice['taxest']['5.00']['base'] - $invoice['invoice']['taxest']['5.00']['base']);
                        $jpk_data .= "\t\t<P_13_3>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_3>\n";
                        $tax = ($invoice['taxest']['5.00']['tax'] - $invoice['invoice']['taxest']['5.00']['tax']);
                        $jpk_data .= "\t\t<P_14_3>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_3>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_3W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_3W>\n";
                        }
                    } elseif (isset($invoice['invoice']['taxest']['5.00'])) {
                        $base = -$invoice['invoice']['taxest']['5.00']['base'];
                        $jpk_data .= "\t\t<P_13_3>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_3>\n";
                        $tax = -$invoice['invoice']['taxest']['5.00']['tax'];
                        $jpk_data .= "\t\t<P_14_3>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_3>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_3W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_3W>\n";
                        }
                    } else {
                        $base = $tax = 0;
                    }
                    $totalvalue += $base + $tax;

                    if (isset($invoice['taxest']['0.00'])) {
                        $base = ($invoice['taxest']['0.00']['base'] - $invoice['invoice']['taxest']['0.00']['base']);
                        $jpk_data .= "\t\t<P_13_6>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_6>\n";
                    } elseif (isset($invoice['invoice']['taxest']['0.00'])) {
                        $base = -$invoice['invoice']['taxest']['0.00']['base'];
                        $jpk_data .= "\t\t<P_13_6>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_6>\n";
                    } else {
                        $base = 0;
                    }
                    $totalvalue += $base;

                    if (isset($invoice['taxest']['-1'])) {
                        $base = ($invoice['taxest']['-1']['base'] - $invoice['invoice']['taxest']['-1']['base']);
                        $jpk_data .= "\t\t<P_13_7>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_7>\n";
                    } elseif (isset($invoice['invoice']['taxest']['-1'])) {
                        $base = -$invoice['invoice']['taxest']['-1']['base'];
                        $jpk_data .= "\t\t<P_13_7>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_7>\n";
                    } else {
                        $base = 0;
                    }
                    $totalvalue += $base;

                    $jpk_data .= "\t\t<P_15>" . str_replace(',', '.', sprintf("%.2f", $invoice['total'] - $invoice['invoice']['total'])) . "</P_15>\n";
                } else {
                    if (isset($invoice['taxest']['23.00'])) {
                        $base = $invoice['taxest']['23.00']['base'];
                        $tax = $invoice['taxest']['23.00']['tax'];
                        $jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
                        $jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_1W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_1W>\n";
                        }
                    }
                    if (isset($invoice['taxest']['22.00'])) {
                        $base = $invoice['taxest']['22.00']['base'];
                        $tax = $invoice['taxest']['22.00']['tax'];
                        $jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
                        $jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_1W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_1W>\n";
                        }
                    }

                    if (isset($invoice['taxest']['8.00'])) {
                        $base = $invoice['taxest']['8.00']['base'];
                        $tax = $invoice['taxest']['8.00']['tax'];
                        $jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
                        $jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_2W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_2W>\n";
                        }
                    }
                    if (isset($invoice['taxest']['7.00'])) {
                        $base = $invoice['taxest']['7.00']['base'];
                        $tax = $invoice['taxest']['7.00']['tax'];
                        $jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
                        $jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_2W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_2W>\n";
                        }
                    }

                    if (isset($invoice['taxest']['5.00'])) {
                        $base = $invoice['taxest']['5.00']['base'];
                        $tax = $invoice['taxest']['5.00']['tax'];
                        $jpk_data .= "\t\t<P_13_3>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_3>\n";
                        $jpk_data .= "\t\t<P_14_3>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_3>\n";
                        if ($jpk_fa_version == 3 && isset($invoice['currency']) && $invoice['currency'] != Localisation::getCurrentCurrency()) {
                            $jpk_data .= "\t\t<P_14_3W>" . str_replace(',', '.', sprintf('%.2f', $tax * $currencyvalue)) . "</P_14_3W>\n";
                        }
                    }

                    if (isset($invoice['taxest']['0.00'])) {
                        $base = $invoice['taxest']['0.00']['base'];
                        $jpk_data .= "\t\t<P_13_6>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_6>\n";
                    }

                    if (isset($invoice['taxest']['-1'])) {
                        $base = $invoice['taxest']['-1']['base'];
                        $jpk_data .= "\t\t<P_13_7>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_7>\n";
                    }

                    $total = $invoice['total'];
                    $jpk_data .= "\t\t<P_15>" . str_replace(',', '.', sprintf("%.2f", $total)) . "</P_15>\n";

                    $totalvalue += $total;
                }
                $jpk_data .= "\t\t<P_16>false</P_16>\n";
                $jpk_data .= "\t\t<P_17>false</P_17>\n";
                $jpk_data .= "\t\t<P_18>" . (isset($invoice['taxest']['-2']['base']) ? 'true' : 'false') . "</P_18>\n";
                if ($jpk_fa_version == 3) {
                    $splitpayment = isset($invoice['splitpayment']) && !empty($invoice['splitpayment']);
                    $jpk_data .= "\t\t<P_18A>" . ($splitpayment ? 'true' : 'false') . "</P_18A>\n";
                }
                $jpk_data .= "\t\t<P_19>false</P_19>\n";
                $jpk_data .= "\t\t<P_20>false</P_20>\n";
                $jpk_data .= "\t\t<P_21>false</P_21>\n";
                if ($jpk_fa_version == 3) {
                    $jpk_data .= "\t\t<P_22>false</P_22>\n";
                }
                $jpk_data .= "\t\t<P_23>false</P_23>\n";
                $jpk_data .= "\t\t<P_106E_2>false</P_106E_2>\n";
                if ($jpk_fa_version == 3) {
                    $jpk_data .= "\t\t<P_106E_3>false</P_106E_3>\n";
                }
                $jpk_data .= "\t\t<RodzajFaktury>" . (isset($invoice['invoice']) ? 'KOREKTA' : 'VAT') . "</RodzajFaktury>\n";
                if (isset($invoice['invoice'])) {
                    $jpk_data .= "\t\t<PrzyczynaKorekty>" . (empty($invoice['reason']) ? 'błędne wystawienie faktury' : $invoice['reason']) . "</PrzyczynaKorekty>\n";
                    $invoice['invoice']['fullnumber'] = docnumber(array(
                    'number' => $invoice['invoice']['number'],
                    'template' => $invoice['invoice']['template'],
                        'cdate' => $invoice['invoice']['cdate'],
                        'customerid' => $invoice['customerid'],
                        ));
                        $jpk_data .= "\t\t<NrFaKorygowanej>" . $invoice['invoice']['fullnumber'] . "</NrFaKorygowanej>\n";
                        $jpk_data .= "\t\t<OkresFaKorygowanej>" . strftime('%Y-%m', $invoice['invoice']['sdate']) . "</OkresFaKorygowanej>\n";
                }
                $jpk_data .= "\t</Faktura>\n";
            }
        } else {
            foreach (array_keys($DOCENTITIES) as $type) {
                if ($which & $type) {
                    $i++;
                    if ($i == $count) {
                        $invoice['last'] = true;
                    }
                    $invoice['type'] = $type;
                    invoice_body($document, $invoice);
                }
            }
        }
    }

    if ($jpk) {
        if ($jpk_type == 'vat') {
            $jpk_data .= "\t<SprzedazCtrl>\n";
            $jpk_data .= "\t\t<LiczbaWierszySprzedazy>" . count($ids) . "</LiczbaWierszySprzedazy>\n";
            $jpk_data .= "\t\t<PodatekNalezny>" . str_replace(',', '.', sprintf('%.2f', $totaltax)) . "</PodatekNalezny>\n";
            $jpk_data .= "\t</SprzedazCtrl>\n";
            if ($jpk_vat_version == 4) {
                $jpk_data .= "\t<ZakupCtrl>\n";
                $jpk_data .= "\t\t<LiczbaWierszyZakupow>0</LiczbaWierszyZakupow>\n";
                $jpk_data .= "\t\t<PodatekNaliczony>0</PodatekNaliczony>\n";
                $jpk_data .= "\t</ZakupCtrl>\n";
                $jpk_data .= "\t</Ewidencja>\n";
            }
        } else {
            $jpk_data .= "\t<FakturaCtrl>\n";
            $jpk_data .= "\t\t<LiczbaFaktur>" . count($ids) . "</LiczbaFaktur>\n";
            $jpk_data .= "\t\t<WartoscFaktur>" . str_replace(',', '.', sprintf('%.2f', $totalvalue)) . "</WartoscFaktur>\n";
            $jpk_data .= "\t</FakturaCtrl>\n";

            $taxrates = $DB->GetCol('SELECT DISTINCT value FROM taxes
				WHERE taxed = 1 AND value > 0 AND validfrom < ? AND (validto = 0 OR validto > ?)
				ORDER BY value DESC', array($datefrom, $dateto));
            if (empty($taxrates)) {
                $taxrates = array(23, 8, 5, 0, 0);
            } else {
                $taxrates = array_merge($taxrates, array_fill(0, 5 - count($taxrates), 0));
            }
            if ($jpk_fa_version == 2) {
                $jpk_data .= "\t<StawkiPodatku>\n";
                $i = 1;
                foreach ($taxrates as $taxrate) {
                    $jpk_data .= "\t\t<Stawka" . $i . ">" . str_replace(',', '.', sprintf('%.2f', $taxrate / 100))
                        . "</Stawka" . $i . ">\n";
                    $i++;
                }
                $jpk_data .= "\t</StawkiPodatku>\n";
            }

            $positions = 0;
            foreach ($invoices as $invoice) {
                foreach ($invoice['content'] as $idx => $position) {
                    $jpk_data .= "\t<FakturaWiersz" . ($jpk_fa_version == 2 ? ' typ="G"' : '') . ">\n";
                    $jpk_data .= "\t\t<P_2B>" . $invoice['fullnumber'] . "</P_2B>\n";
                    $jpk_data .= "\t\t<P_7>"
                        . htmlspecialchars(
                            mb_strlen($position['description']) > 240
                                ? mb_substr($position['description'], 0, 240) . ' ...'
                                : $position['description']
                        )
                        . "</P_7>\n";
                    $jpk_data .= "\t\t<P_8A>" . htmlspecialchars($position['content']) . "</P_8A>\n";

                    if (isset($invoice['invoice'])) {
                        $count = $position['count'] - $invoice['invoice']['content'][$idx]['count'];
                        $basevalue = ($position['basevalue'] - $invoice['invoice']['content'][$idx]['basevalue']);
                        $value = ($position['value'] - $invoice['invoice']['content'][$idx]['value']);
                        $totalbase = ($position['totalbase'] - $invoice['invoice']['content'][$idx]['totalbase']);
                        $total = ($position['total'] - $invoice['invoice']['content'][$idx]['total']);
                    } else {
                        $count = $position['count'];
                        $basevalue = $position['basevalue'];
                        $value = $position['value'];
                        $totalbase = $position['totalbase'];
                        $total = $position['total'];
                    }
                    $jpk_data .="\t\t<P_8B>" . str_replace('&', '&amp;', $count) . "</P_8B>\n";
                    $jpk_data .="\t\t<P_9A>" . str_replace(',', '.', sprintf('%.2f', $basevalue)) . "</P_9A>\n";
                    $jpk_data .="\t\t<P_9B>" . str_replace(',', '.', sprintf('%.2f', $value)) . "</P_9B>\n";
                    $jpk_data .="\t\t<P_11>" . str_replace(',', '.', sprintf('%.2f', $totalbase)) . "</P_11>\n";
                    $jpk_data .="\t\t<P_11A>" . str_replace(',', '.', sprintf('%.2f', $total)) . "</P_11A>\n";

                    if ($position['taxvalue'] >= 0) {
                        $jpk_data .= "\t\t<P_12>" . str_replace(',', '.', round($position['taxvalue'])) . "</P_12>\n";
                    } elseif ($position['taxvalue'] == -1) {
                        $jpk_data .= "\t\t<P_12>zw</P_12>\n";
                    } else {
                        $jpk_data .= "\t\t<P_12>0</P_12>\n";
                    }
                    $jpk_data .="\t</FakturaWiersz>\n";
                    $positions++;
                }
            }

            $jpk_data .= "\t<FakturaWierszCtrl>\n";
            $jpk_data .= "\t\t<LiczbaWierszyFaktur>" . $positions . "</LiczbaWierszyFaktur>\n";
            $jpk_data .= "\t\t<WartoscWierszyFaktur>" . str_replace(',', '.', sprintf('%.2f', $totalvalue)) . "</WartoscWierszyFaktur>\n";
            $jpk_data .= "\t</FakturaWierszCtrl>\n";
        }

        $jpk_data .= "</JPK>\n";
    }
} elseif ($invoice = $LMS->GetInvoiceContent($_GET['id'])) {
    $ids = array($_GET['id']);

    $docnumber = docnumber(array(
        'number' => $invoice['number'],
        'template' => $invoice['template'],
        'cdate' => $invoice['cdate'],
        'customerid' => $invoice['customerid'],
    ));
    if (!isset($invoice['invoice'])) {
        $layout['pagetitle'] = trans('Invoice No. $a', $docnumber);
    } else {
        $layout['pagetitle'] = trans('Credit Note No. $a', $docnumber);
    }

    $which = isset($_GET['which']) ? intval($_GET['which']) : 0;
    if ($which & DOC_ENTITY_DUPLICATE) {
        $duplicate_date = isset($_GET['duplicate-date']) ? intval($_GET['duplicate-date']) : 0;
    } else {
        $duplicate_date = 0;
    }
    if (!$which) {
        $which = DOC_ENTITY_ORIGINAL;
    }

    if (!$which) {
        $tmp = explode(',', ConfigHelper::getConfig('invoices.default_printpage'));
        foreach ($tmp as $t) {
            if (trim($t) == 'original') {
                $which |= DOC_ENTITY_ORIGINAL;
            } elseif (trim($t) == 'copy') {
                $which |= DOC_ENTITY_COPY;
            } elseif (trim($t) == 'duplicate') {
                $which |= DOC_ENTITY_DUPLICATE;
            }
        }

        if (!$which) {
            $which = DOC_ENTITY_ORIGINAL;
        }
    }

    if ($invoice['archived'] && !($which & DOC_ENTITY_DUPLICATE)) {
        $invoice = $LMS->GetArchiveDocument($_GET['id']);
        if ($invoice) {
            header('Content-Type: ' . $invoice['content-type']);
            header('Content-Disposition: inline; filename=' . $invoice['filename']);
            echo $invoice['data'];
        }
        $SESSION->close();
        die;
    }

    $count = Utils::docEntityCount($which);
    $i = 0;

    $invoice['dontpublish'] = $dontpublish;
    foreach (array_keys($DOCENTITIES) as $type) {
        if ($which & $type) {
            $i++;
            if ($i == $count) {
                $invoice['last'] = true;
            }
            $invoice['type'] = $type;
            $invoice['duplicate-date'] = $duplicate_date;

            invoice_body($document, $invoice);
        }
    }
} else {
    $SESSION->redirect('?m=invoicelist');
}

if (!is_null($attachment_name) && isset($docnumber)) {
    $attachment_name = str_replace('%number', $docnumber, $attachment_name);
    $attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} elseif ($jpk) {
    if ($jpk_type == 'fa') {
        $attachment_name = 'JPK_FA_' . date('Y-m-d', $datefrom) . '_' . date('Y-m-d', $dateto)
            . '_' . strftime('%Y-%m-%d-%H-%M-%S') . '.xml';
    } else {
        $attachment_name = 'JPK_VAT_' . date('Y-m-d', $datefrom) . '_' . date('Y-m-d', $dateto)
            . '_' . strftime('%Y-%m-%d-%H-%M-%S') . '.' . ($jpk_format == 'xml' ? 'xml' : 'csv');
    }
} else {
    $attachment_name = 'invoices.' . ($invoice_type == 'pdf' ? 'pdf' : 'html');
}

if ($jpk) {
    // send jpk data to web browser
    if ($jpk_format == 'csv') {
        if (!class_exists('DOMDocument')) {
                die('Fatal error! PHP XML exenstion is not installed!');
        }
        if (!class_exists('XSLTProcessor')) {
                die('Fatal error! PHP XSLT extension is not installed!');
        }

        $xsldoc = new DOMDocument();
        $xsldoc->load(LIB_DIR . DIRECTORY_SEPARATOR . 'Schemat_JPK_VAT(3)_v1-1.xsl');

        $xmldoc = new DOMDocument();
        $xmldoc->loadXML($jpk_data);

        $xslt = new XSLTProcessor();
        $xslt->importStyleSheet($xsldoc);
        $jpk_data = $xslt->transformToXML($xmldoc);
    }


    header('Content-Type: text/xml');
    header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
    header('Pragma: public');

    echo $jpk_data;
} else {
    $document->WriteToBrowser($attachment_name);
}

if (!$dontpublish && isset($ids) && !empty($ids)) {
    $LMS->PublishDocuments($ids);
}
