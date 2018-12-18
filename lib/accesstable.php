<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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
$global_access_regexp = '^(welcome|copyrights|logout|chpasswd|quicksearch|calendar|persistentsetting|zipcode|indicators)$';

$access_table = array(
	'full_access' => array(
		'label' => trans('full access'),
		'allow_regexp' => '^.*$'
	),
	'read_only' => array(
		'label' => trans('read only (excluding helpdesk)'),
		'allow_regexp' => '^(([a-z]+(list|info|view|search|balance|infoshort))|customeraddresses|customerassignmenthelper|netdevmap|eventprint|eventnote|nodelistshort|number|choose[a-z]+)$',
	),
	'node_connections' => array(
		'label' => trans('nodes connection/disconnection'),
		'allow_regexp' => '^nodeset$',
	),
	'finances_management' => array(
		'label' => trans('finances management'),
		'allow_regexp' => '^((tariff|customerassignment)(add|info|list|move|edit|del)|(payment)(add|del|edit|info|list)|(balance|customerbalance)(new|add|ok|del|list|)|(cashreg(list|info))|(invoice|invoice(list|new|edit|del|note|report|paid|info|send))|(note|note(list|add|edit|del|paid))|number|export|print|cashimport|cashimportparser|cashpackagedel|customertransferform)$',
	),
	'published_document_modification' => array(
		'label' => trans('published document modification'),
	),
	'reload' => array(
		'label' => trans('configuration reload'),
		'allow_regexp' => '^reload$',
	),
	'customer_management' => array(
		'label' => trans('customers management'),
		'allow_regexp' => '^((customer|document)(add|edit|info|infoshort|list|del|print|search|warn|cutoffstop|group)|customertransferform|customeraddresses|customerassignmenthelper|documentsend|documentgen|documentview|nodewarn|choosenode|gusapi)$',
	),
	'node_management' => array(
		'label' => trans('nodes management'),
		'allow_regexp' => '^(node(add|info|infoshort|list|listshort|scan|search|del|edit|print|warn)|choose(mac|ip|location|gpscoords|netdevice)|ping|sessionlist)$',
	),
	'traffic_stats' => array(
		'label' => trans('traffic stats'),
		'allow_regexp' => '^(traffic|traffic(print|graph))$',
	),
	'messaging' => array(
		'label' => trans('messaging (email, sms)'),
		'allow_regexp' => '^message(add|del|list|info|template(del|list))$',
	),
	'helpdesk_administration' => array(
		'label' => trans('Helpdesk (RT) administration'),
		'allow_regexp' => '^(rtsearch|rtprint|(rtqueue|rtticket|rtmessage|rtnote|rtcategory)(add|del|edit|info|view|list|print))$',
	),
	'helpdesk_operation' => array(
		'label' => trans('Helpdesk (RT) operation'),
		'allow_regexp' => '^(rtsearch|rtqueue(list|info|view)|(rtticket|rtmessage|rtnote)(add|edit|info|view|del|print))$',
	),
	'helpdesk_advanced_operation' => array(
			'label' => trans('Helpdesk (RT) advanced operation'),
			'allow_regexp' => '^(rtremove|rtrestore)$',
	),
	'hosting_management' => array(
		'label' => trans('hosting management'),
		'allow_regexp' => '^(accountpasswd|(account|domain|alias|record)(list|edit|add|del|info|search))$',
	),
	'configuration' => array(
		'label' => trans('configuration'),
		'allow_regexp' => '^(((host|config|numberplan|taxrate|state|division|cashsource)(list|edit|add|del|load|clone))|((promotion|promotionschema)(list|edit|add|del|set|info|clone)))$',
	),
	'network_management' => array(
		'label' => trans('networks and devices management'),
		'allow_regexp' => '^((net|netdev|ewxch)(info|list|edit|add|del|print|cmp|map(refresh|)|remap|search)|choose(mac|ip|gpscoords|netdevfrommap|netdevfornetnode|netdevmodel)|ewxnodelist|ewxdevlist|chooselocation|ping'
			. '|netnode(add|adddev|del|deldev|edit|info|list)|netdevmodels|netlinkproperties|netusage|attachments)$',
	),
	'timetable_management' => array(
		'label' => trans('timetable management'),
		'allow_regexp' => '^(event(list|edit|add|del|info|print|search|note)|choosecustomer)$',
	),
	'daemon_management' => array(
		'label' => trans('daemon management and configuration'),
		'allow_regexp' => '^((daemoninstance|daemonconfig)(list|edit|add|del|view))$',
	),
	'cash_operations' => array(
		'label' => trans('cash operations'),
		'allow_regexp' => '^(cashreglist|receipt|receipt(list|edit|add|del|adv)|cashreglog(info|view|add|edit|del)|choosecustomer)$',
	),
	'customer_group_management' => array(
		'label' => trans('customers groups management'),
		'allow_regexp' => '^(customergroup|customergroup(add|edit|del|info|list|move))$',
	),
	'node_group_management' => array(
		'label' => trans('nodes groups management'),
		'allow_regexp' => '^(nodegroup|nodegroup(add|edit|del|info|list|move))$',
	),
	'customer_group_assignments' => array(
		'label' => trans('customers to groups assignment'),
		'allow_regexp' => '^customergroup$',
	),
	'node_group_assignments' => array(
		'label' => trans('nodes to groups assignment'),
		'allow_regexp' => '^nodegroup$',
	),
	'hide_summaries' => array(
		'label' => trans('summaries hiding'),
	),
	'voip_account_management' => array(
		'label' => trans('voip accounts management'),
		'allow_regexp' => '^(voipimport|voipaccount(list|search|info|add|del|edit|rules))$',
	),
	'userpanel_management' => array(
		'label' => trans('Userpanel management'),
		'allow_regexp' => '^userpanel$',
	),
	'hide_sysinfo' => array(
		'label' => trans('system information hiding'),
	),
	'hide_links' => array(
		'label' => trans('links hiding'),
	),
	'hide_finances' => array(
		'label' => trans('finances hiding'),
	),
	'reports' => array(
		'label' => trans('reports'),
	),
	'cash_registry_administration' => array(
		'label' => trans('cash registry administration'),
		'allow_regexp' => '^cashreg(add|edit|del)$',
	),
	'transaction_logs' => array(
		'label' => trans('transaction logs'),
		'allow_regexp' => '^archive(info|view)$',
	),
	'hide_voip_passwords' => array(
		'label' => trans('hide VoIP passwords'),
	),
	'traffic_stats_compacting' => array(
		'label' => trans('traffic stats compacting'),
		'allow_regexp' => '^trafficdbcompact$',
	),
	'backup_management_forbidden' => array(
		'label' => trans('backup access forbidden'),
		'deny_regexp' => '^db(del|list|new|recover|view)$',
	),
	'user_management_forbidden' => array(
		'label' => trans('users edition and addition forbidden'),
		'deny_regexp' => '^(user(add|del|edit|passwd))$',
	),
	'no_access' => array(
		'label' => trans('no access'),
		'deny_regexp' => '^.*$',
	),
);

$access = AccessRights::getInstance();
foreach ($access_table as $name => $permission)
	$access->appendPermission(new Permission($name, $permission['label'],
		array_key_exists('allow_regexp', $permission) ? $permission['allow_regexp'] : null,
		array_key_exists('deny_regexp', $permission) ? $permission['deny_regexp'] : null)
	);

// read user-defined access rights table
$custom_access_table = ConfigHelper::getConfig('phpui.custom_accesstable');
if (!is_null($custom_access_table))
	if (is_readable($custom_access_table) && ($custom_access_table[0] == DIRECTORY_SEPARATOR))
		@include_once($custom_access_table);
	else if (is_readable(LIB_DIR . DIRECTORY_SEPARATOR . $custom_access_table))
		@include_once(LIB_DIR . DIRECTORY_SEPARATOR . $custom_access_table);

?>
