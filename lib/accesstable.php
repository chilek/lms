<?php

/*
 * LMS version 1.5-cvs
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

// lista modu³ów które zawsze s± dostêpne dla ka¿dego
$access['allow'] = '^(welcome|copyrights|logout|chpasswd|quicksearch)$';

$access['table'][0]['name']		= 'pe³en dostêp';
$access['table'][0]['allow_reg']	= '^.*$';

$access['table'][1]['name']		= 'odczyt wszystkich danych';
$access['table'][1]['allow_reg']	= '^((admin|balance|db|net|node|netdev|tariff|payment|user|usergroup)(list|info|view|search|balance)|netdevmap|print)$';

$access['table'][2]['name']		= 'w³±czanie i od³±czanie komputerów';
$access['table'][2]['allow_reg']	= '^nodeset$';

$access['table'][3]['name']		= 'manipulacja finansami';
$access['table'][3]['allow_reg']	= '^((tariff)(add|info|list|move|edit|delete)|(payment)(add|del|edit|info|list)|(balance|userbalance)(new|add|ok|del|list)|(invoice|invoice(list|new|report|paid)))$';

$access['table'][4]['name']    		= 'prze³adowywanie konfiguracji';
$access['table'][4]['allow_reg']    	= '^reload$';

$access['table'][5]['name']		= 'manipulacja danymi u¿ytkowników';
$access['table'][5]['allow_reg']	= '^(user(add|edit|del|assignments|assignmentsedit|warn)|nodewarn|usergroup(add|edit|delete|move))$';

$access['table'][6]['name'] 		= 'manipulacja danymi komputerów';
$access['table'][6]['allow_reg']  	= '^(node(add|scan|del|edit|set|warn)|choose(mac|ip))$';

$access['table'][7]['name']    	     	= 'dostêp do statystyk';
$access['table'][7]['allow_reg']	= '^traffic$';

$access['table'][8]['name']         	= 'dostêp do korespondencji seryjnej';
$access['table'][8]['allow_reg']    	= '^(mailing)$';

$access['table'][9]['name']         	= 'zarz±dzanie Helpdeskiem (RT)';
$access['table'][9]['allow_reg']    	= '^(rtsearch|(rtqueue|rtticket|rtmessage)(add|del|edit|info|view|list|print))$';

$access['table'][10]['name']        	= 'obs³uga Helpdesku (RT)';
$access['table'][10]['allow_reg']   	= '^(rtsearch|rtqueue(list|info|view)|(rtticket|rtmessage)(add|edit|info|view|print))$';

$access['table'][11]['name']        	= 'manipulacja kontami';
$access['table'][11]['allow_reg']   	= '^(account(list|edit|add|del)|domain(list|edit|del))$';

$access['table'][12]['name']        	= 'konfiguracja interfejsu u¿ytkownika';
$access['table'][12]['allow_reg']   	= '^(config(list|edit|add|del))$';

$access['table'][253]['name']		= 'brak dostêpu do modyfikacji i zak³adania nowych kont administratorów';
$access['table'][253]['deny_reg']	= '^(admin(add|del|edit|passwd))$';

$access['table'][255]['name']		= 'brak dostêpu';
$access['table'][255]['deny_reg']	= '^.*$';

?>
