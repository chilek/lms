<?

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

// Array with left menu on engine.

$menu = Array();

$menu[name]	[] = "Witamy !";
$menu[img]	[] = "l.gif";
$menu[link]	[] = "?";
$menu[accesskey][] = "";
$menu[tip]	[] = "";

$menu[name]	[] = "U¿ytkownicy";
$menu[img]	[] = "user.gif";
$menu[link]	[] = "?m=userlist";
$menu[accesskey][] = "u";
$menu[tip]	[] = "U¿ytkownicy: lista, wyszukiwanie, dodanie nowego";

$menu[name]	[] = "Komputery";
$menu[img]	[] = "node.gif";
$menu[link]	[] = "?m=nodelist";
$menu[accesskey][] = "k";
$menu[tip]	[] = "Komputery: lista, wyszukiwanie, dodawanie";

$menu[name]	[] = "Osprzêt sieciowy";
$menu[img]	[] = "mac.gif";
$menu[link]	[] = "?m=netdevlist";
$menu[accesskey][] = "p";
$menu[tip]	[] = "Ewidencja osprzêtu sieciowego";

$menu[name]	[] = "Sieci IP";
$menu[img]	[] = "ip.gif";
$menu[link]	[] = "?m=netlist";
$menu[accesskey][] = "s";
$menu[tip]	[] = "Zarz±dzanie klasami adresowymi IP";

$menu[name]	[] = "Taryfy i finanse";
$menu[img]	[] = "money.gif";
$menu[link]	[] = "?m=tarifflist";
$menu[accesskey][] = "t";
$menu[tip]	[] = "Zarz±dzanie taryfami oraz finansami sieci";

$menu[name]	[] = "Mailing";
$menu[img]	[] = "mail.gif";
$menu[link]	[] = "?m=mailing";
$menu[accesskey][] = "m";
$menu[tip]	[] = "";

$menu[name]	[] = "Prze³adowanie";
$menu[img]	[] = "reload.gif";
$menu[link]	[] = "?m=reload";
$menu[accesskey][] = "r";
$menu[tip]	[] = "";

$menu[name]	[] = "Bazy danych";
$menu[img]	[] = "db.gif";
$menu[link]	[] = "?m=dblist";
$menu[accesskey][] = "b";
$menu[tip]	[] = "Zarz±dzanie kopiami zapasowymi bazy danych";

$menu[name]	[] = "Administratorzy";
$menu[img]	[] = "admins.gif";
$menu[link]     [] = "?m=adminlist";
$menu[accesskey][] = "d";
$menu[tip]	[] = "Konta administratorów systemu";

?>
