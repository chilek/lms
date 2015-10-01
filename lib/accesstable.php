<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
$global_access_regexp = '^(welcome|copyrights|logout|chpasswd|quicksearch|calendar)$';

$access = AccessRights::getInstance();
$access->appendPermission(new Permission('full_access', trans('full access'), '^.*$'));
$access->appendPermission(new Permission('read_only', trans('read only (excluding helpdesk)'),
	'^(([a-z]+(list|info|view|search|balance|infoshort))|netdevmap|eventprint|nodelistshort|number|choose[a-z]+)$'));
$access->appendPermission(new Permission('node_connections', trans('nodes connection/disconnection'), '^nodeset$'));
$access->appendPermission(new Permission('finances_management', trans('finances management'),
	'^((tariff|customerassignment)(add|info|list|move|edit|del)|(payment)(add|del|edit|info|list)|(balance|customerbalance)(new|add|ok|del|list|)|(cashreg(list|info))|(invoice|invoice(list|new|edit|del|note|report|paid))|(note|note(list|add|edit|del|paid))|number|export|print|cashimport|cashimportparser|cashpackagedel)$'));
$access->appendPermission(new Permission('reload', trans('configuration reload'), '^reload$'));
$access->appendPermission(new Permission('customer_management', trans('customers management'),
	'^((customer|document)(add|edit|info|infoshort|list|del|print|search|warn|cutoffstop|group)|documentgen|documentview|nodewarn|choosenode)$'));
$access->appendPermission(new Permission('node_management', trans('nodes management'),
	'^(node(add|info|infoshort|list|listshort|scan|search|del|edit|print|warn)|choose(mac|ip|location|gpscoords)|ping)$'));
$access->appendPermission(new Permission('traffic_stats', trans('traffic stats'),
	'^(traffic|traffic(print|graph))$'));
$access->appendPermission(new Permission('messaging', trans('messaging (email, sms)'), '^message(add|del|list|info)$'));
$access->appendPermission(new Permission('helpdesk_administration', trans('Helpdesk (RT) administration'),
	'^(rtsearch|rtprint|(rtqueue|rtticket|rtmessage|rtnote|rtcategory)(add|del|edit|info|view|list|print))$'));
$access->appendPermission(new Permission('helpdesk_operation', trans('Helpdesk (RT) operation'),
	'^(rtsearch|rtattachmentview|rtqueue(list|info|view)|(rtticket|rtmessage|rtnote)(add|edit|info|view|del|print))$'));
$access->appendPermission(new Permission('hosting_management', trans('hosting management'),
	'^(accountpasswd|(account|domain|alias|record)(list|edit|add|del|info|search))$'));
$access->appendPermission(new Permission('configuration', trans('configuration'),
	'^(((host|config|numberplan|taxrate|state|division|cashsource)(list|edit|add|del|load))|((promotion|promotionschema)(list|edit|add|del|set|info)))$'));
$access->appendPermission(new Permission('network_management', trans('networks and devices management'),
	'^((net|netdev|ewxch)(info|list|edit|add|del|print|cmp|map(refresh|)|remap|search)|choose(mac|ip|gpscoords|netdevfrommap|netdev)|ewxnodelist|ewxdevlist|chooselocation|ping|netnode(add|adddev|del|deldev|edit|info|list)|netdevmodels|netlinkproperties)$'));
$access->appendPermission(new Permission('timetable_management', trans('timetable management'),
	'^(event(list|edit|add|del|info|print|search)|choosecustomer)$'));
$access->appendPermission(new Permission('daemon_management', trans('daemon management and configuration'),
	'^((daemoninstance|daemonconfig)(list|edit|add|del|view))$'));
$access->appendPermission(new Permission('cash_operations', trans('cash operations'),
	'^(cashreglist|receipt|receipt(list|edit|add|del|adv)|cashreglog(info|view|add|edit|del)|choosecustomer)$'));
$access->appendPermission(new Permission('customer_group_management', trans('customers groups management'),
	'^(customergroup|customergroup(add|edit|del|info|list|move))$'));
$access->appendPermission(new Permission('node_group_management', trans('nodes groups management'),
	'^(nodegroup|nodegroup(add|edit|del|info|list|move))$'));
$access->appendPermission(new Permission('customer_group_assignments', trans('customers to groups assignment'), '^customergroup$'));
$access->appendPermission(new Permission('node_group_assignments', trans('nodes to groups assignment'), '^nodegroup$'));
$access->appendPermission(new Permission('hide_summaries', trans('summaries hiding')));
$access->appendPermission(new Permission('voip_account_management', trans('voip accounts management'),
	'^(voipimport|voipaccount(list|search|info|add|del|edit))$'));
$access->appendPermission(new Permission('userpanel_management', trans('Userpanel management'), '^userpanel$'));
$access->appendPermission(new Permission('hide_sysinfo', trans('system information hiding')));
$access->appendPermission(new Permission('hide_links', trans('links hiding')));
$access->appendPermission(new Permission('hide_finances', trans('finances hiding')));
$access->appendPermission(new Permission('reports', trans('reports')));
$access->appendPermission(new Permission('cash_registry_administration', trans('cash registry administration'),
	'^cashreg(add|edit|del)$'));
$access->appendPermission(new Permission('transaction_logs', trans('transaction logs'),
	'^archive(info|view)$'));
$access->appendPermission(new Permission('hide_voip_passwords', trans('hide VoIP passwords')));
$access->appendPermission(new Permission('traffic_stats_compacting', trans('traffic stats compacting'),
	'^trafficdbcompact$'));
$access->appendPermission(new Permission('backup_management_forbidden', trans('backup access forbidden'),
	null, '^db(del|list|new|recover|view)$'));
$access->appendPermission(new Permission('user_management_forbidden', trans('users edition and addition forbidden'),
	null, '^(user(add|del|edit|passwd))$'));
$access->appendPermission(new Permission('no_access', trans('no access'),
	null, '^.*$'));

// read user-defined access rights table
$custom_access_table = ConfigHelper::getConfig('phpui.custom_accesstable');
if (!is_null($custom_access_table))
	if (is_readable($custom_access_table) && ($custom_access_table[0] == DIRECTORY_SEPARATOR))
		@include_once($custom_access_table);
	else if (is_readable(LIB_DIR . DIRECTORY_SEPARATOR . $custom_access_table))
		@include_once(LIB_DIR . DIRECTORY_SEPARATOR . $custom_access_table);

?>
