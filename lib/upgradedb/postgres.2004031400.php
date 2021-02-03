<?php

/*
 * LMS version 1.11-git
 *
 * (C) Copyright 2001-2013 LMS Developers
 *
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License Version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 * USA.
 *
 * $Id$
 */

$this->Execute("BEGIN");
$this->Execute("
	ALTER TABLE admins ALTER lastloginip SET DEFAULT '';
	ALTER TABLE admins ALTER failedloginip SET DEFAULT '';
	UPDATE admins SET lastloginip='' WHERE lastloginip IS NULL;
	UPDATE admins SET failedloginip='' WHERE failedloginip IS NULL;
	ALTER TABLE admins ALTER lastloginip SET NOT NULL;
	ALTER TABLE admins ALTER failedloginip SET NOT NULL;
");
$this->Execute("ALTER TABLE admins ADD UNIQUE (login)");

$this->Execute("
	ALTER TABLE invoicecontents ALTER invoiceid SET DEFAULT 0;
	ALTER TABLE invoicecontents ALTER count SET DEFAULT 0;
	ALTER TABLE invoicecontents ALTER value SET DEFAULT 0;
	ALTER TABLE invoicecontents ALTER taxvalue SET DEFAULT 0;
	ALTER TABLE invoicecontents ALTER tariffid SET DEFAULT 0;
	ALTER TABLE invoicecontents ALTER content SET DEFAULT '';
	ALTER TABLE invoicecontents ALTER description SET DEFAULT '';
	ALTER TABLE invoicecontents ALTER pkwiu SET DEFAULT '';
	UPDATE invoicecontents SET pkwiu='' WHERE pkwiu IS NULL;
	ALTER TABLE invoicecontents ALTER pkwiu SET NOT NULL;
");
$this->Execute("
	ALTER TABLE invoices ALTER number SET DEFAULT 0;
	ALTER TABLE invoices ALTER cdate SET DEFAULT 0;
	ALTER TABLE invoices ALTER paytime SET DEFAULT 0;
	ALTER TABLE invoices ALTER customerid SET DEFAULT 0;
	ALTER TABLE invoices ALTER name SET DEFAULT '';
	ALTER TABLE invoices ALTER address SET DEFAULT '';	
	ALTER TABLE invoices ALTER zip SET DEFAULT '';	
	ALTER TABLE invoices ALTER city SET DEFAULT '';	
	ALTER TABLE invoices ALTER phone SET DEFAULT '';	
	ALTER TABLE invoices ALTER pesel SET DEFAULT '';
	ALTER TABLE invoices ALTER nip SET DEFAULT '';
	UPDATE invoices SET pesel='' WHERE pesel IS NULL;
	UPDATE invoices SET nip='' WHERE nip IS NULL;
	ALTER TABLE invoices ALTER pesel SET NOT NULL;
	ALTER TABLE invoices ALTER nip SET NOT NULL;
");
$this->Execute("
	ALTER TABLE netdevices ALTER name SET DEFAULT '';
	UPDATE netdevices SET name='' WHERE name IS NULL;
	ALTER TABLE netdevices ALTER name SET NOT NULL;	
	ALTER TABLE netdevices ALTER location SET DEFAULT '';
	UPDATE netdevices SET location='' WHERE location IS NULL;
	ALTER TABLE netdevices ALTER location SET NOT NULL;
	ALTER TABLE netdevices ALTER description SET DEFAULT '';
	UPDATE netdevices SET description='' WHERE description IS NULL;
	ALTER TABLE netdevices ALTER description SET NOT NULL;
	ALTER TABLE netdevices ALTER producer SET DEFAULT '';
	UPDATE netdevices SET producer='' WHERE producer IS NULL;
	ALTER TABLE netdevices ALTER producer SET NOT NULL;
	ALTER TABLE netdevices ALTER model SET DEFAULT '';
	UPDATE netdevices SET model='' WHERE model IS NULL;
	ALTER TABLE netdevices ALTER model SET NOT NULL;
	ALTER TABLE netdevices ALTER serialnumber SET DEFAULT '';
	UPDATE netdevices SET serialnumber='' WHERE serialnumber IS NULL;
	ALTER TABLE netdevices ALTER serialnumber SET NOT NULL;
	ALTER TABLE netdevices ALTER ports SET DEFAULT 0;
	UPDATE netdevices SET ports=0 WHERE ports IS NULL;
	ALTER TABLE netdevices ALTER ports SET NOT NULL;
");
$this->Execute("ALTER TABLE netlinks ADD UNIQUE (src, dst)");

$this->Execute("
	ALTER TABLE networks ALTER name SET DEFAULT '';
	ALTER TABLE networks ALTER address SET DEFAULT 0;	
	ALTER TABLE networks ALTER mask SET DEFAULT '';
	ALTER TABLE networks ALTER gateway SET DEFAULT '';
	ALTER TABLE networks ALTER interface SET DEFAULT '';
	ALTER TABLE networks ALTER dns SET DEFAULT '';
	ALTER TABLE networks ALTER dns2 SET DEFAULT '';
	ALTER TABLE networks ALTER domain SET DEFAULT '';
	ALTER TABLE networks ALTER wins SET DEFAULT '';
	ALTER TABLE networks ALTER dhcpstart SET DEFAULT '';
	ALTER TABLE networks ALTER dhcpend SET DEFAULT '';
	UPDATE networks SET gateway='' WHERE gateway IS NULL;
	UPDATE networks SET interface='' WHERE interface IS NULL;
	UPDATE networks SET dns='' WHERE dns IS NULL;
	UPDATE networks SET dns2='' WHERE dns2 IS NULL;
	UPDATE networks SET domain='' WHERE domain IS NULL;
	UPDATE networks SET wins='' WHERE wins IS NULL;
	UPDATE networks SET dhcpstart='' WHERE dhcpstart IS NULL;
	UPDATE networks SET dhcpend='' WHERE dhcpend IS NULL;	
	ALTER TABLE networks ALTER gateway SET NOT NULL;	
	ALTER TABLE networks ALTER interface SET NOT NULL;	
	ALTER TABLE networks ALTER dns SET NOT NULL;	
	ALTER TABLE networks ALTER dns2 SET NOT NULL;	
	ALTER TABLE networks ALTER domain SET NOT NULL;
	ALTER TABLE networks ALTER wins SET NOT NULL;
	ALTER TABLE networks ALTER dhcpstart SET NOT NULL;
	ALTER TABLE networks ALTER dhcpend SET NOT NULL;
");
$this->Execute("
	ALTER TABLE nodes ALTER name SET DEFAULT '';
	ALTER TABLE nodes ALTER mac SET DEFAULT '';
	ALTER TABLE nodes ALTER ipaddr SET DEFAULT 0;
	ALTER TABLE nodes ALTER creationdate SET DEFAULT 0;
	ALTER TABLE nodes ALTER creatorid SET DEFAULT 0;
	ALTER TABLE nodes ADD UNIQUE (name);
	ALTER TABLE nodes ADD UNIQUE (mac);	
	ALTER TABLE nodes ADD UNIQUE (ipaddr);	
");
$this->Execute("
	ALTER TABLE payments ALTER description SET DEFAULT '';
	UPDATE payments SET description='' WHERE description IS NULL;
	ALTER TABLE payments ALTER description SET NOT NULL;
");
$this->Execute("
	ALTER TABLE rtattachments ALTER filename SET DEFAULT '';
	ALTER TABLE rtattachments ALTER contenttype SET DEFAULT '';
");
$this->Execute("
	ALTER TABLE tariffs ALTER name SET DEFAULT '';
	ALTER TABLE tariffs ALTER pkwiu SET DEFAULT '';
	UPDATE tariffs SET pkwiu='' WHERE pkwiu IS NULL;
	ALTER TABLE tariffs ALTER pkwiu SET NOT NULL;
	ALTER TABLE tariffs ALTER uprate SET DEFAULT 0;
	ALTER TABLE tariffs ALTER downrate SET DEFAULT 0;	
	UPDATE tariffs SET uprate=0 WHERE uprate IS NULL;
	UPDATE tariffs SET downrate=0 WHERE downrate IS NULL;
	ALTER TABLE tariffs ALTER uprate SET NOT NULL;
	ALTER TABLE tariffs ALTER downrate SET NOT NULL;
	ALTER TABLE tariffs ALTER description SET DEFAULT '';
	UPDATE tariffs SET description='' WHERE description IS NULL;
	ALTER TABLE tariffs ALTER description SET NOT NULL;	
	ALTER TABLE tariffs ADD UNIQUE (name);
");
$this->Execute("ALTER TABLE timestamps ADD UNIQUE (tablename)");

$this->Execute("
	ALTER TABLE users ALTER lastname SET DEFAULT '';
	ALTER TABLE users ALTER name SET DEFAULT '';
	UPDATE users SET lastname='' WHERE lastname IS NULL;
	UPDATE users SET name='' WHERE name IS NULL;
	ALTER TABLE users ALTER lastname SET NOT NULL;
	ALTER TABLE users ALTER name SET NOT NULL;
	ALTER TABLE users ALTER status SET DEFAULT 0;
	ALTER TABLE users ALTER email SET DEFAULT '';
	ALTER TABLE users ALTER phone1 SET DEFAULT '';
	ALTER TABLE users ALTER phone2 SET DEFAULT '';
	ALTER TABLE users ALTER phone3 SET DEFAULT '';
	UPDATE users SET status=0 WHERE status IS NULL;
	UPDATE users SET email='' WHERE email IS NULL;
	UPDATE users SET phone1='' WHERE phone1 IS NULL;	
	UPDATE users SET phone2='' WHERE phone2 IS NULL;
	UPDATE users SET phone3='' WHERE phone3 IS NULL;
	ALTER TABLE users ALTER status SET NOT NULL;
	ALTER TABLE users ALTER email SET NOT NULL;
	ALTER TABLE users ALTER phone1 SET NOT NULL;
	ALTER TABLE users ALTER phone2 SET NOT NULL;
	ALTER TABLE users ALTER phone3 SET NOT NULL;
	ALTER TABLE users ALTER zip SET DEFAULT '';
	ALTER TABLE users ALTER city SET DEFAULT '';
	ALTER TABLE users ALTER nip SET DEFAULT '';
	ALTER TABLE users ALTER pesel SET DEFAULT '';
	ALTER TABLE users ALTER info SET DEFAULT '';
	ALTER TABLE users ALTER message SET DEFAULT '';
	UPDATE users SET zip='' WHERE zip IS NULL;
	UPDATE users SET city='' WHERE city IS NULL;
	UPDATE users SET nip='' WHERE nip IS NULL;	
	UPDATE users SET pesel='' WHERE pesel IS NULL;
	UPDATE users SET info='' WHERE info IS NULL;
	UPDATE users SET message='' WHERE message IS NULL;
	ALTER TABLE users ALTER zip SET NOT NULL;
	ALTER TABLE users ALTER city SET NOT NULL;
	ALTER TABLE users ALTER nip SET NOT NULL;
	ALTER TABLE users ALTER pesel SET NOT NULL;
	ALTER TABLE users ALTER info SET NOT NULL;
	ALTER TABLE users ALTER message SET NOT NULL;
");
$this->Execute("UPDATE dbinfo SET keyvalue='2004031400' WHERE keytype='dbversion'");

$this->Execute("COMMIT");
