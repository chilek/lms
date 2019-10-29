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

//$taxeslist = $LMS->GetTaxes();

$SESSION->restore('notecontents', $contents);
$SESSION->restore('notecustomer', $customer);
$SESSION->restore('note', $note);
$SESSION->restore('notenewerror', $error);

$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'init':
            unset($note);
            unset($contents);
            unset($customer);
            unset($error);

        // get default note's numberplanid and next number
        $note['cdate'] = time();
        $note['paytime'] = ConfigHelper::getConfig('notes.paytime');
        if (isset($_GET['customerid']) && $_GET['customerid'] != '' && $LMS->CustomerExists($_GET['customerid'])) {
            $customer = $LMS->GetCustomer($_GET['customerid'], true);

            $note['numberplanid'] = $DB->GetOne(
                'SELECT n.id FROM numberplans n
				JOIN numberplanassignments a ON (n.id = a.planid)
				WHERE n.doctype = ? AND n.isdefault = 1 AND a.divisionid = ?',
                array(DOC_DNOTE, $customer['divisionid'])
            );
        }

        if (empty($note['numberplanid'])) {
            $note['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans
				WHERE doctype = ? AND isdefault = 1', array(DOC_DNOTE));
        }

        $note['currency'] = LMS::$default_currency;

        break;

    case 'additem':
        $itemdata = r_trim($_POST);

        $itemdata['value'] = f_round($itemdata['value']);
        $itemdata['description'] = $itemdata['description'];

        if ($itemdata['value'] > 0 && $itemdata['description'] != '') {
            $itemdata['posuid'] = (string) getmicrotime();
            $contents[] = $itemdata;
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
        unset($note);
        unset($customer);
        unset($error);

        $currtime = time();

        if ($note = $_POST['note']) {
            foreach ($note as $key => $val) {
                $note[$key] = $val;
            }
        }

        $note['customerid'] = $_POST['customerid'];

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            if ($note['cdate']) {
                list ($year, $month, $day) = explode('/', $note['cdate']);
                if (checkdate($month, $day, $year)) {
                    $note['cdate'] = mktime(
                        date('G', $currtime),
                        date('i', $currtime),
                        date('s', $currtime),
                        $month,
                        $day,
                        $year
                    );
                    $currmonth = $month;
                } else {
                    $error['cdate'] = trans('Incorrect date format!');
                    $note['cdate'] = $currtime;
                    break;
                }
            }
        } else {
            $note['cdate'] = $currtime;
        }

        if (ConfigHelper::checkPrivilege('invoice_consent_date') && $note['cdate'] && !isset($note['cdatewarning'])) {
            $maxdate = $DB->GetOne(
                'SELECT MAX(cdate) FROM documents WHERE type = ? AND numberplanid = ?',
                array(DOC_DNOTE, $note['numberplanid'])
            );

            if ($note['cdate'] < $maxdate) {
                $error['cdate'] = trans(
                    'Last date of debit note settlement is $a. If sure, you want to write note with date of $b, then click "Submit" again.',
                    date('Y/m/d H:i', $maxdate),
                    date('Y/m/d H:i', $note['cdate'])
                );
                $note['cdatewarning'] = 1;
            }
        } elseif (!$note['cdate']) {
            $note['cdate'] = $currtime;
        }

        if ($note['number']) {
            if (!preg_match('/^[0-9]+$/', $note['number'])) {
                $error['number'] = trans('Debit note number must be integer!');
            } elseif ($LMS->DocumentExists(array(
                    'number' => $note['number'],
                    'doctype' => DOC_DNOTE,
                    'planid' => $note['numberplanid'],
                    'cdate' => $note['cdate'],
                ))) {
                $error['number'] = trans('Debit note number $a already exists!', $note['number']);
            }
        }

        if (empty($note['paytime_default']) && !preg_match('/^[0-9]+$/', $note['paytime'])) {
                $error['paytime'] = trans('Integer value required!');
        }

        if (!isset($CURRENCIES[$note['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        if (!isset($error)) {
            $cid = isset($_GET['customerid']) && $_GET['customerid'] != '' ? intval($_GET['customerid']) : intval($_POST['customerid']);
            if ($LMS->CustomerExists($cid)) {
                $customer = $LMS->GetCustomer($cid, true);
            }

            // finally check if selected customer can use selected numberplan
            if ($note['numberplanid'] && isset($customer)) {
                if (!$DB->GetOne('SELECT 1 FROM numberplanassignments
					WHERE planid = ? AND divisionid = ?', array($note['numberplanid'], $customer['divisionid']))) {
                    $error['number'] = trans('Selected numbering plan doesn\'t match customer\'s division!');
                    unset($customer);
                }
            }
        }
        break;

    case 'save':
        if ($contents && $customer) {
            if (!ConfigHelper::checkPrivilege('invoice_consent_date')) {
                $note['cdate'] = time();
            }

            $note['currencyvalue'] = $LMS->getCurrencyValue($note['currency'], $note['cdate']);
            if (!isset($note['currencyvalue'])) {
                die('Fatal error: couldn\'t get quote for ' . $note['currency'] . ' currency!<br>');
            }

            $DB->BeginTrans();
            $DB->LockTables(array('documents', 'cash', 'debitnotecontents', 'numberplans', 'divisions', 'vdivisions'));

            if (!$note['number']) {
                $note['number'] = $LMS->GetNewDocumentNumber(array(
                    'doctype' => DOC_DNOTE,
                    'planid' => $note['numberplanid'],
                    'cdate' => $note['cdate'],
                    'customerid' => $customer['id'],
                ));
            } else {
                if (!preg_match('/^[0-9]+$/', $note['number'])) {
                    $error['number'] = trans('Debit note number must be integer!');
                } elseif ($LMS->DocumentExists(array(
                        'number' => $note['number'],
                        'doctype' => DOC_DNOTE,
                        'planid' => $note['numberplanid'],
                        'cdate' => $note['cdate'],
                        'customerid' => $customer['id'],
                    ))) {
                    $error['number'] = trans('Debit note number $a already exists!', $note['number']);
                }

                if ($error) {
                    $note['number'] = $LMS->GetNewDocumentNumber(array(
                        'doctype' => DOC_DNOTE,
                        'planid' => $note['numberplanid'],
                        'cdate' => $note['cdate'],
                        'customerid' => $customer['id'],
                    ));
                }
            }

            // set paytime
            if (!empty($note['paytime_default'])) {
                if ($customer['paytime'] != -1) {
                        $note['paytime'] = $customer['paytime'];
                } elseif (($paytime = $DB->GetOne('SELECT inv_paytime FROM divisions
			                WHERE id = ?', array($customer['divisionid']))) !== null) {
                    $note['paytime'] = $paytime;
                } else {
                    $note['paytime'] = ConfigHelper::getConfig('notes.paytime');
                }
            }

            $cdate = !empty($note['cdate']) ? $note['cdate'] : time();

            $division = $LMS->GetDivision($customer['divisionid']);

            if ($note['numberplanid']) {
                $fullnumber = docnumber(array(
                    'number' => $note['number'],
                    'template' => $DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($note['numberplanid'])),
                    'cdate' => $cdate,
                    'customerid' => $customer['id'],
                ));
            } else {
                $fullnumber = null;
            }

            $args = array(
                'number' => $note['number'],
                SYSLOG::RES_NUMPLAN => !empty($note['numberplanid']) ? $note['numberplanid'] : null,
                'type' => DOC_DNOTE,
                'cdate' => $cdate,
                SYSLOG::RES_USER => Auth::GetCurrentUser(),
                SYSLOG::RES_CUST => $customer['id'],
                'name' => $customer['customername'],
                'address' => ($customer['postoffice'] && $customer['postoffice'] != $customer['city'] && $customer['street']
                    ? $customer['city'] . ', ' : '') . $customer['address'],
                'paytime' => $note['paytime'],
                'ten' => $customer['ten'],
                'ssn' => $customer['ssn'],
                'zip' => $customer['zip'],
                'city' => $customer['postoffice'] ? $customer['postoffice'] : $customer['city'],
                SYSLOG::RES_COUNTRY => !empty($customer['countryid']) ? $customer['countryid'] : null,
                SYSLOG::RES_DIV => !empty($customer['divisionid']) ? $customer['divisionid'] : null,
                'div_name' => ($division['name'] ? $division['name'] : ''),
                'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
                'div_address' => ($division['address'] ? $division['address'] : ''),
                'div_city' => ($division['city'] ? $division['city'] : ''),
                'div_zip' => ($division['zip'] ? $division['zip'] : ''),
                'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => !empty($division['countryid']) ? $division['countryid'] : null,
                'div_ten'=> ($division['ten'] ? $division['ten'] : ''),
                'div_regon' => ($division['regon'] ? $division['regon'] : ''),
                'div_bank' => $division['bank'] ?: null,
                'div_account' => ($division['account'] ? $division['account'] : ''),
                'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
                'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
                'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
                'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
                'fullnumber' => $fullnumber,
                'currency' => $note['currency'],
                'currencyvalue' => $note['currencyvalue'],
            );
            $DB->Execute('INSERT INTO documents (number, numberplanid, type,
					cdate, userid, customerid, name, address, paytime,
					ten, ssn, zip, city, countryid, divisionid,
					div_name, div_shortname, div_address, div_city, div_zip, div_countryid,
					div_ten, div_regon, div_bank, div_account, div_inv_header, div_inv_footer, div_inv_author, div_inv_cplace, fullnumber,
					currency, currencyvalue)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

            $nid = $DB->GetLastInsertID('documents');

            $LMS->UpdateDocumentPostAddress($nid, $customer['id']);

            if ($SYSLOG) {
                $args[SYSLOG::RES_DOC] = $nid;
                unset($args[SYSLOG::RES_USER]);
                $SYSLOG->AddMessage(
                    SYSLOG::RES_DOC,
                    SYSLOG::OPER_ADD,
                    $args,
                    array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY))
                );
            }

            $itemid = 0;
            foreach ($contents as $idx => $item) {
                $itemid++;
                $item['value'] = str_replace(',', '.', $item['value']);

                $args = array(
                    SYSLOG::RES_DOC => $nid,
                    'itemid' => $itemid,
                    'value' => $item['value'],
                    'description' => $item['description']
                );
                $DB->Execute('INSERT INTO debitnotecontents (docid, itemid, value, description)
					VALUES (?, ?, ?, ?)', array_values($args));

                if ($SYSLOG) {
                    $args[SYSLOG::RES_DNOTECONT] = $DB->GetLastInsertID('debitnotecontents');
                    $args[SYSLOG::RES_CUST] = $customer['id'];
                    $SYSLOG->AddMessage(SYSLOG::RES_DNOTECONT, SYSLOG::OPER_ADD, $args);
                }

                $LMS->AddBalance(array(
                    'time' => $cdate,
                    'value' => $item['value']*-1,
                    'currency' => $note['currency'],
                    'currencyvalue' => $note['currencyvalue'],
                    'taxid' => 0,
                    'customerid' => $customer['id'],
                    'comment' => $item['description'],
                    'docid' => $nid,
                    'itemid'=> $itemid
                ));
            }

            $DB->UnLockTables();
            $DB->CommitTrans();

            $SESSION->remove('notecontents');
            $SESSION->remove('notecustomer');
            $SESSION->remove('note');
            $SESSION->remove('notenewerror');

            if (isset($_GET['print'])) {
                $SESSION->save('noteprint', $nid);
            }

            $SESSION->redirect('?m=noteadd&action=init');
        }
        break;
}

$SESSION->save('note', $note);
$SESSION->save('notecontents', isset($contents) ? $contents : null);
$SESSION->save('notecustomer', isset($customer) ? $customer : null);
$SESSION->save('notenewerror', isset($error) ? $error : null);

if ($action) {
    // redirect needed because we don't want to destroy contents of note in order of page refresh
    $SESSION->redirect('?m=noteadd');
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

if ($newnote = $SESSION->get('noteprint')) {
        $SMARTY->assign('newnoteid', $newnote);
        $SESSION->remove('noteprint');
}

$layout['pagetitle'] = trans('New Debit Note<!long>');

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('note', $note);
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(array(
    'doctype' => DOC_DNOTE,
    'cdate' => date('Y/m', $note['cdate']),
)));
//$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->display('note/noteadd.html');
