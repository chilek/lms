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

$DB->Execute("
CREATE TABLE states (
    	id int(11) NOT NULL auto_increment,
	name varchar(255) NOT NULL DEFAULT '',
	description text NOT NULL DEFAULT '',
	PRIMARY KEY (id),
	UNIQUE KEY name (name)
) ENGINE=MyISAM");

$DB->Execute("
CREATE TABLE zipcodes (
    	id int(11) NOT NULL auto_increment,
	zip varchar(10) NOT NULL DEFAULT '',
	stateid int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	UNIQUE KEY zip (zip),
	INDEX stateid (stateid)
) ENGINE=MyISAM");

$DB->Execute("ALTER TABLE customers ADD INDEX zip (zip)");
$DB->Execute("INSERT INTO zipcodes (zip) SELECT DISTINCT zip FROM customers");

if(ConfigHelper::getConfig('phpui.lang') == 'pl' 
	|| $DB->GetOne("SELECT 1 FROM uiconfig WHERE var='lang' AND section='phpui' AND disabled=0 AND value='pl'"))
{
	$DB->Execute("INSERT INTO states (name) VALUES ('dolnośląskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('kujawsko-pomorskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('lubelskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('lubuskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('łódzkie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('małopolskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('mazowieckie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('opolskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('podkarpackie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('podlaskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('pomorskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('śląskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('świętokrzyskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('warmińsko-mazurskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('wielkopolskie')");
	$DB->Execute("INSERT INTO states (name) VALUES ('zachodniopomorskie')");
}

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008080800', 'dbversion'));

?>
