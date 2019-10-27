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

function GetCustomerCovenants($id)
{
    global $DB;

    if (!$id) {
        return null;
    }

    if ($invoicelist = $DB->GetAllByKey('SELECT docid AS id, cdate, SUM(value)*-1 AS value,
                cash.currency, cash.currencyvalue, number, numberplans.template,
				d.customerid, reference AS ref,
				(SELECT dd.id FROM documents dd WHERE dd.reference = docid AND dd.closed = 0 LIMIT 1) AS reference
			FROM cash
			LEFT JOIN documents d ON (docid = d.id)
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			WHERE cash.customerid = ? AND d.type IN (?,?) AND d.closed = 0
			GROUP BY docid, cdate, number, numberplans.template, reference, d.customerid, cash.currency, cash.currencyvalue
			HAVING SUM(value) < 0
			ORDER BY cdate DESC', 'id', array($id, DOC_INVOICE, DOC_CNOTE))) {
        foreach ($invoicelist as $idx => $row) {
            if ($row['ref'] && isset($invoicelist[$row['ref']])) {
                unset($invoicelist[$idx]);
                continue;
            }

            $invoicelist[$idx]['number'] = docnumber(array(
                'number' => $row['number'],
                'template' => $row['template'],
                'cdate' => $row['cdate'],
                'customerid' => $row['customerid'],
            ));

            // invoice has cnote reference
            if ($row['reference']) {
                // get cnotes values if those values decreases invoice value
                if ($cnotes = $DB->GetAll(
                    'SELECT SUM(value) AS value, cash.currency, cash.currencyvalue,
                            cdate, number, numberplans.template, d.customerid
						FROM cash
						LEFT JOIN documents d ON (docid = d.id)
						LEFT JOIN numberplans ON (numberplanid = numberplans.id)
						WHERE reference = ? AND d.closed = 0
						GROUP BY docid, cdate, number, numberplans.template, d.customerid, cash.currency, cash.currencyvalue',
                    array($row['id'])
                )) {
                    $invoicelist[$idx]['number'] .= ' (';
                    foreach ($cnotes as $cidx => $cnote) {
                        $invoicelist[$idx]['number'] .= docnumber(array(
                            'number' => $cnote['number'],
                            'template' => $cnote['template'],
                            'cdate' => $cnote['cdate'],
                            'customerid' => $cnote['customerid'],
                        ));
                        $invoicelist[$idx]['value'] -= $cnote['value'];
                        if ($cidx < count($cnotes)-1) {
                            $invoicelist[$idx]['number'] .= ',';
                        }
                    }
                    $invoicelist[$idx]['number'] .= ')';
                }
            }
        }
    } else {
        $invoicelist = array();
    }

    if ($notelist = $DB->GetAllByKey('
		SELECT d.id, d.cdate, number, np.template, d.customerid, SUM(n.value) AS value,
		    c.currency, c.currencyvalue
		FROM documents d
		LEFT JOIN debitnotecontents n ON (n.docid = d.id)
		JOIN cash c ON c.docid = d.id AND c.itemid = n.itemid
		LEFT JOIN numberplans np ON (numberplanid = np.id)
		WHERE d.customerid = ? AND d.type = ? AND d.closed = 0
		GROUP BY d.id, d.cdate, number, np.template, d.customerid, c.currency, c.currencyvalue
		ORDER BY d.cdate DESC', 'id', array($id, DOC_DNOTE))) {
        foreach ($notelist as $idx => $row) {
            $notelist[$idx]['number'] = docnumber(array(
                'number' => $row['number'],
                'template' => $row['template'],
                'cdate' => $row['cdate'],
                'customerid' => $row['customerid'],
            ));
        }
        $invoicelist = array_merge($invoicelist, $notelist);
    }

    return $invoicelist;
}

function GetCustomerNotes($id)
{
    global $DB;

    if (!$id) {
        return null;
    }

    if ($invoicelist = $DB->GetAll('SELECT docid AS id, cdate, SUM(value) AS value, number, numberplans.template, documents.customerid
			FROM cash
			LEFT JOIN documents ON (docid = documents.id)
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			WHERE cash.customerid = ? AND documents.type = ? AND documents.closed = 0
			GROUP BY docid, cdate, number, numberplans.template, documents.customerid
			HAVING SUM(value) > 0
			ORDER BY cdate DESC', array($id, DOC_CNOTE))) {
        foreach ($invoicelist as $idx => $row) {
            $invoicelist[$idx]['number'] = docnumber(array(
                'number' => $row['number'],
                'template' => $row['template'],
                'cdate' => $row['cdate'],
                'customerid' => $row['customerid'],
            ));
        }

        return $invoicelist;
    }
}

function GetCashRegistriesXajax($cid, $regid)
{
    global $LMS, $SMARTY;

    $result = new xajaxResponse();

    $cashreglist = $LMS->GetCashRegistries($cid);
    $SMARTY->assign('cashreglist', $cashreglist);
    $SMARTY->assign('regid', $regid);
    $contents = $SMARTY->fetch('receipt/receiptcashregistries.html');
    $result->assign('cashregistries', 'innerHTML', $contents);

    return $result;
}

function GetCashRegistryBalance($regid)
{
    $result = new xajaxResponse();

    $DB = LMSDB::getInstance();

    $balance = $DB->GetOne('SELECT SUM(value) FROM receiptcontents
				WHERE regid = ?', array($regid));

    $result->script("$('form[name=\"movecash\"] input[name=\"value\"]').val(" . $balance . ")");

    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('GetCashRegistriesXajax', 'GetCashRegistryBalance'));
$SMARTY->assign('xajax', $LMS->RunXajax());

// receipt positions adding with double click protection
function additem(&$content, $item)
{
    if (is_array($content)) {
        for ($i=0, $x=count($content); $i<$x; $i++) {
            if ($content[$i]['value'] == $item['value']
                && $content[$i]['description'] == $item['description']
                && $content[$i]['posuid'] + 1 > $item['posuid']) {
                break;
            }
        }
    } else {
        $content = array();
    }

    if ($i == $x) {
        $content[] = $item;
    }
}

$SESSION->restore('receiptcontents', $contents);
$SESSION->restore('receiptcustomer', $customer);
$SESSION->restore('receipt', $receipt);
$SESSION->restore('receiptregid', $receipt['regid']);
$SESSION->restore('receipttype', $receipt['type']);
$SESSION->restore('receiptadderror', $error);

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'init':
        $oldreg = $receipt['regid'];
            unset($receipt);
            unset($contents);
            unset($customer);
            unset($error);

        // get default receipt's numberplanid and next number
        $receipt['regid'] = isset($_GET['regid']) ? $_GET['regid'] : $oldreg;
        $receipt['type'] = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['type']) ? $_POST['type'] : 0);
        $receipt['customerid'] = isset($_GET['customerid']) ? $_GET['customerid'] : null;

        $cashreglist = $LMS->GetCashRegistries($receipt['customerid']);

        // when registry is not selected but we've got only one registry in database
        if (!$receipt['regid'] && (!empty($cashreglist) && count($cashreglist) == 1)) {
            $receipt['regid'] = key($cashreglist);
        }

        if (!$receipt['regid'] || !$receipt['type']) {
            break;
        }

        $receipt['cdate'] = time();

        if ($receipt['type'] == 'in') {
            $receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
        } elseif ($receipt['type'] == 'out') {
            $receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
            if ($DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']))<=0) {
                $error['regid'] = trans('There is no cash in selected registry!');
            }
        }

        if ($receipt['numberplanid']) {
            if (strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==false) {
                $receipt['extended'] = true;
            }
        }

        if (!isset($error) && $receipt['customerid'] && $LMS->CustomerExists($receipt['customerid'])) {
            $customer = $LMS->GetCustomer($receipt['customerid'], true);
            $customer['groups'] = $LMS->CustomergroupGetForCustomer($receipt['customerid']);
            if (!ConfigHelper::checkConfig('receipts.show_notes')) {
                unset($customer['notes']);
            }

            // niezatwierdzone dokumenty klienta
            if (ConfigHelper::checkConfig('receipts.show_documents_warning')) {
                if ($DB->GetOne('SELECT COUNT(*) FROM documents WHERE customerid = ? AND closed = 0 AND type < 0', array($receipt['customerid']))) {
                    $documents_warning = ConfigHelper::getConfig('receipts.documents_warning');
                    if (!empty($documents_warning)) {
                        $customer['docwarning'] = $documents_warning;
                    } else {
                        $customer['docwarning'] = trans('Customer has got unconfirmed documents!');
                    }
                }
            }

            // jesli klient posiada zablokowane komputery poinformujmy
            // o tym kasjera, moze po wplacie trzeba bedzie zmienic ich status
            if (ConfigHelper::checkConfig('receipts.show_nodes_warning')) {
                if ($DB->GetOne('SELECT COUNT(*) FROM vnodes WHERE ownerid = ? AND access = 0', array($receipt['customerid']))) {
                    $nodes_warning = ConfigHelper::getConfig('receipts.nodes_warning');
                    if (!empty($nodes_warning)) {
                        $customer['nodeswarning'] = $nodes_warning;
                    } else {
                        $customer['nodeswarning'] = trans('Customer has got disconnected nodes!');
                    }
                }
            }

            // jesli klient posiada komputery przypisane do wybranych grup..., u mnie
            // komputery zadluzonych dodawane sa do grupy "zadluzenie"
            $show_nodegroups_warning = ConfigHelper::getConfig('receipts.show_nodegroups_warning');
            if (!empty($show_nodegroups_warning)) {
                $list = preg_split("/\s+/", $show_nodegroups_warning);
                if ($DB->GetOne(
                    'SELECT COUNT(*) FROM vnodes n
						JOIN nodegroupassignments a ON (n.id = a.nodeid)
						JOIN nodegroups g ON (g.id = a.nodegroupid)
						WHERE n.ownerid = ? AND UPPER(g.name) IN (UPPER(\''
                        .implode("'),UPPER('", $list).'\'))',
                    array($receipt['customerid'])
                )) {
                    $nodegroups_warning = ConfigHelper::getConfig('receipts.nodegroups_warning');
                    if (!empty($nodegroups_warning)) {
                        $customer['nodegroupswarning'] = $nodegroups_warning;
                    } else {
                        $customer['nodegroupswarning'] = trans('Customer has got nodes in groups: <b>$a</b>!', $show_nodegroups_warning);
                    }
                }
            }
        }
        break;

    case 'setreg':
            unset($receipt);
            unset($contents);
            unset($customer);
            unset($error);

        // get default receipt's numberplanid and next number
        $receipt = ($_POST['receipt']) ? $_POST['receipt'] : null;
        $receipt['customerid'] = isset($_POST['customerid']) ? $_POST['customerid'] : null;
        $receipt['type'] = isset($receipt['type']) ? $receipt['type'] : $_POST['type'];

        if (!$receipt['regid']) {
            $error['regid'] = trans('Registry not selected!');
        } else if ($DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $receipt['regid']))<=1) {
            $error['regid'] = trans('You have no write rights to selected registry!');
        }

        if (isset($error)) {
            break;
        }

        $receipt['cdate'] = time();

        if ($receipt['type'] == 'in') {
            $receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
        } else {
            $receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
            if ($DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']))<=0) {
                $error['regid'] = trans('There is no cash in selected registry!');
            }
        }

        if ($receipt['numberplanid']) {
            if (strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==false) {
                $receipt['extended'] = true;
            }
        }
        break;

    case 'additem':
        unset($error['nocash']);

        $itemdata = r_trim($_POST);
        $itemdata['value'] = round((float) str_replace(',', '.', $itemdata['value']), 2);
        $itemdata['posuid'] = (string) getmicrotime();
        $itemdata['value'] = str_replace(',', '.', $itemdata['value']);

        if ($receipt['type'] != 'in') {
            // sprawdzamy czy mamy tyle kasy w kasie ;)
            $cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));

            $sum = 0;
            if ($contents) {
                foreach ($contents as $item) {
                    $sum += $item['value'];
                }
            }
            $sum += $itemdata['value'];

            if ($cash < $sum) {
                $error['nocash'] = trans('There is no cash in selected registry! You can expense only $a.', moneyf($cash));
            }
        }

        if (!$error && $itemdata['value'] && $itemdata['description']) {
            additem($contents, $itemdata);
        }
        break;

    case 'additemlist':
        if (isset($_POST['marks'])) {
            unset($error['nocash']);

            $cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));

            foreach ($_POST['marks'] as $id) {
                $row = $DB->GetRow('SELECT SUM(value) AS value, cash.currency, cash.currencyvalue,
                            number, cdate, numberplans.template, documents.type AS type, documents.customerid,
						    (SELECT dd.id FROM documents dd WHERE dd.reference = docid AND dd.closed = 0 LIMIT 1) AS reference
						    FROM cash 
						    LEFT JOIN documents ON (docid = documents.id)
						    LEFT JOIN numberplans ON (numberplanid = numberplans.id)
						    WHERE docid = ?
						    GROUP BY docid, number, cdate, numberplans.template, documents.type, documents.customerid,
						        cash.currency, cash.currencyvalue', array($id));

                $itemdata['value'] = ($receipt['type']=='in' ? -$row['value'] : $row['value']) * $row['currencyvalue'];
                $itemdata['docid'] = $id;
                $itemdata['posuid'] = (string) (getmicrotime()+$id);

                if ($row['type']==DOC_INVOICE) {
                    $itemdata['description'] = trans('Invoice No. $a', docnumber(array(
                        'number' => $row['number'],
                        'template' => $row['template'],
                        'cdate' => $row['cdate'],
                        'customerid' => $row['customerid'],
                    )));
                } elseif ($row['type']==DOC_CNOTE) {
                    $itemdata['description'] = trans('Credit Note No. $a', docnumber(array(
                        'number' => $row['number'],
                        'template' => $row['template'],
                        'cdate' => $row['cdate'],
                        'customerid' => $row['customerid'],
                    )));
                } else {
                    $itemdata['description'] = trans('Debit Note No. $a', docnumber(array(
                        'number' => $row['number'],
                        'template' => $row['template'],
                        'cdate' => $row['cdate'],
                        'customerid' => $row['customerid'],
                    )));
                }

                if ($row['reference'] && $receipt['type']=='in') {
                    // get cnotes values if those values decreases invoice value
                    if ($cnotes = $DB->GetAll(
                        'SELECT SUM(value) AS value, docid, cdate, number, numberplans.template,
							d.customerid
						FROM cash
						LEFT JOIN documents d ON (docid = d.id)
						LEFT JOIN numberplans ON (numberplanid = numberplans.id)
						WHERE reference = ? AND d.closed = 0
						GROUP BY docid, cdate, number, numberplans.template, d.customerid',
                        array($id)
                    )) {
                        $itemdata['description'] .= ' (';
                        foreach ($cnotes as $cidx => $cnote) {
                            $itemdata['description'] .= docnumber(array(
                                'number' => $cnote['number'],
                                'template' => $cnote['template'],
                                'cdate' => $cnote['cdate'],
                                'customerid' => $cnote['customerid'],
                            ));
                            $itemdata['value'] -= $cnote['value'];
                            $itemdata['references'][] = $cnote['docid'];
                            if ($cidx < count($cnotes)-1) {
                                $itemdata['description'] .= ',';
                            }
                        }
                        $itemdata['description'] .= ')';
                    }
                }

                if ($receipt['type'] != 'in') {
                    // sprawdzamy czy mamy tyle kasy w kasie ;)
                    $sum = 0;
                    if ($contents) {
                        foreach ($contents as $item) {
                            $sum += $item['value'];
                        }
                    }
                    $sum += $itemdata['value'];

                    if ($cash < $sum) {
                        $error['nocash'] = trans('There is no cash in selected registry! You can expense only $a.', moneyf($cash));
                        break;
                    }
                }

                if (!$error) {
                    additem($contents, $itemdata);
                }
            }
        }
        break;
    case 'deletepos':
        if (count($contents)) {
            foreach ($contents as $idx => $row) {
                if ($row['posuid'] == $_GET['posuid']) {
                    unset($contents[$idx]);
                }
            }
        }
        break;
    case 'setcustomer':
        $oldreg = $receipt['regid'];
        $oldtype = $receipt['type'];
        $oldcid = $customer['id'];
        unset($receipt);
        unset($customer);
        unset($error);

        if ($receipt = $_POST['receipt']) {
            foreach ($receipt as $key => $val) {
                $receipt[$key] = $val;
            }
        }

        //$receipt['customerid'] = $_POST['customerid'];
        $receipt['type'] = isset($_POST['type']) ? $_POST['type'] : $oldtype;

        if ($receipt['regid'] != $oldreg || !$receipt['numberplanid']) {
            if ($receipt['type'] == 'in') {
                $receipt['numberplanid'] = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
            } else {
                $receipt['numberplanid'] = $DB->GetOne('SELECT out_numberplanid FROM cashregs WHERE id=?', array($receipt['regid']));
            }
        }

        if (isset($receipt['cdate']) && $receipt['cdate']) {
            list($year, $month, $day) = explode('/', $receipt['cdate']);
            if (checkdate($month, $day, $year)) {
                $receipt['cdate'] = mktime(date('G', time()), date('i', time()), date('s', time()), $month, $day, $year);
            } else {
                $error['cdate'] = trans('Incorrect date format!');
                $receipt['cdate'] = time();
                break;
            }
        } else {
            $receipt['cdate'] = time();
        }

        if ($receipt['cdate'] && !isset($receipt['cdatewarning'])) {
            $maxdate = $DB->GetOne('SELECT MAX(cdate) FROM documents 
						WHERE type = ? AND numberplanid = ?', array(DOC_RECEIPT, $receipt['numberplanid']));

            if ($receipt['cdate'] < $maxdate) {
                $error['cdate'] = trans('Last date of receipt settlement is $a. If sure, you want to write receipt with date of $b, then click "Submit" again.', date('Y/m/d H:i', $maxdate), date('Y/m/d H:i', $receipt['cdate']));
                $receipt['cdatewarning'] = 1;
            }
        }

        if (isset($receipt['number']) && $receipt['number']) {
            if (!preg_match('/^[0-9]+$/', $receipt['number'])) {
                $error['number'] = trans('Receipt number must be integer!');
            } elseif ($LMS->DocumentExists(array(
                    'number' => $receipt['number'],
                    'doctype' => DOC_RECEIPT,
                    'planid' => $receipt['numberplanid'],
                    'cdate' => $receipt['cdate'],
                ))) {
                $error['number'] = trans('Receipt number $a already exists!', $receipt['number']);
            }
        }

        if ($receipt['numberplanid'] && !isset($receipt['extnumber'])) {
            if (strpos($DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid'])), '%I')!==false) {
                $receipt['extended'] = true;
            }
        }

        $rights = $DB->GetOne('SELECT rights FROM cashrights WHERE regid=? AND userid=?', array($receipt['regid'], Auth::GetCurrentUser()));

        if (isset($receipt['o_type'])) {
            switch ($receipt['o_type']) {
                case 'customer':
                    if (($rights & 2)!=2) {
                        $rightserror = true;
                    }
                    break;
                case 'move':
                    if (($rights & 4)!=4) {
                        $rightserror = true;
                    }
                    break;
                case 'advance':
                    if (($rights & 8)!=8) {
                        $rightserror = true;
                    }
                    break;
                case 'other':
                    if (($rights & 16)!=16) {
                        $rightserror = true;
                    }
                    break;
            }
        } else {
            $receipt['o_type'] = 'customer';
        }

        if (!$receipt['regid']) {
            $error['regid'] = trans('Registry not selected!');
        } elseif (isset($rightserror)) {
            $error['regid'] = trans('You don\'t have permission to add receipt in selected cash registry!');
        }

        if ($receipt['o_type'] != 'customer') {
            $receipt['customerid'] = null;

            switch ($receipt['o_type']) {
                case 'advance':
                    if (trim($receipt['adv_name']) == '') {
                        $error['adv_name'] = trans('Target is required!');
                    }
                    break;
                case 'other':
                    if (trim($receipt['other_name']) == '') {
                        $error['other_name'] = trans('Target is required!');
                    }
                    break;
            }

            if (!isset($error)) {
                $receipt['selected'] = true;
            }
            break;
        }

        if (isset($_GET['customerid']) && $_GET['customerid'] != '') {
            $cid = intval($_GET['customerid']);
        } else {
            $cid = isset($_POST['customerid']) ? intval($_POST['customerid']) : null;
        }

        $receipt['customerid'] = $cid;

        if (!isset($error) && $cid) {
            if ($LMS->CustomerExists($cid)) {
                if ($receipt['type'] == 'out') {
                    $balance = $LMS->GetCustomerBalance($cid);
                    if ($balance<0) {
                        $error['customerid'] = trans('Selected customer is in debt for $a!', moneyf($balance*-1));
                    }
                }

                if (!isset($error)) {
                    $customer = $LMS->GetCustomer($cid, true);
                    $customer['groups'] = $LMS->CustomergroupGetForCustomer($cid);
                    if (!ConfigHelper::checkConfig('receipts.show_notes')) {
                        unset($customer['notes']);
                    }

                    // niezatwierdzone dokumenty klienta
                    if (ConfigHelper::checkConfig('receipts.show_documents_warning')) {
                        if ($DB->GetOne('SELECT COUNT(*) FROM documents WHERE customerid = ? AND closed = 0 AND type < 0', array($cid))) {
                            $documents_warning = ConfigHelper::getConfig('receipts.documents_warning');
                            if (!empty($documents_warning)) {
                                $customer['docwarning'] = $documents_warning;
                            } else {
                                $customer['docwarning'] = trans('Customer has got unconfirmed documents!');
                            }
                        }
                    }

                    // jesli klient posiada zablokowane komputery poinformujmy
                    // o tym kasjera, moze po wplacie trzeba bedzie zmienic ich status

                    if (ConfigHelper::checkConfig('receipts.show_nodes_warning')) {
                        if ($DB->GetOne('SELECT COUNT(*) FROM vnodes WHERE ownerid = ? AND access = 0', array($cid))) {
                            $nodes_warning = ConfigHelper::getConfig('receipts.nodes_warning');
                            if (!empty($nodes_warning)) {
                                $customer['nodeswarning'] = $nodes_warning;
                            } else {
                                $customer['nodeswarning'] = trans('Customer has got disconnected nodes!');
                            }
                        }
                    }

                    // jesli klient posiada komputery przypisane do wybranych grup, u mnie
                    // komputery zadluzonych dodawane sa do grupy "zadluzenie"
                    $show_nodegroups_warning = ConfigHelper::getConfig('receipts.show_nodegroups_warning');
                    if (!empty($show_nodegroups_warning)) {
                        $list = preg_split("/\s+/", $show_nodegroups_warning);

                        if ($DB->GetOne(
                            'SELECT COUNT(*) FROM vnodes n
			    				JOIN nodegroupassignments a ON (n.id = a.nodeid)
				    			JOIN nodegroups g ON (g.id = a.nodegroupid)
					    		WHERE n.ownerid = ? AND UPPER(g.name) IN (UPPER(\''
                            .implode("'),UPPER('", $list).'\'))',
                            array($cid)
                        )) {
                            $nodegroups_warning = ConfigHelper::getConfig('receipts.nodegroups_warning');
                            if (!empty($nodegroups_warning)) {
                                $customer['nodegroupswarning'] = $nodegroups_warning;
                            } else {
                                $customer['nodegroupswarning'] = trans('Customer has got nodes in group(s): <b>$a</b>!', $show_nodegroups_warning);
                            }
                        }
                    }

                    // remove positions if customer was changed
                    if ($oldcid != $customer['id']) {
                        unset($contents);
                    }
                }
            }
        }

        if (!isset($error) && isset($customer)) {
            $receipt['selected'] = true;
        }
        break;

    case 'save':
        if ($contents && $customer) {
            $receipt['customer'] = $customer;
            $receipt['contents'] = $contents;
            $result = $LMS->AddReceipt($receipt);
            if (is_array($result)) {
                $error = array_merge($error, $result);
            } else {
                $rid = $result;
            }


            $hook_data = $LMS->executeHook(
                'receiptadd_after_submit',
                array(
                    'customer' => $customer,
                )
            );
            $print = true;
        } elseif ($contents && ($receipt['o_type'] == 'other'
                || $receipt['o_type'] == 'advance')) {
            $receipt['contents'] = $contents;
            $result = $LMS->AddReceipt($receipt);
            if (is_array($result)) {
                $error = array_merge($error, $result);
            } else {
                $rid = $result;
            }

            $print = true;
        }

        if (isset($print)) {
            $SESSION->remove('receiptcontents');
            $SESSION->remove('receiptcustomer');
            $SESSION->remove('receipt');
            $SESSION->remove('receiptadderror');

            if (isset($_GET['print'])) {
                $which = isset($_GET['which']) ? $_GET['which'] : 0;

                $SESSION->save('receiptprint', array('receipt' => $rid, 'which' => $which));
            }

            $SESSION->redirect('?m=receiptlist&regid='.$receipt['regid'].'#'.$rid);
        }
        break;

    case 'movecash':
        $value = str_replace(',', '.', $_POST['value']);
        $dest = $_POST['registry'];

        if ($value && $dest) {
            $cash = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid = ?', array($receipt['regid']));

            if ($cash < $value) {
                $error['nocash'] = trans('There is no cash in selected registry! You can expense only $a.', moneyf($cash));
                break;
            }

            $DB->BeginTrans();

            if (!$receipt['number']) {
                $receipt['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => DOC_RECEIPT,
                    'planid' => $receipt['numberplanid'],
                    'cdate' => $receipt['cdate'],
                ));
            } else {
                if (!preg_match('/^[0-9]+$/', $receipt['number'])) {
                    $error['number'] = trans('Receipt number must be integer!');
                } elseif ($LMS->DocumentExists(array(
                        'number' => $receipt['number'],
                        'doctype' => DOC_RECEIPT,
                        'planid' => $receipt['numberplanid'],
                        'cdate' => $receipt['cdate'],
                    ))) {
                    $error['number'] = trans('Receipt number $a already exists!', $receipt['number']);
                }

                if ($error) {
                    $receipt['number'] = $LMS->GetNewDocumentNumber(array(
                        'doctype' => DOC_RECEIPT,
                        'planid' => $receipt['numberplanid'],
                        'cdate' => $receipt['cdate'],
                    ));
                }
            }

            // cash-out
            $description = trans('Moving assets to registry $a', $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($dest)));

            $fullnumber = docnumber(array(
                'number' => $receipt['number'],
                'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($receipt['numberplanid'])),
                'cdate' => $receipt['cdate'],
                'customerid' => $customer['id'],
            ));

            $args = array(
                'type' => DOC_RECEIPT,
                'number' => $receipt['number'],
                'extnumber' => isset($receipt['extnumber']) ? $receipt['extnumber'] : '',
                SYSLOG::RES_NUMPLAN => $receipt['numberplanid'],
                'cdate' => $receipt['cdate'],
                SYSLOG::RES_USER => Auth::GetCurrentUser(),
                'name' => '',
                'closed' => 1,
                'fullnumber' => $fullnumber,
                'currency' => LMS::$currency,
            );
            $DB->Execute('INSERT INTO documents (type, number, extnumber, numberplanid, cdate, userid, name, closed, fullnumber, currency)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

            $rid = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=? AND numberplanid=?', array(DOC_RECEIPT, $receipt['number'], $receipt['cdate'], $receipt['numberplanid']));

            if ($SYSLOG) {
                unset($args[SYSLOG::RES_USER]);
                $args[SYSLOG::RES_DOC] = $rid;
                $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_ADD, $args);
            }

            $args = array(
                SYSLOG::RES_DOC => $rid,
                'itemid' => 1,
                'value' => str_replace(',', '.', $value * -1),
                'description' => $description,
                SYSLOG::RES_CASHREG => $receipt['regid'],
            );
            $DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
				VALUES(?, ?, ?, ?, ?)', array_values($args));

            if ($SYSLOG) {
                $SYSLOG->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_ADD, $args);
            }

            // number of cash-out receipt
            $template = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($receipt['numberplanid']));
            $r_number = docnumber(array(
                'number' => $receipt['number'],
                'template' => $template,
                'cdate' => $receipt['cdate'],
                'customerid' => $customer['id'],
            ));

            // cash-in
            $description = trans('Moving assets from registry $a ($b)', $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($receipt['regid'])), $r_number);
            $numberplan = $DB->GetOne('SELECT in_numberplanid FROM cashregs WHERE id=?', array($dest));
            $number = $LMS->GetNewDocumentNumber(array(
                'doctype' => DOC_RECEIPT,
                'planid' => $numberplan,
                'cdate' => $receipt['cdate'],
            ));

            if ($numberplan) {
                $fullnumber = docnumber(array(
                    'number' => $number,
                    'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($numberplan)),
                    'cdate' => $receipt['cdate'],
                    'customerid' => $customer['id'],
                ));
            } else {
                $fullnumber = null;
            }

            $args = array(
                'type' => DOC_RECEIPT,
                'number' => $number,
                SYSLOG::RES_NUMPLAN => !empty($numberplan) ? $numberplan : null,
                'cdate' => $receipt['cdate'],
                SYSLOG::RES_USER => Auth::GetCurrentUser(),
                'closed' => 1,
                'fullnumber' => $fullnumber,
                'currency' => LMS::$currency,
            );
            $DB->Execute('INSERT INTO documents (type, number, numberplanid, cdate, userid, closed, fullnumber, currency)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

            $did = $DB->GetOne('SELECT id FROM documents WHERE type=? AND number=? AND cdate=? AND numberplanid=?', array(DOC_RECEIPT, $number, $receipt['cdate'], $numberplan));

            if ($SYSLOG) {
                $args[SYSLOG::RES_DOC] = $did;
                unset($args[SYSLOG::RES_USER]);
                $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_ADD, $args);
            }

            $args = array(
                SYSLOG::RES_DOC => $did,
                'itemid' => 1,
                'value' => str_replace(',', '.', $value),
                'description' => $description,
                SYSLOG::RES_CASHREG => $dest,
            );
            $DB->Execute('INSERT INTO receiptcontents (docid, itemid, value, description, regid)
				VALUES(?, ?, ?, ?, ?)', array_values($args));

            if ($SYSLOG) {
                $SYSLOG->AddMessage(SYSLOG::RES_RECEIPTCONT, SYSLOG::OPER_ADD, $args);
            }

            $DB->CommitTrans();

            $SESSION->remove('receipt');
            $SESSION->remove('receiptadderror');

            if (isset($_GET['print'])) {
                $which = isset($_GET['which']) ? $_GET['which'] : 0;

                $SESSION->save('receiptprint', array('receipt' => $rid, 'which' => $which));
            }

            $SESSION->redirect('?m=receiptlist&regid='.$receipt['regid'].'#'.$rid);
        }
        break;
}

