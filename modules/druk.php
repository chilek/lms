<?php

/*
 * LMS version 1.6-cvs
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

if(! $invoice = $LMS->GetInvoiceContent($_GET['id']))
{
	$SESSION->redirect('?m=invoicelist');
}

//echo '<PRE>';print_r($invoice);

$img = imagecreatefromgif($LMS->CONFIG['directories']['sys_dir'].'/img/druk.gif');
$black = imagecolorallocate($img, 0, 0, 0);

$cols = array( 46, 65, 84, 104, 123, 143, 162, 181, 200, 219, 238, 257, 276, 295, 314, 333, 352, 371, 390, 409, 428, 447, 466, 485, 504, 523, 542 );
$rows = array( 14, 46, 78, 109, 142, 171, 203, 234, 265 );

//	imagestring($out, 2, 5, $i, ($i/4), $black);

for($i = 0; $i < strlen($_GET[linia1]) && $i < sizeof($cols); $i++)
	imagestring($img, 5, $cols[$i]+3, $rows[0]+3, $_GET[linia1][$i], $black);
for($i = 0; $i < strlen($_GET[linia2]) && $i < sizeof($cols); $i++)
	imagestring($img, 5, $cols[$i]+3, $rows[1]+3, $_GET[linia2][$i], $black);

$linia3 = substr($_GET['nrrk'],0,25);
$linia4 = substr($_GET['nrrk'],25,8);

for($i = 0; $i < strlen($linia3); $i++ )
	imagestring($img, 5, $cols[$i+2]+3, $rows[2]+3, $linia3[$i], $black);
for($i = 0; $i < strlen($linia4); $i++ )
	imagestring($img, 5, $cols[$i]+3, $rows[3]+3, $linia4[$i], $black);

$cur = ($_GET['cur'] != '' ? $_GET['cur'] : 'PLN');

for($i = 0; $i < strlen($cur) && $i < 3; $i++)
	imagestring($img, 5, $cols[$i+11]+3, $rows[3]+3, $cur[$i], $black);

$val = str_replace('.',',',sprintf('%.2f',$invoice['total']));

for($i = 0; $i < strlen($val) && $i < 11; $i++)
	imagestring($img, 5, $cols[$i+15]+3, $rows[3]+3, $val[$i], $black);

imagestring($img, 3, $cols[0]+5,$rows[4]+4, trim(to_words(sprintf('%d',$invoice['total']))).' z³ '.trim(to_words($invoice['totalg'])).' gr', $black);

$ntempl = $LMS->CONFIG['invoices']['number_template'];
$ntempl = str_replace('%N',$invoice['number'],$ntempl);
$ntempl = str_replace('%M',$invoice['month'],$ntempl);
$ntempl = str_replace('%Y',$invoice['year'],$ntempl);

$linia5 = $invoice['name'];
$linia6 = $invoice['zip'].' '.$invoice['city'].', '.$invoice['address'];
$linia7 = 'ID KLIENTA: '.sprintf('%04d',$invoice['customerid']);
$linia8 = 'Op³ata za fakturê VAT nr '.$ntempl;

//imagestring($img, 5, $cols[0]+3, $rows[5]+3, $linia5, $black);
for($i = 0; $i < strlen($linia5); $i++ )
	imagestring($img, 5, $cols[$i]+3, $rows[5]+3, $linia5[$i], $black);

imagestring($img, 5, $cols[0]+3, $rows[6]+3, $linia6, $black);
/*for($i = 0; $i < strlen($linia6); $i++ )
	imagestring($img, 5, $cols[$i]+3, $rows[6]+3, $linia6[$i], $black);*/

//imagestring($img, 5, $cols[0]+3, $rows[7]+3, $linia7, $black);
for($i = 0; $i < strlen($linia7); $i++ )
	imagestring($img, 5, $cols[$i]+3, $rows[7]+3, $linia7[$i], $black);

imagestring($img, 5, $cols[0]+3, $rows[8]+3, $linia8, $black);
//for($i = 0; $i < strlen($linia8); $i++ ) imagestring($img, 5, $cols[$i]+3, $rows[8]+3, $linia8[$i], $black);

imagegif($img);
/*
	46, 65, 84, 104, 123, 143, 162, 181, 200, 219, 238, 257, 276, 295, 314, 333, 352, 371, 390, 409, 428, 447, 466, 485, 504, 523, 542,

	14, 46, 78, 104, 142, 171, 234, 265, 
	    19, 19,
*/

?>
