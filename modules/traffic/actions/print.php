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

$layout['pagetitle'] = trans('Printing');
        
$yearstart = date('Y', $DB->GetOne('SELECT MIN(dt) FROM stats'));
$yearend = date('Y', $DB->GetOne('SELECT MAX(dt) FROM stats'));
for ($i=$yearstart; $i<$yearend+1; $i++) {
    $statyears[] = $i;
}
for ($i=1; $i<13; $i++) {
    $months[$i] = strftime('%B', mktime(0, 0, 0, $i, 1));
}
    
$SMARTY->assign('currmonth', date('n'));
$SMARTY->assign('curryear', date('Y'));
$SMARTY->assign('statyears', $statyears);
$SMARTY->assign('months', $months);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
