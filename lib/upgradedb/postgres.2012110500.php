<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$DB->BeginTrans();

$DB->Execute("DROP SEQUENCE IF EXISTS syslog_id_seq;");
$DB->Execute("CREATE SEQUENCE syslog_id_seq;");
$DB->Execute("DROP TABLE IF EXISTS syslog;");
$DB->Execute("
CREATE TABLE syslog (
    id bigint DEFAULT nextval('syslog_id_seq'::text) NOT NULL,
    cdate integer default 0 not null,
    uid integer default null,
    cid integer default null,
    nid integer default null,
    module smallint default null,
    event smallint default null,
    msg varchar(255) default null,
    diff text default null,
    PRIMARY KEY (id)
);
");
$DB->Execute("CREATE INDEX syslog_cdate_idx ON syslog(cdate);");
$DB->Execute("CREATE INDEX syslog_uid_idx ON syslog(uid);");
$DB->Execute("CREATE INDEX syslog_module_idx ON syslog(module);");
$DB->Execute("CREATE INDEX syslog_event_idx ON syslog(event);");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012110500', 'dbversion'));

$DB->CommitTrans();

?>
