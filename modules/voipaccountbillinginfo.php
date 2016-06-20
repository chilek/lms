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

if(!preg_match('/^[0-9]+$/', $_GET['id']))
	$SESSION->redirect('?m=voipaccountbillinglist');
else
	$billing_id = $_GET['id'];

$cdr_record = $DB->GetAll('SELECT
										id, caller, callee, call_start_time, time_start_to_end, time_answer_to_end, price, status, type, 
										callervoipaccountid, calleevoipaccountid, caller_flags, callee_flags, caller_prefix_group, callee_prefix_group
									FROM
										voip_cdr
									WHERE
										id = ?', array($billing_id));

$SMARTY->assign('cdr', $cdr_record[0]);
$SMARTY->display('voipaccount/voipaccountbillinginfo.html');

?>
