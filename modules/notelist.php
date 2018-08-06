<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$layout['pagetitle'] = trans('Debit Notes List<!long>');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SESSION->restore('dnlm', $marks);
if(isset($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$marks[$id] = $mark;
$SESSION->save('dnlm', $marks);

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('dnls', $s);
$SESSION->save('dnls', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('dnlo', $o);
$SESSION->save('dnlo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('dnlc', $c);
$SESSION->save('dnlc', $c);

if (isset($_POST['search']))
	$h = isset($_POST['hideclosed']);
elseif (($h = $SESSION->get('dnlh')) === NULL)
	$h = ConfigHelper::checkConfig('notes.hide_closed');
$SESSION->save('dnlh', $h);

if(isset($_POST['group'])) {
	$g = $_POST['group'];
	$ge = isset($_POST['groupexclude']) ? $_POST['groupexclude'] : NULL;
} else {
	$SESSION->restore('dnlg', $g);
	$SESSION->restore('dnlge', $ge);
}
$SESSION->save('dnlg', $g);
$SESSION->save('dnlge', $ge);

if($c == 'cdate' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $s))
{
	list($year, $month, $day) = explode('/', $s);
	$s = mktime(0,0,0, $month, $day, $year);
}
elseif($c == 'month' && $s && preg_match('/^[0-9]{4}\/[0-9]{2}$/', $s))
{
	list($year, $month) = explode('/', $s);
        $s = mktime(0,0,0, $month, 1, $year);
}

$total = intval($LMS->GetNoteList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude' => $ge,
	'hideclosed' => $h, 'order' => $o, 'count' => true)));

$limit = intval(ConfigHelper::getConfig('phpui.debitnotelist_pagelimit', $total));
$page = !isset($_GET['page']) ? 1 : intval($_GET['page']);
$offset = ($page - 1) * $limit;

$notelist = $LMS->GetNoteList(array('search' => $s, 'cat' => $c, 'group' => $g, 'exclude' => $ge,
	'hideclosed' => $h, 'order' => $o, 'count' => false, 'offset' => $offset, 'limit' => $limit));

$pagination = LMSPaginationFactory::getPagination($page, $total, $limit, ConfigHelper::checkConfig('phpui.short_pagescroller'));

$SESSION->restore('dnlc', $listdata['cat']);
$SESSION->restore('dnls', $listdata['search']);
$SESSION->restore('dnlg', $listdata['group']);
$SESSION->restore('dnlge', $listdata['groupexclude']);
$SESSION->restore('dnlh', $listdata['hideclosed']);

$listdata['order'] = $notelist['order'];
$listdata['direction'] = $notelist['direction'];

unset($notelist['order']);
unset($notelist['direction']);

if($note = $SESSION->get('noteprint'))
{
        $SMARTY->assign('note', $note);
        $SESSION->remove('noteprint');
}

$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('marks', $marks);
$SMARTY->assign('grouplist', $LMS->CustomergroupGetAll());
$SMARTY->assign('notelist', $notelist);
$SMARTY->display('note/notelist.html');

?>
