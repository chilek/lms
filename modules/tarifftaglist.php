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

$layout['pagetitle'] = trans('Tags list');

$tarifftaglist = $LMS->TarifftagGetList();

$listdata['total'] = empty($tarifftaglist) ? 0 : $tarifftaglist['total'];
$listdata['totalcount'] = empty($tarifftaglist) ? 0 : $tarifftaglist['totalcount'];

if (!empty($tarifftaglist)) {
    unset($tarifftaglist['total'], $tarifftaglist['totalcount']);
}

$SMARTY->assign('tarifftaglist', $tarifftaglist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('tariff/tarifftaglist.html');
