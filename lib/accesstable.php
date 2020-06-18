<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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
$global_access_regexp = '^(welcome|copyrights|logout|chpasswd|twofactorauth(info|edit)|quicksearch|calendar|persistentsetting|zipcode|indicators|dns|configinfo)$';

$access_table = array(
    'full_access' => array(
        'label' => trans('full access'),
        'allow_regexp' => '^.*$',
        'allow_menu_items' => Permission::MENU_ALL,
    ),
    'read_only' => array(
        'label' => trans('read only (excluding helpdesk)'),
        'allow_regexp' => '^(([a-z]+(list|info|view|search|balance|infoshort))|customeraddresses|customerassignmenthelper|netdevmap|eventprint|eventnote|nodelistshort|number|choose[a-z]+)$',
        'allow_menu_items' => Permission::MENU_ALL,
    ),
    'node_connections' => array(
        'label' => trans('nodes connection/disconnection'),
        'allow_regexp' => '^nodeset$',
        'allow_menu_items' => array('nodes'),
    ),
    'finances_management' => array(
        'label' => trans('finances management'),
        'allow_regexp' => '^((tariff|customerassignment)(add|info|list|move|edit|del)|(payment)(add|del|edit|info|list)|(balance|customerbalance)(new|add|ok|del|list|)|(cashreg(list|info))|(invoice|invoice(list|new|edit|del|note|report|paid|info|send))|(note|note(list|add|edit|del|paid))|number|export|print|cashimport|cashimportparser|cashpackagedel|customertransferform)$',
        'allow_menu_items' => array('finances'),
    ),
    'used_tariff_edit' => array(
        'label' => trans('used tariff edit'),
    ),
    'promotion_management' => array(
        'label' => trans('promotion management'),
        'allow_regexp' => '^(promotion|promotionschema)(list|edit|add|del|set|info|clone)$',
        'allow_menu_items' => array('config'),
    ),
    'trade_document_archiving' => array(
        'label' => trans('trade document archiving'),
        'allow_regexp' => '^(invoice|note)archive$',
    ),
    'trade_document_unarchiving' => array(
        'label' => trans('trade document unarchiving'),
        'allow_regexp' => '^(invoice|note)unarchive$',
    ),
    'invoice_consent_date' => array(
        'label' => trans('invoice consent date manipulation'),
    ),
    'invoice_sale_date' => array(
        'label' => trans('invoice sale date manipulation'),
    ),
    'published_document_modification' => array(
        'label' => trans('published document modification'),
    ),
    'reload' => array(
        'label' => trans('configuration reload'),
        'allow_regexp' => '^reload$',
        'allow_menu_items' => array('reload'),
    ),
    'customer_management' => array(
        'label' => trans('customers management'),
        'allow_regexp' => '^((customer|document)(add|edit|info|infoshort|list|print|search|warn|cutoffstop|group)|documentdel|customertransferform|customeraddresses|customerassignmenthelper|documentsend|documentgen|documentview|nodewarn|choosenode|gusapi)$',
        'allow_menu_items' => array('customers', 'documents'),
    ),
    'customer_removal' => array(
        'label' => trans('customer removal'),
        'allow_regexp' => '^customerdel$',
    ),
    'permanent_customer_removal' => array(
        'label' => trans('permanent customer removal'),
        'allow_regexp' => '^customerdel$',
    ),
    'node_management' => array(
        'label' => trans('nodes management'),
        'allow_regexp' => '^(node(add|info|infoshort|list|listshort|scan|search|del|edit|print|warn|sessionlist)|choose(mac|ip|location|gpscoords|netdevice)|ping)|customeraddresses$',
        'allow_menu_items' => array('nodes'),
    ),
    'traffic_stats' => array(
        'label' => trans('traffic stats'),
        'allow_regexp' => '^(traffic|traffic(print|graph))$',
        'allow_menu_items' => array('stats'),
    ),
    'messaging' => array(
        'label' => trans('messaging (email, sms)'),
        'allow_regexp' => '^message(add|del|list|info|template(del|list))$',
        'allow_menu_items' => array('messages'),
    ),
    'helpdesk_administration' => array(
        'label' => trans('Helpdesk (RT) administration'),
        'allow_regexp' => '^(rtsearch|rtprint|(rtqueue|rtticket|rtmessage|rtnote|rtcategory)(add|del|edit|info|view|list|print))$',
        'allow_menu_items' => array('helpdesk'),
    ),
    'helpdesk_operation' => array(
        'label' => trans('Helpdesk (RT) operation'),
        'allow_regexp' => '^(rtsearch|rtqueue(list|info|view)|(rtticket|rtmessage|rtnote)(add|edit|info|view|del|print))$',
        'allow_menu_items' => array('helpdesk'),
    ),
    'helpdesk_advanced_operation' => array(
        'label' => trans('Helpdesk (RT) advanced operation'),
        'allow_regexp' => '^(rtremove|rtrestore)$',
    ),
    'hosting_management' => array(
        'label' => trans('hosting management'),
        'allow_regexp' => '^(accountpasswd|(account|domain|alias|record)(list|edit|add|del|info|search))$',
        'allow_menu_items' => array('hosting'),
    ),
    'configuration' => array(
        'label' => trans('configuration'),
        'allow_regexp' => '^(((host|config|numberplan|taxrate|state|division|cashsource)(list|edit|add|del|load|clone))|((promotion|promotionschema)(list|edit|add|del|set|info|clone)))$',
        'allow_menu_items' => array('config'),
    ),
    'network_management' => array(
        'label' => trans('networks and devices management'),
        'allow_regexp' => '^((net|netdev|ewxch)(info|list|edit|add|del|print|cmp|map(refresh|)|remap|search)|choose(mac|ip|gpscoords|netdevfrommap|netdevfornetnode|netdevmodel|netdevreplace)|ewxnodelist|ewxdevlist|chooselocation|ping'
            . '|netnode(add|adddev|del|deldev|edit|info|list)|netdevmodels|netlinkproperties|netusage|attachments)$',
        'allow_menu_items' => array('networks', 'netdevices'),
    ),
    'timetable_management' => array(
        'label' => trans('timetable management'),
        'allow_regexp' => '^(event(list|edit|add|del|info(short)?|print|search|note|schedule)|choosecustomer)$',
        'allow_menu_items' => array('timetable'),
    ),
    'project_management' => array(
        'label' => trans('investment project management'),
        'allow_regexp' => '^invproject(add|del|edit|list)$',
        'allow_menu_items' => array('config'),
    ),
    'daemon_management' => array(
        'label' => trans('daemon management and configuration'),
        'allow_regexp' => '^((daemoninstance|daemonconfig)(list|edit|add|del|view))$',
        'allow_menu_items' => array('config'),
    ),
    'cash_operations' => array(
        'label' => trans('cash operations'),
        'allow_regexp' => '^(cashreglist|receipt|receipt(list|edit|add|del|adv)|cashreglog(info|view|add|edit|del)|choosecustomer)$',
        'allow_menu_items' => array('finances'),
    ),
    'customer_group_management' => array(
        'label' => trans('customers groups management'),
        'allow_regexp' => '^(customergroup|customergroup(add|edit|del|info|list|move))$',
        'allow_menu_items' => array('customers'),
    ),
    'node_group_management' => array(
        'label' => trans('nodes groups management'),
        'allow_regexp' => '^(nodegroup|nodegroup(add|edit|del|info|list|move))$',
        'allow_menu_items' => array('nodes'),
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
        'allow_menu_items' => array('VoIP'),
    ),
    'userpanel_management' => array(
        'label' => trans('Userpanel management'),
        'allow_regexp' => '^userpanel$',
        'allow_menu_items' => array('userpanel'),
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
        'allow_menu_items' => array('finances'),
    ),
    'transaction_logs' => array(
        'label' => trans('transaction logs'),
        'allow_regexp' => '^archive(info|view)$',
        'allow_menu_items' => array('log'),
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
        'deny_menu_items' => Permission::MENU_ALL,
    ),
);

$access = AccessRights::getInstance();
foreach ($access_table as $name => $permission) {
    $access->appendPermission(new Permission(
        $name,
        $permission['label'],
        isset($permission['allow_regexp']) ? $permission['allow_regexp'] : null,
        isset($permission['deny_regexp']) ? $permission['deny_regexp'] : null,
        isset($permission['allow_menu_items']) ? $permission['allow_menu_items'] : null,
        isset($permission['deny_menu_items']) ? $permission['deny_menu_items'] : null,
    ));
}
$access->appendPermission(
    new Permission(
        'documentation',
        null,
        null,
        null,
        array('documentation')
    ),
    'full_access'
);

// read user-defined access rights table
$custom_access_table = ConfigHelper::getConfig('phpui.custom_accesstable');
if (!is_null($custom_access_table)) {
    if (is_readable($custom_access_table) && ($custom_access_table[0] == DIRECTORY_SEPARATOR)) {
        @include_once($custom_access_table);
    } else if (is_readable(LIB_DIR . DIRECTORY_SEPARATOR . $custom_access_table)) {
        @include_once(LIB_DIR . DIRECTORY_SEPARATOR . $custom_access_table);
    }
}
