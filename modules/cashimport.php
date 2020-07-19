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

$layout['pagetitle'] = trans('Cash Operations Import');

if (isset($_GET['action']) && $_GET['action'] == 'csv') {
    $search = array('"', "\n");
    $replace = array('""', ' ');

    if (isset($_GET['division']) && $_GET['division'] != '') {
        if (intval($_GET['division'])) {
            $div = ' AND c.divisionid = '.intval($_GET['division']);
        } else {
            $div = ' AND c.divisionid IS NULL';
        }
    } else {
        $div = '';
    }

    $filename = 'import-'.date('Y-m-d').($div ? '-'.intval($_GET['division']) : '').'.csv';

    header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename='.$filename);
    header('Pragma: public');

    if ($importlist = $DB->GetAll('SELECT i.date, i.value, i.customer, i.description,
		i.customerid, c.divisionid, '.$DB->Concat('UPPER(lastname)', "' '", 'name').' AS customername
		FROM cashimport i
		LEFT JOIN customers c ON (i.customerid = c.id)
		WHERE i.closed = 0 AND i.value > 0'
        .$div
        .' ORDER BY i.date')) {
        foreach ($importlist as $idx => $row) {
            printf(
                "%s,%s,\"%s\",\"%s\"\r\n",
                date('Y-m-d', $row['date']),
                str_replace(',', '.', $row['value']),
                str_replace($search, $replace, $row['customername'] ? $row['customername'] : $row['customer']),
                str_replace($search, $replace, $row['description'])
            );
        }
    }
    die;
} elseif (isset($_GET['action']) && $_GET['action'] == 'txt') {
    $filename = 'import-'.date('Y-m-d').'.txt';

    header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename='.$filename);
    header('Pragma: public');

    if ($importlist = $DB->GetAll('SELECT i.date, i.value, i.customer, i.description,
		i.customerid, '.$DB->Concat('UPPER(lastname)', "' '", 'name').' AS customername
		FROM cashimport i
		LEFT JOIN customers c ON (i.customerid = c.id)
		WHERE i.closed = 0 AND i.value > 0'
        .' ORDER BY i.date')) {
        foreach ($importlist as $idx => $row) {
            printf(
                "%s\t%s\t%s\t%s\r\n",
                date('Y-m-d', $row['date']),
                str_replace(',', '.', $row['value']),
                $row['customername'] ? $row['customername'] : $row['customer'],
                str_replace("\n", ' ', $row['description'])
            );
        }
    }
    die;
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    if ($marks = $_POST['marks']) {
        foreach ($marks as $id) {
            $DB->Execute(
                'UPDATE cashimport SET closed = 1 WHERE id = ?',
                array($id)
            );
            if ($SYSLOG) {
                list ($customerid, $sourceid, $sourcefileid) = array_values(
                    $DB->GetRow('SELECT customerid, sourceid, sourcefileid
						FROM cashimport WHERE id = ?', array($id))
                );
                $args = array(
                    SYSLOG::RES_CASHIMPORT => $id,
                    SYSLOG::RES_CUST => $customerid,
                    SYSLOG::RES_CASHSOURCE => $sourceid,
                    SYSLOG::RES_SOURCEFILE => $sourcefileid,
                    'closed' => 1,
                );
                $SYSLOG->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_UPDATE, $args);
            }
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'save') {
    if (!empty($_POST['customer'])) {
        foreach ($_POST['customer'] as $idx => $id) {
            if ($id) {
                $DB->Execute(
                    'UPDATE cashimport SET customerid = ? WHERE id = ?',
                    array($id, $idx)
                );
                if ($SYSLOG) {
                    list ($sourceid, $sourcefileid) = array_values(
                        $DB->GetRow('SELECT sourceid, sourcefileid
						FROM cashimport WHERE id = ?', array($idx))
                    );
                    $args = array(
                        SYSLOG::RES_CASHIMPORT => $idx,
                        SYSLOG::RES_CUST => $id,
                        SYSLOG::RES_CASHSOURCE => $sourceid,
                        SYSLOG::RES_SOURCEFILE => $sourcefileid,
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_UPDATE, $args);
                }
            }
        }
    }
} elseif (isset($_POST['marks'])) {
    $marks = (array) $_POST['marks'];
    $customers = $_POST['customer'];

    foreach ($marks as $idx => $id) {
        if (empty($customers[$id])) {
            $error[$id] = trans('Customer not selected!');
            unset($marks[$idx]);
        } else {
            $marks[$idx] = intval($id);
        }
    }

    if (!empty($marks)) {
        $imports = $DB->GetAll('SELECT i.*, f.idate
			FROM cashimport i
			LEFT JOIN sourcefiles f ON (f.id = i.sourcefileid)
			WHERE i.closed = 0 AND i.id IN ('.implode(',', $marks).')');
    }

    if (!empty($imports)) {
        $idate = ConfigHelper::checkConfig('finances.cashimport_use_idate');
        $icheck = ConfigHelper::checkConfig('finances.cashimport_checkinvoices');

        foreach ($imports as $import) {
            // do not insert if the record is already closed (prevent multiple inserts of the same record)
            if ($import['closed'] == 1) {
                continue;
            }

            $DB->BeginTrans();

            $balance['time'] = $idate ? $import['idate'] : $import['date'];
            $balance['type'] = 1;
            $balance['value'] = $import['value'];
            $balance['customerid'] = $customers[$import['id']];
            $balance['comment'] = $import['description'];
            $balance['importid'] = $import['id'];
            $balance['sourceid'] = $import['sourceid'];

            if ($import['value'] > 0 && $icheck) {
                if ($invoices = $DB->GetAll(
                    'SELECT d.id,
						(SELECT SUM(value*count) FROM invoicecontents WHERE docid = d.id) +
						COALESCE((SELECT SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count)
							FROM documents dd
							JOIN invoicecontents a ON (a.docid = dd.id)
        						JOIN invoicecontents b ON (dd.reference = b.docid AND a.itemid = b.itemid)
	        					WHERE dd.reference = d.id
		    					GROUP BY dd.reference), 0) AS value
					FROM documents d
					WHERE d.customerid = ? AND d.type = ? AND d.closed = 0
					GROUP BY d.id, d.cdate ORDER BY d.cdate',
                    array($balance['customerid'], DOC_INVOICE)
                )) {
                    foreach ($invoices as $inv) {
                        $sum += $inv['value'];
                    }

                    $bval = $LMS->GetCustomerBalance($balance['customerid']);
                    $value = f_round($bval + $import['value'] + $sum);

                    foreach ($invoices as $inv) {
                        $inv['value'] = f_round($inv['value']);
                        if ($inv['value'] > $value) {
                            break;
                        } else {
                            // close invoice and assigned credit notes
                            $DB->Execute(
                                'UPDATE documents SET closed = 1
								WHERE id = ? OR reference = ?',
                                array($inv['id'], $inv['id'])
                            );

                            if ($SYSLOG) {
                                $docid = $DB->GetOne(
                                    'SELECT id FROM documents
									WHERE id = ? OR reference = ?',
                                    array($inv['id'], $inv['id'])
                                );
                                $args = array(
                                    SYSLOG::RES_DOC => $docid,
                                    SYSLOG::RES_CUST => $balance['customerid'],
                                    'closed' => 1,
                                );
                                $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
                            }

                            $value -= $inv['value'];
                        }
                    }
                }
            }

            $DB->Execute('UPDATE cashimport SET closed = 1 WHERE id = ?', array($import['id']));
            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_CASHIMPORT => $import['id'],
                    SYSLOG::RES_CUST => $balance['customerid'],
                    SYSLOG::RES_CASHSOURCE => $import['sourceid'],
                    SYSLOG::RES_SOURCEFILE => $import['sourcefileid'],
                    'closed' => 1,
                );
                $SYSLOG->AddMessage(SYSLOG::RES_CASHIMPORT, SYSLOG::OPER_UPDATE, $args);
            }

            $balance['currency'] = Localisation::getCurrentCurrency();
            $LMS->AddBalance($balance);

            $DB->CommitTrans();
        }
    }
}

$divisions = $LMS->GetDivisions(array('order' => 'name'));

$divisions[0] = array('id' => 0, 'name' => '');

if ($importlist = $DB->GetAll('SELECT i.*, c.divisionid
	FROM cashimport i
	LEFT JOIN customerview c ON (i.customerid = c.id)
	WHERE i.closed = 0 AND i.value > 0
	ORDER BY i.id')) {
    $listdata['total'] = count($importlist);

    foreach ($importlist as $idx => $row) {
        if ($row['divisionid'] && isset($divisions[$row['divisionid']])) {
            $divisions[$row['divisionid']]['list'][] = $row;
        } else {
            $divisions[0]['list'][] = $row;
        }

        unset($importlist[$idx]);
    }
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$sourcefiles = $DB->GetAll('SELECT s.*, u.name AS username,
    (SELECT COUNT(*) FROM cashimport WHERE sourcefileid = s.id) AS count
    FROM sourcefiles s
    LEFT JOIN vusers u ON (u.id = s.userid)
    ORDER BY s.idate DESC');

$SMARTY->assign('divisions', $divisions);
$SMARTY->assign('listdata', isset($listdata) ? $listdata : null);
$SMARTY->assign('error', $error);
$SMARTY->assign('sourcefiles', $sourcefiles);
if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}
$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources WHERE deleted = 0 ORDER BY name'));
$SMARTY->display('cash/cashimport.html');
