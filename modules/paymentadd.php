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

$payment = $_POST['payment'];

if(isset($payment))
{
	foreach($payment as $key => $value)
		$payment[$key] = trim($value);

	if($payment['creditor']=="" && $payment['name']=="" && $payment['value']=="")
	{
		header("Location: ?m=paymentlist");
		die;
	}

	$payment['value'] = str_replace(",",".",$payment['value']);

	if(!(ereg("^[-]?[0-9.,]+$",$payment['value'])))
		$error['value'] = "Podana warto�� nie jest poprawna!";

	if($payment['creditor'] == "")
		$error['creditor'] = "Musisz poda� nazw� wierzyciela!";

	if($payment['name'] == "")
		$error['name'] = "Musisz poda� nazw� op�aty sta�ej!";
	else
		if($LMS->GetPaymentIDByName($payment['name']))
			$error['name'] = "Istnieje ju� op�ata o nazwie '".$payment['name']."'!";

	$period = sprintf('%d',$payment['period']);
	
	if($period < 0 || $period > 3)
		$period = 1;

	switch($period)
	{
		case 0:
			$at = sprintf('%d',$payment['at']);
			if($at < 1 || $at > 7)
				$error['at'] = 'Niepoprawny dzie� tygodnia';
		break;
		case 1:
			$at = sprintf('%d',$payment['at']);
			if($at == 0)
			{
				$at = 1 + date('d',time());
				if($at > 28)
					$at = 1;
			}
			if($at < 1 || $at > 28)
		    		$error['at'] = 'Niepoprawny dzie� miesi�ca';
		break;
		case 2:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',trim($payment['at'])))
				$error['at'] = 'Niepoprawny format daty';
			else {
				list($d,$m) = split('/',trim($payment['at']));
				if($d>30 || $d<1)
					$error['at'] = 'Niepoprawna liczba dni w miesi�cu';
				if($m>3 || $m<1)
					$error['at'] = 'Niepoprawny numer miesi�ca (max.3)';
				
				$at = ($m-1) * 100 + $d;
			};
		break;
		case 3:
			if(!eregi('^[0-9]{2}/[0-9]{2}$',trim($payment['at'])))
				$error['at'] = 'Niepoprawny format daty';
			else
				list($d,$m) = split('/',trim($payment['at']));
			$ttime = mktime(12, 0, 0, $m, $d, 1990);
			$at = date('z',$ttime) + 1;
			
		break;	
	}
	
	$payment['period'] = $period;
	
	if(!$error)
	{
		$payment['at'] = $at;
		if($payment['reuse'] =='')
		{
			header("Location: ?m=paymentlist&id=".$LMS->PaymentAdd($payment));
			die;
		} else
			$LMS->PaymentAdd($payment);
			
		unset($payment);
		$payment['reuse'] = '1';
	}
}

$layout['pagetitle']="Nowa op�ata sta�a";

$SMARTY->assign("layout",$layout);
$SMARTY->assign("error",$error);
$SMARTY->assign("payment",$payment);
$SMARTY->display("paymentadd.html");

?>
