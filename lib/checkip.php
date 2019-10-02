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

// Checking if connection is from allowed IP

$allow_from = ConfigHelper::getConfig('phpui.allow_from', null);

if ($allow_from) {
    // delete ipv6 prefix if it's present:

    $ipaddr = str_replace('::ffff:', '', $_SERVER['REMOTE_ADDR']);

    if (!Utils::isAllowedIP($ipaddr, $allow_from)) {
        header('HTTP/1.1 403 Forbidden');
        echo '<!DOCTYPE html>
		<HTML><HEAD>
		<TITLE>403 Forbidden</TITLE>
		</HEAD><BODY>
		<H1>Forbidden</H1>
		You don\'t have permission to access ' . $_SERVER['REQUEST_URI'] . '
		on this server.<P>
		<HR>
		' . $_SERVER['SERVER_SIGNATURE'] . '
		</BODY></HTML>';
        exit(0);
    }
}
