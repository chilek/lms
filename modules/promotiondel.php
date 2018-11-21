<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$id = intval($_GET['id']);

if ($id && $_GET['is_sure'] == '1') {
	$args = array(SYSLOG::RES_PROMO => $id);
	if ($SYSLOG) {
		$SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_DELETE, $args);
		$schemas = $DB->GetAll('SELECT id, ctariffid FROM promotionschemas
			WHERE promotionid = ?', array_values($args));
		if (!empty($schemas))
			foreach ($schemas as $schema) {
				$args[SYSLOG::RES_PROMOSCHEMA] = $schema['id'];
				$args[SYSLOG::RES_TARIFF] = $schema['ctariffid'];
				$SYSLOG->AddMessage(SYSLOG::RES_PROMOSCHEMA, SYSLOG::OPER_DELETE, $args);
				$assigns = $DB->GetCol('SELECT id FROM promotionassignments
					WHERE promotionschemaid = ?', array($schema['id']));
				if (!empty($assigns))
					foreach ($assigns as $assign) {
						$args[SYSLOG::RES_PROMOASSIGN] = $assign;
						$SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
					}
			}
	}
	$DB->Execute('DELETE FROM promotions WHERE id = ?', array($id));
}

$SESSION->redirect('?m=promotionlist');

?>
