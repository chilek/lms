<?php

/*
 *  LMS version 1.11-git
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

global $LMS,$SMARTY,$SESSION,$DB;
$balance = $LMS->GetCustomerBalanceList($SESSION->id);
$userinfo = $LMS->GetCustomer($SESSION->id);
$assignments = $LMS->GetCustomerAssignments($SESSION->id);

if(isset($balance['docid']))
	foreach($balance['docid'] as $idx => $val)
	{
		if($balance['doctype'][$idx] == 1)
		{
			if($number = $LMS->docnumber($val))
				$balance['number'][$idx] = trans('Invoice No. $a', $number);
		}
	}

$SMARTY->assign('custom_content','');
$SMARTY->assign('userinfo', $userinfo);
$SMARTY->assign('balancelist', $balance);
$SMARTY->assign('assignments', $assignments);
$SMARTY->display('module:finances.html');

?>
