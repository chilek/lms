<?php


/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
 *  $Id: syslog.php, 2012/11/02 19:21, Sylwester Kondracki Exp $
 *
 */


$layout['pagetitle'] = 'Logi systemowe';

$filter = array();

if (!isset($_GET['page'])) $SESSION->restore('sl_page',$_GET['page']);

if (!isset($_GET['sl_df']))	$SESSION->restore('sl_df',$filter['df']);	else $filter['df'] = $_GET['sl_df'];	$SESSION->save('sl_df',$filter['df']);
if (!isset($_GET['sl_dt']))	$SESSION->restore('sl_dt',$filter['dt']);	else $filter['dt'] = $_GET['sl_dt'];	$SESSION->save('sl_dt',$filter['dt']);
if (!isset($_GET['sl_mod']))	$SESSION->restore('sl_mod',$filter['mod']);	else $filter['mod'] = $_GET['sl_mod'];	$SESSION->save('sl_mod',$filter['mod']);
if (!isset($_GET['sl_ev']))	$SESSION->restore('sl_ev',$filter['ev']);	else $filter['ev'] = $_GET['sl_ev'];	$SESSION->save('sl_ev',$filter['ev']);
if (!isset($_GET['sl_us']))	$SESSION->restore('sl_us',$filter['us']);	else $filter['us'] = $_GET['sl_us'];	$SESSION->save('sl_us',$filter['us']);
if (!isset($_GET['sl_dus']))	$SESSION->restore('sl_dus',$filter['dus']);	else $filter['dus'] = $_GET['sl_dus'];	$SESSION->save('sl_dus',$filter['dus']);


$syslog = $DB->GetAll('SELECT s.*, u.login FROM syslog AS s JOIN users AS u ON (u.id = s.uid) WHERE 1=1 '
    .($filter['mod'] ? ' AND s.module='.$filter['mod'] : '')
    .($filter['ev'] ? ' AND s.event='.$filter['ev'] : '')
    .($filter['us'] ? ' AND s.uid='.$filter['us'] : '')
    .($filter['df'] ? ' AND s.cdate>='.strtotime($filter['df'].' 00:00:00') : '')
    .($filter['dt'] ? ' AND s.cdate<='.strtotime($filter['dt'].' 23:59:59') : '')
    .(!$filter['dus'] ? ' AND u.deleted=0 ' : '')
    .' ORDER BY s.cdate DESC');

$page = (!isset($_GET['page']) ? 1 : $_GET['page']);
$pagelimit = (! $LMS->CONFIG['phpui']['syslog_pagelimit'] ? 100 : $LMS->CONFIG['phpui']['syslog_pagelimit']);
$start = ($page - 1) * $pagelimit;

$SESSION->save('sl_page',$page);
$SESSION->save('backto',$_SERVER['QUERY_STRING']);

$listdata['total'] = sizeof($syslog);

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('page',$page);
$SMARTY->assign('start',$start);
$SMARTY->assign('filter',$filter);
$SMARTY->assign('users',$DB->GetAll('SELECT id, login, deleted FROM users'.(!$filter['dus'] ? ' WHERE deleted=0' : '').' ORDER BY login ASC;'));
$SMARTY->assign('syslog',$syslog);

$SMARTY->display('syslog.html');

?>