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

if (!preg_match('/^[0-9]+$/', $_GET['id'])) {
    $SESSION->redirect('?m=voipaccountbillinglist');
} else {
    $billing_id = $_GET['id'];
}

$cdr = $DB->GetRow('SELECT
						c.id, caller, callee, call_start_time, totaltime, billedtime, price, c.status, c.type, 
						callervoipaccountid, calleevoipaccountid, caller_flags, callee_flags, caller_prefix_group, callee_prefix_group,
						a1.ownerid AS callerownerid, ' . $DB->Concat('c1.lastname', "' '", 'c1.name') . ' AS callercustomername,
						a2.ownerid AS calleeownerid, ' . $DB->Concat('c2.lastname', "' '", 'c2.name') . ' AS calleecustomername
					FROM
						voip_cdr c
						LEFT JOIN voipaccounts a1 ON a1.id = c.callervoipaccountid
						LEFT JOIN customers c1 ON c1.id = a1.ownerid
						LEFT JOIN voipaccounts a2 ON a2.id = c.calleevoipaccountid
						LEFT JOIN customers c2 ON c2.id = a2.ownerid
					WHERE
						c.id = ?', array($billing_id));

$SMARTY->assign('cdr', $cdr);
$SMARTY->display('voipaccount/voipaccountbillinginfo.html');
