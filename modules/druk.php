<?php

/*
 * LMS version 1.0-pre10
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

header("Content-type: image/png");

if(isset($_SERVER[HTTP_X_FORWARDED_FOR]))
    $ipaddr = str_replace("::ffff:","",$_SERVER[HTTP_X_FORWARDED_FOR]);
else
    $ipaddr = str_replace("::ffff:","",$_SERVER[REMOTE_ADDR]);

$id = $LMS->GetUserIDByIP($ipaddr);
$userinfo=$LMS->GetUser($id);

$font = imagepsloadfont ("img/arial.iso8859-2.pfb");
$obraz = imagecreatefrompng('img/przelew.png');

$czarny = imagecolorallocate($obraz, 0, 0, 0);
$bialy = imagecolorallocate ($obraz, 255, 255, 255);

// Dobra, czytamy z lms.ini
$_NAME = (! $_CONFIG[finances]['name'] ? "Nazwa nieustawiona" : $_CONFIG[finances]['name']);
$_ADDRESS = (! $_CONFIG[finances]['address'] ? "Adres nieustawiony" : $_CONFIG[finances]['address']);
$_ZIP = (! $_CONFIG[finances]['zip'] ? "00-000" : $_CONFIG[finances]['zip']);
$_CITY = (! $_CONFIG[finances]['city'] ? "Miasto nieustawione" : $_CONFIG[finances]['city']);
$_SERVICE = (! $_CONFIG[finances]['service'] ? "Nazwa us³ugi nieustawiona" : $_CONFIG[finances]['service']);
$_ACCOUNT = (! $_CONFIG[finances]['account'] ? "Numer konta nieustawiony" : $_CONFIG[finances]['account']);

imagepstext ($obraz, $_NAME, $font, 25, $czarny, $bialy, 40, 40);
imagepstext ($obraz, $_ADDRESS." ".$_ZIP." ".$_CITY, $font, 25, $czarny, $bialy, 40, 92);
imagepstext ($obraz, $_ACCOUNT, $font, 25, $czarny, $bialy, 100, 144);
imagepstext ($obraz, "**".-$userinfo['balance']."**", $font, 25, $czarny, $bialy, 488, 196);
imagepstext ($obraz, $userinfo['username'], $font, 25, $czarny, $bialy, 40, 300);
imagepstext ($obraz, $userinfo['address']." ".$userinfo['zip']." ".$userinfo['city'], $font, 25, $czarny, $bialy, 40, 352);
imagepstext ($obraz, $_SERVICE, $font, 25, $czarny, $bialy, 40, 404);

imagepstext ($obraz, $_NAME, $font, 25, $czarny, $bialy, 40,684);
imagepstext ($obraz, $_ADDRESS." ".$_ZIP." ".$_CITY, $font, 25, $czarny, $bialy, 40, 736);
imagepstext ($obraz, $_ACCOUNT, $font, 25, $czarny, $bialy, 100, 788);
imagepstext ($obraz, "**".-$userinfo['balance']."**", $font, 25, $czarny, $bialy, 488, 840);
imagepstext ($obraz, $userinfo['username'], $font, 25, $czarny, $bialy, 40, 944);
imagepstext ($obraz, $userinfo['address']." ".$userinfo['zip']." ".$userinfo['city'], $font, 25, $czarny, $bialy, 40, 996);
imagepstext ($obraz, $_SERVICE, $font, 25, $czarny, $bialy, 40, 1048);

imagepstext ($obraz, $_ACCOUNT, $font, 20, $czarny, $bialy, 935, 35);
imagepstext ($obraz, $_NAME, $font, 20, $czarny, $bialy, 935,125);
imagepstext ($obraz, $_ADDRESS, $font, 20, $czarny, $bialy, 935, 150);
imagepstext ($obraz, $_ZIP." ".$_CITY, $font, 20, $czarny, $bialy, 935, 175);
imagepstext ($obraz, "**".-$userinfo['balance']."**", $font, 20, $czarny, $bialy, 935, 220);
imagepstext ($obraz, $userinfo['username'], $font, 20, $czarny, $bialy, 935, 310);
imagepstext ($obraz, $userinfo['address'], $font, 20, $czarny, $bialy, 935, 335);
imagepstext ($obraz, $userinfo['zip']." ".$userinfo['city'], $font, 20, $czarny, $bialy, 935, 360);

imagepstext ($obraz, $_ACCOUNT, $font, 20, $czarny, $bialy, 935, 679);
imagepstext ($obraz, $_NAME, $font, 20, $czarny, $bialy, 935, 769);
imagepstext ($obraz, $_ADDRESS, $font, 20, $czarny, $bialy, 935, 794);
imagepstext ($obraz, $_ZIP." ".$_CITY, $font, 20, $czarny, $bialy, 935, 819);
imagepstext ($obraz, "**".-$userinfo['balance']."**", $font, 20, $czarny, $bialy, 935, 864);
imagepstext ($obraz, $userinfo['username'], $font, 20, $czarny, $bialy, 935, 954);
imagepstext ($obraz, $userinfo['address'], $font, 20, $czarny, $bialy, 935, 979);
imagepstext ($obraz, $userinfo['zip']." ".$userinfo['city'], $font, 20, $czarny, $bialy, 935, 1004);

imagepsfreefont ($font);
imagepng($obraz);
imagedestroy($obraz);
?>
							    
