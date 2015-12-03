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

// that definitions should be included before LMS.class.php but after Smarty

// customers and contractor type
define('CTYPES_PRIVATE',0);
define('CTYPES_COMPANY',1);
define('CTYPES_CONTRACTOR',2);

$CTYPES = array(
    CTYPES_PRIVATE	=> trans('private person'),
    CTYPES_COMPANY	=> trans('legal entity'),
    CTYPES_CONTRACTOR	=> trans('contractor'),
);

// customer statuses
define('CSTATUS_INTERESTED', 1);
define('CSTATUS_WAITING', 2);
define('CSTATUS_CONNECTED', 3);
define('CSTATUS_DISCONNECTED', 4);

$CSTATUSES = array(
	CSTATUS_CONNECTED => array(
		'singularlabel' => trans('connected<!singular>'),
		'plurallabel' => trans('connected<!plural>'),
		'summarylabel' => trans('Connected:'),
		'img' => 'customer.gif',
		'alias' => 'connected'
	),
	CSTATUS_WAITING => array(
		'singularlabel' => trans('waiting'),
		'plurallabel' => trans('waiting'),
		'summarylabel' => trans('Waiting:'),
		'img' => 'wait.gif',
		'alias' => 'awaiting'
	),
	CSTATUS_INTERESTED => array(
		'singularlabel' => trans('interested<!singular>'),
		'plurallabel' => trans('interested<!plural>'),
		'summarylabel' => trans('Interested:'),
		'img' => 'unk.gif',
		'alias' => 'interested'
	),
	CSTATUS_DISCONNECTED => array(
		'singularlabel' => trans('disconnected<!singular>'),
		'plurallabel' => trans('disconnected<!plural>'),
		'summarylabel' => trans('Disconnected:<!summary>'),
		'img' => 'node_off.gif',
		'alias' => 'disconnected'
	),
);

// Helpdesk ticket status
define('RT_NEW', 0);
define('RT_OPEN', 1);
define('RT_RESOLVED', 2);
define('RT_DEAD', 3);

$RT_STATES = array(
    RT_NEW      => trans('new'),
    RT_OPEN     => trans('opened'),
    RT_RESOLVED => trans('resolved'),
    RT_DEAD     => trans('dead')
);

// Helpdesk cause type
define('RT_CAUSE_OTHER', 0);
define('RT_CAUSE_CUSTOMER', 1);
define('RT_CAUSE_COMPANY', 2);

$RT_CAUSE = array(
    RT_CAUSE_OTHER => trans("unknown/other"),
    RT_CAUSE_CUSTOMER => trans("customer's side"),
    RT_CAUSE_COMPANY => trans("company's side")
);

// Helpdesk note type
define('RTNOTE', 1);
define('RTNOTE_OWNER_CHANGE', 2);
define('RTNOTE_QUEUE_CHANGE', 4);
define('RTNOTE_STATE_CHANGE', 8);
define('RTNOTE_CAUSE_CHANGE', 16);
define('RTNOTE_CUSTOMER_CHANGE', 32);
define('RTNOTE_SUBJECT_CHANGE', 64);

// Messages status and type
define('MSG_NEW', 1);
define('MSG_SENT', 2);
define('MSG_ERROR', 3);
define('MSG_DRAFT', 4);
define('MSG_DELIVERED', 5);

define('MSG_MAIL', 1);
define('MSG_SMS', 2);
define('MSG_ANYSMS', 3);
define('MSG_WWW', 4);
define('MSG_USERPANEL', 5);
define('MSG_USERPANEL_URGENT', 6);

// Template types
define('TMPL_WARNING', 1);
define('TMPL_MAIL', 2);
define('TMPL_SMS', 3);
define('TMPL_WWW', 4);
define('TMPL_USERPANEL', 5);
define('TMPL_USERPANEL_URGENT', 6);

