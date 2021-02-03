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

for ($i=4; $i<11; $i++) {
    $weekdays[] = strftime('%a', $i*86400);
}
for ($i=1; $i<13; $i++) {
    $months[] = strftime('%B', mktime(0, 0, 0, $i, 1, 1970));
}

$SMARTY->assign('months', $months);
$SMARTY->assign('weekdays', $weekdays);
$SMARTY->display('calendar.html');
