<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
define('DOC_CONTRACT', -1);
define('DOC_ANNEX', -2);
define('DOC_PROTOCOL', -3);
define('DOC_ORDER', -4);
define('DOC_SHEET', -5);
define('DOC_OTHER', -10);

$DOCTYPES = array(
    DOC_INVOICE 	=>	trans('invoice'),
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
define('TARIFF_OTHER', -1);

$TARIFFTYPES = array(
    TARIFF_INTERNET => trans('internet'),
    TARIFF_HOSTING 	=> trans('hosting'),
    TARIFF_SERVICE 	=> trans('service'),
    TARIFF_OTHER	=> trans('other'),
);

$PAYTYPES = array(
    1   => trans('cash'),
    2   => trans('transfer'),
    3   => trans('transfer/cash'),
    4   => trans('card'),
    5   => trans('compensation'),
    6   => trans('barter'),
    7   => trans('contract'),
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

if(isset($SMARTY))
{
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
}

define('DEFAULT_NUMBER_TEMPLATE', '%N/LMS/%Y');

?>