// Account types
define('ACCOUNT_SHELL', 1);
define('ACCOUNT_MAIL', 2);
define('ACCOUNT_WWW', 4);
define('ACCOUNT_FTP', 8);
define('ACCOUNT_SQL', 16);

// Document types
define('DOC_INVOICE', 1);
define('DOC_RECEIPT', 2);
define('DOC_CNOTE', 3);
//define('DOC_CMEMO', 4);
define('DOC_DNOTE', 5);
define('DOC_INVOICE_PRO',6);
define('DOC_INVOICE_PURCHASE',7);

define('DOC_CONTRACT', -1);
define('DOC_ANNEX', -2);
define('DOC_PROTOCOL', -3);
define('DOC_ORDER', -4);
define('DOC_SHEET', -5);
define('DOC_OTHER', -128);
define('DOC_BILLING',-10);

$DOCTYPES = array(
    DOC_BILLING         =>      trans('billing'),
    DOC_INVOICE         =>      trans('invoice'),
    DOC_INVOICE_PRO     =>      trans('pro-forma invoice'),
    DOC_INVOICE_PURCHASE =>     trans('purchase invoice'),
    DOC_RECEIPT         =>      trans('cash receipt'),
    DOC_CNOTE       =>  trans('credit note'), // faktura korygujaca
//    DOC_CMEMO     =>  trans('credit memo'), // nota korygujaca
    DOC_DNOTE       =>  trans('debit note'), // nota obciazeniowa/debetowa/odsetkowa
    DOC_CONTRACT        =>      trans('contract'),
    DOC_ANNEX       =>  trans('annex'),
    DOC_PROTOCOL        =>      trans('protocol'),
    DOC_ORDER       =>  trans('order'),
    DOC_SHEET       =>  trans('customer sheet'), // karta klienta
    -6  =>      trans('contract termination'),
    -7  =>      trans('payments book'), // ksiazeczka oplat
    -8  =>      trans('payment summons'), // wezwanie do zapłaty
    -9  =>      trans('payment pre-summons'), // przedsądowe wezw. do zapłaty
    DOC_OTHER       =>  trans('other'),
);

// Guarantee periods
$GUARANTEEPERIODS = array(
    -1 => trans('lifetime'),
    0  => trans('none'),
    12 => trans('$a months', 12),
    24 => trans('24 months', 24),
    36 => trans('$a months', 36),
    48 => trans('$a months', 48),
    60 => trans('$a months', 60)
);

// Internet Messengers
define('IM_GG', 0);
define('IM_YAHOO', 1);
define('IM_SKYPE', 2);

$MESSENGERS = array(
    IM_GG    => trans('Gadu-Gadu'),
    IM_YAHOO => trans('Yahoo'),
    IM_SKYPE => trans('Skype'),
);

define('DISPOSABLE', 0);
define('DAILY', 1);
define('WEEKLY', 2);
define('MONTHLY', 3);
define('QUARTERLY', 4);
define('YEARLY', 5);
define('CONTINUOUS', 6);
define('HALFYEARLY', 7);

// Accounting periods
$PERIODS = array(
    YEARLY	=>	trans('yearly'),
    HALFYEARLY  =>      trans('half-yearly'),
    QUARTERLY	=>	trans('quarterly'),
    MONTHLY	=>	trans('monthly'),
//    WEEKLY	=>	trans('weekly'),
//    DAILY	=>	trans('daily'),
    DISPOSABLE	=>	trans('disposable')
);

// Numbering periods
$NUM_PERIODS = array(
    CONTINUOUS	=>	trans('continuously'),
    YEARLY	=>	trans('yearly'),
    HALFYEARLY	=>	trans('half-yearly'),
    QUARTERLY	=>	trans('quarterly'),
    MONTHLY	=>	trans('monthly'),
//    WEEKLY	=>	trans('weekly'),
    DAILY	=>	trans('daily'),
);

