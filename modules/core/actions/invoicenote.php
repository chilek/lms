<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$taxeslist = $LMS->GetTaxes();
$numberplanlist = $LMS->GetNumberPlans(DOC_CNOTE);

if ((isset($_GET['id'])) && ($_GET['action']=='init'))
{
	$invoice = $LMS->GetInvoiceContent($_GET['id']);

	foreach ($invoice['content'] as $item)
	{
    		$nitem['tariffid']	= $item['tariffid'];
		$nitem['name']		= $item['description'];
		$nitem['prodid']	= $item['prodid'];
    		$nitem['count']		= str_replace(',','.',$item['count']);
		$nitem['jm']		= str_replace(',','.',$item['content']);
    		$nitem['valuenetto']	= str_replace(',','.',$item['basevalue']);
    		$nitem['valuebrutto']	= str_replace(',','.',$item['value']);
		$nitem['s_valuenetto']	= str_replace(',','.',$item['totalbase']);
    		$nitem['s_valuebrutto']	= str_replace(',','.',$item['total']);
		$nitem['tax']		= $taxeslist[$item['taxid']]['label'];
		$nitem['taxid']		= $item['taxid'];
		$nitem['itemid']	= $item['itemid'];
		$invoicecontents[$nitem['itemid']] = $nitem;
	}
    
	$cnote['numberplanid'] = $DB->GetOne('SELECT id FROM numberplans WHERE doctype = ? AND isdefault = 1', array(DOC_CNOTE));
	$cnote['number'] = $LMS->GetNewDocumentNumber(DOC_CNOTE, $cnote['numberplanid']);
	$cnote['cdate'] = time();
	
	$t = $invoice['cdate'] + $invoice['paytime']*86400;
	$deadline = mktime(23, 59, 59, date('m',$t), date('d',$t), date('Y',$t));
	
	if($cnote['cdate'] > $deadline)
		$cnote['paytime'] = 0;
	else
		$cnote['paytime'] = floor(($deadline - $cnote['cdate'])/86400);
    
	$SESSION->save('cnote', $cnote);
	$SESSION->save('invoice', $invoice);
	$SESSION->save('invoiceid', $invoice['id']);
	$SESSION->save('invoicecontents', $invoicecontents);
}

$SESSION->restore('invoicecontents', $contents);
$SESSION->restore('invoice', $invoice);
$SESSION->restore('cnote', $cnote);
$SESSION->restore('cnoteerror', $error);

$ntempl = docnumber($invoice['number'], $invoice['template'], $invoice['cdate']);
$layout['pagetitle'] = trans('Credit Note for Invoice: $0', $ntempl);

