<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
			
			$DB->Execute('UPDATE documents SET number = ?, numberplanid = ?,
                                cdate = ?, customerid = ?, name = ?, address = ?, paytime = ?,
				ten = ?, ssn = ?, zip = ?, city = ?, countryid = ?, divisionid = ?
				WHERE id = ?',
				array($note['number'],
				        !empty($note['numberplanid']) ? $note['numberplanid'] : 0,
				        $cdate,
				        $customer['id'],
				        $customer['customername'],
				        $customer['address'],
					$note['paytime'],
				        $customer['ten'],
				        $customer['ssn'],
				        $customer['zip'],
				        $customer['city'],
				        $customer['countryid'],
				        $customer['divisionid'],
					$note['id']
			        ));
			
			$DB->Execute('DELETE FROM debitnotecontents WHERE docid = ?', array($note['id']));
			$DB->Execute('DELETE FROM cash WHERE docid = ?', array($note['id']));
			
			$itemid=0;
                        foreach($contents as $idx => $item)
                        {
                                $itemid++;
                                $item['value'] = str_replace(',','.', $item['value']);

                                $DB->Execute('INSERT INTO debitnotecontents (docid, itemid, value, description)
                                        VALUES (?, ?, ?, ?)',
					array($note['id'], $itemid, $item['value'], $item['description']));

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

if(!isset($CONFIG['phpui']['big_networks']) || !chkconfig($CONFIG['phpui']['big_networks']))
{
        $SMARTY->assign('customers', $LMS->GetCustomerNames());
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('note', $note);
$SMARTY->display('noteedit.html');

?>
