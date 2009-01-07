<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

$RT_STATES = array(RT_NEW => trans('new'), RT_OPEN => trans('opened'), RT_RESOLVED => trans('resolved'), RT_DEAD => trans('dead'));

// Account types
define('ACCOUNT_SHELL', 1);
define('ACCOUNT_MAIL', 2);
define('ACCOUNT_WWW', 4);
define('ACCOUNT_FTP', 8);
define('ACCOUNT_SQL', 16);

// Document types
$DOCTYPES = array(
    1 	=>	trans('invoice'),
    2 	=>	trans('cash receipt'),
    3	=>	trans('credit note'), // faktura korygujaca
//    4	=>	trans('credit memo'), // nota korygujaca
    -1	=>	trans('contract'),
    -2	=>	trans('annex'),
    -3	=>	trans('protocol'),
    -4  =>	trans('order'), 
    -5  =>	trans('customer sheet'), // karta klienta 
    -6  =>	trans('contract termination'),  
    -7  =>	trans('payments book'), // ksiazeczka oplat
    -10 =>	trans('other')
);

define('DOC_INVOICE', 1);
define('DOC_RECEIPT', 2);
define('DOC_CNOTE', 3);
//define('DOC_CMEMO', 4);
define('DOC_CONTRACT', -1);
define('DOC_ANNEX', -2);
define('DOC_PROTOCOL', -3);
define('DOC_ORDER', -4);
define('DOC_SHEET', -5);
define('DOC_OTHER', -10);

// Guarantee periods
$GUARANTEEPERIODS = array(
-1 => trans('lifetime'),
 0 => trans('none'),
12 => trans('$0 months', 12), 
24 => trans('24 months', 24), 
36 => trans('$0 months', 36), 
48 => trans('$0 months', 48), 
60 => trans('$0 months', 60)
);

// Internet Messengers
define('IM_GG', 0);
define('IM_YAHOO', 1);
define('IM_SKYPE', 2);

$MESSENGERS = array(
    IM_GG => trans('Gadu-Gadu'),
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
    WEEKLY	=>	trans('weekly'),
    DAILY	=>	trans('daily'),
    DISPOSABLE	=>	trans('disposable')
);

// Numbering periods
$NUM_PERIODS = array(
    CONTINUOUS	=>	trans('continuously'),
    YEARLY	=>	trans('yearly'),
    HALFYEARLY	=>	trans('half-yearly'),
    QUARTERLY	=>	trans('quarterly'),
    MONTHLY	=>	trans('monthly'),
    WEEKLY	=>	trans('weekly'),
    DAILY	=>	trans('daily'),
);


// Tariff types
$TARIFFTYPES = array(
    1 	=>	trans('internet'),
    2 	=>	trans('hosting'),
    3 	=>	trans('service'),
    -1	=>	trans('other'),
);

define('TARIFF_INTERNET', 1);
define('TARIFF_HOSTING', 2);
define('TARIFF_SERVICE', 3);
define('TARIFF_OTHER', -1);

define('DEFAULT_NUMBER_TEMPLATE', '%N/LMS/%Y');

if(isset($SMARTY))
{
	$SMARTY->assign('_DOCTYPES', $DOCTYPES);
	$SMARTY->assign('_PERIODS', $PERIODS);
	$SMARTY->assign('_GUARANTEEPERIODS', $GUARANTEEPERIODS);
	$SMARTY->assign('_NUM_PERIODS', $NUM_PERIODS);
	$SMARTY->assign('_RT_STATES', $RT_STATES);
	$SMARTY->assign('_MESSENGERS', $MESSENGERS);
	$SMARTY->assign('_TARIFFTYPES', $TARIFFTYPES);
}

?>
