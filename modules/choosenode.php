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

$layout['pagetitle'] = trans('Select node');

$p = isset($_GET['p']) ? $_GET['p'] : '';
$js = '';
$customerid = intval($_GET['id']);

if (!$p || $p == 'main') {
        $SMARTY->assign('js', 'var targetfield = window.parent.targetfield;');
}

$SMARTY->assign('customerid', $customerid);
$SMARTY->assign('part', $p);
$SMARTY->assign('nodelist', $LMS->GetCustomerNodes(intval($_GET['id'])));
$SMARTY->display('choose/choosenode.html');
