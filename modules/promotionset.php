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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id) {
    $args = array(
        'disabled' => !empty($_GET['access']) ? 0 : 1,
        SYSLOG::RES_PROMO => $id
    );
    $DB->Execute(
        'UPDATE promotions SET disabled = ? WHERE id = ?',
        array_values($args)
    );

    if ($SYSLOG) {
        $SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_UPDATE, $args);
    }
}

header('Location: ?'.$SESSION->get('backto'));
