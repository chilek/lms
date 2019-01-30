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
/**
 * @author Maciej_Wawryk
 */
$id = ($_GET['id']);
$DB->Execute('INSERT INTO promotions (name, description, disabled)
	SELECT ' . $DB->Concat('name',"' (".trans('copy').")'"). ', description, disabled
	FROM promotions WHERE id = ?', array($id));
$newid = $DB->GetLastInsertID('promotions');
$schemas = $DB->GetAll('SELECT * FROM promotionschemas WHERE promotionid = ?', array($id));
if($schemas) foreach ($schemas as $schema) {
    $DB->Execute('INSERT INTO promotionschemas (name, description, data, promotionid, disabled, continuation, ctariffid) VALUES (?, ?, ?, ?, ?, ?, ?)
    ', array(
        $schema['name'], $schema['description'],
        $schema['data'], $newid, $schema['disabled'],
        $schema['continuation'], $schema['ctariffid']
    ));
    $schemaid = $DB->GetLastInsertID('promotionschemas');
    $DB->Execute('INSERT INTO promotionassignments (promotionschemaid, tariffid, data, optional, label, orderid)
        SELECT ?, tariffid, data, optional, label, orderid
        FROM promotionassignments WHERE promotionschemaid = ?', array($schemaid, $schema['id']));
}

$SESSION->redirect('?m=promotionlist');

?>
