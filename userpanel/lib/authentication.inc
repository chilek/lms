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

global $LMS;

function GetCustomerIDByPhoneAndPIN($phone, $pin)
{
	global $DB;
	if(!preg_match('/^[0-9]+$/', $pin))
		return null;

	$authinfo['id'] = $DB->GetOne('SELECT c.id FROM customers c, customercontacts cc
		WHERE customerid = c.id AND contact = ? AND cc.type < ?
			AND deleted = 0 LIMIT 1', 
		array($phone, CONTACT_EMAIL));

	if (empty($authinfo['id']))
		return null;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin FROM customers
		WHERE pin = ? AND id = ?', array($pin, $authinfo['id']));

	return $authinfo;
}

function GetCustomerIDByIDAndPIN($id, $pin)
{
	global $DB;
	if(!preg_match('/^[0-9]+$/', $pin) || !preg_match('/^[0-9]+$/', $id))
		return null;

	$authinfo['id'] = $DB->GetOne('SELECT id FROM customers
		WHERE id = ? AND deleted = 0', array($id));

	if (empty($authinfo['id']))
		return null;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin FROM customers
		WHERE pin = ? AND id = ?', array($pin, $id));

	return $authinfo;
}

// It requires "contract" field.
function GetCustomerIDByContractAndPIN($contract, $pin)
{
	global $DB;
	if(!preg_match('/^[0-9]+$/', $pin))
		return null;

	$authinfo['id'] = $DB->GetOne('SELECT id FROM customers
		WHERE contract = ? AND deleted = 0', array($contract));

	if (empty($authinfo['id']))
		return null;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin FROM customers
		WHERE pin = ? AND id = ?', array($pin, $authinfo['id']));

	return $authinfo;
}

function GetCustomerIDByEmailAndPIN($email, $pin)
{
	global $DB;
	if (!preg_match('/^[0-9]+$/', $pin))
		return null;

	$authinfo['id'] = $DB->GetOne('SELECT c.id FROM customers c, customercontacts cc
		WHERE cc.customerid = c.id AND contact = ? AND cc.type = ?
			AND deleted = 0 LIMIT 1',
		array($email, CONTACT_EMAIL));

	if (empty($authinfo['id']))
		return null;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin FROM customers
		WHERE pin = ? AND id = ?', array($pin, $authinfo['id']));

	return $authinfo;
}

function GetCustomerAuthInfo($customerid)
{
	global $DB;
	return $DB->GetRow('SELECT customerid AS id, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled FROM up_customers WHERE customerid=?',
		array($customerid));
}

function SetCustomerAuthInfo($authinfo)
{
	global $DB;
	$actauthinfo = GetCustomerAuthInfo($authinfo['id']);
	if ($actauthinfo != null)
	{
		$DB->Execute('UPDATE up_customers SET lastlogindate=?, lastloginip=?, failedlogindate=?, failedloginip=?, enabled=? WHERE customerid=?',
			array($authinfo['lastlogindate'], $authinfo['lastloginip'], $authinfo['failedlogindate'], $authinfo['failedloginip'],
			$authinfo['enabled'], $authinfo['id']));
	}
	else
	{
		$DB->Execute('INSERT INTO up_customers(customerid, lastlogindate, lastloginip, failedlogindate, failedloginip, enabled) VALUES (?, ?, ?, ?, ?, ?)',
			array($authinfo['id'], $authinfo['lastlogindate'], $authinfo['lastloginip'],
			$authinfo['failedlogindate'], $authinfo['failedloginip'], $authinfo['enabled']));
	}
}

?>
