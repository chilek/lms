<?

/*
 * LMS version 1.0.0
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

if($LMS->TariffExists($_GET[from])&&$LMS->TariffExists($_GET[to])&&$_GET[is_sure] = 1)
{
	$ADB->Execute("UPDATE users SET tariff=? WHERE tariff=? AND status=3",array($_GET[to],$_GET[from]));
	header("Location: ?m=tariffinfo&id=".$_GET[to]);
	exit(0);
}
else
	header("Location: ?".$_SESSION[backto]);
?>
