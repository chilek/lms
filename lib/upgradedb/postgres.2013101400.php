<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$this->Execute("ALTER TABLE documents ADD div_name TEXT NOT NULL DEFAULT '';"); // pełna nazwa firmy
$this->Execute("ALTER TABLE documents ADD div_address VARCHAR (255) NOT NULL DEFAULT '';"); // adres firmy
$this->Execute("ALTER TABLE documents ADD div_city VARCHAR (255) NOT NULL DEFAULT '';"); // miasto
$this->Execute("ALTER TABLE documents ADD div_zip VARCHAR (255) NOT NULL DEFAULT '';"); // kod pocztowy
$this->Execute("ALTER TABLE documents ADD div_countryid INTEGER NOT NULL DEFAULT '0';");
$this->Execute("ALTER TABLE documents ADD div_ten VARCHAR (255) NOT NULL DEFAULT '';"); // nip
$this->Execute("ALTER TABLE documents ADD div_regon VARCHAR (255) NOT NULL DEFAULT '';"); // regon
$this->Execute("ALTER TABLE documents ADD div_account VARCHAR (48) NOT NULL DEFAULT '';"); // nr konta bankowego
$this->Execute("ALTER TABLE documents ADD div_inv_header TEXT NOT NULL DEFAULT '';"); // nagłówek faktury
$this->Execute("ALTER TABLE documents ADD div_inv_footer TEXT NOT NULL DEFAULT '';"); // stopka faktury
$this->Execute("ALTER TABLE documents ADD div_inv_author TEXT NOT NULL DEFAULT '';"); // kto wystawił
$this->Execute("ALTER TABLE documents ADD div_inv_cplace TEXT NOT NULL DEFAULT '';"); // miejsce wystawienia

$dl = $this->GetAll('SELECT id, name, address, city, zip, countryid, ten, regon, 
		account, inv_header, inv_footer, inv_author, inv_cplace 
		FROM divisions;');

$count = sizeof($dl);

if ($dl) {
    for ($i=0; $i<$count; $i++) {
        $this->Execute("UPDATE documents SET div_name = ?, div_address = ?, div_city = ?, div_zip = ?, 
			div_countryid = ?, div_ten = ?, div_regon = ?, div_account = ?, div_inv_header = ?, 
			div_inv_footer = ?, div_inv_author = ?, div_inv_cplace = ? 
			WHERE divisionid = ? ;", array(
            ($dl[$i]['name'] ? $dl[$i]['name'] : ''),
            ($dl[$i]['address'] ? $dl[$i]['address'] : ''),
            ($dl[$i]['city'] ? $dl[$i]['city'] : ''),
            ($dl[$i]['zip'] ? $dl[$i]['zip'] : ''),
            ($dl[$i]['countryid'] ? $dl[$i]['countryid'] : 0),
            ($dl[$i]['ten'] ? $dl[$i]['ten'] : ''),
            ($dl[$i]['regon'] ? $dl[$i]['regon'] : ''),
            ($dl[$i]['account'] ? $dl[$i]['account'] : ''),
            ($dl[$i]['inv_header'] ? $dl[$i]['inv_header'] : ''),
            ($dl[$i]['inv_footer'] ? $dl[$i]['inv_footer'] : ''),
            ($dl[$i]['inv_author'] ? $dl[$i]['inv_author'] : ''),
            ($dl[$i]['inv_cplace'] ? $dl[$i]['inv_cplace'] : ''),
            $dl[$i]['id']
            ));
    }
}

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013101400', 'dbversion'));
$this->CommitTrans();
