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

$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action == 'delete')
{
        $LMS->CustomerAssignmentDelete(
		array('customerid' => intval($_GET['id']),
			'customergroupid' => $_GET['customergroupid']));
}
elseif($action == 'add')
{
	$groupid = intval($_POST['customergroupid']);
	$uid = intval($_GET['id']);
	
        if ($LMS->CustomerGroupExists($groupid)
		&& !$LMS->CustomerassignmentExist($groupid, $uid)
		 && $LMS->CustomerExists($uid))
        {
	        $LMS->CustomerAssignmentAdd(
			array('customerid' => $uid, 'customergroupid' => $groupid));
	}
}
elseif(!empty($_POST['setwarnings']))
{
	$setwarnings = $_POST['setwarnings'];
	$oper = isset($_GET['oper']) ? $_GET['oper'] : '';
	$groupid = isset($setwarnings['customergroup']) ? $setwarnings['customergroup'] : 0;

	if ($oper != '' && $groupid != 0)
	{
		$customerassignmentdata['customergroupid'] = $groupid;
		foreach($setwarnings['mcustomerid'] as $uid)
		{
			$customerassignmentdata['customerid'] = $uid;
			switch ($oper)
			{
				case 'addtogroup':
					if (!$LMS->CustomerassignmentExist($groupid, $uid))
						$LMS->CustomerassignmentAdd($customerassignmentdata);
					break;
				case 'removefromgroup':
					$LMS->CustomerassignmentDelete($customerassignmentdata);
					break;
			}
		}
	}
}
elseif(!empty($_POST['customerassignments']) && $LMS->CustomerGroupExists($_GET['id']))
{
	$oper = $_POST['oper'];
	$customerassignments = $_POST['customerassignments'];
	
	if(isset($customerassignments['gmcustomerid']) && $oper=='0')
	{
		$assignment['customergroupid'] = $_GET['id'];
		foreach($customerassignments['gmcustomerid'] as $value)
		{
			$assignment['customerid'] = $value;
			$LMS->CustomerassignmentDelete($assignment);
		}
	}
	elseif (isset($customerassignments['mcustomerid']) && $oper=='1')
	{
		$assignment['customergroupid'] = $_GET['id'];
		foreach($customerassignments['mcustomerid'] as $value)
		{
			$assignment['customerid'] = $value;
			if(! $LMS->CustomerassignmentExist($assignment['customergroupid'],$value))
				$LMS->CustomerassignmentAdd($assignment);
		}
	}
	elseif ($oper=='2' || $oper=='3')
		$SESSION->redirect('?'.preg_replace('/&[a-z]*id=[0-9]+/i', '', $SESSION->get('backto')).'&id='.$_GET['id']
			.(isset($customerassignments['membersnetid']) && $customerassignments['membersnetid'] != '0' ? '&membersnetid='.$customerassignments['membersnetid'] : '')
			.(isset($customerassignments['othersnetid']) && $customerassignments['othersnetid'] != '0' ? '&othersnetid='.$customerassignments['othersnetid'] : ''));
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
