<?php
/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
$division = $LMS->GetDivision($id);

$args = array(
    'name'            => $division['name'],
    'shortname'       => $division['shortname'] . ' (copy)',
    'ten'             => $division['ten'],
    'regon'           => $division['regon'],
    'rbe'             => $division['rbe'] ? $division['rbe'] : '',
    'rbename'         => $division['rbename'] ? $division['rbename'] : '',
    'account'         => $division['account'],
    'inv_header'      => $division['inv_header'],
    'inv_footer'      => $division['inv_footer'],
    'inv_author'      => $division['inv_author'],
    'inv_cplace'      => $division['inv_cplace'],
    'inv_paytime'     => $division['inv_paytime'],
    'inv_paytype'     => $division['inv_paytype'] ? $division['inv_paytype'] : null,
    'description'     => $division['description'],
    'tax_office_code' => $division['tax_office_code'],
    'address_id'      => $division['address_id'] ? $division['address_id'] : null
);

$DB->Execute('INSERT INTO divisions (name, shortname,
	ten, regon, rbe, rbename, account, inv_header, inv_footer, inv_author,
	inv_cplace, inv_paytime, inv_paytype, description, tax_office_code, address_id)
	VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array_values($args));

if ($SYSLOG) {
    $args[SYSLOG::RES_DIV] = $DB->GetLastInsertID('divisions');
    $SYSLOG->AddMessage(SYSLOG::RES_DIV, SYSLOG::OPER_ADD, $args);
}

$SESSION->redirect('?m=divisionlist');
