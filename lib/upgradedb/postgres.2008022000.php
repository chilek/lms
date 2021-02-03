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
	ALTER TABLE tariffs ADD sh_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD mail_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD www_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD sql_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD ftp_limit integer DEFAULT NULL;

	ALTER TABLE tariffs ADD quota_sh_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD quota_mail_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD quota_www_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD quota_sql_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD quota_ftp_limit integer DEFAULT NULL;

	ALTER TABLE tariffs ADD domain_limit integer DEFAULT NULL;
	ALTER TABLE tariffs ADD alias_limit integer DEFAULT NULL;

	ALTER TABLE passwd ADD description text NOT NULL DEFAULT '';

	UPDATE tariffs SET domain_limit=0, alias_limit=0, sh_limit=0, www_limit=0,
            	ftp_limit=0, mail_limit=0, sql_limit=0, quota_sh_limit=0, quota_www_limit=0,
		quota_ftp_limit=0, quota_mail_limit=0, quota_sql_limit=0;
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2008022000', 'dbversion'));

$this->CommitTrans();
