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

$layout['pagetitle']="Lista op³at sta³ych";

$paymentlist = $LMS->GetPaymentList();

$listdata['total'] = $paymentlist['total'];
unset($paymentlist[total]);

foreach($paymentlist as $idx => $value)
{
	switch($paymentlist[$idx]['period'])
	{
		case '0':
			$paymentlist[$idx]['payday'] = "co miesi±c (".$paymentlist[$idx]['at'].")"; 
		break;
		case '1':
			switch($paymentlist[$idx]['at'])
			{
				case '1': $paymentlist[$idx]['payday'] = "co tydzieñ (pon)"; break;
				case '2': $paymentlist[$idx]['payday'] = "co tydzieñ (wt)"; break;
				case '3': $paymentlist[$idx]['payday'] = "co tydzieñ (¶r)"; break;
				case '4': $paymentlist[$idx]['payday'] = "co tydzieñ (czw)"; break;
				case '5': $paymentlist[$idx]['payday'] = "co tydzieñ (pt)"; break;
				case '6': $paymentlist[$idx]['payday'] = "co tydzieñ (sob)"; break;
				case '7': $paymentlist[$idx]['payday'] = "co tydzieñ (nie)"; break;
				default : $paymentlist[$idx]['payday'] = "brak"; break;
			}
		break;
		case '2':
			$at = date("d/m",($paymentlist[$idx]['at']-1)*86400);
			$paymentlist[$idx]['payday'] = "co rok (".$at.")";
		break;
		default:
			$paymentlist[$idx]['payday'] = "brak";
		break;
	}
}

$_SESSION['backto'] = $_SERVER[QUERY_STRING];

$SMARTY->assign("paymentlist",$paymentlist);
$SMARTY->assign("listdata",$listdata);
$SMARTY->assign("layout",$layout);
$SMARTY->display("paymentlist.html");

?>
