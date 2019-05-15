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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (isset($_POST['search'])) {
    $voipaccountsearch = $_POST['search'];
}

if (!isset($voipaccountsearch)) {
    $SESSION->restore('voipaccountsearch', $voipaccountsearch);
} else {
    $SESSION->save('voipaccountsearch', $voipaccountsearch);
}

if (!isset($_GET['o'])) {
    $SESSION->restore('vaslo', $o);
} else {
    $o = $_GET['o'];
}
$SESSION->save('vaslo', $o);

if (!isset($_POST['k'])) {
    $SESSION->restore('vaslk', $k);
} else {
    $k = $_POST['k'];
}
$SESSION->save('vaslk', $k);

if (isset($_GET['search'])) {
    $layout['pagetitle'] = trans('Voip Account Search Results');

    $voipaccountlist = $LMS->GetVoipAccountList($o, $voipaccountsearch, $k);

    $listdata['total'] = $voipaccountlist['total'];
    $listdata['order'] = $voipaccountlist['order'];
    $listdata['direction'] = $voipaccountlist['direction'];

    unset($voipaccountlist['total']);
    unset($voipaccountlist['order']);
    unset($voipaccountlist['direction']);
    
    if ($SESSION->is_set('vaslp') && !isset($_GET['page'])) {
        $SESSION->restore('vaslp', $_GET['page']);
    }
        
    $page = (!isset($_GET['page']) ? 1 : $_GET['page']);
    
    $pagelimit = ConfigHelper::getConfig('phpui.voipaccountlist_pagelimit', $listdata['total']);
    $start = ($page - 1) * $pagelimit;
    $SESSION->save('vaslp', $page);
    
    $SMARTY->assign('page', $page);
    $SMARTY->assign('pagelimit', $pagelimit);
    $SMARTY->assign('start', $start);
    $SMARTY->assign('voipaccountlist', $voipaccountlist);
    $SMARTY->assign('listdata', $listdata);
    
    if (isset($_GET['print'])) {
        $SMARTY->display('print/printvoipaccountlist.html');
    } elseif ($listdata['total']==1) {
        $SESSION->redirect('?m=voipaccountinfo&id='.$voipaccountlist[0]['id']);
    } else {
        $SMARTY->display('voipaccount/voipaccountsearchresults.html');
    }
} else {
    $layout['pagetitle'] = trans('Voip Accounts Search');

    $SESSION->remove('vaslp');

    $SMARTY->assign('k', $k);
    $SMARTY->display('voipaccount/voipaccountsearch.html');
}