switch($_GET['action'])
{
	case 'deletepos':
		$contents[$_GET['itemid']]['deleted'] = true;
	break;

	case 'recoverpos':
		$contents[$_GET['itemid']]['deleted'] = false;
	break;

	case 'setheader':
		
		unset($cnote); 
		unset($error);
		
		if($cnote = $_POST['cnote'])
			foreach($cnote as $key => $val)
				$cnote[$key] = $val;
		
		$cnote['paytime'] = sprintf('%d', $cnote['paytime']);
		
		if($cnote['paytime'] < 0)
			$cnote['paytime'] = 14;

		if($cnote['cdate'])
		{
			list($year, $month, $day) = split('/',$cnote['cdate']);
			if(checkdate($month, $day, $year))
			{
				$cnote['cdate'] = mktime(date('G',time()),date('i',time()),date('s',time()),$month,$day,$year);
				if($cnote['cdate'] < $invoice['cdate'])
				{
					$error['cdate'] = trans('Credit note date cannot be earlier than invoice date!');
				}
			}
			else
			{
				$error['cdate'] = trans('Incorrect date format! Using current date.');
				$cnote['cdate'] = time();
			}
		}
		else
			$cnote['cdate'] = time();
		
		if(!$cnote['number'])
		        $cnote['number'] = $LMS->GetNewDocumentNumber(DOC_CNOTE, $cnote['numberplanid'], $cnote['cdate']);
		else
		{
			if(!eregi('^[0-9]+$', $cnote['number']))
			        $error['number'] = trans('Credit note number must be integer!');
			elseif($LMS->DocumentExists($cnote['number'], DOC_CNOTE, $cnote['numberplanid'], $cnote['cdate']))
			        $error['number'] = trans('Credit note number $0 already exists!', $cnote['number']);
		}
	break;

	case 'save':

		if($contents && $cnote)
		{
			$SESSION->restore('invoiceid', $invoice['id']);
			$newcontents = r_trim($_POST);

			foreach($contents as $item)
			{
				$idx = $item['itemid'];
				$contents[$idx]['taxid'] = $newcontents['taxid'][$idx] ? $newcontents['taxid'][$idx] : $item['taxid'];
				$contents[$idx]['prodid'] = $newcontents['prodid'][$idx] ? $newcontents['prodid'][$idx] : $item['prodid'];
				$contents[$idx]['jm'] = $newcontents['jm'][$idx] ? $newcontents['jm'][$idx] : $item['jm'];
				$contents[$idx]['count'] = $newcontents['count'][$idx] ? $newcontents['count'][$idx] : $item['count'];
				$contents[$idx]['name'] = $newcontents['name'][$idx] ? $newcontents['name'][$idx] : $item['name'];
				$contents[$idx]['tariffid'] = $newcontents['tariffid'][$idx] ? $newcontents['tariffid'][$idx] : $item['tariffid'];
				$contents[$idx]['valuebrutto'] = $newcontents['valuebrutto'][$idx] ? $newcontents['valuebrutto'][$idx] : $item['valuebrutto'];
				$contents[$idx]['valuenetto'] = $newcontents['valuenetto'][$idx] ? $newcontents['valuenetto'][$idx] : $item['valuenetto'];
				$contents[$idx]['valuebrutto'] = f_round($contents[$idx]['valuebrutto']);
				$contents[$idx]['valuenetto'] = f_round($contents[$idx]['valuenetto']);
				$contents[$idx]['count'] = f_round($contents[$idx]['count']);

				$taxvalue = $taxeslist[$contents[$idx]['taxid']]['value'];
				
				if($contents[$idx]['valuenetto'] != $item['valuenetto'])
				{
					$contents[$idx]['valuebrutto'] = round($contents[$idx]['valuenetto'] * ($taxvalue / 100 + 1),2);
				}
				
				if($item['deleted'])
				{
					$contents[$idx]['valuebrutto'] = 0;
					$contents[$idx]['cash'] = round($item['valuebrutto'] * $item['count'],2);
				}
				elseif($contents[$idx]['count'] != $item['count'] ||
				    $contents[$idx]['valuebrutto'] != $item['valuebrutto'])
				{
					$contents[$idx]['cash'] = round($item['valuebrutto'] * $item['count'],2) - round($contents[$idx]['valuebrutto'] * $contents[$idx]['count'],2);
				}
								
				$contents[$idx]['valuebrutto'] = $contents[$idx]['valuebrutto'] - $item['valuebrutto'];
				$contents[$idx]['count'] = $contents[$idx]['count'] - $item['count'];
			}

			$DB->Execute('INSERT INTO documents (number, numberplanid, type, cdate, paytime, paytype, userid, customerid, name, address, ten, ssn, zip, city, reference)
		                	VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array($cnote['number'],
				    		$cnote['numberplanid'] ? $cnote['numberplanid'] : 0,
						DOC_CNOTE,
		    				$cnote['cdate'],
			    			$cnote['paytime'],
						$invoice['paytype'],
						$AUTH->id,
						$invoice['customerid'],
						$invoice['name'],
						$invoice['address'],
						$invoice['ten'],
						$invoice['ssn'],
						$invoice['zip'],
						$invoice['city'],
						$invoice['id']
					));
																																																							    	
			$id = $DB->GetOne('SELECT id FROM documents WHERE number = ? AND cdate = ? AND type = ?', array($cnote['number'],$cnote['cdate'],DOC_CNOTE));

			foreach($contents as $idx => $item)
			{
				$item['valuebrutto'] = str_replace(',','.', $item['valuebrutto']);
		    		$item['count'] = str_replace(',','.', $item['count']);
						
			        $DB->Execute('INSERT INTO invoicecontents (docid, itemid, value, taxid, prodid, content, count, description, tariffid)
					    VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)',
				            array($id,
				        	    $idx,
					            $item['valuebrutto'],
					            $item['taxid'],
						    $item['prodid'],
						    $item['jm'],
						    $item['count'],
						    $item['name'],
						    $item['tariffid']
					    ));

				if($item['cash'] != 0)
					$DB->Execute('INSERT INTO cash (time, userid, value, taxid, customerid, comment, docid, itemid)
			                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
						array($cnote['cdate'],
						        $AUTH->id,
						        str_replace(',','.',$item['cash']),
						        $item['taxid'],
						        $invoice['customerid'],
						        $item['name'],
						        $id,
						        $idx
						));
			}
			
			$SESSION->remove('invoice');
			$SESSION->remove('invoiceid');
			$SESSION->remove('cnote');
			$SESSION->remove('invoicecontents');
			$SESSION->remove('cnoteerror');
			$SESSION->redirect('?m=invoice&id='.$id);
		}
	break;
	
	case 'invoicedel':
	    
	        $LMS->InvoiceDelete($_GET['id']);
	        $SESSION->redirect('?m=invoicelist');
	break;				
}

$SESSION->save('invoice', $invoice);
$SESSION->save('cnote', $cnote);
$SESSION->save('invoicecontents', $contents);
$SESSION->save('cnoteerror', $error);

if($_GET['action'] != '')
{
	// redirect, ¿eby refreshem nie spierdoliæ faktury
	$SESSION->redirect('?m=invoicenote');
}

$SMARTY->assign('error', $error);
$SMARTY->assign('contents', $contents);
$SMARTY->assign('cnote', $cnote);
$SMARTY->assign('invoice', $invoice);
$SMARTY->assign('taxeslist', $taxeslist);
$SMARTY->assign('numberplanlist', $numberplanlist);
$SMARTY->display('invoicenote.html');

?>
