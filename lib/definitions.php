<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
define('RT_NEW', trans('new'));
define('RT_OPEN', trans('opened'));
define('RT_RESOLVED', trans('resolved'));
define('RT_DEAD', trans('dead'));

// Account types
define('ACCOUNT_SHELL', 1);
define('ACCOUNT_MAIL', 2);
define('ACCOUNT_WWW', 4);
define('ACCOUNT_FTP', 8);

// Document types
$DOCTYPES = array(
    1 	=>	trans('invoice'),
    2 	=>	trans('cash receipt'),
    3	=>	trans('correction invoice'),
    -1	=>	trans('contract'),
    -2	=>	trans('annex'),
    -3	=>	trans('protocol'),
    -10 =>	trans('other')
);

define('DOC_INVOICE', 1);
define('DOC_RECEIPT', 2);
define('DOC_CINVOICE', 3);
define('DOC_CONTRACT', -1);
define('DOC_ANNEX', -2);
define('DOC_PROTOCOL', -3);
define('DOC_OTHER', -10);

// Accounting or numbering periods
$PERIODS = array(
    3	=>	trans('monthly'), // first will be default in UI
    5	=>	trans('yearly'),
    4	=>	trans('quarterly'),
    2	=>	trans('weekly'),
    1	=>	trans('daily')
);

define('DAILY', 1);
define('WEEKLY', 2);
define('MONTHLY', 3);
define('QUARTERLY', 4);
define('YEARLY', 5);

define('DEFAULT_NUMBER_TEMPLATE', '%N/LMS/%Y');

$SMARTY->assign('_DOCTYPES', $DOCTYPES);
$SMARTY->assign('_PERIODS', $PERIODS);

?>
