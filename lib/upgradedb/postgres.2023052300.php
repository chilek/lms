<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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
 */

$this->BeginTrans();

define('DOC_DNOTE_2023052300', 5);

$notes = $this->GetAll(
    'SELECT id, customerid, divisionid
    FROM documents
    WHERE type = ?
        AND paytype IS NULL',
    array(
        DOC_DNOTE_2023052300,
    )
);

if (!empty($notes)) {
    $division_paytypes = $this->GetAllBykey(
        'SELECT id, inv_paytype
        FROM divisions
        WHERE inv_paytype IS NOT NULL',
        'id'
    );
    $customer_paytypes = $this->GetAllByKey(
        'SELECT id, paytype
        FROM customers
        WHERE paytype IS NOT NULL',
        'id'
    );
    $default_paytype = intval(ConfigHelper::getConfig('notes.paytype', ConfigHelper::getConfig('invoices.paytype')));

    foreach ($notes as $note) {
        $this->Execute(
            'UPDATE documents SET paytype = ? WHERE id = ?',
            array(
                isset($customer_paytypes[$note['customerid']])
                    ? $customer_paytypes[$note['customerid']]['paytype']
                    : (isset($division_paytypes[$note['divisionid']])
                        ? $division_paytypes[$note['divisionid']]['inv_paytype']
                        : $default_paytype),
                $note['id'],
            )
        );
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2023052300', 'dbversion'));

$this->CommitTrans();
