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

$tables = $this->ListTables();
$versions = $this->GetAllByKey("SELECT keytype, keyvalue FROM dbinfo WHERE keytype ?LIKE? 'up_%'", 'keytype');

$this->BeginTrans();

if (!in_array('up_rights', $tables)) {
    $this->Execute("

    CREATE SEQUENCE up_rights_id_seq;
    CREATE TABLE up_rights (
	id integer DEFAULT nextval('up_rights_id_seq'::text) NOT NULL,
        module varchar(255) DEFAULT 0 NOT NULL,
        name varchar(255) DEFAULT 0 NOT NULL,
        description varchar(255) DEFAULT 0,
	setdefault smallint DEFAULT 0,
	PRIMARY KEY (id)
    )
");
}

if (!in_array('up_rights_assignments', $tables)) {
    $this->Execute("

    CREATE SEQUENCE up_rights_assignments_id_seq;
    CREATE TABLE up_rights_assignments (
	id integer DEFAULT nextval('up_rights_assignments_id_seq'::text) NOT NULL,
	customerid integer DEFAULT 0 NOT NULL,
        rightid integer DEFAULT 0 NOT NULL,
	PRIMARY KEY (id),
	CONSTRAINT up_rights_assignments_customerid_key UNIQUE (customerid, rightid)
    )
");
}

if (!in_array('up_customers', $tables)) {
    $this->Execute("

    CREATE SEQUENCE up_customers_id_seq;
    CREATE TABLE up_customers (
	id integer DEFAULT nextval('up_customers_id_seq'::text) NOT NULL,
        customerid integer DEFAULT 0 NOT NULL,
	lastlogindate integer DEFAULT 0 NOT NULL,
	lastloginip varchar(16) DEFAULT '' NOT NULL,
	failedlogindate integer DEFAULT 0 NOT NULL,
	failedloginip varchar(16) DEFAULT '' NOT NULL,
	enabled smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
    )
");
}

if (!in_array('up_help', $tables)) {
    $this->Execute("

    CREATE SEQUENCE up_help_id_seq;
    CREATE TABLE up_help (
        id integer DEFAULT nextval('up_help_id_seq'::text) NOT NULL,
	reference integer DEFAULT 0 NOT NULL,
	title varchar(128) DEFAULT 0 NOT NULL,
	body text DEFAULT '' NOT NULL,
	PRIMARY KEY (id)
    )
");
}

if (!in_array('up_info_changes', $tables)) {
    $this->Execute("

    CREATE SEQUENCE up_info_changes_id_seq;
    CREATE TABLE up_info_changes (
	id integer DEFAULT nextval('up_info_changes_id_seq'::text) NOT NULL,
	customerid integer DEFAULT 0 NOT NULL,
	fieldname varchar(255) DEFAULT 0 NOT NULL,
	fieldvalue varchar(255) DEFAULT 0 NOT NULL,
	PRIMARY KEY (id)
    )
");
}

if (empty($versions['up_module_finances']) || $versions['up_module_finances']['keyvalue'] < 2005081901) {
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled) 
		VALUES ('userpanel', 'disable_transferform', '0', '', 0)");
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'disable_invoices', '0', '', 0)");
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'invoice_duplicate', '0', '', 0)");
}
if (empty($versions['up_module_finances']) || $versions['up_module_finances']['keyvalue'] < 2005090601) {
    $this->Execute("INSERT INTO uiconfig (section, var, value) VALUES ('userpanel', 'show_tariffname', '1')");
    $this->Execute("INSERT INTO uiconfig (section, var, value) VALUES ('userpanel', 'show_speeds', '1')");
}

if (empty($versions['up_module_helpdesk']) || $versions['up_module_helpdesk']['keyvalue'] < 2005081901) {
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'default_queue', '1', '', 0)");
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'default_userid', '0', '', 0)");
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'debug_email', '', '', 0)");
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'lms_url', '', '', 0)");
}

if (empty($versions['up_module_info']) || $versions['up_module_info']['keyvalue'] < 2005091701) {
    $this->Execute("INSERT INTO up_rights(module, name, description)
    		VALUES ('info', 'edit_addr_ack', 'Customer can change address information with admin acknowlegment')");
    $this->Execute("INSERT INTO up_rights(module, name, description)
	        VALUES ('info', 'edit_addr', 'Customer can change address information')");
    $this->Execute("INSERT INTO up_rights(module, name, description, setdefault)
	        VALUES ('info', 'edit_contact_ack', 'Customer can change contact information with admin acknowlegment', 0)");
    $this->Execute("INSERT INTO up_rights(module, name, description)
	        VALUES ('info', 'edit_contact', 'Customer can change contact information')");
}

if (empty($versions['up_module_info']) || $versions['up_module_info']['keyvalue'] < 2006070500) {
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'hide_nodesbox', '0', '', 0)");
}

if (empty($versions['up_module_logout']) || $versions['up_module_logout']['keyvalue'] < 2005081901) {
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'logout_url', '', '', 0)");
}

if (empty($versions['up_module_stats']) || $versions['up_module_stats']['keyvalue'] < 2005081901) {
    $this->Execute("INSERT INTO uiconfig (section, var, value, description, disabled)
		VALUES ('userpanel', 'owner_stats', '0', '', 0)");
}

$this->Execute("DELETE FROM dbinfo WHERE keytype ?LIKE? 'up_%'");
$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008061300', 'dbversion'));

$this->CommitTrans();
