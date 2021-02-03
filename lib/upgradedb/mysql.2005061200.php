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

$this->Execute("
	CREATE TABLE taxes (
	    id int(11) NOT NULL auto_increment,
	    value decimal(4,2) NOT NULL DEFAULT '0',
	    taxed tinyint NOT NULL DEFAULT '0',
	    label varchar(16) NOT NULL DEFAULT '',
	    validfrom int(11) NOT NULL DEFAULT '0',
	    validto int(11) NOT NULL DEFAULT '0',
	    PRIMARY KEY (id))
");
$this->Execute("ALTER TABLE cash ADD taxid int(11) NOT NULL DEFAULT '0'");
$this->Execute("ALTER TABLE tariffs ADD taxid int(11) NOT NULL DEFAULT '0'");
$this->Execute("ALTER TABLE invoicecontents ADD taxid int(11) NOT NULL DEFAULT '0'");

//Mysql 3.x hasn't got UNION clause
//Using 3 tables to be sure that all used tax rates are retrived

$this->Execute("CREATE TABLE temp_union ENGINE=HEAP SELECT taxvalue FROM cash GROUP BY taxvalue");
$this->Execute("INSERT INTO temp_union SELECT taxvalue FROM tariffs GROUP BY taxvalue");
$this->Execute("INSERT INTO temp_union SELECT taxvalue FROM invoicecontents GROUP BY taxvalue");

$i=0;
if ($taxes = $this->GetCol("SELECT taxvalue FROM temp_union GROUP BY taxvalue")) {
    foreach ($taxes as $tax) {
        $i++;
        if ($tax=='') { //tax-free
            $this->Execute("INSERT INTO taxes (value, taxed, label) VALUES(0,0,'tax-free')");
            $this->Execute("UPDATE cash SET taxid=? WHERE taxvalue IS NULL", array($i));
            $this->Execute("UPDATE tariffs SET taxid=? WHERE taxvalue IS NULL", array($i));
            $this->Execute("UPDATE invoicecontents SET taxid=? WHERE taxvalue IS NULL", array($i));
        } else {
            $this->Execute("INSERT INTO taxes (value, taxed, label) VALUES(?,1,?)", array($tax, $tax.' %'));
            $this->Execute("UPDATE cash SET taxid=? WHERE taxvalue=?", array($i, $tax));
            $this->Execute("UPDATE tariffs SET taxid=? WHERE taxvalue=?", array($i, $tax));
            $this->Execute("UPDATE invoicecontents SET taxid=? WHERE taxvalue=?", array($i, $tax));
        }
    }
}

$this->Execute("DROP TABLE temp_union");
$this->Execute("ALTER TABLE cash DROP taxvalue");
$this->Execute("ALTER TABLE tariffs DROP taxvalue");
$this->Execute("ALTER TABLE invoicecontents DROP taxvalue");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005061200', 'dbversion'));

$this->CommitTrans();
