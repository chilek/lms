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

$this->BeginTrans();

$tables = array(
    'documents' => 'paytype',
    'customers' => 'paytype',
    'divisions' => 'inv_paytype',
);

$paytypes = array(
    1   => array('cash', 'gotówka', 'grynieji'),
    2   => array('transfer', 'przelew', 'pavedimas'),
    3   => array('transfer/cash', 'przelew/karta', 'pavedimas/grynieji'),
    4   => array('card', 'karta', 'kortelė'),
    5   => array('compensation', 'kompensata', 'kompensacija'),
    6   => array('barter', 'barteris'),
    7   => array('contract', 'umowa', 'sutartis'),
);

foreach ($tables as $tab => $col) {
    $this->Execute("ALTER TABLE $tab ADD paytype2 smallint DEFAULT NULL");

    $types = $this->GetCol("SELECT LOWER($col) AS paytype FROM $tab GROUP BY LOWER($col)");

    if (!empty($types)) {
        foreach ($types as $type) {
            foreach ($paytypes as $pid => $pname) {
                if (in_array($type, $pname)) {
                    $this->Execute("UPDATE $tab SET paytype2 = $pid WHERE LOWER($col) = ?", array($type));
                    break;
                }
            }
        }
    }

    $this->Execute("ALTER TABLE $tab DROP $col");
    $this->Execute("ALTER TABLE $tab CHANGE paytype2 $col smallint DEFAULT NULL");
}

$cfg = $this->GetOne("SELECT value FROM uiconfig WHERE var = 'paytype' AND section = 'invoices'");

if ($cfg) {
    foreach ($paytypes as $pid => $pname) {
        if (in_array($cfg, $pname)) {
            $this->Execute("UPDATE uiconfig SET value = $pid WHERE var = 'paytype' AND section = 'invoices'");
            break;
        }
    }
}

$cfg = $this->GetOne("SELECT value FROM daemonconfig WHERE var = 'paytype'");

if ($cfg) {
    foreach ($paytypes as $pid => $pname) {
        if (in_array($cfg, $pname)) {
            $this->Execute("UPDATE daemonconfig SET value = '$pid' WHERE var = 'paytype'");
            break;
        }
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010061800', 'dbversion'));

$this->CommitTrans();
