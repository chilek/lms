<?php

/*
 * LMS version 1.3-cvs
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

// lista modu��w kt�re zawsze s� dost�pne dla ka�dego

$access[allow] = "^(welcome|copyrights|logout|chpasswd|quicksearch)$";

$access[table][0][name]		= "pe�en dost�p";
$access[table][0][allow_reg]	= "^.*$";

$access[table][1][name]		= "odczyt wszystkich danych";
$access[table][1][allow_reg]	= "^((admin|balance|db|net|node|netdev|tariff|payment|user|usergroup)(list|info|view|search|balance)|netdevmap|print)$";

$access[table][2][name]		= "w��czanie i od��czanie komputer�w";
$access[table][2][allow_reg]	= "^nodeset$";

$access[table][3][name]		= "manipulacja finansami";
$access[table][3][allow_reg]	= "^((tariff)(add|info|list|move|edit|delete)|(payment)(add|del|edit|info|list)|(balance|balance|userbalance)(new|add|ok)|(invoice|invoice(list|new|report)))$";

$access[table][4][name]         = "prze�adowywanie konfiguracji";
$access[table][4][allow_reg]    = "^reload$";

$access[table][5][name]		= "manipulacja kontami u�ytkownik�w";
$access[table][5][allow_reg]	= "^(user(add|edit|del)|nodewarn|usergroup(add|edit|delete|move))$";

$access[table][6][name]		= "manipulacja danymi komputer�w";
$access[table][6][allow_reg]	= "^(node(add|scan|del|edit|set|warn)|choose(mac|ip))$";

$access[table][7][name]         = "dost�p do statystyk";
$access[table][7][allow_reg]    = "^traffic$";

$access[table][8][name]         = "dost�p do korespondencji seryjnej";
$access[table][8][allow_reg]    = "^(mailing|mailingsend)$";

$access[table][9][name]         = "zarz�dzanie Helpdeskiem (RT)";
$access[table][9][allow_reg]    = "^((rtqueue|rtticket|rtmessage)(add|del|edit|info|view|list))$";

$access[table][10][name]        = "obs�uga Helpdesku (RT)";
$access[table][10][allow_reg]   = "^(rtqueue(list|info|view)|(rtticket|rtmessage)(add|del|edit|info|view))$";

$access[table][253][name]	= "brak dost�pu do modyfikacji i zak�adania nowych kont administrator�w";
$access[table][253][deny_reg]	= "^(admin(add|del|edit|passwd))$";

$access[table][255][name]	= "brak dost�pu";
$access[table][255][deny_reg]	= "^.*$";

?>
