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

$id = !empty($_GET['id']) ? $_GET['id'] : null;

if (!$id || !$LMS->TarifftagExists($id)) {
    $SESSION->redirect('?m=tarifftaglist');
}

$tarifftag = $LMS->TarifftagGet($id);

$tariffs = $LMS->GetTariffWithoutTagNames($id);

$tariffscount = count($tariffs);

$layout['pagetitle'] = trans('Tag Info: $a', $tarifftag['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('tarifftag', $tarifftag);
$SMARTY->assign('tariffs', $tariffs);
$SMARTY->assign('tariffscount', $tariffscount);
$SMARTY->display('tariff/tarifftaginfo.html');
