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

// Messages status and type
define('MSG_NEW', 1);
define('MSG_SENT', 2);
define('MSG_ERROR', 3);
define('MSG_DRAFT', 4);

define('MSG_MAIL', 1);
define('MSG_SMS', 2);
define('MSG_ANYSMS', 3);

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
define('DOC_OTHER', -10);

$DOCTYPES = array(
    DOC_INVOICE 	=>	trans('invoice'),
    DOC_INVOICE_PRO	=>	trans('pro-forma invoice'),
    DOC_INVOICE_PURCHASE =>	trans('purchase invoice'),
    DOC_RECEIPT 	=>	trans('cash receipt'),
    DOC_CNOTE	    =>	trans('credit note'), // faktura korygujaca
//    DOC_CMEMO	    =>	trans('credit memo'), // nota korygujaca
    DOC_DNOTE	    =>	trans('debit note'), // nota obciazeniowa/debetowa/odsetkowa
    DOC_CONTRACT	=>	trans('contract'),
    DOC_ANNEX	    =>	trans('annex'),
    DOC_PROTOCOL	=>	trans('protocol'),
    DOC_ORDER       =>	trans('order'),
    DOC_SHEET       =>	trans('customer sheet'), // karta klienta 
    -6  =>	trans('contract termination'),
    -7  =>	trans('payments book'), // ksiazeczka oplat
    -8  =>	trans('payment summons'), // wezwanie do zapłaty
    -9	=>	trans('payment pre-summons'), // przedsądowe wezw. do zapłaty
    DOC_OTHER       =>	trans('other'),
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
	TARIFF_INTERNET	=> isset($CONFIG['tarifftypes']['internet']) ? $CONFIG['tarifftypes']['internet'] : trans('internet'),
	TARIFF_HOSTING	=> isset($CONFIG['tarifftypes']['hosting']) ? $CONFIG['tarifftypes']['config'] : trans('hosting'),
	TARIFF_SERVICE	=> isset($CONFIG['tarifftypes']['service']) ? $CONFIG['tarifftypes']['service'] : trans('service'),
	TARIFF_PHONE	=> isset($CONFIG['tarifftypes']['phone']) ? $CONFIG['tarifftypes']['phone'] : trans('phone'),
	TARIFF_TV	=> isset($CONFIG['tarifftypes']['tv']) ? $CONFIG['tarifftypes']['tv'] : trans('tv'),
	TARIFF_OTHER	=> isset($CONFIG['tarifftypes']['other']) ? $CONFIG['tarifftypes']['other'] : trans('other'),
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

$CONTACTTYPES = array(
    CONTACT_MOBILE 	=>	trans('mobile'),
    CONTACT_FAX 	=>	trans('fax'),
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

if(isset($SMARTY))
{
	$SMARTY->assign('_CTYPES',$CTYPES);
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
	$SMARTY->assign('_LINKSPEEDS', $LINKSPEEDS);
	$SMARTY->assign('_BOROUGHTYPES', $BOROUGHTYPES);
	$SMARTY->assign('_PASSWDEXPIRATIONS', $PASSWDEXPIRATIONS);
}

define('DEFAULT_NUMBER_TEMPLATE', '%N/LMS/%Y');

?>
