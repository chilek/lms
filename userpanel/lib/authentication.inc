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
		return NULL;

	$authinfo['id'] = $DB->GetOne('SELECT id FROM customers, customercontacts
	                WHERE customerid = customers.id AND phone = ?
			AND deleted = 0 LIMIT 1', 
			array($phone));

	if ($authinfo['id'] == NULL)
		return NULL;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin FROM customers, customercontacts
	                WHERE customerid = customers.id AND pin=? AND phone=? LIMIT 1',
			array($pin, $phone));

	return $authinfo;
}

function GetCustomerIDByIDAndPIN($id, $pin)
{
	global $DB;
	if(!preg_match('/^[0-9]+$/', $pin) || !preg_match('/^[0-9]+$/', $id))
		return NULL;

	$authinfo['id'] = $DB->GetOne('SELECT id from customers
		WHERE id = ? AND deleted = 0', array($id));

	if ($authinfo['id'] == NULL)
		return NULL;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin from customers WHERE pin=? AND id=?', array($pin, $id));

	return $authinfo;
}

// It requires "contract" field.
function GetCustomerIDByContractAndPIN($contract, $pin)
{
	global $DB;
	if(!preg_match('/^[0-9]+$/', $pin))
		return NULL;
	
	$authinfo['id'] = $DB->GetOne('SELECT id FROM customers
		WHERE contract = ? AND deleted = 0', array($contract));

	if ($authinfo['id'] == NULL)
		return NULL;

	$authinfo['passwd'] = $DB->GetOne('SELECT pin FROM customers WHERE pin=? AND contract=?', array($pin, $contract));

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
