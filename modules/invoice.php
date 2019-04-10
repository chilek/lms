<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function invoice_body($document, $invoice) {
	$document->Draw($invoice);
	if (!isset($invoice['last']))
		$document->NewPage();
}

function parse_address($address) {
	$address = trim($address);
	if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*(?:\/[0-9][0-9a-z]*)?)(?:\s+|\s*(?:\/|m\.?|lok\.?)\s*)(?<flat>[0-9a-z]+)$/i', $address, $m)))
		if (!($res = preg_match('/^(?<street>.+)\s+(?<house>[0-9][0-9a-z]*)$/i', $address, $m))) {
			$res = preg_match('/^(?<street>.+)$/i', $address, $m);
			if (!$res)
				return null;
		}

	// ARRAY_FILTER_USE_KEY flag is only for php 5.6 and above
	$m = array_filter($m, 'is_string');

	foreach ($m as $k => $v) {
		if ( is_numeric($k) ) {
			unset( $m[$k] );
		}
	}

	return $m;
}

function try_generate_archive_invoices($ids) {
	global $LMS, $invoice_type, $which, $document, $classname, $dontpublish;

	$SMARTY = LMSSmarty::getInstance();

	$archive_stats = $LMS->GetFinancialDocumentArchiveStats($ids);

	if (($invoice_type == 'pdf' && ($archive_stats['html'] > 0 || $archive_stats['rtype'] == 'html'))
		|| ($invoice_type == 'html' && ($archive_stats['pdf'] > 0 || $archive_stats['rtype'] == 'pdf')))
		die('Currently you can only print many documents of type text/html or application/pdf!');

	if (!empty($archive_stats) && $archive_stats['archive'] > 0 && !in_array(trans('DUPLICATE'), $which)) {
		$attachment_name = 'invoices.' . ($invoice_type == 'pdf' ? 'pdf' : 'html');
		header('Content-Type: application/pdf');
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
				$count = count($which);
				$i = 0;

				if (!$document)
					if ($invoice_type == 'pdf')
						$document = new $classname(trans('Invoices'));
					else
						$document = new LMSHtmlInvoice($SMARTY);

				$invoice = $LMS->GetInvoiceContent($invoiceid);
				$invoice['dontpublish'] = $dontpublish;
				foreach ($which as $type) {
					$i++;
					if ($i == $count)
						$invoice['last'] = true;
					$invoice['type'] = $type;
					invoice_body($document, $invoice);
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

					$pdf->AddPage($size['orientation'], $size);

					// use the imported page
					$pdf->useTemplate($templateId);
				}
			} else {
				echo $file['data'];
				if ($idx < count($ids) - 1)
					echo '<div style="page-break-after: always;">&nbsp;</div>';
			}
		}

		if ($invoice_type == 'pdf')
			$pdf->Output();

		if (!$dontpublish && !empty($ids))
			$LMS->PublishDocuments($ids);

		die;
	}
}

switch ( intval($_GET['customertype']) ) {
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
}

if ($invoice_type == 'pdf') {
	$pdf_type = ConfigHelper::getConfig('invoices.pdf_type', 'tcpdf');
	$pdf_type = ucwords($pdf_type);
	$classname = 'LMS' . $pdf_type . 'Invoice';
	$document = new $classname(trans('Invoices'));
} else
	$document = new LMSHtmlInvoice($SMARTY);

