<?php

/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

$layout[pagetitle] = 'Faktura VAT nr '.$_GET[id].'/LMS/'.$_GET[year];

$invoice = $LMS->GetInvoiceContent($_GET[id],$_GET[year]);

$invoice[content][vat] = $invoice[content][value] * 0.07;
$invoice[content][brutto] = $invoice[content][vat] + $invoice[content][value];

$kesz = explode(".",sprintf("%1.2f",$invoice[content][brutto]));
$invoice[content][text] = $LMS->NumberSpell($kesz[0])." ".$kesz[1]."/100 z³otych";

$invoice[provider][name] 	= $_CONFIG[finances][name];
$invoice[provider][address] 	= $_CONFIG[financesces][address];
$invoice[provider][zip] 	= $_CONFIG[finances][zip];
$invoice[provider][city] 	= $_CONFIG[finances][city];
$invoice[provider][footer] 	= $_CONFIG[finances][footer];
$invoice[provider][bank] 	= $_CONFIG[finances][bank];
$invoice[provider][account] 	= $_CONFIG[finances][account];
$invoice[deadline] 		= $_CONFIG[finances][deadline];

setlocale(LC_ALL, "pl_PL");

$SMARTY->assign('layout',$layout);
$SMARTY->assign('invoice',$invoice);
$SMARTY->display('invoice.html');

?>