// Tariff types
define('TARIFF_INTERNET', 1);
define('TARIFF_HOSTING', 2);
define('TARIFF_SERVICE', 3);
define('TARIFF_PHONE', 4);
define('TARIFF_TV', 5);
define('TARIFF_OTHER', -1);

$TARIFFTYPES = array(
	TARIFF_INTERNET	=> ConfigHelper::getConfig('tarifftypes.internet', trans('internet')),
	TARIFF_HOSTING	=> ConfigHelper::getConfig('tarifftypes.hosting', trans('hosting')),
	TARIFF_SERVICE	=> ConfigHelper::getConfig('tarifftypes.service', trans('service')),
	TARIFF_PHONE	=> ConfigHelper::getConfig('tarifftypes.phone', trans('phone')),
	TARIFF_TV	=> ConfigHelper::getConfig('tarifftypes.tv', trans('tv')),
	TARIFF_OTHER	=> ConfigHelper::getConfig('tarifftypes.other', trans('other')),
);

$PAYTYPES = array(
    1   => trans('cash'),
    2   => trans('transfer'),
    3   => trans('transfer/cash'),
    4   => trans('card'),
    5   => trans('compensation'),
    6   => trans('barter'),
    7   => trans('contract'),
    8   => trans('paid'),
);

// Contact types
define('CONTACT_MOBILE', 1);
define('CONTACT_FAX', 2);
define('CONTACT_LANDLINE', 4);
define('CONTACT_EMAIL', 8);
define('CONTACT_INVOICES', 16);
define('CONTACT_NOTIFICATIONS', 32);
define('CONTACT_DISABLED', 64);

$CONTACTTYPES = array(
    CONTACT_MOBILE          =>	trans('mobile'),
    CONTACT_FAX             =>	trans('fax'),
    CONTACT_INVOICES        =>	trans('Invoice'),
    CONTACT_DISABLED        =>	trans('disabled'),
    CONTACT_NOTIFICATIONS   =>	trans('Notification'),
);

define('DISCOUNT_PERCENTAGE', 1);
define('DISCOUNT_AMOUNT', 2);

$DISCOUNTTYPES = array(
	DISCOUNT_PERCENTAGE	=> '%',
	DISCOUNT_AMOUNT		=> trans('amount'),
);

define('DAY_MONDAY', 0);
define('DAY_TUESDAY', 1);
define('DAY_THURSDAY', 2);
define('DAY_WEDNESDAY', 3);
define('DAY_FRIDAY', 4);
define('DAY_SATURDAY', 5);
define('DAY_SUNDAY', 6);

$DAYS = array(
	DAY_MONDAY	=> trans('Mon'),
	DAY_TUESDAY	=> trans('Tue'),
	DAY_THURSDAY	=> trans('Thu'),
	DAY_WEDNESDAY	=> trans('Wed'),
	DAY_FRIDAY	=> trans('Fri'),
	DAY_SATURDAY	=> trans('Sat'),
	DAY_SUNDAY	=> trans('Sun'),
);

$LINKTYPES = array(
	0		=> trans('wire'),
	1		=> trans('wireless'),
	2		=> trans('fiber'),
);