if (!isset($cashreglist)) {
    $cashreglist = $LMS->GetCashRegistries($receipt['customerid']);
}

$SESSION->save('receipt', $receipt);
$SESSION->save('receiptregid', $receipt['regid']);
$SESSION->save('receipttype', $receipt['type']);
$SESSION->save('receiptcontents', isset($contents) ? $contents : null);
$SESSION->save('receiptcustomer', isset($customer) ? $customer : null);
$SESSION->save('receiptadderror', isset($error) ? $error : null);

if ($action != '') {
    $SESSION->redirect('?m=receiptadd');
}

switch ($receipt['type']) {
    case 'in':
        $layout['pagetitle'] = trans('New Cash-in Receipt');
        $list = GetCustomerCovenants($customer['id']);
        break;
    case 'out':
        $layout['pagetitle'] = trans('New Cash-out Receipt');
        $list = GetCustomerNotes($customer['id']);
        break;
    default:
        $layout['pagetitle'] = trans('New Cash Receipt');
        break;
}

$invoicelist = array();

if (isset($list)) {
    if ($contents) {
        foreach ($list as $idx => $row) {
            for ($i=0, $x=count($contents); $i<$x; $i++) {
                if (isset($contents[$i]['docid']) && $row['id'] == $contents[$i]['docid']) {
                    break;
                }
            }
            if ($i == $x) {
                $invoicelist[$idx] = $row;
            }
        }
    } else {
        $invoicelist = $list;
    }
    $invoicelist = array_slice($invoicelist, 0, 10);
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customerlist', $LMS->GetCustomerNames());
}

$SMARTY->assign('invoicelist', $invoicelist);
$SMARTY->assign('rights', $DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array(Auth::GetCurrentUser(), $receipt['regid'])));
$SMARTY->assign('cashreglist', $cashreglist);
$SMARTY->assign('cashregcount', empty($cashreglist) ? 0 : count($cashreglist));
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('receipt', $receipt);
$SMARTY->assign('error', $error);
$SMARTY->display('receipt/receiptadd.html');
