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

$fkeys = $DB->GetAll("SELECT * FROM information_schema.table_constraints WHERE constraint_type = 'FOREIGN KEY'");
foreach ($fkeys as $fkey)
	$DB->Execute("ALTER TABLE ? DROP CONSTRAINT ?",
		array($fkey['table_name'],
			$fkey['constraint_name']));

$DB->Execute("ALTER TABLE assignments ADD CONSTRAINT assignments_customerid_fkey FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE assignments ADD CONSTRAINT assignments_numberplanid_fkey FOREIGN KEY (numberplanid)
	REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE location_districts ADD CONSTRAINT location_districts_stateid_fkey FOREIGN KEY (stateid)
	REFERENCES location_states (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE location_boroughs ADD CONSTRAINT location_boroughs_districtid_fkey FOREIGN KEY (districtid)
	REFERENCES location_districts (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE location_cities ADD CONSTRAINT location_cities_boroughid_fkey FOREIGN KEY (boroughid)
	 REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE location_streets ADD CONSTRAINT location_streets_typeid_fkey FOREIGN KEY (typeid)
	REFERENCES location_street_types (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE location_streets ADD CONSTRAINT location_streets_cityid_fkey FOREIGN KEY (cityid)
	REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE pna ADD CONSTRAINT pna_cityid_fkey FOREIGN KEY (cityid)
	REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE pna ADD CONSTRAINT pna_streetid_fkey FOREIGN KEY (streetid)
	REFERENCES location_streets (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE nodes ADD CONSTRAINT nodes_location_city_fkey FOREIGN KEY (location_city)
	REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE nodes ADD CONSTRAINT nodes_location_street_fkey FOREIGN KEY (location_street)
	REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE nodelocks ADD CONSTRAINT nodelocks_nodeid_fkey FOREIGN KEY (nodeid)
	REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE macs ADD CONSTRAINT macs_nodeid_fkey FOREIGN KEY (nodeid)
	REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE nodeassignments ADD CONSTRAINT nodeassignments_nodeid_fkey FOREIGN KEY (nodeid)
	REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE nodeassignments ADD CONSTRAINT nodeassignments_assignmentid_fkey FOREIGN KEY (assignmentid)
	REFERENCES assignments (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE promotionschemas ADD CONSTRAINT promotionschemas_promotionid_fkey FOREIGN KEY (promotionid)
	REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE promotionschemas ADD CONSTRAINT promotionschemas_ctariffid_fkey FOREIGN KEY (ctariffid)
	REFERENCES tariffs (id) ON DELETE RESTRICT ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE promotionassignments ADD CONSTRAINT promotionassignments_promotionschemaid_fkey FOREIGN KEY (promotionschemaid)
	REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE promotionassignments ADD CONSTRAINT promotionassignments_tariffid_fkey FOREIGN KEY (tariffid)
	REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE customerassignments ADD CONSTRAINT customerassignments_customergroupid_fkey FOREIGN KEY (customergroupid)
	REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE customerassignments ADD CONSTRAINT customerassignments_customerid_fkey FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rttickets ADD CONSTRAINT rttickets_queueid_fkey FOREIGN KEY (queueid)
	REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rtmessages ADD CONSTRAINT rtmessages_ticketid_fkey FOREIGN KEY (ticketid)
	REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rtnotes ADD CONSTRAINT rtnotes_ticketid_fkey FOREIGN KEY (ticketid)
	REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE rtnotes ADD CONSTRAINT rtnotes_userid_fkey FOREIGN KEY (userid)
	REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rtrights ADD CONSTRAINT rtrights_userid_fkey FOREIGN KEY (userid)
	REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE rtrights ADD CONSTRAINT rtrights_queueid_fkey FOREIGN KEY (queueid)
	REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rtattachments ADD CONSTRAINT rtattachments_messageid_fkey FOREIGN KEY (messageid)
	REFERENCES rtmessages (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rtcategoryusers ADD CONSTRAINT rtcategoryusers_userid_fkey FOREIGN KEY (userid)
	REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE rtcategoryusers ADD CONSTRAINT rtcategoryusers_categoryid_fkey FOREIGN KEY (categoryid)
	REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE rtticketcategories ADD CONSTRAINT rtticketcategories_ticketid_fkey FOREIGN KEY (ticketid)
	REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE rtticketcategories ADD CONSTRAINT rtticketcategories_categoryid_fkey FOREIGN KEY (categoryid)
	REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE records ADD CONSTRAINT records_domain_id_fkey FOREIGN KEY (domain_id)
	REFERENCES domains (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE sourcefiles ADD CONSTRAINT sourcefiles_userid_fkey FOREIGN KEY (userid)
	REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE cashimport ADD CONSTRAINT cashimport_customerid_fkey FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE cashimport ADD CONSTRAINT cashimport_sourceid_fkey FOREIGN KEY (sourceid)
	REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE cashimport ADD CONSTRAINT cashimport_sourcefileid_fkey FOREIGN KEY (sourcefileid)
	REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE netdevices ADD CONSTRAINT netdevices_location_city_fkey FOREIGN KEY (location_city)
	REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE netdevices ADD CONSTRAINT netdevices_location_street_fkey FOREIGN KEY (location_street)
	REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");
$DB->Execute("ALTER TABLE netdevices ADD CONSTRAINT netdevices_channelid_fkey FOREIGN KEY (channelid)
	REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE imessengers ADD CONSTRAINT imessengers_customerid_fkey FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE customercontacts ADD CONSTRAINT customercontacts_customerid_fkey FOREIGN KEY (customerid)
	REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE excludedgroups ADD CONSTRAINT excludedgroups_customergroupid_fkey FOREIGN KEY (customergroupid)
	REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("ALTER TABLE managementurls ADD CONSTRAINT managementurls_netdevid_fkey FOREIGN KEY (netdevid)
	REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE INITIALLY DEFERRED");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2012110300', 'dbversion'));

$DB->CommitTrans();

?>
