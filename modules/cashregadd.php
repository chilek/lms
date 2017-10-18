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

if(isset($_POST['registry']))
{
	$registry = $_POST['registry'];

	if($registry['name']=='' && $registry['description']=='')
	{
		$SESSION->redirect('?m=cashreglist');
	}

	if($registry['name'] == '')
		$error['name'] = trans('Registry name must be defined!');

	if($registry['name'] != '' && $DB->GetOne('SELECT id FROM cashregs WHERE name=?',array($registry['name'])))
		$error['name'] = trans('Registry with specified name already exists!');

	if(isset($registry['users']))
		foreach($registry['users'] as $key => $value)
			$registry['rights'][] = array('id' => $key, 'rights' => array_sum($value), 'name' => $registry['usernames'][$key]);

	if (!$error) {
		$args = array(
			'name' => $registry['name'],
			'description' => $registry['description'],
			'in_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN) => empty($registry['in_numberplanid']) ? null : $registry['in_numberplanid'],
			'out_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN) => empty($registry['out_numberplanid']) ? null : $registry['out_numberplanid'],
			'disabled' => isset($registry['disabled']) ? 1 : 0,
		);
		$DB->Execute('INSERT INTO cashregs (name, description, in_numberplanid, out_numberplanid, disabled)
			VALUES(?, ?, ?, ?, ?)', array_values($args));

		$id = $DB->GetOne('SELECT id FROM cashregs WHERE name=?', array($registry['name']));

		if ($SYSLOG) {
			$args[SYSLOG::RES_CASHREG] = $id;
			$SYSLOG->AddMessage(SYSLOG::RES_CASHREG, SYSLOG::OPER_ADD, $args,
				array('in_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN),
					'out_' . SYSLOG::getResourceKey(SYSLOG::RES_NUMPLAN)));
		}

		if (isset($registry['rights']))
			foreach ($registry['rights'] as $right)
				if ($right['rights']) {
					$args = array(
						SYSLOG::RES_CASHREG => $id,
						SYSLOG::RES_USER => $right['id'],
						'rights' => $right['rights'],
					);
					$DB->Execute('INSERT INTO cashrights (regid, userid, rights) VALUES(?, ?, ?)', array_values($args));
					if ($SYSLOG) {
						$args[SYSLOG::RES_CASHRIGHT] = $DB->GetLastInsertID('cashrights');
						$SYSLOG->AddMessage(SYSLOG::RES_CASHRIGHT, SYSLOG::OPER_ADD, $args);
					}
				}

		$SESSION->redirect('?m=cashreginfo&id='.$id);
	}
}

$users = $LMS->GetUserNames();

foreach($users as $user) 
{
	$user['rights'] = isset($registry['users'][$user['id']]) ? $registry['users'][$user['id']] : 0;
	$registry['nrights'][] = $user;
}
$registry['rights'] = $registry['nrights'];

$layout['pagetitle'] = trans('New Cash Registry');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('registry', $registry);
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(array(
	'doctype' => DOC_RECEIPT,
)));
$SMARTY->assign('error', $error);
$SMARTY->display('cash/cashregadd.html');

?>