if (isset($_GET['print']) && $_GET['print'] == 'cached') {
	$SESSION->restore('ilm', $ilm);
	$SESSION->remove('ilm');

	if (isset($_POST['marks']))
		foreach ($_POST['marks'] as $idx => $mark)
			$ilm[$idx] = intval($mark);

	if (count($ilm))
		foreach ($ilm as $mark)
			$ids[] = $mark;

	if (!isset($ids)) {
		$SESSION->close();
		die;
	}

	$layout['pagetitle'] = trans('Invoices');

	if (isset($_GET['cash'])) {
		$ids = $DB->GetCol('SELECT DISTINCT docid
			FROM cash, documents
			WHERE docid = documents.id AND (documents.type = ? OR documents.type = ?)
				AND cash.id IN ('.implode(',', $ids).')
			ORDER BY docid',
			array(DOC_INVOICE, DOC_CNOTE));
	}

	$which = array();

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!count($which)) $which[] = trans('ORIGINAL');

	try_generate_archive_invoices($ids);

	$count = count($ids) * count($which);
	$i = 0;

	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		if (count($ids) == 1)
			$docnumber = docnumber(array(
				'number' => $invoice['number'],
				'template' => $invoice['template'],
				'cdate' => $invoice['cdate'],
				'customerid' => $invoice['customerid'],
			));

		$invoice['dontpublish'] = $dontpublish;
		foreach ($which as $type) {
			$i++;
			if ($i == $count) $invoice['last'] = TRUE;
			$invoice['type'] = $type;
			invoice_body($document, $invoice);
		}
	}
} elseif (isset($_GET['fetchallinvoices'])) {
	$layout['pagetitle'] = trans('Invoices');

	$datefrom = intval($_GET['from']);
	$dateto = intval($_GET['to']);
	$einvoice = intval($_GET['einvoice']);
	$ids = $DB->GetCol('SELECT id FROM documents d
				WHERE cdate >= ? AND cdate <= ? AND (type = ? OR type = ?) AND d.cancelled = 0'
				.($einvoice ? ' AND d.customerid IN (SELECT id FROM customers WHERE ' . ($einvoice == 1 ? 'einvoice = 1' : 'einvoice = 0 OR einvoice IS NULL') . ')' : '')
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
				array($datefrom, $dateto, DOC_INVOICE, DOC_CNOTE));
	if (!$ids) {
		$SESSION->close();
		die;
	}

	$which = array();

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!count($which)) $which[] = trans('ORIGINAL');

	try_generate_archive_invoices($ids);

	$count = count($ids) * count($which);
	$i = 0;

	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		if (count($ids) == 1)
			$docnumber = docnumber(array(
				'number' => $invoice['number'],
				'template' => $invoice['template'],
				'cdate' => $invoice['cdate'],
				'customerid' => $invoice['customerid'],
			));

		$invoice['dontpublish'] = $dontpublish;
		foreach ($which as $type) {
			$i++;
			if ($i == $count) $invoice['last'] = TRUE;
			$invoice['type'] = $type;
			invoice_body($document, $invoice);
		}
	}

	$count = count($ids) * count($which);
	$i = 0;

	if ($jpk) {
		if ($jpk_type == 'vat')
			// if date from for report is earlier than 1 I 2018
			$jpk_vat_version = $datefrom < mktime(0, 0, 0, 1, 1, 2018) ? 2 : 3;
			// if current date is earlier than 1 I 2018
			//$jpk_vat_version = time() < mktime(0, 0, 0, 1, 1, 2018) ? 2 : 3;

		$jpk_data .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		if ($jpk_type == 'fa')
			$jpk_data .= "<JPK xmlns=\"http://jpk.mf.gov.pl/wzor/2016/03/09/03095/\" xmlns:etd=\"http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/\">\n";
		else
			$jpk_data .= "<JPK xmlns=\""
				. ($jpk_vat_version == 2 ? "http://jpk.mf.gov.pl/wzor/2016/10/26/10261/"
					: "http://jpk.mf.gov.pl/wzor/2017/11/13/1113/")
				. "\" xmlns:etd=\"http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/\">\n";

		$divisionid = intval($_GET['divisionid']);
		$division = $DB->GetRow("SELECT d.name, shortname, va.address, va.city,
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

		// JPK header
		$jpk_data .= "\t<Naglowek>\n";
		if ($jpk_type == 'vat') {
			if ($jpk_vat_version == 2) {
				$jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_VAT (2)\" wersjaSchemy=\"1-0\">JPK_VAT</KodFormularza>\n";
				$jpk_data .= "\t\t<WariantFormularza>2</WariantFormularza>\n";
			} else {
				$jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_VAT (3)\" wersjaSchemy=\"1-1\">JPK_VAT</KodFormularza>\n";
				$jpk_data .= "\t\t<WariantFormularza>3</WariantFormularza>\n";
			}
			$tns = '';
		} else {
			$jpk_data .= "\t\t<KodFormularza kodSystemowy=\"JPK_FA (1)\" wersjaSchemy=\"1-0\">JPK_FA</KodFormularza>\n";
			$jpk_data .= "\t\t<WariantFormularza>1</WariantFormularza>\n";
			$tns = 'etd:';
		}
		$jpk_data .= "\t\t<CelZlozenia>" . ($jpk_vat_version == 2 || $jpk_type == 'fa' ? '1' : '0') . "</CelZlozenia>\n";
		$jpk_data .= "\t\t<DataWytworzeniaJPK>" . strftime('%Y-%m-%dT%H:%M:%S') . "</DataWytworzeniaJPK>\n";
		$jpk_data .= "\t\t<DataOd>" . strftime('%Y-%m-%d', $datefrom) . "</DataOd>\n";
		$jpk_data .= "\t\t<DataDo>" . strftime('%Y-%m-%d', $dateto) . "</DataDo>\n";

		if ($jpk_type == 'fa' || $jpk_vat_version == 2) {
			$jpk_data .= "\t\t<DomyslnyKodWaluty>PLN</DomyslnyKodWaluty>\n";
			$jpk_data .= "\t\t<KodUrzedu>" . (!empty($division['tax_office_code']) ? $division['tax_office_code']
				: ConfigHelper::getConfig('jpk.tax_office_code', '', true)) . "</KodUrzedu>\n";
		} else
			$jpk_data .= "\t\t<NazwaSystemu>LMS</NazwaSystemu>\n";

		$jpk_data .= "\t</Naglowek>\n";

		$jpk_data .= "\t<Podmiot1>\n";

		if ($jpk_type == 'fa' || $jpk_vat_version == 2) {
			$jpk_data .= "\t\t<IdentyfikatorPodmiotu>\n";
			$jpk_data .= "\t\t\t<etd:NIP>" . preg_replace('/[\s\-]/', '', $division['ten']) . "</etd:NIP>\n";
			$jpk_data .= "\t\t\t<etd:PelnaNazwa>" . str_replace('&', '&amp;', $division['name']) . "</etd:PelnaNazwa>\n";
			$jpk_data .= "\t\t\t<etd:REGON>" . $division['regon'] . "</etd:REGON>\n";
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
			if (isset($address['flat']))
				$jpk_data .= "\t\t\t<${tns}NrLokalu>" . $address['flat'] . "</${tns}NrLokalu>\n";
			$jpk_data .= "\t\t\t<${tns}Miejscowosc>" . $division['city'] . "</${tns}Miejscowosc>\n";
			$jpk_data .= "\t\t\t<${tns}KodPocztowy>" . $division['zip'] . "</${tns}KodPocztowy>\n";
			$jpk_data .= "\t\t\t<${tns}Poczta>" . ConfigHelper::getConfig('jpk.division_postal_city', $division['city']) . "</${tns}Poczta>\n";
			$jpk_data .= "\t\t</AdresPodmiotu>\n";
		} else {
			$jpk_data .= "\t\t<NIP>" . preg_replace('/[\s\-]/', '', $division['ten']) . "</NIP>\n";
			$jpk_data .= "\t\t<PelnaNazwa>" . str_replace('&', '&amp;', $division['name']) . "</PelnaNazwa>\n";
		}

		$jpk_data .= "\t</Podmiot1>\n";
		$totalvalue = 0;
		$totaltax = 0;
	}

	$invoices = array();
	foreach ($ids as $idx => $invoiceid) {
		$invoice = $LMS->GetInvoiceContent($invoiceid);
		if (count($ids) == 1)
			$docnumber = docnumber(array(
				'number' => $invoice['number'],
				'template' => $invoice['template'],
				'cdate' => $invoice['cdate'],
				'customerid' => $invoice['customerid'],
			));

		$invoice['dontpublish'] = $dontpublish;
		if ($jpk) {
			if (isset($docnumber))
				$invoice['fullnumber'] = $docnumber;
			else
				$invoice['fullnumber'] = docnumber(array(
					'number' => $invoice['number'],
					'template' => $invoice['template'],
					'cdate' => $invoice['cdate'],
					'customerid' => $invoice['customerid'],
				));

			if ($jpk_type == 'vat') {
				// JPK body positions (sale)
				$jpk_data .= "\t<SprzedazWiersz" . ($jpk_vat_version == 2 ? " typ=\"G\"" : '') . ">\n";

				$jpk_data .= "\t\t<LpSprzedazy>" . ($idx + 1) . "</LpSprzedazy>\n";
				if (empty($invoice['ten']))
					$invoice['ten'] = 'brak';
				$jpk_data .= "\t\t<NrKontrahenta>" . preg_replace('/[\s\-]/', '', $invoice['ten']) . "</NrKontrahenta>\n";
				$jpk_data .= "\t\t<NazwaKontrahenta>" . str_replace('&', '&amp;', $invoice['name']) . "</NazwaKontrahenta>\n";
				$jpk_data .= "\t\t<AdresKontrahenta>" . ($invoice['postoffice'] && $invoice['postoffice'] != $invoice['city'] && $invoice['street'] ? $invoice['city'] . ', ' : '')
					. $invoice['address'] . ', ' . (empty($invoice['zip']) ? '' : $invoice['zip'] . ' ') . ($invoice['postoffice'] ? $invoice['postoffice'] : $invoice['city']) . "</AdresKontrahenta>\n";
				$jpk_data .= "\t\t<DowodSprzedazy>" . $invoice['fullnumber'] . "</DowodSprzedazy>\n";
				$jpk_data .= "\t\t<DataWystawienia>" . strftime('%Y-%m-%d', $invoice['cdate']) . "</DataWystawienia>\n";
				//if ($invoice['cdate'] != $invoice['sdate'])
				$jpk_data .= "\t\t<DataSprzedazy>" . strftime('%Y-%m-%d', $invoice['sdate']) . "</DataSprzedazy>\n";

				$ue = $foreign = false;
				if (!empty($invoice['ten'])) {
					$ten = str_replace('-', '', $invoice['ten']);
					if (preg_match('/^[A-Z]{2}[0-9]+$/', $ten))
						$ue = true;
					elseif (!empty($invoice['countryid']) && !empty($invoice['division_countryid']) && $invoice['countryid'] != $invoice['division_countryid'])
						$foreign = true;
				}

				if (isset($invoice['invoice'])) {
					if (isset($invoice['taxest']['-1'])) {
						$base = $invoice['taxest']['-1']['base'] - $invoice['invoice']['taxest']['-1']['base'];
						if ($ue || $foreign) {
							$jpk_data .= "\t\t<K_11>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_11>\n";
							$jpk_data .= "\t\t<K_12>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_12>\n";
						} else
							$jpk_data .= "\t\t<K_10>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_10>\n";
					} elseif (isset($invoice['invoice']['taxest']['-1'])) {
						$base = -$invoice['invoice']['taxest']['-1']['base'];
						if ($ue || $foreign) {
							$jpk_data .= "\t\t<K_11>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_11>\n";
							$jpk_data .= "\t\t<K_12>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_12>\n";
						} else
							$jpk_data .= "\t\t<K_10>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_10>\n";
					}

					if (!$foreign && isset($invoice['taxest']['0.00'])) {
						$base = $invoice['taxest']['0.00']['base'] - $invoice['invoice']['taxest']['0.00']['base'];
						$jpk_data .= "\t\t<K_13>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_13>\n";
					} elseif (isset($invoice['invoice']['taxest']['0.00'])) {
						$base = -$invoice['invoice']['taxest']['0.00']['base'];
						$jpk_data .= "\t\t<K_13>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_13>\n";
					}

					if (isset($invoice['taxest']['5.00'])) {
						$base = $invoice['taxest']['5.00']['base'] - $invoice['invoice']['taxest']['5.00']['base'];
						$jpk_data .= "\t\t<K_15>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_15>\n";
						$tax = $invoice['taxest']['5.00']['tax'] - $invoice['invoice']['taxest']['5.00']['tax'];
						$jpk_data .= "\t\t<K_16>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_16>\n";
						$totaltax += $tax;
					} elseif (isset($invoice['invoice']['taxest']['5.00'])) {
						$base = -$invoice['invoice']['taxest']['5.00']['base'];
						$jpk_data .= "\t\t<K_15>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_15>\n";
						$tax = -$invoice['invoice']['taxest']['5.00']['tax'];
						$jpk_data .= "\t\t<K_16>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_16>\n";
						$totaltax += $tax;
					}

					if (isset($invoice['taxest']['7.00'])) {
						$base = $invoice['taxest']['7.00']['base'] - $invoice['invoice']['taxest']['7.00']['base'];
						$jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
						$tax = $invoice['taxest']['7.00']['tax'] - $invoice['invoice']['taxest']['7.00']['tax'];
						$jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
						$totaltax += $tax;
					} elseif (isset($invoice['invoice']['taxest']['7.00'])) {
						$base = -$invoice['invoice']['taxest']['7.00']['base'];
						$jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
						$tax = -$invoice['invoice']['taxest']['7.00']['tax'];
						$jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
						$totaltax += $tax;
					}

					if (isset($invoice['taxest']['8.00'])) {
						$base = $invoice['taxest']['8.00']['base'] - $invoice['invoice']['taxest']['8.00']['base'];
						$jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
						$tax = $invoice['taxest']['8.00']['tax'] - $invoice['invoice']['taxest']['8.00']['tax'];
						$jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
						$totaltax += $tax;
					} elseif (isset($invoice['invoice']['taxest']['8.00'])) {
						$base = -$invoice['invoice']['taxest']['8.00']['base'];
						$jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_17>\n";
						$tax = -$invoice['invoice']['taxest']['8.00']['tax'];
						$jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_18>\n";
						$totaltax += $tax;
					}

					if (isset($invoice['taxest']['22.00'])) {
						$base = $invoice['taxest']['22.00']['base'] - $invoice['invoice']['taxest']['22.00']['base'];
						$jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
						$tax = $invoice['taxest']['22.00']['tax'] - $invoice['invoice']['taxest']['22.00']['tax'];
						$jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
						$totaltax += $tax;
					} elseif (isset($invoice['invoice']['taxest']['22.00'])) {
						$base = -$invoice['invoice']['taxest']['22.00']['base'];
						$jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
						$tax = -$invoice['invoice']['taxest']['22.00']['tax'];
						$jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
						$totaltax += $tax;
					}

					if (isset($invoice['taxest']['23.00'])) {
						$base = $invoice['taxest']['23.00']['base'] - $invoice['invoice']['taxest']['23.00']['base'];
						$jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
						$tax = $invoice['taxest']['23.00']['tax'] - $invoice['invoice']['taxest']['23.00']['tax'];
						$jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
						$totaltax += $tax;
					} elseif (isset($invoice['invoice']['taxest']['23.00'])) {
						$base = -$invoice['invoice']['taxest']['23.00']['base'];
						$jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_19>\n";
						$tax = -$invoice['invoice']['taxest']['23.00']['tax'];
						$jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</K_20>\n";
						$totaltax += $tax;
					}

					if (isset($invoice['taxest']['-2'])) {
						$base = $invoice['taxest']['-2']['base'] - $invoice['invoice']['taxest']['-2']['base'];
						$jpk_data .= "\t\t<K_31>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_31>\n";
					} elseif (isset($invoice['invoice']['taxest']['-2'])) {
						$base = -$invoice['invoice']['taxest']['-2']['base'];
						$jpk_data .= "\t\t<K_31>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</K_31>\n";
					}
				} else {
					if (isset($invoice['taxest']['-1'])) {
						if ($ue || $foreign) {
							$jpk_data .= "\t\t<K_11>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-1']['base'])) . "</K_11>\n";
							$jpk_data .= "\t\t<K_12>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-1']['base'])) . "</K_12>\n";
						} else
							$jpk_data .= "\t\t<K_10>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-1']['base'])) . "</K_10>\n";
					}

					if (!$foreign && isset($invoice['taxest']['0.00']))
						$jpk_data .= "\t\t<K_13>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['0.00']['base'])) . "</K_13>\n";

					if (isset($invoice['taxest']['5.00'])) {
						$jpk_data .= "\t\t<K_15>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['5.00']['base'])) . "</K_15>\n";
						$jpk_data .= "\t\t<K_16>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['5.00']['tax'])) . "</K_16>\n";
					}

					if (isset($invoice['taxest']['7.00'])) {
						$jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['7.00']['base'])) . "</K_17>\n";
						$jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['7.00']['tax'])) . "</K_18>\n";
					}

					if (isset($invoice['taxest']['8.00'])) {
						$jpk_data .= "\t\t<K_17>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['8.00']['base'])) . "</K_17>\n";
						$jpk_data .= "\t\t<K_18>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['8.00']['tax'])) . "</K_18>\n";
					}

					if (isset($invoice['taxest']['22.00'])) {
						$jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['22.00']['base'])) . "</K_19>\n";
						$jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['22.00']['tax'])) . "</K_20>\n";
					}

					if (isset($invoice['taxest']['23.00'])) {
						$jpk_data .= "\t\t<K_19>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['23.00']['base'])) . "</K_19>\n";
						$jpk_data .= "\t\t<K_20>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['23.00']['tax'])) . "</K_20>\n";
					}

					if (isset($invoice['taxest']['-2']))
						$jpk_data .= "\t\t<K_31>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-2']['base'])) . "</K_31>\n";

					$totaltax += $invoice['totaltax'];
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
				$jpk_data .= "\t<Faktura typ=\"G\">\n";
				$jpk_data .= "\t\t<P_1>" . strftime('%Y-%m-%d', $invoice['cdate']) . "</P_1>\n";
				$invoices[$invoiceid] = $invoice;
				$jpk_data .= "\t\t<P_2A>" . $invoice['fullnumber'] . "</P_2A>\n";
				$jpk_data .= "\t\t<P_3A>" . str_replace('&', '&amp;', $invoice['name']) . "</P_3A>\n";
				$jpk_data .= "\t\t<P_3B>" . ($invoice['postoffice'] && $invoice['postoffice'] != $invoice['city'] && $invoice['street'] ? $invoice['city'] . ', ' : '')
					. $invoice['address'] . ', ' . (empty($invoice['zip']) ? '' : $invoice['zip'] . ' ') . ($invoice['postoffice'] ? $invoice['postoffice'] : $invoice['city']) . "</P_3B>\n";
				$jpk_data .= "\t\t<P_3C>" . str_replace('&', '&amp;', $invoice['division_name']) . "</P_3C>\n";
				$jpk_data .= "\t\t<P_3D>" . $invoice['division_address'] . ', '
					. (empty($invoice['division_zip']) ? $invoice['division_city'] : $invoice['division_zip'] . ' ' . $invoice['division_city']) . "</P_3D>\n";
				if (preg_match('/^(?<country>[A-Z]{2})(?<ten>[0-9]+)$/', $invoice['division_ten'], $m)) {
					$jpk_data .= "\t\t<P_4A>" . $m['country'] . "</P_4A>\n";
					$jpk_data .= "\t\t<P_4B>" . $m['ten'] . "</P_4B>\n";
				} else
					$jpk_data .= "\t\t<P_4B>" . preg_replace('/[\s\-]/', '', $invoice['division_ten']) . "</P_4B>\n";
				if (!empty($invoice['ten']))
					if (preg_match('/^(?<country>[A-Z]{2})(?<ten>[0-9]+)$/', $invoice['ten'], $m)) {
						if (preg_match('/^[1-9]((\d[1-9])|([1-9]\d))\d{7}$/', $m['ten'])) {
							$jpk_data .= "\t\t<P_5A>" . $m['country'] . "</P_5A>\n";
							$jpk_data .= "\t\t<P_5B>" . $m['ten'] . "</P_5B>\n";
						}
					} else
						$jpk_data .= "\t\t<P_5B>" . preg_replace('/[\s\-]/', '', $invoice['ten']) . "</P_5B>\n";
				elseif ($jpk_type != 'fa')
					$jpk_data .= "\t\t<P_5B>brak</P_5B>\n";

				if (isset($invoice['invoice'])) {
					if (isset($invoice['taxest']['23.00'])) {
						$base = $invoice['taxest']['23.00']['base'] - $invoice['invoice']['taxest']['23.00']['base'];
						$jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
						$tax = $invoice['taxest']['23.00']['tax'] - $invoice['invoice']['taxest']['23.00']['tax'];
						$jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
					} elseif (isset($invoice['invoice']['taxest']['23.00'])) {
						$base = -$invoice['invoice']['taxest']['23.00']['base'];
						$jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
						$tax = -$invoice['invoice']['taxest']['23.00']['tax'];
						$jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
					} else
						$base = $tax = 0;
					$totalvalue += $base + $tax;

					if (isset($invoice['taxest']['22.00'])) {
						$base = $invoice['taxest']['22.00']['base'] - $invoice['invoice']['taxest']['22.00']['base'];
						$jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
						$tax = $invoice['taxest']['22.00']['tax'] - $invoice['invoice']['taxest']['22.00']['tax'];
						$jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
					} elseif (isset($invoice['invoice']['taxest']['22.00'])) {
						$base = -$invoice['invoice']['taxest']['22.00']['base'];
						$jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_1>\n";
						$tax = -$invoice['invoice']['taxest']['22.00']['tax'];
						$jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_1>\n";
					} else
						$base = $tax = 0;
					$totalvalue += $base + $tax;

					if (isset($invoice['taxest']['8.00'])) {
						$base = $invoice['taxest']['8.00']['base'] - $invoice['invoice']['taxest']['8.00']['base'];
						$jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
						$tax = $invoice['taxest']['8.00']['tax'] - $invoice['invoice']['taxest']['8.00']['tax'];
						$jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
					} elseif (isset($invoice['invoice']['taxest']['8.00'])) {
						$base = -$invoice['invoice']['taxest']['8.00']['base'];
						$jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
						$tax = -$invoice['invoice']['taxest']['8.00']['tax'];
						$jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
					} else
						$base = $tax = 0;
					$totalvalue += $base + $tax;

					if (isset($invoice['taxest']['7.00'])) {
						$base = $invoice['taxest']['7.00']['base'] - $invoice['invoice']['taxest']['7.00']['base'];
						$jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
						$tax = $invoice['taxest']['7.00']['tax'] - $invoice['invoice']['taxest']['7.00']['tax'];
						$jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
					} elseif (isset($invoice['invoice']['taxest']['7.00'])) {
						$base = -$invoice['invoice']['taxest']['7.00']['base'];
						$jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_2>\n";
						$tax = -$invoice['invoice']['taxest']['7.00']['tax'];
						$jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_2>\n";
					} else
						$base = $tax = 0;
					$totalvalue += $base + $tax;

					if (isset($invoice['taxest']['5.00'])) {
						$base = $invoice['taxest']['5.00']['base'] - $invoice['invoice']['taxest']['5.00']['base'];
						$jpk_data .= "\t\t<P_13_3>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_3>\n";
						$tax = $invoice['taxest']['5.00']['tax'] - $invoice['invoice']['taxest']['5.00']['tax'];
						$jpk_data .= "\t\t<P_14_3>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_3>\n";
					} elseif (isset($invoice['invoice']['taxest']['5.00'])) {
						$base = -$invoice['invoice']['taxest']['5.00']['base'];
						$jpk_data .= "\t\t<P_13_3>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_3>\n";
						$tax = -$invoice['invoice']['taxest']['5.00']['tax'];
						$jpk_data .= "\t\t<P_14_3>" . str_replace(',', '.', sprintf('%.2f', $tax)) . "</P_14_3>\n";
					} else
						$base = $tax = 0;
					$totalvalue += $base + $tax;

					if (isset($invoice['taxest']['0.00'])) {
						$base = $invoice['taxest']['0.00']['base'] - $invoice['invoice']['taxest']['0.00']['base'];
						$jpk_data .= "\t\t<P_13_6>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_6>\n";
					} elseif (isset($invoice['invoice']['taxest']['0.00'])) {
						$base = -$invoice['invoice']['taxest']['0.00']['base'];
						$jpk_data .= "\t\t<P_13_6>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_6>\n";
					} else
						$base = 0;
					$totalvalue += $base;

					if (isset($invoice['taxest']['-1'])) {
						$base = $invoice['taxest']['-1']['base'] - $invoice['invoice']['taxest']['-1']['base'];
						$jpk_data .= "\t\t<P_13_7>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_7>\n";
					} elseif (isset($invoice['invoice']['taxest']['-1'])) {
						$base = -$invoice['invoice']['taxest']['-1']['base'];
						$jpk_data .= "\t\t<P_13_7>" . str_replace(',', '.', sprintf('%.2f', $base)) . "</P_13_7>\n";
					} else
						$base = 0;
					$totalvalue += $base;

					$jpk_data .= "\t\t<P_15>" . str_replace(',', '.', sprintf("%.2f", $invoice['total'] - $invoice['invoice']['total'])) . "</P_15>\n";
				} else {
					if (isset($invoice['taxest']['23.00'])) {
						$jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['23.00']['base'])) . "</P_13_1>\n";
						$jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['23.00']['tax'])) . "</P_14_1>\n";
					}
					if (isset($invoice['taxest']['22.00'])) {
						$jpk_data .= "\t\t<P_13_1>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['22.00']['base'])) . "</P_13_1>\n";
						$jpk_data .= "\t\t<P_14_1>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['22.00']['tax'])) . "</P_14_1>\n";
					}

					if (isset($invoice['taxest']['8.00'])) {
						$jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['8.00']['base'])) . "</P_13_2>\n";
						$jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['8.00']['tax'])) . "</P_14_2>\n";
					}
					if (isset($invoice['taxest']['7.00'])) {
						$jpk_data .= "\t\t<P_13_2>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['7.00']['base'])) . "</P_13_2>\n";
						$jpk_data .= "\t\t<P_14_2>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['7.00']['tax'])) . "</P_14_2>\n";
					}

					if (isset($invoice['taxest']['5.00'])) {
						$jpk_data .= "\t\t<P_13_3>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['5.00']['base'])) . "</P_13_3>\n";
						$jpk_data .= "\t\t<P_14_3>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['5.00']['tax'])) . "</P_14_3>\n";
					}

					if (isset($invoice['taxest']['0.00']))
						$jpk_data .= "\t\t<P_13_6>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['0.00']['base'])) . "</P_13_6>\n";

					if (isset($invoice['taxest']['-1']))
						$jpk_data .= "\t\t<P_13_7>" . str_replace(',', '.', sprintf('%.2f', $invoice['taxest']['-1']['base'])) . "</P_13_7>\n";

					$jpk_data .= "\t\t<P_15>" . str_replace(',', '.', sprintf("%.2f", $invoice['total'])) . "</P_15>\n";

					$totalvalue += $invoice['total'];
				}
				$jpk_data .= "\t\t<P_16>false</P_16>\n";
				$jpk_data .= "\t\t<P_17>false</P_17>\n";
				$jpk_data .= "\t\t<P_18>" . (isset($invoice['taxest']['-2']['base']) ? 'true' : 'false') . "</P_18>\n";
				$jpk_data .= "\t\t<P_19>false</P_19>\n";
				$jpk_data .= "\t\t<P_20>false</P_20>\n";
				$jpk_data .= "\t\t<P_21>false</P_21>\n";
				$jpk_data .= "\t\t<P_23>false</P_23>\n";
				$jpk_data .= "\t\t<P_106E_2>false</P_106E_2>\n";
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
		} else
			foreach ($which as $type) {
				$i++;
				if ($i == $count) $invoice['last'] = TRUE;
				$invoice['type'] = $type;
				invoice_body($document, $invoice);
			}
	}

	if ($jpk) {
		if ($jpk_type == 'vat') {
			$jpk_data .= "\t<SprzedazCtrl>\n";
			$jpk_data .= "\t\t<LiczbaWierszySprzedazy>" . count($ids) . "</LiczbaWierszySprzedazy>\n";
			$jpk_data .= "\t\t<PodatekNalezny>" . str_replace(',', '.', sprintf('%.2f', $totaltax)) . "</PodatekNalezny>\n";
			$jpk_data .= "\t</SprzedazCtrl>\n";
		} else {
			$jpk_data .= "\t<FakturaCtrl>\n";
			$jpk_data .= "\t\t<LiczbaFaktur>" . count($ids) . "</LiczbaFaktur>\n";
			$jpk_data .= "\t\t<WartoscFaktur>" . str_replace(',', '.', sprintf('%.2f', $totalvalue)) . "</WartoscFaktur>\n";
			$jpk_data .= "\t</FakturaCtrl>\n";

			$taxrates = $DB->GetCol('SELECT DISTINCT value FROM taxes
				WHERE taxed = 1 AND value > 0 AND validfrom < ? AND (validto = 0 OR validto > ?)
				ORDER BY value DESC', array($datefrom, $dateto));
			if (empty($taxrates))
				$taxrates = array(23, 8, 5, 0, 0);
			else
				$taxrates = array_merge($taxrates, array_fill(0, 5 - count($taxrates), 0));
			$jpk_data .= "\t<StawkiPodatku>\n";
			$i = 1;
			foreach ($taxrates as $taxrate) {
				$jpk_data .= "\t\t<Stawka" . $i . ">" . str_replace(',', '.', sprintf('%.2f', $taxrate / 100))
					. "</Stawka" . $i . ">\n";
				$i++;
			}
			$jpk_data .= "\t</StawkiPodatku>\n";

			$positions = 0;
			foreach ($invoices as $invoice)
				foreach ($invoice['content'] as $idx => $position) {
					$jpk_data .="\t<FakturaWiersz typ=\"G\">\n";
					$jpk_data .="\t\t<P_2B>" . $invoice['fullnumber'] . "</P_2B>\n";
					$jpk_data .="\t\t<P_7>" . str_replace('&', '&amp;', $position['description']) . "</P_7>\n";
					$jpk_data .="\t\t<P_8A>" . str_replace('&', '&amp;', $position['content']) . "</P_8A>\n";
					if (isset($invoice['invoice'])) {
						$jpk_data .="\t\t<P_8B>" . str_replace('&', '&amp;', $position['count'] - $invoice['invoice']['content'][$idx]['count']) . "</P_8B>\n";
						$jpk_data .="\t\t<P_9A>" . str_replace(',', '.', sprintf('%.2f', $position['basevalue'] - $invoice['invoice']['content'][$idx]['basevalue'])) . "</P_9A>\n";
						$jpk_data .="\t\t<P_9B>" . str_replace(',', '.', sprintf('%.2f', $position['value'] - $invoice['invoice']['content'][$idx]['value'])) . "</P_9B>\n";
						$jpk_data .="\t\t<P_11>" . str_replace(',', '.', sprintf('%.2f', $position['totalbase'] - $invoice['invoice']['content'][$idx]['totalbase'])) . "</P_11>\n";
						$jpk_data .="\t\t<P_11A>" . str_replace(',', '.', sprintf('%.2f', $position['total'] - $invoice['invoice']['content'][$idx]['total'])) . "</P_11A>\n";
					} else {
						$jpk_data .="\t\t<P_8B>" . str_replace('&', '&amp;', $position['count']) . "</P_8B>\n";
						$jpk_data .="\t\t<P_9A>" . str_replace(',', '.', sprintf('%.2f', $position['basevalue'])) . "</P_9A>\n";
						$jpk_data .="\t\t<P_9B>" . str_replace(',', '.', sprintf('%.2f', $position['value'])) . "</P_9B>\n";
						$jpk_data .="\t\t<P_11>" . str_replace(',', '.', sprintf('%.2f', $position['totalbase'])) . "</P_11>\n";
						$jpk_data .="\t\t<P_11A>" . str_replace(',', '.', sprintf('%.2f', $position['total'])) . "</P_11A>\n";
					}
					if ($position['taxvalue'] >= 0)
						$jpk_data .= "\t\t<P_12>" . str_replace(',', '.', round($position['taxvalue'])) . "</P_12>\n";
					elseif ($position['taxvalue'] == -1)
						$jpk_data .= "\t\t<P_12>zw</P_12>\n";
					else
						$jpk_data .= "\t\t<P_12>0</P_12>\n";
					$jpk_data .="\t</FakturaWiersz>\n";
					$positions++;
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
	if(!isset($invoice['invoice']))
		$layout['pagetitle'] = trans('Invoice No. $a', $docnumber);
	else
		$layout['pagetitle'] = trans('Credit Note No. $a', $docnumber);

	$which = array();

	if (!empty($_GET['original'])) $which[] = trans('ORIGINAL');
	if (!empty($_GET['copy'])) $which[] = trans('COPY');
	if (!empty($_GET['duplicate'])) $which[] = trans('DUPLICATE');

	if (!count($which)) {
		$tmp = explode(',', ConfigHelper::getConfig('invoices.default_printpage'));
		foreach ($tmp as $t)
			if (trim($t) == 'original') $which[] = trans('ORIGINAL');
			elseif (trim($t) == 'copy') $which[] = trans('COPY');
			elseif (trim($t) == 'duplicate') $which[] = trans('DUPLICATE');

		if (!count($which)) $which[] = trans('ORIGINAL');
	}

	if ($invoice['archived'] && !in_array(trans('DUPLICATE'), $which)) {
		$invoice = $LMS->GetArchiveDocument($_GET['id']);
		if ($invoice) {
			header('Content-Type: ' . $invoice['content-type']);
			header('Content-Disposition: inline; filename=' . $invoice['filename']);
			echo $invoice['data'];
		}
		$SESSION->close();
		die;
	}

	$count = count($which);
	$i = 0;

	$invoice['dontpublish'] = $dontpublish;
	foreach ($which as $type) {
		$i++;
		if ($i == $count) $invoice['last'] = TRUE;
		$invoice['type'] = $type;
		invoice_body($document, $invoice);
	}
} else
	$SESSION->redirect('?m=invoicelist');

if (!is_null($attachment_name) && isset($docnumber)) {
	$attachment_name = str_replace('%number', $docnumber, $attachment_name);
	$attachment_name = preg_replace('/[^[:alnum:]_\.]/i', '_', $attachment_name);
} elseif ($jpk)
	if ($jpk_type == 'fa')
		$attachment_name = strftime('JPK_FA-%Y-%m-%d-%H-%M-%S.xml');
	else
		$attachment_name = strftime('JPK_VAT-%Y-%m-%d-%H-%M-%S.xml');
else
	$attachment_name = 'invoices.' . ($invoice_type == 'pdf' ? 'pdf' : 'html');

if ($jpk) {
	// send jpk data to web browser
	header('Content-Type: text/xml');
	header('Content-Disposition: attachment; filename="' . $attachment_name . '"');
	header('Pragma: public');
	echo $jpk_data;
} else
	$document->WriteToBrowser($attachment_name);

if (!$dontpublish && isset($ids) && !empty($ids))
	$LMS->PublishDocuments($ids);

?>
