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

$this->Execute("DELETE FROM assignments WHERE customerid NOT IN (SELECT id FROM customers)");
$this->Execute("UPDATE assignments SET numberplanid = NULL WHERE numberplanid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM numberplans WHERE id = numberplanid)");
$this->Execute("ALTER TABLE assignments ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("DELETE FROM location_districts WHERE stateid NOT IN (SELECT id FROM location_states)");
$this->Execute("ALTER TABLE location_districts ADD FOREIGN KEY (stateid) REFERENCES location_states (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM location_boroughs WHERE districtid NOT IN (SELECT id FROM location_districts)");
$this->Execute("ALTER TABLE location_boroughs ADD FOREIGN KEY (districtid) REFERENCES location_districts (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM location_cities WHERE boroughid NOT IN (SELECT id FROM location_boroughs)");
$this->Execute("ALTER TABLE location_cities ADD FOREIGN KEY (boroughid) REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM location_streets WHERE cityid NOT IN (SELECT id FROM location_cities)");
$this->Execute("UPDATE location_streets SET typeid = NULL WHERE typeid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM location_street_types WHERE id = typeid)");
$this->Execute("ALTER TABLE location_streets ADD FOREIGN KEY (cityid) REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (typeid) REFERENCES location_street_types (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("DELETE FROM pna WHERE cityid NOT IN (SELECT id FROM location_cities) OR streetid NOT IN (SELECT id FROM location_streets)");
$this->Execute("ALTER TABLE pna ADD INDEX streetid (streetid)");
$this->Execute("ALTER TABLE pna ADD INDEX cityid (cityid)");
$this->Execute("ALTER TABLE pna ADD FOREIGN KEY (cityid) REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (streetid) REFERENCES location_streets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("UPDATE netdevices SET location_city = NULL WHERE location_city IS NOT NULL AND NOT EXISTS (SELECT 1 FROM location_cities WHERE id = location_city)");
$this->Execute("UPDATE netdevices SET location_street = NULL WHERE location_street IS NOT NULL AND NOT EXISTS (SELECT 1 FROM location_streets WHERE id = location_street)");
$this->Execute("UPDATE netdevices SET channelid = NULL WHERE channelid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM ewx_channels WHERE id = channelid)");
$this->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD FOREIGN KEY (channelid) REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("UPDATE nodes SET location_city = NULL WHERE location_city IS NOT NULL AND NOT EXISTS (SELECT 1 FROM location_cities WHERE id = location_city)");
$this->Execute("UPDATE nodes SET location_street = NULL WHERE location_street IS NOT NULL AND NOT EXISTS (SELECT 1 FROM location_streets WHERE id = location_street)");
$this->Execute("ALTER TABLE nodes ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("ALTER TABLE nodelocks ADD FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM rtattachments WHERE messageid NOT IN (SELECT id FROM rtmessages)");
$this->Execute("ALTER TABLE rtattachments ADD FOREIGN KEY (messageid) REFERENCES rtmessages (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM rtmessages WHERE ticketid NOT IN (SELECT id FROM rttickets)");
$this->Execute("ALTER TABLE rtmessages ADD FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM rtnotes WHERE ticketid NOT IN (SELECT id FROM rttickets) OR userid NOT IN (SELECT id FROM users)");
$this->Execute("ALTER TABLE rtnotes ADD FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("ALTER TABLE rttickets ADD FOREIGN KEY (queueid) REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM rtrights WHERE queueid NOT IN (SELECT id FROM rtqueues) OR userid NOT IN (SELECT id FROM users)");
$this->Execute("ALTER TABLE rtrights ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (queueid) REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM rtcategoryusers WHERE userid NOT IN (SELECT id FROM users) OR categoryid NOT IN (SELECT id FROM rtcategories)");
$this->Execute("ALTER TABLE rtcategoryusers ADD INDEX categoryid (categoryid)");
$this->Execute("ALTER TABLE rtcategoryusers ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (categoryid) REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM rtticketcategories WHERE ticketid NOT IN (SELECT id FROM rttickets) OR categoryid NOT IN (SELECT id FROM rtcategories)");
$this->Execute("ALTER TABLE rtticketcategories ADD INDEX categoryid (categoryid)");
$this->Execute("ALTER TABLE rtticketcategories ADD FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (categoryid) REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM promotionschemas WHERE promotionid NOT IN (SELECT id FROM promotions) OR ctariffid NOT IN (SELECT id FROM tariffs)");
$this->Execute("ALTER TABLE promotionschemas ADD FOREIGN KEY (promotionid) REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (ctariffid) REFERENCES tariffs (id) ON DELETE RESTRICT ON UPDATE CASCADE");
$this->Execute("DELETE FROM promotionassignments WHERE promotionschemaid NOT IN (SELECT id FROM promotionschemas) OR tariffid NOT IN (SELECT id FROM tariffs)");
$this->Execute("ALTER TABLE promotionassignments ADD FOREIGN KEY (promotionschemaid) REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM customerassignments WHERE customergroupid NOT IN (SELECT id FROM customergroups) OR customerid NOT IN (SELECT id FROM customers)");
$this->Execute("ALTER TABLE customerassignments ADD FOREIGN KEY (customergroupid) REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE,
	ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("UPDATE sourcefiles SET userid = NULL WHERE userid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE id = userid)");
$this->Execute("ALTER TABLE sourcefiles ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("UPDATE cashimport SET customerid = NULL WHERE customerid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM customers WHERE id = customerid)");
$this->Execute("UPDATE cashimport SET sourceid = NULL WHERE sourceid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cashsources WHERE id = sourceid)");
$this->Execute("UPDATE cashimport SET sourcefileid = NULL WHERE sourcefileid IS NOT NULL AND NOT EXISTS (SELECT 1 FROM sourcefiles WHERE id = sourcefileid)");
$this->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD FOREIGN KEY (sourceid) REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE,
	ADD FOREIGN KEY (sourcefileid) REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE");
$this->Execute("DELETE FROM imessengers WHERE customerid NOT IN (SELECT id FROM customers)");
$this->Execute("ALTER TABLE imessengers ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM customercontacts WHERE customerid NOT IN (SELECT id FROM customers)");
$this->Execute("ALTER TABLE customercontacts ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM excludedgroups WHERE customergroupid NOT IN (SELECT id FROM customergroups)");
$this->Execute("ALTER TABLE excludedgroups ADD FOREIGN KEY (customergroupid) REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE");
$this->Execute("DELETE FROM managementurls WHERE netdevid NOT IN (SELECT id FROM netdevices)");
$this->Execute("ALTER TABLE managementurls ADD FOREIGN KEY (netdevid) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013031800', 'dbversion'));
$this->CommitTrans();
