<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

unset($access);
// modules with access for everyone
$access['allow'] = '^(welcome|copyrights|logout|chpasswd|quicksearch|calendar)$';

$access['table'][0]['name']		= trans('full access');
$access['table'][0]['allow_reg']	= '^.*$';

$access['table'][1]['name']		= trans('read only (excluding helpdesk)');
$access['table'][1]['allow_reg']	= '^(([a-z]+(list|info|view|search|balance|infoshort))|netdevmap|eventprint|nodelistshort|number)$';

$access['table'][2]['name']		= trans('nodes connection/disconnection');
$access['table'][2]['allow_reg']	= '^nodeset$';

$access['table'][3]['name']		= trans('finances management');
$access['table'][3]['allow_reg']	= '^((tariff|customerassignment)(add|info|list|move|edit|del)|(payment)(add|del|edit|info|list)|(balance|customerbalance)(new|add|ok|del|list|)|(cashreg(list|info))|(invoice|invoice(list|new|edit|del|note|report|paid))|(note|note(list|add|edit|del|paid))|number|export|print|cashimport|cashimportparser|cashpackagedel)$';
$access['table'][3]['privilege']	= 'finances_management';

$access['table'][4]['name']    		= trans('configuration reload');
$access['table'][4]['allow_reg']    	= '^reload$';

$access['table'][5]['name']		= trans('customers management');
$access['table'][5]['allow_reg']	= '^((customer|document)(add|edit|info|infoshort|list|del|print|search|warn|cutoffstop|group)|documentgen|documentview|nodewarn|choosenode)$';

$access['table'][6]['name'] 		= trans('nodes management');
$access['table'][6]['allow_reg']  	= '^(node(add|info|infoshort|list|listshort|scan|search|del|edit|print|warn)|choose(mac|ip|location))$';

$access['table'][7]['name']    	     	= trans('traffic stats');
$access['table'][7]['allow_reg']	= '^(traffic|traffic(print|graph))$';

$access['table'][8]['name']         	= trans('messaging (email, sms)');
$access['table'][8]['allow_reg']    	= '^message(add|del|list|info)$';

$access['table'][9]['name']         	= trans('Helpdesk (RT) administration');
$access['table'][9]['allow_reg']    	= '^(rtsearch|rtprint|(rtqueue|rtticket|rtmessage|rtnote|rtcategory)(add|del|edit|info|view|list|print))$';

$access['table'][10]['name']        	= trans('Helpdesk (RT) operation');
$access['table'][10]['allow_reg']   	= '^(rtsearch|rtattachmentview|rtqueue(list|info|view)|(rtticket|rtmessage|rtnote)(add|edit|info|view|del|print))$';

$access['table'][11]['name']        	= trans('hosting management');
$access['table'][11]['allow_reg']   	= '^(accountpasswd|(account|domain|alias|record)(list|edit|add|del|info|search))$';

$access['table'][12]['name']        	= trans('configuration');
$access['table'][12]['allow_reg']   	= '^(((host|config|numberplan|taxrate|state|division|cashsource)(list|edit|add|del|load))|((promotion|promotionschema)(list|edit|add|del|set|info)))$';

$access['table'][13]['name']        	= trans('networks and devices management');
$access['table'][13]['allow_reg']   	= '^((net|netdev|ewxch)(info|list|edit|add|del|cmp|map(refresh|)|remap|search)|choose(mac|ip|gpscoords)|ewxnodelist|ewxdevlist|chooselocation|ping)$';

$access['table'][14]['name']        	= trans('timetable management');
$access['table'][14]['allow_reg']   	= '^(event(list|edit|add|del|info|print|search)|choosecustomer)$';

$access['table'][15]['name']        	= trans('daemon management and configuration');
$access['table'][15]['allow_reg']   	= '^((daemoninstance|daemonconfig)(list|edit|add|del|view))$';

$access['table'][16]['name']        	= trans('cash operations');
$access['table'][16]['allow_reg']   	= '^(cashreglist|receipt|receipt(list|edit|add|del|adv)|cashreglog(info|view|add|edit|del)|choosecustomer)$';
$access['table'][16]['privilege']	= 'cash_operations';

$access['table'][17]['name']		= trans('customers groups management');
$access['table'][17]['allow_reg']	= '^(customergroup|customergroup(add|edit|del|info|list|move))$';

$access['table'][18]['name']		= trans('nodes groups management');
$access['table'][18]['allow_reg']	= '^(nodegroup|nodegroup(add|edit|del|info|list|move))$';

$access['table'][19]['name']		= trans('customers to groups assignment');
$access['table'][19]['allow_reg']	= '^customergroup$';

$access['table'][20]['name']		= trans('nodes to groups assignment');
$access['table'][20]['allow_reg']	= '^nodegroup$';

$access['table'][21]['name']		= trans('summaries hiding');
$access['table'][21]['privilege']	= 'hide_summaries';

$access['table'][22]['name']		= trans('voip accounts management');
$access['table'][22]['allow_reg']	= '^(voipimport|voipaccount(list|search|info|add|del|edit))$';

$access['table'][23]['name']		= trans('Userpanel management');
$access['table'][23]['allow_reg']	= '^userpanel$';

$access['table'][24]['name']		= trans('system information hiding');
$access['table'][24]['privilege']	= 'hide_sysinfo';

$access['table'][25]['name']		= trans('links hiding');
$access['table'][25]['privilege']	= 'hide_links';

$access['table'][26]['name']		= trans('finances hiding');
$access['table'][26]['privilege']	= 'hide_finances';

$access['table'][27]['name']		= trans('reports');
$access['table'][27]['privilege']	= 'reports';

$access['table'][28]['name']		= trans('cash registry administration');
$access['table'][28]['allow_reg']	= '^cashreg(add|edit|del)$';

$access['table'][29]['name']		= trans('transaction logs');
$access['table'][29]['allow_reg']	= '^archive(info|view)$';
$access['table'][29]['privilege']	= 'transaction_logs';

$access['table'][253]['name']		= trans('users edition and addition forbidden');
$access['table'][253]['deny_reg']	= '^(user(add|del|edit|passwd))$';

$access['table'][255]['name']		= trans('no access');
$access['table'][255]['deny_reg']	= '^.*$';

// read user-defined access rights table
if(isset($CONFIG['phpui']['custom_accesstable']))
	if(is_readable($CONFIG['phpui']['custom_accesstable']))
                @include_once($CONFIG['phpui']['custom_accesstable']);

?>
