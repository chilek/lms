<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

if(isset($_POST['netdev']))
{
	$netdevdata = $_POST['netdev'];

	if($netdevdata['ports'] == '')
		$netdevdata['ports'] = 0;
		
	if($netdevdata['name'] == '')
		$error['name'] = trans('Device name is required!');
	elseif(strlen($netdevdata['name'])>32)
		$error['name'] = trans('Device name is too long (max.32 characters)!');

	// date format 'yyyy/mm/dd'
	// FIXME: check if purchasedate isn't in the future
	$netdevdata['purchasetime'] = NULL;
	if($netdevdata['purchasedate'] != '') 
	{
		if(!ereg('^[0-9]{4}/[0-9]{2}/[0-9]{2}$', $netdevdata['purchasedate']))
		{
			$error['purchasedate'] = trans('Invalid date format!');
		}
		else
		{
			$date = explode('/', $netdevdata['purchasedate']);
			if(checkdate($date[1], $date[2], (int)$date[0]))
				$netdevdata['purchasetime'] = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
			else
				$error['purchasedate'] = trans('Invalid date format!');
		}
	}

	if($netdevdata['guaranteeperiod'] != 0 && $netdevdata['purchasetime'] == NULL)
	{
		$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
	}

        if(!$error)
        {
		if($netdevdata['guaranteeperiod'] == -1)
			$netdevdata['guaranteeperiod'] = NULL;
		
		$netdevid = $LMS->NetDevAdd($netdevdata);
		$SESSION->redirect('?m=netdevinfo&id='.$netdevid);
        }
	
	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdev', $netdevdata);
}
		
$layout['pagetitle'] = trans('New Device');

$SMARTY->display('netdevadd.html');

?>
