<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("
CREATE TABLE imessengers (
  id int(11) NOT NULL auto_increment, 
  customerid int(11) NOT NULL, 
  uid varchar(32) NOT NULL default '',
  type tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (id) 
  ) TYPE=MyISAM;
");

// // YMSGR (yahoo messenger id )
//$DB->Execute("ALTER TABLE customers ADD COLUMN ymsgr text NOT NULL DEFAULT ''");
// //Skype (skype ID )
//$DB->Execute("ALTER TABLE customers ADD COLUMN skype text NOT NULL DEFAULT ''");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?",array('2007033100', 'dbversion'));

$DB->CommitTrans();

?>
