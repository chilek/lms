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

//$taxeslist = $LMS->GetTaxes();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if(isset($_GET['id']) && $action=='edit')
{
    $note = $LMS->GetNoteContent($_GET['id']);

    $SESSION->remove('notecontents');
    $SESSION->remove('notecustomer');

    $i = 0;
    foreach ($note['content'] as $item) {
	$i++;
	$nitem['description']	= $item['description'];
	$nitem['value']		= $item['value'];
	$nitem['posuid']	= $i;
	$SESSION->restore('notecontents', $notecontents);
	$notecontents[] = $nitem;
	$SESSION->save('notecontents', $notecontents);
    }

    $SESSION->save('notecustomer', $LMS->GetCustomer($note['customerid'], true));
    $note['oldcdate'] = $note['cdate'];
    $SESSION->save('note', $note);
    $SESSION->save('noteid', $note['id']);
}

$SESSION->restore('notecontents', $contents);
$SESSION->restore('notecustomer', $customer);
$SESSION->restore('note', $note);
$SESSION->restore('noteediterror', $error);

$ntempl = docnumber($note['number'], $note['template'], $note['cdate']);
$layout['pagetitle'] = trans('Debit Note Edit: $a', $ntempl);

if(!empty($_GET['customerid']) && $LMS->CustomerExists($_GET['customerid']))
	$action = 'setcustomer';

