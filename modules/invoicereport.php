<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$from = $_POST['from'];
$to = $_POST['to'];

switch (intval($_POST['customer_type'])) {
    case CTYPES_PRIVATE:
    case CTYPES_COMPANY:
        $ctype = $_POST['customer_type'];
        break;

    default:
        $ctype = -1; //all
}

// date format 'yyyy/mm/dd'
if ($from) {
    list($year, $month, $day) = explode('/', $from);
    $unixfrom = mktime(0, 0, 0, $month, $day, $year);
} else {
    $from = date('Y/m/d', time());
    $unixfrom = mktime(0, 0, 0); //today
}
if ($to) {
    list($year, $month, $day) = explode('/', $to);
    $unixto = mktime(23, 59, 59, $month, $day, $year);
} else {
    $to = date('Y/m/d', time());
    $unixto = mktime(23, 59, 59); //today
}

$layout['pagetitle'] = trans('Sale Registry for period $a - $b', $from, $to);

$listdata = array('tax' => 0, 'brutto' => 0, 'tax_receipt' => 0, 'brutto_receipt' => 0);
$invoicelist = array();
$taxeslist = array();
$taxes = array();

if (!empty($_POST['group'])) {
    if (is_array($_POST['group'])) {
        $groups = Utils::filterIntegers($_POST['group']);
        $groups = implode(',', $groups);
    } else {
        $groups = intval($_POST['group']);
    }

    $groupwhere = ' AND '.(isset($_POST['groupexclude']) ? 'NOT' : '').'
		EXISTS (SELECT 1 FROM customerassignments a
			WHERE a.customergroupid IN ('.$groups.')
			AND a.customerid = d.customerid)';

    $names = $DB->GetAll('SELECT name FROM customergroups WHERE id IN ('.$groups.')');

    $groupnames = '';
    foreach ($names as $idx => $row) {
        $groupnames .= ($idx ? ', ' : '') . $row['name'];
    }

    if (isset($_POST['groupexclude'])) {
        $layout['group'] = trans('Group: all excluding $a', $groupnames);
    } else {
        $layout['group'] = trans('Group: $a', $groupnames);
    }
}

if (!empty($_POST['division'])) {
    $divwhere = ' AND d.divisionid '.(isset($_POST['divexclude']) ? '!=' : '=').' '.intval($_POST['division']);

    $divname = $DB->GetOne(
        'SELECT name FROM divisions WHERE id = ?',
        array(intval($_POST['division']))
    );

    $layout['division'] = $divname;
}

// Sorting
switch ($_POST['sorttype']) {
    case 'sdate':
        $sortcol = 'CEIL(COALESCE(d.sdate, d.cdate) / 86400)';
        $wherecol = 'COALESCE(d.sdate, d.cdate)';
        break;
    case 'pdate':
        $sortcol = 'CEIL((d.cdate + (d.paytime * 86400)) / 86400)';
        $wherecol = '(d.cdate + (d.paytime * 86400))';
        break;
    case 'number':
        $sortcol = 'd.number';
        $wherecol = 'd.cdate';
        break;
    case 'cdate':
    default:
        $sortcol = 'CEIL(d.cdate / 86400)';
        $wherecol = 'd.cdate';
}

$doctypes = array();
if (!empty($_POST['doctype']) && is_array($_POST['doctype'])) {
    foreach ($_POST['doctype'] as $doctype) {
        switch ($doctype) {
            case 'invoices':
                $doctypes[] = DOC_INVOICE;
                break;
            case 'cnotes':
                $doctypes[] = DOC_CNOTE;
                break;
            case 'dnotes':
                $doctypes[] = DOC_DNOTE;
                break;
        }
    }
}
if (empty($doctypes)) {
    $doctypes = array(DOC_INVOICE, DOC_CNOTE);
}

if (in_array(DOC_DNOTE, $doctypes)) {
    $taxescount = 0;
} else {
    $taxescount = 1;
}

if (!empty($_POST['numberplanid'])) {
    if (is_array($_POST['numberplanid'])) {
        $numberplans = Utils::filterIntegers($_POST['numberplanid']);
        $numberplans = implode(',', $numberplans);
    } else {
        $numberplans = intval($_POST['numberplanid']);
    }
}

$args = array($doctypes, $unixfrom, $unixto);

$taxes = $DB->GetAllByKey('SELECT id, value, label, taxed FROM taxes', 'id');

$documents = $DB->GetAll('SELECT d.id, d.type,
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
	    ' .
        ( $ctype != -1 ? ' LEFT JOIN customers cu ON d.customerid = cu.id ' : '' )
        . ' WHERE cancelled = 0 AND d.type IN ? AND (' . $wherecol . ' BETWEEN ? AND ?) '
        .(isset($numberplans) ? 'AND d.numberplanid IN (' . $numberplans . ')' : '')
        .(isset($divwhere) ? $divwhere : '')
        .(isset($groupwhere) ? $groupwhere : '')
        .( $ctype != -1 ? ' AND cu.type = ' . $ctype : '')
        .' AND NOT EXISTS (
                	    SELECT 1 FROM customerassignments a
			    JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			    WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)
	    ORDER BY ' . $sortcol . ', d.id', $args);

