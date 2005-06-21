<?php

/*
 * LMS version 1.6-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

function GetUserIDByPhone1AndPIN($phone1, $pin)
{
	global $LMS;
	if(!ereg('^[0-9]+$',$pin))
		return NULL;
	return $LMS->DB->GetOne('SELECT id from users WHERE pin=? AND phone1=?', array($pin, $phone1));
}

function GetUserIDByIDAndPIN($id, $pin)
{
	global $LMS;
	if(!eregi('^[0-9]+$',$id) || !eregi('^[0-9]+$',$pin))
		return NULL;
	return $LMS->DB->GetOne('SELECT id from users WHERE pin=? AND id=?', array($pin, $id));
}

// It requires "contract" field.
function GetUserIDByContractAndPIN($contract, $pin)
{
	global $LMS;
	if(!eregi('^[0-9]+$',$pin))
		return NULL;
	return $LMS->DB->GetOne('SELECT id FROM users WHERE pin=? AND contract=?', array($pin, $contract));
}

?>
