<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

if(!$LMS->PaymentExists($_GET['id']))
{
	header("Location: ?m=paymentlist");
	die;
}

$payment = $_POST['payment'];

if(isset($payment))
{
	foreach($payment as $key => $value)
		$payment[$key] = trim($value);

	$payment['value'] = str_replace(",",".",$payment['value']);
	
	if($payment['creditor'] == "")
		$error['creditor'] = "Musisz podaæ nazwê wierzyciela!";

	if($payment['name'] == "")
		$error['name'] = "Proszê podaæ nazwê op³aty sta³ej!";
	elseif($LMS->GetpaymentIDByName($payment['name']) && $payment['name'] != $LMS->GetPaymentName($_GET['id']))
		$error['name'] = "Istnieje ju¿ op³ata o takiej nazwie!";	

	if($payment['value'] == "")
		$error['value'] = "Proszê podaæ warto¶æ!";
	elseif(!(ereg("^[0-9.,]+$", $payment['value'])))
		$error['value'] = "Podana warto¶æ jest niepoprawna!";

	$period = sprintf('%d',$payment['period']);
	
	if($period < 0 || $period > 2)
		$period = 0;

	switch($period)
	{
		case 0:
			$at = sprintf('%d',$payment['at']);
			if($at == 0)
			{
				$at = 1 + date('d',time());
				if($at > 28)
					$at = 1;
			}
			if($at < 1)
				$at = 1;
			elseif($at > 28)
				$at = 28;
		break;
		case 1:
			$at = sprintf('%d',$payment['at']);
			if($at < 1)
				$at = 1;
			elseif($at > 7)
				$at = 7;
		break;
		case 2:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',trim($payment['at'])))
				$error[] = 'Niepoprawny format daty';
			else
				list($d,$m) = split('/',trim($payment['at']));
			$ttime = mktime(12, 0, 0, $m, $d, 1990);
			$at = date('z',$ttime) + 1;
		break;	
	}
	
	$payment['period'] = $period;
	$payment['at'] = $at;
	$payment['id'] = $_GET['id'];

	if(!$error)
	{
		$LMS->PaymentUpdate($payment);
		header("Location: ?m=paymentinfo&id=".$payment['id']);
		die;
	}

} else
	$payment = $LMS->GetPayment($_GET['id']);

if($payment['period']==2)
	$payment['at'] = date("d/m",($payment['at']-1)*86400);
	
$layout['pagetitle']="Edycja op³aty sta³ej: ".$payment['name'];	

$SMARTY->assign("layout",$layout);
$SMARTY->assign("payment",$payment);
$SMARTY->assign("error",$error);
$SMARTY->display("paymentedit.html");

?>
