<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

global $SMARTY;

//if (isset($_POST['document'])) {
///* short example of errors handling
//
//    $error['notes'] = 'Error';
//    $result = 'Error';
//    return;
//*/
//} else // AJAX request
//{
//    // Variables accessible in AJAX request
//    // $customer    - selected customer ID
//    // $JSResponse  - xajaxResponse object
//}

$SMARTY->assign('default_payment_aggregation', MONTHLY);
$SMARTY->assign('daily_payment_aggregation', DAILY);


$result = $SMARTY->fetch(DOC_DIR.'/templates/'.$engine['name'].'/plugin.html');
