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
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (isset($_GET['id']) && $action=='edit') {
    if ($LMS->isDocumentPublished($_GET['id']) && !ConfigHelper::checkConfig('privileges.superuser')) {
        return;
    }

    if ($LMS->isArchiveDocument($_GET['id'])) {
        return;
    }

    $note = $LMS->GetNoteContent($_GET['id']);

    $SESSION->remove('notecontents');
    $SESSION->remove('notecustomer');

    $i = 0;
    foreach ($note['content'] as $item) {
        $i++;
        $nitem['description']   = $item['description'];
        $nitem['value']     = $item['value'];
        $nitem['posuid']    = $i;
        $SESSION->restore('notecontents', $notecontents);
        $notecontents[] = $nitem;
        $SESSION->save('notecontents', $notecontents);
    }

    $note['oldcdate'] = $note['cdate'];
    $note['oldnumber'] = $note['number'];
    $note['oldnumberplanid'] = $note['numberplanid'];
    $note['oldcustomerid'] = $note['customerid'];

    $SESSION->save('notecustomer', $LMS->GetCustomer($note['customerid'], true));
    $SESSION->save('note', $note);
    $SESSION->save('noteid', $note['id']);
}

$SESSION->restore('notecontents', $contents);
$SESSION->restore('notecustomer', $customer);
$SESSION->restore('note', $note);
$SESSION->restore('noteediterror', $error);

$ntempl = docnumber(array(
    'number' => $note['number'],
    'template' => $note['template'],
    'cdate' => $note['cdate'],
    'customerid' => $note['customerid'],
));
$layout['pagetitle'] = trans('Debit Note Edit: $a', $ntempl);

if (!empty($_GET['customerid']) && $LMS->CustomerExists($_GET['customerid'])) {
    $action = 'setcustomer';
}

