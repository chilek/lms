<?php

/*
 * LMS version 1.11-git
 *
 * (C) Copyright 2001-2015 LMS Developers
 *
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 * USA.
 *
 * $Id$
 */

$menu = array(
        'admin' => array(
            'name' => trans('Administration'),
            'css' => 'lms-ui-icon-administration',
            'link' =>'?m=welcome',
            'tip' => trans('System information and management'),
            'accesskey' =>'i',
            'prio' => 0,
            'submenu' => array(
                array(
                    'name' => trans('Info'),
                    'link' =>'?m=welcome',
                    'tip' => trans('Basic system information'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('Users'),
                    'link' =>'?m=userlist',
                    'tip' => trans('User list'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('New User'),
                    'link' =>'?m=useradd',
                    'tip' => trans('New User'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Backups'),
                    'link' =>'?m=dblist',
                    'tip' => trans('Allows you to manage database backups'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Copyrights'),
                    'link' =>'?m=copyrights',
                    'tip' => trans('Copyrights, authors, etc.'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('User groups'),
                    'link' =>'?m=usergrouplist',
                    'tip' => trans('User groups'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('New Group'),
                    'link' =>'?m=usergroupadd',
                    'tip' => trans('Allows you to add new group'),
                    'prio' => 70,
                ),
            ),
        ),


        'customers' => array(
            'name' => trans('Customers'),
            'css' => 'lms-ui-icon-customer',
            'link' =>'?m=customerlist',
            'tip' => trans('Customers Management'),
            'accesskey' =>'u',
            'prio' => 5,
            'submenu' => array(
                array(
                    'name' => trans('List'),
                    'link' =>'?m=customerlist',
                    'tip' => trans('List of Customers'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Customer'),
                    'link' =>'?m=customeradd',
                    'tip' => trans('Allows you to add new customer'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Search'),
                    'link' =>'?m=customersearch',
                    'tip' => trans('Allows you to find customer'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Groups'),
                    'link' =>'?m=customergrouplist',
                    'tip' => trans('List of Customers Groups'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('New Group'),
                    'link' =>'?m=customergroupadd',
                    'tip' => trans('Allows you to add new group'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('Notices'),
                    'link' =>'?m=customerwarn',
                    'tip' => trans('Allows you to send notices to customers'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Reports'),
                    'link' =>'?m=customerprint',
                    'tip' => trans('Lists and reports printing'),
                    'prio' => 70,
                ),
            ),
        ),

        'nodes' => array(
            'name' => trans('Nodes'),
            'css' => 'lms-ui-icon-node',
            'link' =>'?m=nodelist',
            'tip' => trans('Nodes Management'),
            'accesskey' =>'k',
            'prio' => 10,
            'submenu' => array(
                array(
                    'name' => trans('List'),
                    'link' => '?m=nodelist',
                    'tip' => trans('List of nodes'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Node'),
                    'link' => '?m=nodeadd',
                    'tip' => trans('Allows you to add new node'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=nodesearch',
                    'tip' => trans('Allows you to search node'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Groups'),
                    'link' =>'?m=nodegrouplist',
                    'tip' => trans('List of Nodes Groups'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('New Group'),
                    'link' =>'?m=nodegroupadd',
                    'tip' => trans('Allows you to add new group'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('Node Sessions'),
                    'link' => '?m=nodesessionlist',
                    'tip' => trans('Allows you to view node sessions'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Notices'),
                    'link' => '?m=nodewarn',
                    'tip' => trans('Allows you to send notices to customers'),
                    'prio' => 70,
                ),
                array(
                    'name' => trans('Reports'),
                    'link' => '?m=nodeprint',
                    'tip' => trans('Lists and reports printing'),
                    'prio' => 80,
                ),
            ),
        ),

        'VoIP' => array(
            'name' => trans('VoIP'),
            'css' => 'lms-ui-icon-phone',
            'tip' => trans('VoIP Management'),
            'accesskey' =>'v',
            'prio' => 11,
            'submenu' => array(
                array(
                    'name' => trans('New Account'),
                    'link' => '?m=voipaccountadd',
                    'tip' => trans('Allows you to add the new VoIP account'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('Accounts List'),
                    'link' => '?m=voipaccountlist',
                    'tip' => trans('List of Accounts'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Billing list'),
                    'link' => '?m=voipaccountbillinglist',
                    'tip' => trans('Allows you to view billing list'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Subscription List'),
                    'link' => '?m=tarifflist&t=' . SERVICE_PHONE,
                    'tip' => trans('Phone tariff list'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Tariff rules'),
                    'link' => '?m=voiptariffrules',
                    'tip' => trans('Promotions/special rules for tariffs'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('Price lists'),
                    'link' => '?m=voippricelist',
                    'tip' => trans('Edit price lists assigned to VoIP tariffs'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Pool numbers'),
                    'link' => '?m=voippoolnumberlist',
                    'tip' => trans('Number pools management'),
                    'prio' => 70,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=voipaccountsearch',
                    'tip' => trans('Allows you to search VoIP account'),
                    'prio' => 80,
                ),
            ),
        ),

        'netdevices' => array(
            'name' => trans('Net Devices'),
            'css' => 'lms-ui-icon-netdev',
            'link' =>'?m=netdevlist',
            'tip' => trans('Network Devices Management'),
            'accesskey' =>'o',
            'prio' => 15,
            'submenu' => array(
                array(
                    'name' => trans('List'),
                    'link' => '?m=netdevlist',
                    'tip' => trans('Network devices list'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Device'),
                    'link' => '?m=netdevadd',
                    'tip' => trans('Add new device'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=netdevsearch',
                    'tip' => trans('Allows you to search device'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Nodes list'),
                    'link' => '?m=netnodelist',
                    'tip' => trans('Network device nodes list'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('New node'),
                    'link' => '?m=netnodeadd',
                    'tip' => trans('Add new network device node'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('Producers and models'),
                    'link' => '?m=netdevmodels',
                    'tip' => trans('Network device producers and models management'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Map'),
                    'link' => '?m=netdevmap',
                    'tip' => trans('Network map display'),
                    'prio' => 70,
                ),
                array(
                    'name' => trans('Reports'),
                    'link' => '?m=netdevprint',
                    'tip' => trans('Lists and reports printing'),
                    'prio' => 80,
                ),
                ),
            ),

        'networks' => array(
            'name' => trans('IP Networks'),
            'css' => 'lms-ui-icon-ipnetwork',
            'link' =>'?m=netlist',
            'tip' => trans('IP Address Pools Management'),
            'accesskey' =>'t',
            'prio' => 20,
            'submenu' => array(
                array(
                    'name' => trans('List'),
                    'link' => '?m=netlist',
                    'tip' => trans('List of IP pools'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Network'),
                    'link' => '?m=netadd',
                    'tip' => trans('Add new address pool'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=netsearch&searchform=1',
                    'tip' => trans('Allows you to search for IP address pools'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Network usage'),
                    'link' => '?m=netusage',
                    'tip' => trans('Allows you to display IP address usage from whole network.'),
                    'prio' => 40,
                ),
            ),
        ),

        'finances' => array(
            'name' => trans('Finances'),
            'css' => 'lms-ui-icon-finances',
            'link' =>'?m=tarifflist',
            'tip' => trans('Subscriptions and Network Finances Management'),
            'accesskey' =>'f',
            'prio' => 25,
            'submenu' => array(
                array(
                    'name' => trans('Subscriptions List'),
                    'link' => '?m=tarifflist',
                    'tip' => trans('List of subscription fees'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Subscription'),
                    'link' => '?m=tariffadd',
                    'tip' => trans('Add new subscription fee'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Payments List'),
                    'link' => '?m=paymentlist',
                    'tip' => trans('List of standing payments'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('New Payment'),
                    'link' => '?m=paymentadd',
                    'tip' => trans('Add new standing payment'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Balance Sheet'),
                    'link' => '?m=balancelist',
                    'tip' => trans('Table of financial operations'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('New Balance'),
                    'link' => '?m=balancenew',
                    'tip' => trans('Add new financial operation'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Invoices List'),
                    'link' => '?m=invoicelist',
                    'tip' => trans('List of invoices'),
                    'prio' => 70,
                ),
                array(
                    'name' => trans('New Invoice'),
                    'link' => '?m=invoicenew&action=init',
                    'tip' => trans('Generate invoice'),
                    'prio' => 80,
                ),
                array(
                    'name' => trans('Pro Forma Invoice List'),
                    'link' => '?m=invoicelist&proforma=1',
                    'tip' => trans('List of pro forma invoices'),
                    'prio' => 90,
                ),
                array(
                    'name' => trans('New Pro Forma Invoice'),
                    'link' => '?m=invoicenew&action=init&proforma=1',
                    'tip' => trans('Generate pro forma invoice'),
                    'prio' => 100,
                ),
                array(
                    'name' => trans('Debit Notes List'),
                    'link' => '?m=notelist',
                    'tip' => trans('List of debit notes'),
                    'prio' => 110,
                ),
                array(
                    'name' => trans('New Debit Note'),
                    'link' => '?m=noteadd&action=init',
                    'tip' => trans('Generate debit note'),
                    'prio' => 120,
                ),
                array(
                    'name' => trans('Cash Registry'),
                    'link' => '?m=cashreglist',
                    'tip' => trans('List of cash registries'),
                    'prio' => 130,
                ),
                array(
                    'name' => trans('New Cash Receipt'),
                    'link' => '?m=receiptadd&action=init',
                    'tip' => trans('Generate cash receipt'),
                    'prio' => 140,
                ),
                array(
                    'name' => trans('Import'),
                    'link' => '?m=cashimport',
                    'tip' => trans('Import cash operations'),
                    'prio' => 150,
                ),
                array(
                    'name' => trans('Export'),
                    'link' => '?m=export',
                    'tip' => trans('Financial data export to external systems'),
                    'prio' => 160,
                ),
                array(
                    'name' => trans('Reports'),
                    'link' => '?m=print',
                    'tip' => trans('Lists and reports printing'),
                    'prio' => 170,
                ),
                array(
                    'name' => trans('New tag'),
                    'link' => '?m=tarifftagadd',
                    'tip' => trans('Allows you to add new tag'),
                    'prio' => 140,
                ),
                array(
                    'name' => trans('Tags list'),
                    'link' => '?m=tarifftaglist',
                    'tip' => trans('Tags list'),
                    'prio' => 150,
                ),
            ),
        ),

        'documents' => array(
            'name' => trans('Documents'),
            'css' => 'lms-ui-icon-document',
            'link' =>'?m=documentlist',
            'tip' => trans('Documents Management'),
            'accesskey' => '',
            'prio' => 26,
            'submenu' => array(
                array(
                    'name' => trans('List'),
                    'link' => '?m=documentlist&init=1',
                    'tip' => trans('List of documents'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Document'),
                    'link' => '?m=documentadd',
                    'tip' => trans('Allows you to add new document'),
                    'prio' => 20,
                ),
//              array(
//                  'name' => trans('Search'),
//                  'link' => '?m=documentsearch',
//                  'tip' => trans('Allows you to search documents'),
//                  'prio' => 30,
//              ),
                array(
                    'name' => trans('Generator'),
                    'link' =>'?m=documentgen',
                    'tip' => trans('Documents mass creation'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Access rights'),
                    'link' => '?m=documenttypes',
                    'tip' => trans('Users access rights to documents by type'),
                    'prio' => 50,
                ),
            ),
        ),

        'hosting' => array(
            'name' => trans('Hosting'),
            'css' => 'lms-ui-icon-hosting',
            'link' =>'?m=accountlist',
            'tip' => trans('Hosting Services Management'),
            'accesskey' =>'a',
            'prio' => 30,
            'submenu' => array(
                array(
                    'name' => trans('Accounts'),
                    'link' => '?m=accountlist',
                    'tip' => trans('List of accounts'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Account'),
                    'link' => '?m=accountadd',
                    'tip' => trans('Add new account'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Aliases'),
                    'link' => '?m=aliaslist',
                    'tip' => trans('List of aliases'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('New Alias'),
                    'link' => '?m=aliasadd',
                    'tip' => trans('Add new alias'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Domains'),
                    'link' => '?m=domainlist',
                    'tip' => trans('List of domains'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('New Domain'),
                    'link' => '?m=domainadd',
                    'tip' => trans('Add new domain'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=accountsearch',
                    'tip' => trans('Allows you to search for account, alias, domain'),
                    'prio' => 70,
                ),
            ),
        ),

        'messages' => array(
            'name' => trans('Messages'),
            'css' => 'lms-ui-icon-message',
            'link' =>'?m=messageadd',
            'tip' => trans('Customers Messaging'),
            'accesskey' =>'m',
            'prio' => 35,
            'submenu' => array(
                array(
                    'name' => trans('List'),
                    'link' => '?m=messagelist',
                    'tip' => trans('List of sent messages'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Message'),
                    'link' => '?m=messageadd',
                    'tip' => trans('Allows you to send messages to customers'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('<!message>Templates'),
                    'link' => '?m=messagetemplatelist',
                    'tip' => trans('Message template list'),
                    'prio' => 30,
                )
            ),
        ),

        'reload' => array(
            'name' => trans('Reload'),
            'css' => 'lms-ui-icon-reload',
            'link' =>'?m=reload',
            'tip' => trans(''),
            'accesskey' =>'r',
            'prio' => 40,
        ),

        'stats' => array(
            'name' => trans('Stats'),
            'css' => 'lms-ui-icon-stats',
            'link' =>'?m=traffic',
            'tip' => trans('Statistics of Internet Link Usage'),
            'accesskey' =>'x',
            'prio' => 45,
            'submenu' => array(
                array(
                    'name' => trans('Filter'),
                    'link' => '?m=traffic',
                    'tip' => trans('User-defined stats'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('Last Hour'),
                    'link' => '?m=traffic&bar=hour',
                    'tip' => trans('Last hour stats for all networks'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Last Day'),
                    'link' => '?m=traffic&bar=day',
                    'tip' => trans('Last day stats for all networks'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('Last 30 Days'),
                    'link' => '?m=traffic&bar=month',
                    'tip' => trans('Last month stats for all networks'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Last year'),
                    'link' => '?m=traffic&bar=year',
                    'tip' => trans('Last year stats for all networks'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('Compacting'),
                    'link' => '?m=trafficdbcompact',
                    'tip' => trans('Compacting Database'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Reports'),
                    'link' => '?m=trafficprint',
                    'tip' => trans('Lists and reports printing'),
                    'prio' => 70,
                ),
            ),
        ),

        'helpdesk' => array(
            'name' => trans('Helpdesk'),
            'css' => 'lms-ui-icon-helpdesk',
            'link' =>'?m=rtqueuelist',
            'tip' => trans('Requests Tracking'),
            'accesskey' =>'h',
            'prio' => 50,
            'submenu' => array(
                array(
                    'name' => trans('Queues List'),
                    'link' => '?m=rtqueuelist',
                    'tip' => trans('List of queues'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('New Queue'),
                    'link' => '?m=rtqueueadd',
                    'tip' => trans('Add new queue'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Categories List'),
                    'link' => '?m=rtcategorylist',
                    'tip' => trans('List of categories'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('New Category'),
                    'link' => '?m=rtcategoryadd',
                    'tip' => trans('Add new category'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=rtsearch',
                    'tip' => trans('Tickets searching'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('New Ticket'),
                    'link' => '?m=rtticketadd',
                    'tip' => trans('Add new ticket'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Reports'),
                    'link' => '?m=rtprint',
                    'tip' => trans('Lists and reports printing'),
                    'prio' => 70,
                ),
            ),
        ),

        'timetable' => array(
            'name' => trans('Timetable'),
            'css' => 'lms-ui-icon-timetable',
            'link' =>'?m=eventlist',
            'tip' => trans('Events Tracking'),
            'accesskey' =>'v',
            'prio' => 55,
            'submenu' => array(
                array(
                    'name' => trans('Timetable'),
                    'link' => '?m=eventlist',
                    'tip' => trans('Timetable'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('Schedule'),
                    'link' => '?m=schedule',
                    'tip' => trans('Schedule'),
                    'prio' => 11,
                ),
                array(
                    'name' => trans('New Event'),
                    'link' => '?m=eventadd',
                    'tip' => trans('New Event Addition'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Search'),
                    'link' => '?m=eventsearch',
                    'tip' => trans('Searching of Events in Timetable'),
                    'prio' => 30,
                ),
            ),
        ),

        'auth' => array(
            'name' => trans('Authentication'),
            'css' => 'lms-ui-icon-password',
            'link' => '?m=chpasswd',
            'tip' => trans('Authentication management'),
            'accesskey' => '',
            'prio' => 63,
            'submenu' => array(
                array(
                    'name' => trans('Password'),
                    'link' => '?m=chpasswd',
                    'tip' => trans('Allows you to change your password'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('Settings'),
                    'link' => '?m=twofactorauthinfo',
                    'tip' => trans('Allows you to view current two factor authentication settings'),
                    'prio' => 20,
                ),
            ),
        ),

        'config' => array(
            'name' => trans('Configuration'),
            'css' => 'lms-ui-icon-configuration',
            'link' =>'?m=configlist',
            'tip' => trans('System Configuration'),
            'accesskey' =>'o',
            'prio' => 60,
            'submenu' => array(
                array(
                    'name' => trans('User Interface'),
                    'link' =>'?m=configlist',
                    'tip' => trans('Allows you to configure UI'),
                    'prio' => 10,
                ),
                array(
                    'name' => trans('Tax Rates'),
                    'link' => '?m=taxratelist',
                    'tip' => trans('Tax Rates Definitions'),
                    'prio' => 20,
                ),
                array(
                    'name' => trans('Numbering Plans'),
                    'link' => '?m=numberplanlist',
                    'tip' => trans('Numbering Plans Definitions'),
                    'prio' => 30,
                ),
                array(
                    'name' => trans('States'),
                    'link' => '?m=statelist',
                    'tip' => trans('Country States Definitions'),
                    'prio' => 40,
                ),
                array(
                    'name' => trans('Divisions'),
                    'link' => '?m=divisionlist',
                    'tip' => trans('Company Divisions Definitions'),
                    'prio' => 50,
                ),
                array(
                    'name' => trans('Hosts'),
                    'link' => '?m=hostlist',
                    'tip' => trans('List of Hosts'),
                    'prio' => 60,
                ),
                array(
                    'name' => trans('Daemon'),
                    'link' => '?m=daemoninstancelist',
                    'tip' => trans('Daemon(s) Configuration'),
                    'prio' => 70,
                ),
                array(
                    'name' => trans('Import Sources'),
                    'link' => '?m=cashsourcelist',
                    'tip' => trans('List of Cash Import Sources'),
                    'prio' => 80,
                ),
                array(
                    'name' => trans('Promotions'),
                    'link' => '?m=promotionlist',
                    'tip' => trans('List of promotions'),
                    'prio' => 90,
                ),
                array(
                    'name' => trans('Plugins'),
                    'link' => '?m=pluginlist',
                    'tip' => trans('Plugin Management'),
                    'prio' => 100,
                ),
                array(
                    'name' => trans('Investment projects'),
                    'link' => '?m=invprojectlist',
                    'tip' => trans('Investment projects Management'),
                    'prio' => 110,
                ),
            ),
        ),

        'documentation' => array(
            'name' => trans('Documentation'),
            'css' => 'lms-ui-icon-documentation',
            'link' => (is_dir('doc' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . $LMS->ui_lang)
                ? 'doc' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . $LMS->ui_lang . DIRECTORY_SEPARATOR
                : 'doc' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR),
            'tip' => trans('Documentation'),
            'accesskey' => 'h',
            'prio' => 70,
            'windowopen' => true,
        ),

    );

// menu item for EtherWerX STM channels management
if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.ewx_support', false))) {
    $menu['netdevices']['submenu'][] = array(
        'name' => trans('Channels List'),
        'link' => '?m=ewxchlist',
        'tip' => trans('List of STM channels'),
        'prio' => 80,
    );
    $menu['netdevices']['submenu'][] = array(
        'name' => trans('New Channel'),
        'link' => '?m=ewxchadd',
        'tip' => trans('Add new STM channel'),
        'prio' => 81,
    );
}

if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.logging', false))) {
    $menu['log'] = array(
        'name' => trans('Transaction Log'),
        'css' => 'lms-ui-icon-archiveview',
        'link' => '?m=archiveview',
        'tip' => trans('Transaction Log Management'),
        'accesskey' => 't',
        'prio' => 3,
        'submenu' => array(
            array(
                'name' => trans('View'),
                'link' =>'?m=archiveview',
                'tip' => trans('Allows you to view transaction log'),
                'prio' => 10,
            ),
        ),
    );
}

// Adding Userpanel menu items
$userpanel_dir = ConfigHelper::getConfig('directories.userpanel_dir');
if (!empty($userpanel_dir)) {
        // be sure that Userpanel exists
    if (file_exists($userpanel_dir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'LMS.menu.php')) {
        require_once($userpanel_dir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'LMS.menu.php');
    }
}

// Adding user-defined menu items
$custom_menu = ConfigHelper::getConfig('phpui.custom_menu');
if (!empty($custom_menu)) {
        // be sure that file exists
    if (file_exists($custom_menu)) {
        require_once($custom_menu);
    }
}

/* Example for custom_menu file
<?php
    $menu['config']['submenu'][] = array(
        'name' => 'My config',
        'link' => '?m=myfile',
        'tip' => 'My Configuration',
        'prio' => 35,
    )
?>
*/

if (!function_exists('menu_cmp')) {
    function menu_cmp($a, $b)
    {
        if (!isset($a['prio'])) {
            $a['prio'] = 0;
        }
        if (!isset($b['prio'])) {
            $b['prio'] = 9999;
        }

        if ($a['prio'] == $b['prio']) {
            return 0;
        }
        return ($a['prio'] < $b['prio']) ? -1 : 1;
    }
}

foreach ($menu as $idx => $item) {
    if (isset($item['submenu'])) {
        uasort($menu[$idx]['submenu'], 'menu_cmp');
    }
}

uasort($menu, 'menu_cmp');