switch($action)
{
	case 'additem':

		$itemdata = r_trim($_POST);

                $itemdata['value'] = f_round($itemdata['value']);
                $itemdata['description'] = $itemdata['description'];

                if ($itemdata['value'] > 0 && $itemdata['description'] != '')
                {
                        $itemdata['posuid'] = (string) getmicrotime();
                        $contents[] = $itemdata;
                }
	break;

	case 'deletepos':
		if(sizeof($contents))
			foreach($contents as $idx => $row)
				if($row['posuid'] == $_GET['posuid']) 
					unset($contents[$idx]);
	break;

	case 'setcustomer':
		
		$olddate = $note['oldcdate'];
		
		unset($note); 
		unset($customer);
		unset($error);
		$error = NULL;
		
		if($note = $_POST['note'])
			foreach($note as $key => $val)
				$note[$key] = $val;
		
		$note['oldcdate'] = $olddate;
		$note['paytime'] = sprintf('%d', $note['paytime']);

                if($note['paytime'] < 0)
                        $note['paytime'] = 14;

		if($note['cdate']) // && !$note['cdatewarning'])
		{
			list($year, $month, $day) = explode('/',$note['cdate']);
			if(checkdate($month, $day, $year))
			{
				$oldday = date('d', $note['oldcdate']);
				$oldmonth = date('m', $note['oldcdate']);
			        $oldyear = date('Y', $note['oldcdate']);
				
				if($oldday != $day || $oldmonth != $month || $oldyear != $year)
				{
					$note['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
				}
				else // save hour/min/sec value if date is the same
					$note['cdate'] = $note['oldcdate'];
			}
			else
				$error['cdate'] = trans('Incorrect date format!');
		}
		
		$note['customerid'] = $_POST['customerid'];
		
		if(!$error)
			if($LMS->CustomerExists($note['customerid']))
				$customer = $LMS->GetCustomer($note['customerid'], true);
	break;

	case 'save':

		if($contents && $customer)
		{
			$SESSION->restore('noteid', $note['id']);

			$DB->BeginTrans();
                        $DB->LockTables(array('documents', 'cash', 'debitnotecontents', 'numberplans'));

			$cdate = !empty($note['cdate']) ? $note['cdate'] : time();

			$division = $DB->GetRow('SELECT name, shortname, address, city, zip, countryid, ten, regon,
				account, inv_header, inv_footer, inv_author, inv_cplace 
				FROM divisions WHERE id = ? ;',array($customer['divisionid']));

			if ($note['numberplanid'])
				$fullnumber = docnumber($note['number'],
					$DB->GetOne('SELECT template FROM numberplans WHERE id = ?', array($note['numberplanid'])),
					$cdate);
			else
				$fullnumber = null;

			$args = array(
				'number' => $note['number'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN] => !empty($note['numberplanid']) ? $note['numberplanid'] : 0,
				'cdate' => $cdate,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
				'name' => $customer['customername'],
				'address' => $customer['address'],
				'paytime' => $note['paytime'],
				'ten' => $customer['ten'],
				'ssn' => $customer['ssn'],
				'zip' => $customer['zip'],
				'city' => $customer['city'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => $customer['countryid'],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV] => $customer['divisionid'],
				'div_name' => ($division['name'] ? $division['name'] : ''),
				'div_shortname' => ($division['shortname'] ? $division['shortname'] : ''),
				'div_address' => ($division['address'] ? $division['address'] : ''), 
				'div_city' => ($division['city'] ? $division['city'] : ''), 
				'div_zip' => ($division['zip'] ? $division['zip'] : ''),
				'div_' . $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY] => ($division['countryid'] ? $division['countryid'] : 0),
				'div_ten'=> ($division['ten'] ? $division['ten'] : ''),
				'div_regon' => ($division['regon'] ? $division['regon'] : ''),
				'div_account' => ($division['account'] ? $division['account'] : ''),
				'div_inv_header' => ($division['inv_header'] ? $division['inv_header'] : ''),
				'div_inv_footer' => ($division['inv_footer'] ? $division['inv_footer'] : ''),
				'div_inv_author' => ($division['inv_author'] ? $division['inv_author'] : ''),
				'div_inv_cplace' => ($division['inv_cplace'] ? $division['inv_cplace'] : ''),
				'fullnumber' => $fullnumber,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $note['id'],
			);
			$DB->Execute('UPDATE documents SET number = ?, numberplanid = ?,
				cdate = ?, customerid = ?, name = ?, address = ?, paytime = ?,
				ten = ?, ssn = ?, zip = ?, city = ?, countryid = ?, divisionid = ?,
				div_name = ?, div_shortname = ?, div_address = ?, div_city = ?, div_zip = ?, div_countryid = ?,
				div_ten = ?, div_regon = ?, div_account = ?, div_inv_header = ?, div_inv_footer = ?,
				div_inv_author = ?, div_inv_cplace = ?, fullnumber = ?
				WHERE id = ?', array_values($args));

			if ($SYSLOG) {
				$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_UPDATE, $args,
					array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NUMPLAN],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_COUNTRY],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DIV]));
				$dnoteconts = $DB->GetCol('SELECT id FROM debitnotecontents WHERE docid = ?', array($note['id']));
				foreach ($dnoteconts as $item) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT] => $item,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $note['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
					);
					$SYSLOG->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
				$cashids = $DB->GetCol('SELECT id FROM cash WHERE docid = ?', array($note['id']));
				foreach ($cashids as $item) {
					$args = array(
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CASH] => $item,
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $note['id'],
						$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $customer['id'],
					);
					$SYSLOG->AddMessage(SYSLOG_RES_CASH, SYSLOG_OPER_DELETE, $args, array_keys($args));
				}
			}
			$DB->Execute('DELETE FROM debitnotecontents WHERE docid = ?', array($note['id']));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($note['id']));

			$itemid = 0;
			foreach ($contents as $idx => $item) {
				$itemid++;
				$item['value'] = str_replace(',','.', $item['value']);

				$args = array(
					$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $note['id'],
					'itemid' => $itemid,
					'value' => $item['value'],
					'description' => $item['description']
				);
				$DB->Execute('INSERT INTO debitnotecontents (docid, itemid, value, description)
					VALUES (?, ?, ?, ?)', array_values($args));
				if ($SYSLOG) {
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT]] = $DB->GetLastInsertID('debitnotecontents');
					$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]] = $customer['id'];
					$SYSLOG->AddMessage(SYSLOG_RES_DNOTECONT, SYSLOG_OPER_ADD, $args,
						array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DNOTECONT], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC],
							$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
				}

				$LMS->AddBalance(array(
					'time' => $cdate,
					'value' => $item['value']*-1,
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

			if(isset($_GET['print']))
			        $SESSION->save('noteprint', $note['id']);

			$SESSION->redirect('?m=notelist');
		}
	break;
}

$SESSION->save('note', $note);
$SESSION->save('notecontents', $contents);
$SESSION->save('notecustomer', $customer);
$SESSION->save('noteediterror', $error);

if($action != '')
{
	// redirect needed because we don't want to destroy contents of note in order of page refresh
	$SESSION->redirect('?m=noteedit');
}

if (!ConfigHelper::checkConfig('phpui.big_networks'))
	$SMARTY->assign('customers', $LMS->GetCustomerNames());

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('note', $note);
$SMARTY->display('note/noteedit.html');

?>