switch ($action) {
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
        $oldcdate = $note['oldcdate'];
        $oldnumber = $note['oldnumber'];
        $oldnumberplanid = $note['oldnumberplanid'];
        $oldcustomerid = $note['oldcustomerid'];

        unset($note);
        unset($customer);
        unset($error);
        $error = null;

        if ($note = $_POST['note']) {
            foreach ($note as $key => $val) {
                $note[$key] = $val;
            }
        }

        $note['oldcdate'] = $oldcdate;
        $note['oldnumber'] = $oldnumber;
        $note['oldnumberplanid'] = $oldnumberplanid;
        $note['oldcustomerid'] = $oldcustomerid;

        $note['paytime'] = sprintf('%d', $note['paytime']);

        if ($note['paytime'] < 0) {
            $note['paytime'] = 14;
        }

        $currtime = time();

        if (ConfigHelper::checkPrivilege('invoice_consent_date')) {
            if ($note['cdate']) { // && !$note['cdatewarning'])
                list($year, $month, $day) = explode('/', $note['cdate']);
                if (checkdate($month, $day, $year)) {
                    $oldday = date('d', $note['oldcdate']);
                    $oldmonth = date('m', $note['oldcdate']);
                    $oldyear = date('Y', $note['oldcdate']);

                    if ($oldday != $day || $oldmonth != $month || $oldyear != $year) {
                        $note['cdate'] = mktime(
                            date('G', $currtime),
                            date('i', $currtime),
                            date('s', $currtime),
                            $month,
                            $day,
                            $year
                        );
                    } else { // save hour/min/sec value if date is the same
                        $note['cdate'] = $note['oldcdate'];
                    }
                } else {
                    $error['cdate'] = trans('Incorrect date format!');
                }
            }
        } else {
            $note['cdate'] = $note['oldcdate'];
        }

        $note['customerid'] = $_POST['customerid'];

        if ($note['number']) {
            if (!preg_match('/^[0-9]+$/', $note['number'])) {
                $error['number'] = trans('Debit note number must be integer!');
            } elseif (($note['oldcdate'] != $note['cdate'] || $note['oldnumber'] != $note['number']
                    || ($note['oldnumber'] == $note['number'] && $note['oldcustomerid'] != $note['customerid'])
                    || $note['oldnumberplanid'] != $note['numberplanid']) && ($docid = $LMS->DocumentExists(array(
                    'number' => $note['number'],
                    'doctype' => DOC_DNOTE,
                    'planid' => $note['numberplanid'],
                    'cdate' => $note['cdate'],
                    'customerid' => $note['customerid'],
                    ))) > 0 && $docid != $note['id']) {
                $error['number'] = trans('Debit note number $a already exists!', $note['number']);
            }
        }

        if (!isset($CURRENCIES[$note['currency']])) {
            $error['currency'] = trans('Invalid currency selection!');
        }

        if (!$error) {
            if ($LMS->CustomerExists($note['customerid'])) {
                $customer = $LMS->GetCustomer($note['customerid'], true);
            }
        }
        break;

    case 'save':
        if ($contents && $customer) {
            if (!ConfigHelper::checkPrivilege('invoice_consent_date')) {
                $note['cdate'] = $note['oldcdate'];
            }

            $note['currencyvalue'] = $LMS->getCurrencyValue($note['currency'], $note['cdate']);
            if (!isset($note['currencyvalue'])) {
                die('Fatal error: couldn\'t get quote for ' . $note['currency'] . ' currency!<br>');
            }

            $SESSION->restore('noteid', $note['id']);

            $DB->BeginTrans();
            $DB->LockTables(array('documents', 'cash', 'debitnotecontents', 'numberplans'));

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
                } elseif (($note['cdate'] != $note['oldcdate'] || $note['number'] != $note['oldnumber']
                    || ($note['oldnumber'] == $note['number'] && $note['oldcustomerid'] != $note['customerid'])
                    || $note['numberplanid'] != $note['oldnumberplanid']) && $docid = $LMS->DocumentExists(array(
                    'number' => $note['number'],
                    'doctype' => DOC_DNOTE,
                    'planid' => $note['numberplanid'],
                    'cdate' => $note['cdate'],
                    'customerid' => $customer['id'],
                    )) > 0 && $docid != $note['id']) {
                    $error['number'] = trans('Debit note number $a already exists!', $note['number']);
                }

                if ($error) {
                    $note['number'] = $LMS->GetNewDocumentNumber(array(
                        'doctype' => DOC_DNOTE,
                        'planid' => $note['numberplanid'],
                        'cdate' => $note['cdate'],
                        'customerid' => $customer['id'],
                    ));
                    $error = null;
                }
            }

            $cdate = !empty($note['cdate']) ? $note['cdate'] : time();

            $division = $DB->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
				account, inv_header, inv_footer, inv_author, inv_cplace 
				FROM vdivisions WHERE id = ?', array($customer['divisionid']));

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
                'cdate' => $cdate,
                SYSLOG::RES_CUST => $customer['id'],
                'name' => $customer['customername'],
                'address' => $customer['address'],
                'paytime' => $note['paytime'],
                'ten' => $customer['ten'],
                'ssn' => $customer['ssn'],
                'zip' => $customer['zip'],
                'city' => $customer['city'],
                SYSLOG::RES_COUNTRY => !empty($customer['countryid']) ? $division['countryid'] : null,
                SYSLOG::RES_DIV => !empty($customer['divisionid']) ? $customer['divisionid'] : null,
                'div_name' => ($division['name'] ? $division['name'] : ''),
                'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
                'div_address' => ($division['address'] ? $division['address'] : ''),
                'div_city' => ($division['city'] ? $division['city'] : ''),
                'div_zip' => ($division['zip'] ? $division['zip'] : ''),
                'div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY) => (!empty($division['countryid']) ? $division['countryid'] : null),
                'div_ten'=> ($division['ten'] ? $division['ten'] : ''),
                'div_regon' => ($division['regon'] ? $division['regon'] : ''),
                'div_account' => ($division['account'] ? $division['account'] : ''),
                'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
                'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
                'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
                'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
                'fullnumber' => $fullnumber,
                'currency' => $note['currency'],
                'currencyvalue' => $note['currencyvalue'],
                SYSLOG::RES_DOC => $note['id'],
            );
            $DB->Execute('UPDATE documents SET number = ?, numberplanid = ?,
				cdate = ?, customerid = ?, name = ?, address = ?, paytime = ?,
				ten = ?, ssn = ?, zip = ?, city = ?, countryid = ?, divisionid = ?,
				div_name = ?, div_shortname = ?, div_address = ?, div_city = ?, div_zip = ?, div_countryid = ?,
				div_ten = ?, div_regon = ?, div_account = ?, div_inv_header = ?, div_inv_footer = ?,
				div_inv_author = ?, div_inv_cplace = ?, fullnumber = ?, currency = ?, currencyvalue = ?
				WHERE id = ?', array_values($args));

            $LMS->UpdateDocumentPostAddress($note['id'], $customer['id']);

            if ($SYSLOG) {
                $SYSLOG->AddMessage(
                    SYSLOG::RES_DOC,
                    SYSLOG::OPER_UPDATE,
                    $args,
                    array('div_' . SYSLOG::getResourceKey(SYSLOG::RES_COUNTRY))
                );
                $dnoteconts = $DB->GetCol('SELECT id FROM debitnotecontents WHERE docid = ?', array($note['id']));
                foreach ($dnoteconts as $item) {
                    $args = array(
                        SYSLOG::RES_DNOTECONT => $item,
                        SYSLOG::RES_DOC => $note['id'],
                        SYSLOG::RES_CUST => $customer['id'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_DNOTECONT, SYSLOG::OPER_DELETE, $args);
                }
                $cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($note['id']));
                foreach ($cashids as $item) {
                    $args = array(
                        SYSLOG::RES_CASH => $item,
                        SYSLOG::RES_DOC => $note['id'],
                        SYSLOG::RES_CUST => $customer['id'],
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_CASH, SYSLOG::OPER_DELETE, $args);
                }
            }
            $DB->Execute('DELETE FROM debitnotecontents WHERE docid = ?', array($note['id']));
            $DB->Execute('DELETE FROM cash WHERE docid = ?', array($note['id']));

            $itemid = 0;
            foreach ($contents as $idx => $item) {
                $itemid++;
                $item['value'] = str_replace(',', '.', $item['value']);

                $args = array(
                    SYSLOG::RES_DOC => $note['id'],
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
                    'docid' => $note['id'],
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
                    $SESSION->save('noteprint', $note['id']);
            }

            $SESSION->redirect('?m=notelist');
        }
        break;
}

$SESSION->save('note', $note);
$SESSION->save('notecontents', $contents);
$SESSION->save('notecustomer', $customer);
$SESSION->save('noteediterror', $error);

if ($action != '') {
    // redirect needed because we don't want to destroy contents of note in order of page refresh
    $SESSION->redirect('?m=noteedit');
}

if (!ConfigHelper::checkConfig('phpui.big_networks')) {
    $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('note', $note);
$SMARTY->display('note/noteedit.html');
