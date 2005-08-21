<?php

/*
 * LMS version 1.7-cvs
 *
 * (C) Copyright 2001-2005 LMS Developers
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
		array(
			'name' => trans('Administration'),
			'img' =>'users.gif',
			'link' =>'?m=welcome',
			'tip' => trans('System informations and management'),
			'accesskey' =>'i',
			'prio' =>'0',
			'submenu' => array(
				array(
					'name' => trans('Info'),
					'link' =>'?m=welcome',
					'tip' => trans('Basic system informations'),
				),
				array(
					'name' => trans('Users'),
					'link' =>'?m=userlist',
					'tip' => trans('User list'),
				),
				array(
					'name' => trans('New User'),
					'link' =>'?m=useradd',
					'tip' => trans('New User'),
				),
				array(
					'name' => trans('Backups'),
					'link' =>'?m=dblist',
					'tip' => trans('Allows you to manage database backups'),
				),
				array(
					'name' => trans('Copyrights'),
					'link' =>'?m=copyrights',
					'tip' => trans('Copyrights, authors, etc.'),
				),
			),
		),


		array(
			'name' => trans('Customers'),
			'img' =>'customer.gif',
			'link' =>'?m=customerlist',
			'tip' => trans('Customers: list, add, search, groups'),
			'accesskey' =>'u',
			'prio' =>'5',
			'submenu' => array(
				array(
					'name' => trans('List'),
					'link' =>'?m=customerlist',
					'tip' => trans('List of Customers'),
				),
				array(
					'name' => trans('New Customer'),
					'link' =>'?m=customeradd',
					'tip' => trans('Allows you to add new customer'),
				),
				array(
					'name' => trans('Search'),
					'link' =>'?m=customersearch',
					'tip' => trans('Allows you to find customer'),
				),
				array(
					'name' => trans('Groups'),
					'link' =>'?m=customergrouplist',
					'tip' => trans('List of Customers Groups'),
				),
				array(
					'name' => trans('New Group'),
					'link' =>'?m=customergroupadd',
					'tip' => trans('Allows you to add new group'),
				),
				array(
					'name' => trans('Messages'),
					'link' =>'?m=customerwarn',
					'tip' => trans('Allows you to send message to customers'),
				),
				array(
					'name' => trans('Printing'),
					'link' =>'?m=print&menu=customers',
					'tip' => trans('Printing'),
				),
			),		 
		),

		array(
			'name' => trans('Nodes'),
			'img' =>'node.gif',
			'link' =>'?m=nodelist',
			'tip' => trans('Nodes: list, searching, adding'),
			'accesskey' =>'k',
			'prio' =>'10',
			'submenu' => array(
				// node
				array(
					'name' => trans('List'),
					'link' => '?m=nodelist',
					'tip' => trans('List of nodes'),
				),
				// node
				array(
					'name' => trans('New Node'),
					'link' => '?m=nodeadd',
					'tip' => trans('Allows you to add new node'),
				),
				// node
				array(
					'name' => trans('Search'),
					'link' => '?m=nodesearch',
					'tip' => trans('Allows you to search node'),
				),
				// node
				array(
					'name' => trans('Messages'),
					'link' => '?m=nodewarn',
					'tip' => trans('Allows you to send message to nodes'),
				),
				// node
				array(
					'name' => trans('Printing'),
					'link' => '?m=print&menu=nodes',
					'tip' => trans('Allows you to print node list'),
				),
			),
		),

		array(
			'name' => trans('Net Devices'),
			'img' =>'netdev.gif',
			'link' =>'?m=netdevlist',
			'tip' => trans('Record of Network Devices'),
			'accesskey' =>'o',
			'prio' => '15',
			'submenu' => array(
				array(
					'name' => trans('List'),
					'link' => '?m=netdevlist',
					'tip' => trans('Network devices list'),
				),
				array(
					'name' => trans('New Device'),
					'link' => '?m=netdevadd',
					'tip' => trans('Add new device'),
				),
				array(
					'name' => trans('Map'),
					'link' => '?m=netdevmap',
					'tip' => trans('Network map display'),
				),
			),				
		),

		array(
			'name' => trans('IP Networks'),
			'img' =>'network.gif',
			'link' =>'?m=netlist',
			'tip' => trans('IP Address Classes Management'),
			'accesskey' =>'t',
			'prio' =>'20',
			'submenu' => array(
				array(
					'name' => trans('List'),
					'link' => '?m=netlist',
					'tip' => trans('List of IP classes'),
				),
				array(
					'name' => trans('New Network'),
					'link' => '?m=netadd',
					'tip' => trans('Add new address class'),
				),
			),
		),

		array(
			'name' => trans('Finances'),
			'img' =>'money.gif',
			'link' =>'?m=tarifflist',
			'tip' => trans('Subscriptions and Network Finances Management'),
			'accesskey' =>'f',
			'prio' =>'25',
			'submenu' => array(
				array(
					'name' => trans('Subscriptions List'),
					'link' => '?m=tarifflist',
					'tip' => trans('List of subscription fees'),
				),
				array(
					'name' => trans('New Subscription'),
					'link' => '?m=tariffadd',
					'tip' => trans('Add new subscription fee'),
				),
				array(
					'name' => trans('Payments List'),
					'link' => '?m=paymentlist',
					'tip' => trans('List of standing payments'),
				),
				array(
					'name' => trans('New Payment'),
					'link' => '?m=paymentadd',
					'tip' => trans('Add new standing payment'),
				),
				array(
					'name' => trans('Balance Sheet'),
					'link' => '?m=balancelist',
					'tip' => trans('Table of financial operations'),
				),
				array(
					'name' => trans('New Balance'),
					'link' => '?m=balancenew',
					'tip' => trans('Add new financial operation'),
				),
				array(
					'name' => trans('Invoices List'),
					'link' => '?m=invoicelist',
					'tip' => trans('List of invoices'),
				),
				array(
					'name' => trans('New Invoice'),
					'link' => '?m=invoicenew&action=init',
					'tip' => trans('Generate invoice'),
				),
				array(
					'name' => trans('Cash Receipts List'),
					'link' => '?m=receiptlist',
					'tip' => trans('List of cash receipts'),
				),
				array(
					'name' => trans('New Receipt'),
					'link' => '?m=receiptadd&action=init',
					'tip' => trans('Generate receipt'),
				),
				array(
					'name' => trans('Import'),
					'link' => '?m=cashimport',
					'tip' => trans('Import cash operations'),
				),
				array(
					'name' => trans('Printing'),
					'link' => '?m=print&menu=finances',
					'tip' => trans('Printing of financial statements'),
				),
			),
		),

		array(
			'name' => trans('Accounts'),
			'img' =>'account.gif',
			'link' =>'?m=accountlist',
			'tip' => trans('Accounts, Domains, Aliases Management'),
			'accesskey' =>'a',
			'prio' =>'30',
			'submenu' => array(
				array(
					'name' => trans('Accounts'),
					'link' => '?m=accountlist',
					'tip' => trans('Accounts in system'),
				),
				array(
					'name' => trans('New Account'),
					'link' => '?m=accountadd',
					'tip' => trans('Add new account'),
				),
				array(
					'name' => trans('Aliases'),
					'link' => '?m=aliaslist',
					'tip' => trans('Aliases of accounts'),
				),
				array(
					'name' => trans('New Alias'),
					'link' => '?m=aliasadd',
					'tip' => trans('Add new alias'),
				),
				array(
					'name' => trans('Domains'),
					'link' => '?m=domainlist',
					'tip' => trans('Domains'),
				),
				array(
						'name' => trans('New Domain'),
						'link' => '?m=domainadd',
						'tip' => trans('Add new domain'),
				),
			),					       
		),

		array(
			'name' => trans('Mailing'),
			'img' =>'mail.gif',
			'link' =>'?m=mailing',
			'tip' => trans('Serial Mail'),
			'accesskey' =>'m',
			'prio' =>'35',
/*			'submenu' => array(
				array(
					'name' => trans('Execute mailing'),
					'link' => '?m=mailing',
					'tip' => trans('Serial Mail'),
				),
			),*/
		),

		array(
			'name' => trans('Reload'),
			'img' =>'reload.gif',
			'link' =>'?m=reload',
			'tip' => trans(''),
			'accesskey' =>'r',
			'prio' =>'40',
			'submenu' => array(
				array(
					'name' => trans('Reload'),
					'link' => '?m=reload',
					'tip' => trans('Configuration Reload')
				),
				array(
					'name' => trans('Configuration'),
					'link' => '?m=daemonhostlist',
					'tip' => trans('Daemon(s) Configuration')
				)
			),
		),

		array(
			'name' => trans('Stats'),
			'img' =>'traffic.gif',
			'link' =>'?m=traffic',
			'tip' => trans('Statistics on Internet Link Usage'),
			'accesskey' =>'x',
			'prio' =>'45',
			'submenu' => array(
				array(
					'name' => trans('Filter'),
					'link' => '?m=traffic',
					'tip' => trans('User-defined stats'),
				),
				array(
					'name' => trans('Last Hour'),
					'link' => '?m=traffic&bar=hour',
					'tip' => trans('Last hour stats for all networks'),
				),
				array(
					'name' => trans('Last Day'),
					'link' => '?m=traffic&bar=day',
					'tip' => trans('Last day stats for all networks'),
				),
				array(
					'name' => trans('Last 30 Days'),
					'link' => '?m=traffic&bar=month',
					'tip' => trans('Last month stats for all networks'),
				),
				array(
					'name' => trans('Last Year'),
					'link' => '?m=traffic&bar=year',
					'tip' => trans('Last year stats for all networks'),
				),
				array(
					'name' => trans('Compacting'),
					'link' => '?m=trafficdbcompact',
					'tip' => trans('Compacting Database'),
				),
			),
		),

		array(
			'name' => trans('Helpdesk'),
			'img' =>'ticket.gif',
			'link' =>'?m=rtqueuelist',
			'tip' => trans('Requests Tracking'),
			'accesskey' =>'h',
			'prio' =>'50',
			'submenu' => array(
				array(
					'name' => trans('Queues List'),
					'link' => '?m=rtqueuelist',
					'tip' => trans('List of queues'),
				),
				array(
					'name' => trans('New Queue'),
					'link' => '?m=rtqueueadd',
					'tip' => trans('Add new queue'),
				),
				array(
					'name' => trans('Searching'),
					'link' => '?m=rtsearch',
					'tip' => trans('Tickets searching'),
				),
				array(
					'name' => trans('New Ticket'),
					'link' => '?m=rtticketadd',
					'tip' => trans('Add new ticket'),
				),
			),				     
		),

		array(
			'name' => trans('Timetable'),
			'img' =>'calendar.gif',
			'link' =>'?m=eventlist',
			'tip' => trans('Events Tracking'),
			'accesskey' =>'v',
			'prio' =>'55',
			'submenu' => array(
				array(
					'name' => trans('Timetable'),
					'link' => '?m=eventlist',
					'tip' => trans('Timetable'),
				),
				array(
					'name' => trans('New Event'),
					'link' => '?m=eventadd',
					'tip' => trans('New Event Addition'),
				),
				array(
					'name' => trans('Search'),
					'link' => '?m=eventsearch',
					'tip' => trans('Searching of Events in Timetable'),
				),
			),				
		),

		array(
			'name' => trans('Password'),
			'img' => 'pass.gif',
			'link' => '?m=chpasswd',
			'tip' => trans('Allows you to change your password'),
			'accesskey' => 'p',
			'prio' => '60',
		),

		array(
			'name' => trans('Configuration'),
			'img' =>'settings.gif',
			'link' =>'?m=configlist',
			'tip' => trans('System configuration'),
			'accesskey' =>'o',
			'prio' =>'65',
			'submenu' => array(
				array(
					'name' => trans('User Interface'),
					'link' =>'?m=configlist',
					'tip' => trans('Allows you to configure UI'),
				),
				array(
					'name' => trans('Tax Rates'),
					'link' => '?m=taxratelist',
					'tip' => trans('Tax Rates Definitions'),
				),
				array(
					'name' => trans('Numbering Plans'),
					'link' => '?m=numberplanlist',
					'tip' => trans('Numbering Plans Definitions'),
				),
			),
		),

		array(
			'name' => trans('Documentation'),
			'img' => 'doc.gif',
			'link' => (is_dir('doc/html/'.$LMS->lang) ? 'doc/html/'.$LMS->lang.'/' : 'doc/html/en/'),
			'tip' => trans('Documentation'),
			'accesskey' => 'h',
			'prio' => '70',
			'windowopen' => TRUE,
		)

	);

//if (isset($_CONFIG['userpanel_dir'])) 
{
    $menu[13][submenu][]=array(
			    'name' => trans('Userpanel'),
			    'link' => '?m=userpanel',
			    'tip' => trans('Userpanel configuration')
			);
}
//echo "<pre>";
//var_dump($menu[13]);
?>
