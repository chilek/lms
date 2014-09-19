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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id) {
	$args = array(
		'disabled' => !empty($_GET['access']) ? 0 : 1,
		$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMOSCHEMA] => $id
	);
	$DB->Execute('UPDATE promotionschemas SET disabled = ? WHERE id = ?',
		array_values($args));
	if ($SYSLOG) {
		$schema = $DB->GetRow('SELECT promotionid, ctariffid
			FROM promotionschemas WHERE id = ?', array($id));
		$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMO]] = $schema['promotionid'];
		$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]] = $schema['ctariffid'];
		$SYSLOG->AddMessage(SYSLOG_RES_PROMOSCHEMA, SYSLOG_OPER_UPDATE, $args,
			array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMOSCHEMA],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_PROMO],
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF]));
	}
}

header('Location: ?'.$SESSION->get('backto'));

?>
