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

$payment = $LMS->GetPayment($_GET['id']);

switch($payment['period'])
{
	case '0':
		$payment['payday'] = "co miesi±c (".$payment['at'].")"; 
	break;
	case '1':
		switch($payment['at'])
		{
			case '1': $payment['payday'] = "co tydzieñ (pon)"; break;
			case '2': $payment['payday'] = "co tydzieñ (wt)"; break;
			case '3': $payment['payday'] = "co tydzieñ (¶r)"; break;
			case '4': $payment['payday'] = "co tydzieñ (czw)"; break;
			case '5': $payment['payday'] = "co tydzieñ (pt)"; break;
			case '6': $payment['payday'] = "co tydzieñ (sob)"; break;
			case '7': $payment['payday'] = "co tydzieñ (nie)"; break;
			default : $payment['payday'] = "brak"; break;
		}
	break;
	case '2':
		$at = date("d/m",($payment['at']-1)*86400);
		$payment['payday'] = "co rok (".$at.")";
	break;
	default:
		$payment['payday'] = "brak";
	break;
}

$layout['pagetitle'] = "Informacja o op³acie sta³ej: ".$payment['name'];

$_SESSION['backto'] = $_SERVER[QUERY_STRING];

$SMARTY->assign("layout",$layout);
$SMARTY->assign("payment",$payment);
$SMARTY->display("paymentinfo.html");

?>
