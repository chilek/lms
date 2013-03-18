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

$DB->BeginTrans();

$DB->Execute("DELETE FROM assignments WHERE customerid NOT IN (SELECT id FROM customers)");
$DB->Execute("ALTER TABLE assignments ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE assignments ADD FOREIGN KEY (numberplanid) REFERENCES numberplans (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE location_districts ADD FOREIGN KEY (stateid) REFERENCES location_states (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE location_boroughs ADD FOREIGN KEY (districtid) REFERENCES location_districts (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE location_cities ADD FOREIGN KEY (boroughid) REFERENCES location_boroughs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE location_streets ADD FOREIGN KEY (cityid) REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE location_streets ADD INDEX (typeid)");
$DB->Execute("ALTER TABLE location_streets ADD FOREIGN KEY (typeid) REFERENCES location_street_types (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE pna ADD INDEX (streetid)");
$DB->Execute("ALTER TABLE pna ADD INDEX (cityid)");
$DB->Execute("ALTER TABLE pna ADD FOREIGN KEY (cityid) REFERENCES location_cities (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE pna ADD FOREIGN KEY (streetid) REFERENCES location_streets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE netdevices ADD FOREIGN KEY (channelid) REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE nodes ADD FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE nodes ADD FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE nodelocks ADD INDEX (nodeid)");
$DB->Execute("ALTER TABLE nodelocks ADD FOREIGN KEY (nodeid) REFERENCES nodes (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtattachments ADD FOREIGN KEY (messageid) REFERENCES rtmessages (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("DELETE FROM rtmessages WHERE ticketid NOT IN (SELECT id FROM rttickets)");
$DB->Execute("ALTER TABLE rtmessages ADD FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("DELETE FROM rtnotes WHERE ticketid NOT IN (SELECT id FROM rttickets)");
$DB->Execute("ALTER TABLE rtnotes ADD FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtnotes ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rttickets ADD FOREIGN KEY (queueid) REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtrights ADD INDEX (queueid)");
$DB->Execute("ALTER TABLE rtrights ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtrights ADD FOREIGN KEY (queueid) REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtcategoryusers ADD INDEX (categoryid)");
$DB->Execute("ALTER TABLE rtcategoryusers ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtcategoryusers ADD FOREIGN KEY (categoryid) REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("DELETE FROM rtticketcategories WHERE ticketid NOT IN (SELECT id FROM rttickets)");
$DB->Execute("ALTER TABLE rtticketcategories ADD INDEX (categoryid)");
$DB->Execute("ALTER TABLE rtticketcategories ADD FOREIGN KEY (ticketid) REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtticketcategories ADD FOREIGN KEY (categoryid) REFERENCES rtcategories (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("DELETE FROM promotionschemas WHERE promotionid NOT IN (SELECT id FROM promotions)");
$DB->Execute("ALTER TABLE promotionschemas ADD FOREIGN KEY (promotionid) REFERENCES promotions (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE promotionschemas ADD FOREIGN KEY (ctariffid) REFERENCES tariffs (id) ON DELETE RESTRICT ON UPDATE CASCADE");
$DB->Execute("DELETE FROM promotionassignments WHERE promotionschemaid NOT IN (SELECT id FROM promotionschemas)");
$DB->Execute("DELETE FROM promotionassignments WHERE tariffid NOT IN (SELECT id FROM tariffs)");
$DB->Execute("ALTER TABLE promotionassignments ADD FOREIGN KEY (promotionschemaid) REFERENCES promotionschemas (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE promotionassignments ADD FOREIGN KEY (tariffid) REFERENCES tariffs (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE customerassignments ADD FOREIGN KEY (customergroupid) REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE customerassignments ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE sourcefiles ADD FOREIGN KEY (userid) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("DELETE FROM cashimport WHERE customerid NOT IN (SELECT id FROM customers)");
$DB->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (sourceid) REFERENCES cashsources (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE cashimport ADD FOREIGN KEY (sourcefileid) REFERENCES sourcefiles (id) ON DELETE SET NULL ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE imessengers ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE customercontacts ADD FOREIGN KEY (customerid) REFERENCES customers (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE excludedgroups ADD FOREIGN KEY (customergroupid) REFERENCES customergroups (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("DELETE FROM managementurls WHERE netdevid NOT IN (SELECT id FROM netdevices)");
$DB->Execute("ALTER TABLE managementurls ADD INDEX (netdevid)");
$DB->Execute("ALTER TABLE managementurls ADD FOREIGN KEY (netdevid) REFERENCES netdevices (id) ON DELETE CASCADE ON UPDATE CASCADE");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2013031800', 'dbversion');
$DB->CommitTrans();

?>
