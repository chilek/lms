<?

/*
 * LMS version 1.0-cvs
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

$access[allow] = "^(welcome|copyrights|logout|chpasswd)$";

$access[table][0][name]		= "pe�en dost�p";
$access[table][0][allow_reg]	= "^.*$";

$access[table][1][name]		= "odczyt wszystkich danych";
$access[table][1][allow_reg]	= "^((admin|balance|db|net|node|tariff|user)(list|list(debt|disc)|info|view|debt|search|balance)|print)$";

$access[table][2][name]		= "w��czanie i od��czanie komputer�w";
$access[table][2][allow_reg]	= "^nodeset$";

$access[table][3][name]		= "manipulacja finansami";
$access[table][3][allow_reg]	= "^(balancenew|balanceadd|userbalanceok)$";

$access[table][4][name]         = "prze�adowywanie konfiguracji";
$access[table][4][allow_reg]    = "^reload$";

$access[table][5][name]		= "manipulacja kontami u�ytkownik�w";
$access[table][5][allow_reg]	= "^user(add|edit|del)$";

$access[table][6][name]		= "manipulacja danymi komputer�w";
$access[table][6][allow_reg]	= "^node(add|del|edit|set)$";

$access[table][255][name]	= "brak dost�pu";
$access[table][255][deny_reg]	= "^.*$";

?>