$LINKTECHNOLOGIES = array(
	0 => array(
		1 => 'ADSL',
		2 => 'ADSL2',
		3 => 'ADSL2+',
		4 => 'VDSL',
		5 => 'VDSL2',
		10 => 'HDSL',
		11 => 'PDH',
		12 => 'POTS/ISDN',
		6 => '10 Mb/s Ethernet',
		7 => '100 Mb/s Fast Ethernet',
		8 => '1 Gigabit Ethernet',
		9 => '10 Gigabit Ethernet',
		50 => '(EURO)DOCSIS 1.x',
		51 => '(EURO)DOCSIS 2.x',
		52 => '(EURO)DOCSIS 3.x',
	),
	1 => array(
		100 => 'WiFi - 2,4 GHz',
		101 => 'WiFi - 5 GHz',
		102 => 'WiMAX',
		103 => 'LMDS',
		104 => 'radiolinia',
		105 => 'CDMA',
		106 => 'GPRS',
		107 => 'EDGE',
		108 => 'HSPA',
		109 => 'HSPA+',
		110 => 'DC-HSPA+',
		111 => 'MC-HSPA+',
		112 => 'LTE',
		113 => 'UMTS',
		114 => 'DMS',
	),
	2 => array(
		200 => 'CWDM',
		201 => 'DWDM',
		202 => 'SDH',
		203 => '10 Mb/s Ethernet',
		204 => '100 Mb/s Fast Ethernet',
		205 => '1 Gigabit Ethernet',
		206 => '10 Gigabit Ethernet',
		210 => '40 Gigabit Ethernet',
		207 => '100 Gigabit Ethernet',
		208 => 'EPON',
		209 => 'GPON',
		211 => 'ATM',
		212 => 'PDH',
		250 => '(EURO)DOCSIS 1.x',
		251 => '(EURO)DOCSIS 2.x',
		252 => '(EURO)DOCSIS 3.x',
	),
);

$LINKSPEEDS = array(
	10000		=> trans('10Mbit/s'),
	25000		=> trans('25Mbit/s'),
	54000		=> trans('54Mbit/s'),
	100000		=> trans('100Mbit/s'),
	200000		=> trans('200Mbit/s'),
	300000		=> trans('300Mbit/s'),
	1000000		=> trans('1Gbit/s'),
	10000000	=> trans('10Gbit/s'),
);

$BOROUGHTYPES = array(
	1 => trans('municipal commune'),
	2 => trans('rural commune'),
	3 => trans('municipal-rural commune'),
	4 => trans('city in the municipal-rural commune'),
	5 => trans('rural area to municipal-rural commune'),
	8 => trans('estate in Warsaw-Centre commune'),
	9 => trans('estate'),
);

$PASSWDEXPIRATIONS = array(
	0	=> trans('never expires'),
	7	=> trans('week'),
	14	=> trans('2 weeks'),
	21	=> trans('21 days'),
	31	=> trans('month'),
	62	=> trans('2 months'),
	93	=> trans('quarter'),
	183	=> trans('half year'),
	365	=> trans('year'),
);

$NETELEMENTSTATUSES = array(
	0	=> trans('existing'),
	1	=> trans('under construction'),
	2	=> trans('planned'),
);

$NETELEMENTTYPES = array(
	0	=> 'budynek biurowy',
	2	=> 'budynek mieszkalny',
	1	=> 'budynek przemysłowy',
	11	=> 'budynek usługowy',
	12	=> 'budynek użyteczności publicznej',
	3	=> 'obiekt sakralny',
	13	=> 'obiekt sieci elektroenergetycznej',
	5	=> 'wieża',
	4	=> 'maszt',
	10	=> 'komin',
	6	=> 'kontener',
	7	=> 'szafa uliczna',
	14	=> 'słup',
	8	=> 'skrzynka',
	9	=> 'studnia kablowa',
);

$NETELEMENTOWNERSHIPS = array(
	0	=> 'węzeł własny',
	1	=> 'węzeł współdzielony z innym podmiotem',
	2	=> 'węzeł obcy',
);

$USERPANEL_ID_TYPES = array(
	1	=> array(
		'label' => trans('Customer ID:'),
		'selection' => trans('Customer ID and PIN'),
	),
	2	=> array(
		'label' => trans('Phone number:'),
		'selection' => trans('Phone number and PIN'),
	),
	3	=> array(
		'label' => trans('Document number:'),
		'selection' => trans('Document number and PIN'),
	),
	4	=> array(
		'label' => trans('Customer e-mail:'),
		'selection' => trans('Customer e-mail and PIN'),
	),
);

