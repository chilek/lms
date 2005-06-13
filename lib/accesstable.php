<?php

/*
 * LMS version 1.7-cvs
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

// modules with access for everyone
$access['allow'] = '^(welcome|copyrights|logout|chpasswd|quicksearch|calendar)$';

$access['table'][0]['name']		= trans('full access');
$access['table'][0]['allow_reg']	= '^.*$';

$access['table'][1]['name']		= trans('read only (excluding helpdesk)');
$access['table'][1]['allow_reg']	= '^((user|balance|db|net|node|netdev|tariff|payment|customer|customergroup|account|alias|domain|config|event|taxrate)(list|info|view|search|balance|infoshort)|netdevmap|print|eventprint|nodelistshort)$';

$access['table'][2]['name']		= trans('nodes connection/disconnection');
$access['table'][2]['allow_reg']	= '^nodeset$';

$access['table'][3]['name']		= trans('finances management');
$access['table'][3]['allow_reg']	= '^((tariff)(add|info|list|move|edit|delete)|(payment)(add|del|edit|info|list)|(balance|customerbalance)(new|add|ok|del|list)|(taxrate)(list|add|edit|del))|(receipt|receipt(list|add|edit|del))|(invoice|invoice(list|new|edit|report|paid))|prepayments|choosecovenants)$';

$access['table'][4]['name']    		= trans('configuration reload');
$access['table'][4]['allow_reg']    	= '^reload$';

$access['table'][5]['name']		= trans('customers management');
$access['table'][5]['allow_reg']	= '^(customer(add|edit|del|assignments|assignmentsedit|warn)|nodewarn|customergroup(add|edit|delete|move))$';

$access['table'][6]['name'] 		= trans('nodes management');
$access['table'][6]['allow_reg']  	= '^(node(add|scan|del|edit|warn)|choose(mac|ip))$';

$access['table'][7]['name']    	     	= trans('stats access');
$access['table'][7]['allow_reg']	= '^traffic$';

$access['table'][8]['name']         	= trans('mailing access');
$access['table'][8]['allow_reg']    	= '^(mailing)$';

$access['table'][9]['name']         	= trans('Helpdesk (RT) administration');
$access['table'][9]['allow_reg']    	= '^(rtsearch|(rtqueue|rtticket|rtmessage)(add|del|edit|info|view|list|print))$';

$access['table'][10]['name']        	= trans('Helpdesk (RT) operation');
$access['table'][10]['allow_reg']   	= '^(rtsearch|rtqueue(list|info|view)|(rtticket|rtmessage)(add|edit|info|view|print))$';

$access['table'][11]['name']        	= trans('accounts management');
$access['table'][11]['allow_reg']   	= '^((account|domain|alias)(list|edit|add|del))$';

$access['table'][12]['name']        	= trans('UI configuration');
$access['table'][12]['allow_reg']   	= '^(config(list|edit|add|del|load))$';

$access['table'][13]['name']        	= trans('networks and devices management');
$access['table'][13]['allow_reg']   	= '^((net|netdev)(info|list|edit|add|del|cmp|map|remap)|choose(mac|ip))$';

$access['table'][14]['name']        	= trans('timetable management');
$access['table'][14]['allow_reg']   	= '^(event(list|edit|add|del|info|print|search))$';

$access['table'][15]['name']        	= trans('daemon management and configuration');
$access['table'][15]['allow_reg']   	= '^((daemonhost|daemoninstance|daemonconfig)(list|edit|add|del|view))$';

$access['table'][253]['name']		= trans('users edition and addition forbidden');
$access['table'][253]['deny_reg']	= '^(user(add|del|edit|passwd))$';

$access['table'][255]['name']		= trans('no access');
$access['table'][255]['deny_reg']	= '^.*$';

// read user-defined access rights table
if(isset($CONFIG['phpui']['custom_accesstable']))
	@include_once($_LIB_DIR.'/'.$CONFIG['phpui']['custom_accesstable']);

?>
