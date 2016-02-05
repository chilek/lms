<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2016 LMS Developers
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


$invprojectlist = $DB->GetAll('SELECT ip.id, ip.name, ip.divisionid, 
                (SELECT COUNT(*) FROM nodes WHERE invprojectid = ip.id) AS nodes,
                (SELECT COUNT(*) FROM netnodes WHERE invprojectid = ip.id) AS netnodes
                FROM invprojects ip
                WHERE ip.type <> ?
                ORDER BY ip.name', array(INV_PROJECT_SYSTEM));

$listdata['total'] = sizeof($invprojectlist);
  
if ($SESSION->is_set('ciplp') && !isset($_GET['page']))
        $SESSION->restore('ciplp', $_GET['page']);

$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = ConfigHelper::getConfig('phpui.invprojectlist_pagelimit', $listdata['total']);
$start = ($page - 1) * $pagelimit;
   
$SESSION->save('ciplp', $page);

$layout['pagetitle'] = trans('Investment projects list');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('invprojectlist', $invprojectlist);
$SMARTY->assign('divisions', $DB->GetAll('SELECT id, shortname FROM divisions ORDER BY shortname'));
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('invproject/invprojectlist.html');

?>