define('EVENT_OTHER', 1);
define('EVENT_NETWORK', 2);
define('EVENT_SERVICE', 3);
define('EVENT_INSTALLATION', 4);
define('EVENT_MEETING', 5);

$EVENTTYPES = array(
	EVENT_SERVICE      => trans('service<!event>'),
	EVENT_INSTALLATION => trans('installation'),
	EVENT_NETWORK      => trans('network'),
	EVENT_MEETING      => trans('meeting'),
	EVENT_OTHER        => trans('other')
);

define('SESSIONTYPE_PPPOE', 1);
define('SESSIONTYPE_DHCP', 2);
define('SESSIONTYPE_EAP', 4);
define('SESSIONTYPE_WIFI', 8);
define('SESSIONTYPE_VOIP', 16);

$SESSIONTYPES = array(
	SESSIONTYPE_PPPOE => array(
		'label' => trans('PPPoE Client'),
		'tip' => 'Enable/disable PPPoE Server Client'
	),
	SESSIONTYPE_DHCP => array(
		'label' => trans('DHCP Client'),
		'tip' => 'Enable/disable DHCP Server Client'
	),
	SESSIONTYPE_EAP => array(
		'label' => trans('EAP Client'),
		'tip' => 'Enable/disable EAP Server Client'
	),
	SESSIONTYPE_WIFI => array(
		'label' => trans('WiFi AP Client'),
		'tip' => 'Enable/disable WiFi AP Client access'
	),
	SESSIONTYPE_VOIP => array(
		'label' => trans('VoIP Gateway'),
		'tip' => 'Enable/disable VoIP Gateway access'
	),
);

if(isset($SMARTY))
{
	$SMARTY->assign('_CTYPES',$CTYPES);
	$SMARTY->assign('_CSTATUSES', $CSTATUSES);
	$SMARTY->assign('_DOCTYPES', $DOCTYPES);
	$SMARTY->assign('_PERIODS', $PERIODS);
	$SMARTY->assign('_GUARANTEEPERIODS', $GUARANTEEPERIODS);
	$SMARTY->assign('_NUM_PERIODS', $NUM_PERIODS);
	$SMARTY->assign('_RT_STATES', $RT_STATES);
	$SMARTY->assign('_MESSENGERS', $MESSENGERS);
	$SMARTY->assign('_TARIFFTYPES', $TARIFFTYPES);
	$SMARTY->assign('_PAYTYPES', $PAYTYPES);
	$SMARTY->assign('_CONTACTTYPES', $CONTACTTYPES);
	$SMARTY->assign('_DISCOUNTTYPES', $DISCOUNTTYPES);
	$SMARTY->assign('_DAYS', $DAYS);
	$SMARTY->assign('_LINKTYPES', $LINKTYPES);
	$SMARTY->assign('_LINKTECHNOLOGIES', $LINKTECHNOLOGIES);
	$SMARTY->assign('_LINKSPEEDS', $LINKSPEEDS);
	$SMARTY->assign('_BOROUGHTYPES', $BOROUGHTYPES);
	$SMARTY->assign('_PASSWDEXPIRATIONS', $PASSWDEXPIRATIONS);
	$SMARTY->assign('_NETELEMENTSTATUSES', $NETELEMENTSTATUSES);
	$SMARTY->assign('_NETELEMENTTYPES', $NETELEMENTTYPES);
	$SMARTY->assign('_NETELEMENTOWNERSHIPS', $NETELEMENTOWNERSHIPS);
	$SMARTY->assign('_USERPANEL_ID_TYPES', $USERPANEL_ID_TYPES);
	$SMARTY->assign('_EVENTTYPES', $EVENTTYPES);
	$SMARTY->assign('_SESSIONTYPES', $SESSIONTYPES);
}

define('DEFAULT_NUMBER_TEMPLATE', '%N/LMS/%Y');

// Investment project types
define('INV_PROJECT_REGULAR', 0);
define('INV_PROJECT_SYSTEM', 1)

?>
