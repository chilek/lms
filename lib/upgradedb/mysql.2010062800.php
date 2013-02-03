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

$DB->Execute("DELETE FROM rtattachments WHERE messageid NOT IN (SELECT id FROM rtmessages)");
$DB->Execute("ALTER TABLE rtattachments ALTER messageid DROP DEFAULT");
$DB->Execute("ALTER TABLE rtattachments ADD INDEX messageid (messageid)");
$DB->Execute("ALTER TABLE rtattachments ADD FOREIGN KEY (messageid)
		REFERENCES rtmessages (id) ON DELETE CASCADE ON UPDATE CASCADE");

$DB->Execute("DELETE FROM rttickets WHERE queueid NOT IN (SELECT id FROM rtqueues)");
$DB->Execute("ALTER TABLE rttickets ADD FOREIGN KEY (queueid)
		REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rttickets ALTER queueid DROP DEFAULT");

$DB->Execute("DELETE FROM rtmessages WHERE ticketid NOT IN (SELECT id FROM rttickets)");
$DB->Execute("ALTER TABLE rtmessages ADD FOREIGN KEY (ticketid)
		REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtmessages ALTER ticketid DROP DEFAULT");

$DB->Execute("DELETE FROM rtnotes WHERE ticketid NOT IN (SELECT id FROM rttickets)");
$DB->Execute("ALTER TABLE rtnotes ADD FOREIGN KEY (ticketid)
		REFERENCES rttickets (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtnotes ALTER ticketid DROP DEFAULT");

$DB->Execute("DELETE FROM rtnotes WHERE userid NOT IN (SELECT id FROM users)");
$DB->Execute("ALTER TABLE rtnotes ALTER userid DROP DEFAULT");
$DB->Execute("ALTER TABLE rtnotes ADD INDEX (userid)");
$DB->Execute("ALTER TABLE rtnotes ADD FOREIGN KEY (userid)
		REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");

$DB->Execute("DELETE FROM rtrights WHERE queueid NOT IN (SELECT id FROM rtqueues)");
$DB->Execute("ALTER TABLE rtrights ADD FOREIGN KEY (queueid)
		REFERENCES rtqueues (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtrights ALTER queueid DROP DEFAULT");

$DB->Execute("DELETE FROM rtrights WHERE userid NOT IN (SELECT id FROM users)");
$DB->Execute("ALTER TABLE rtrights ADD FOREIGN KEY (userid)
		REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE");
$DB->Execute("ALTER TABLE rtrights ALTER userid DROP DEFAULT");

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010062800', 'dbversion'));

$DB->CommitTrans();

?>