if ($documents) {
    foreach ($documents as $document) {
        $idx = $document['id'];
        $doctype = $document['type'];

        switch ($doctype) {
            case DOC_INVOICE:
            case DOC_CNOTE:
                $document = array_merge($document, $LMS->GetInvoiceContent($idx, LMSFinanceManager::INVOICE_CONTENT_DETAIL_GENERAL));
                break;
            case DOC_DNOTE:
                $document = $LMS->GetNoteContent($idx);
                break;
        }

        $invoicelist[$idx]['custname'] = $document['name'];
        $invoicelist[$idx]['custaddress'] = (empty($document['zip']) ? '' : $document['zip'] . ' ') . $document['city'] . ', ' . $document['address'];
        $invoicelist[$idx]['ten'] = ($document['ten'] ? trans('TEN') . ' ' . $document['ten'] : ($document['ssn'] ? trans('SSN') . ' ' . $document['ssn'] : ''));
        $invoicelist[$idx]['number'] = docnumber(array(
            'number' => $document['number'],
            'template' => $document['template'],
            'cdate' => $document['cdate'],
            'customerid' => $document['customerid'],
        ));
        $invoicelist[$idx]['cdate'] = $document['cdate'];
        $invoicelist[$idx]['sdate'] = $document['sdate'];
        $invoicelist[$idx]['pdate'] = $document['pdate'];
        $invoicelist[$idx]['flags'] = $document['flags'];
        $invoicelist[$idx]['customerid'] = $document['customerid'];
        $invoicelist[$idx]['currency'] = $document['currency'];
        $invoicelist[$idx]['currencyvalue'] = $document['currencyvalue'];

        foreach ($document['content'] as $itemid => $item) {
            $taxid = intval($item['taxid']);

            if (!isset($invoicelist[$idx][$taxid])) {
                $invoicelist[$idx][$taxid]['tax'] = 0;
                $invoicelist[$idx][$taxid]['val'] = 0;
            }

            if (!isset($invoicelist[$idx]['tax'])) {
                $invoicelist[$idx]['tax'] = 0;
            }
            if (!isset($invoicelist[$idx]['brutto'])) {
                $invoicelist[$idx]['brutto'] = 0;
            }

            if (!isset($invoicelist[$idx]['tax_receipt'])) {
                $invoicelist[$idx]['tax_receipt'] = 0;
            }
            if (!isset($invoicelist[$idx]['brutto_receipt'])) {
                $invoicelist[$idx]['brutto_receipt'] = 0;
            }

            if ($doctype == DOC_DNOTE) {
                $tax = 0;
                $brutto = $item['value'];
                $netto = $item['value'];
            } elseif (isset($document['invoice'])) {
                $tax = $item['totaltax'] - $document['invoice']['content'][$itemid]['totaltax'];
                $netto = $item['totalbase'] - $document['invoice']['content'][$itemid]['totalbase'];
                $brutto = $item['total'] - $document['invoice']['content'][$itemid]['total'];
            } else {
                $tax = $item['totaltax'];
                $netto = $item['totalbase'];
                $brutto = $item['total'];
            }

            $invoicelist[$idx][$taxid]['tax'] += $tax;
            $invoicelist[$idx][$taxid]['val'] += $netto;
            $invoicelist[$idx]['tax'] += $tax;
            $invoicelist[$idx]['brutto'] += $brutto;

            if (!isset($listdata[$taxid])) {
                $listdata[$taxid]['tax'] = 0;
                $listdata[$taxid]['val'] = 0;
                $listdata[$taxid]['tax_receipt'] = 0;
                $listdata[$taxid]['val_receipt'] = 0;
            }

            if (!empty($invoicelist[$idx]['flags'][DOC_FLAG_RECEIPT])) {
                $listdata[$taxid]['tax_receipt'] += $tax * $document['currencyvalue'];
                $listdata[$taxid]['val_receipt'] += $netto * $document['currencyvalue'];
                $listdata['tax_receipt'] += $tax * $document['currencyvalue'];
                $listdata['brutto_receipt'] += $brutto * $document['currencyvalue'];
            } else {
                $listdata[$taxid]['tax'] += $tax * $document['currencyvalue'];
                $listdata[$taxid]['val'] += $netto * $document['currencyvalue'];
                $listdata['tax'] += $tax * $document['currencyvalue'];
                $listdata['brutto'] += $brutto * $document['currencyvalue'];
            }
        }
    }

    // get used tax rates for building report table
    foreach ($listdata as $idx => $val) {
        if (is_int($idx)) {
            $tax = $taxes[$idx];
            $tax['value'] = f_round($tax['value']);
            $taxeslist[$idx] = $tax;
            $taxescount += $tax['value'] ? 2 : 1;
        }
    }
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('doctypes', $doctypes);
$SMARTY->assign('taxes', $taxeslist);
$SMARTY->assign('taxescount', $taxescount);
$SMARTY->assign('layout', $layout);
$SMARTY->assign('invoicelist', $invoicelist);

if (isset($_POST['extended'])) {
    $pages = array();
    $totals = array();
    $reccount = count($invoicelist);

    // hidden option: records count for one page of printout
    // I thinks 20 records is fine, but someone needs 19.
    $rows = ConfigHelper::getConfig('phpui.printout_pagelimit', 20);

    // create a new array for use with {section}
    // and do some calculations (summaries)
    $i=1;
    foreach ($invoicelist as $row) {
        $invoicelist2[] = $row;

        $page = ceil($i/$rows);

        if (!empty($row['flags'][DOC_FLAG_RECEIPT])) {
            $totals[$page]['total_receipt'] += $row['brutto'] * $row['currencyvalue'];
            $totals[$page]['sumtax_receipt'] += $row['tax'] * $row['currencyvalue'];
            foreach ($taxeslist as $idx => $tax) {
                $totals[$page]['val_receipt'][$idx] += $row[$idx]['val'] * $row['currencyvalue'];
                $totals[$page]['tax_receipt'][$idx] += $row[$idx]['tax'] * $row['currencyvalue'];
            }
        } else {
            $totals[$page]['total'] += $row['brutto'] * $row['currencyvalue'];
            $totals[$page]['sumtax'] += $row['tax'] * $row['currencyvalue'];

            foreach ($taxeslist as $idx => $tax) {
                $totals[$page]['val'][$idx] += $row[$idx]['val'] * $row['currencyvalue'];
                $totals[$page]['tax'][$idx] += $row[$idx]['tax'] * $row['currencyvalue'];
            }
        }

        $i++;
    }

    foreach ($totals as $page => $t) {
        $pages[] = $page;

        $totals[$page]['alltotal_receipt'] = $totals[$page-1]['alltotal_receipt'] + $t['total_receipt'];
        $totals[$page]['allsumtax_receipt'] = $totals[$page-1]['allsumtax_receipt'] + $t['sumtax_receipt'];
        $totals[$page]['alltotal'] = $totals[$page-1]['alltotal'] + $t['total'];
        $totals[$page]['allsumtax'] = $totals[$page-1]['allsumtax'] + $t['sumtax'];

        foreach ($taxeslist as $idx => $tax) {
            $totals[$page]['allval_receipt'][$idx] = $totals[$page-1]['allval_receipt'][$idx] + $t['val_receipt'][$idx];
            $totals[$page]['alltax_receipt'][$idx] = $totals[$page-1]['alltax_receipt'][$idx] + $t['tax_receipt'][$idx];
            $totals[$page]['allval'][$idx] = $totals[$page-1]['allval'][$idx] + $t['val'][$idx];
            $totals[$page]['alltax'][$idx] = $totals[$page-1]['alltax'][$idx] + $t['tax'][$idx];
        }
    }

    $SMARTY->assign('invoicelist', $invoicelist2);
    $SMARTY->assign('pages', $pages);
    $SMARTY->assign('rows', $rows);
    $SMARTY->assign('totals', $totals);
    $SMARTY->assign('pagescount', count($pages));
    $SMARTY->assign('reccount', $reccount);
    if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
        $SMARTY->assign('printcustomerid', $_POST['printcustomerid']);
        $SMARTY->assign('printonlysummary', $_POST['printonlysummary']);
        $output = $SMARTY->fetch('invoice/invoicereport-ext.html');
        html2pdf($output, trans('Reports'), $layout['pagetitle'], null, null, 'L', array(5, 5, 5, 5), ($_GET['save'] == 1) ? true : false);
    } else {
        $SMARTY->assign('printcustomerid', $_POST['printcustomerid']);
        $SMARTY->assign('printonlysummary', $_POST['printonlysummary']);
        $SMARTY->display('invoice/invoicereport-ext.html');
    }
} else {
    if (strtolower(ConfigHelper::getConfig('phpui.report_type')) == 'pdf') {
        $SMARTY->assign('printcustomerid', $_POST['printcustomerid']);
        $SMARTY->assign('printonlysummary', $_POST['printonlysummary']);
        $output = $SMARTY->fetch('invoice/invoicereport.html');
        html2pdf($output, trans('Reports'), $layout['pagetitle'], null, null, 'L', array(5, 5, 5, 5), ($_GET['save'] == 1) ? true : false);
    } else {
        $SMARTY->assign('printcustomerid', $_POST['printcustomerid']);
        $SMARTY->assign('printonlysummary', $_POST['printonlysummary']);
        $SMARTY->display('invoice/invoicereport.html');
    }
}
