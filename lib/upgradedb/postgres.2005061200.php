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
	CREATE SEQUENCE taxes_id_seq;
	CREATE TABLE taxes (
	    id	integer DEFAULT nextval('taxes_id_seq'::text) NOT NULL,
	    value numeric(4,2) DEFAULT 0 NOT NULL,
	    taxed smallint DEFAULT 0 NOT NULL,
	    label varchar(16) DEFAULT '' NOT NULL,
	    validfrom integer DEFAULT 0 NOT NULL,
	    validto integer DEFAULT 0 NOT NULL,
	    PRIMARY KEY (id)
	);
	
	ALTER TABLE cash ADD taxid integer;
	ALTER TABLE tariffs ADD taxid integer;
	ALTER TABLE invoicecontents ADD taxid integer;
");

$i=0;
if ($taxes = $this->GetCol("SELECT taxvalue FROM cash GROUP BY taxvalue
			UNION
			SELECT taxvalue FROM tariffs GROUP BY taxvalue
			UNION
			SELECT taxvalue FROM invoicecontents GROUP BY taxvalue
			")
) {
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
    
$this->Execute("
	UPDATE cash SET taxid = 0 WHERE taxid IS NULL;
	UPDATE tariffs SET taxid = 0 WHERE taxid IS NULL;
	UPDATE invoicecontents SET taxid = 0 WHERE taxid IS NULL;
	ALTER TABLE cash ALTER taxid SET NOT NULL;
	ALTER TABLE cash ALTER taxid SET DEFAULT 0;
	ALTER TABLE tariffs ALTER taxid SET NOT NULL;
	ALTER TABLE tariffs ALTER taxid SET DEFAULT 0;
	ALTER TABLE invoicecontents ALTER taxid SET NOT NULL;
	ALTER TABLE invoicecontents ALTER taxid SET DEFAULT 0;
	ALTER TABLE cash DROP taxvalue;
	ALTER TABLE tariffs DROP taxvalue;
	ALTER TABLE invoicecontents DROP taxvalue;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2005061200', 'dbversion'));

$this->CommitTrans();
