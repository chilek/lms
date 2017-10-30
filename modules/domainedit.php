<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

function GetDomainIdByName($name)
{
	global $DB;
	return $DB->GetOne('SELECT id FROM domains WHERE name = ?', array($name));
}

function DomainExists($id)
{
	global $DB;
	return ($DB->GetOne('SELECT id FROM domains WHERE id = ?', array($id)) ? TRUE : FALSE);
}

$id = $_GET['id'];

if($id && !DomainExists($id))
{
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$domain = $DB->GetRow('SELECT id, name, ownerid, description, master, last_check, type, notified_serial, account, mxbackup
	FROM domains WHERE id = ?', array($id));

$layout['pagetitle'] = trans('Domain Edit: $a', $domain['name']);

if(isset($_POST['domain']))
{
	$olddomain = $domain['name'];
	$oldowner = $domain['ownerid'];
	
	$domain = $_POST['domain'];
	$domain['name'] = trim($domain['name']);
	$domain['description'] = trim($domain['description']);
	$domain['id'] = $id;
	
	if($domain['name']=='' && $domain['description']=='')
	{
		$SESSION->redirect('?'.$SESSION->get('backto'));
	}
	
        if($domain['type'] == 'SLAVE')
        {
    		if (!check_ip($domain['master']))
    			$error['master'] = trans('IP address of master NS is required!');
        }
        else
    		$domain['master'] = '';
	
	if($domain['name'] == '')
		$error['name'] = trans('Domain name is required!');
	elseif(!preg_match('/^[a-z0-9._-]+$/', $domain['name']))
	        $error['name'] = trans('Domain name contains forbidden characters!');
	elseif($olddomain != $domain['name'] && GetDomainIdByName($domain['name']))
		$error['name'] = trans('Domain with specified name exists!');

	if($domain['ownerid'] && $domain['ownerid'] != $oldowner)
        {
                $limits = $LMS->GetHostingLimits($domain['ownerid']);
        
		if($limits['domain_limit'] !== NULL)
                {
			if($limits['domain_limit'] > 0)
			        $cnt = $DB->GetOne('SELECT COUNT(*) FROM domains WHERE ownerid = ?',
		        		array($domainadd['ownerid']));
		
			if($limits['domain_limit'] == 0 || $limits['domain_limit'] <= $cnt)
			        $error['ownerid'] = trans('Exceeded domains limit of selected customer ($a)!', $limits['domain_limit']);
		}
	}

	if(!$error)
	{
		$DB->Execute('UPDATE domains SET name = ?, ownerid = ?, description = ?,
			master = ?, last_check = ?, type = ?, notified_serial = ?,
			account = ?, mxbackup = ? WHERE id = ?', 
			array(	$domain['name'],
				empty($domain['ownerid']) ? null : $domain['ownerid'],
				$domain['description'],
				$domain['master'],
				$domain['last_check'],
				$domain['type'],
				$domain['notified_serial'],
				$domain['account'],
				empty($domain['mxbackup']) ? 0 : 1,
				$domain['id']
				));
		
		// accounts owner update
		if($domain['ownerid'])
			$DB->Execute('UPDATE passwd SET ownerid = ? WHERE domainid = ? AND ownerid IS NOT NULL',
					array($domain['ownerid'], $domain['id'])); 

		$SESSION->redirect('?m=domainlist');
	}
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('error', $error);
$SMARTY->assign('domain', $domain);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->display('domain/domainedit.html');

?>
