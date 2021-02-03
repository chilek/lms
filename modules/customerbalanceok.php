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

$customerid = $_GET['id'];

if ($LMS->CustomerExists($customerid)) {
    $balance = $LMS->GetCustomerBalance($customerid);

    if ($balance<0) {
        $DB->BeginTrans();

        $DB->Execute(
            'INSERT INTO cash (time, type, userid, value, customerid, comment)
			VALUES (?NOW?, 1, ?, ?, ?, ?)',
            array(Auth::GetCurrentUser(),
                str_replace(',', '.', $balance*-1),
                $customerid,
                trans('Accounted'))
        );
    
        $DB->Execute(
            'UPDATE documents SET closed = 1 
			WHERE customerid = ? AND type IN (?, ?) AND closed = 0',
            array($customerid, DOC_INVOICE, DOC_CNOTE)
        );

        $DB->CommitTrans();
    }
}

header('Location: ?'.$SESSION->get('backto'));
