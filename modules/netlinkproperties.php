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

function GetNetLinkRadioSectors($dev1, $dev2) {
	global $DB;

	$result = $DB->GetRow('SELECT (CASE src WHEN ? THEN dstradiosector ELSE srcradiosector END) AS srcradiosector,
		(CASE src WHEN ? THEN srcradiosector ELSE dstradiosector END) AS dstradiosector
		FROM netlinks
		WHERE (src = ? AND dst = ?) OR (dst = ? AND src = ?)',
		array($dev1, $dev1, $dev1, $dev2, $dev1, $dev2));
	if (empty($result))
		$result = array();

	$result['dst'] = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ? ORDER BY name', array($dev1));
	$result['src'] = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ? ORDER BY name', array($dev2));

	return $result;
}

function update_netlink_properties($id, $devid, $link) {
	global $LMS, $DB, $LINKTYPES, $LINKTECHNOLOGIES, $LINKSPEEDS;

	$result = new xajaxResponse();

	$isnetlink = intval($_GET['isnetlink']);
	if ($isnetlink)
		$LMS->SetNetDevLinkType($id, $devid, $link);
	else
		$LMS->SetNodeLinkType($devid, $link);

	switch ($link['type']) {
		case 0: case 2:
			$bitmap = 'netdev_takenports.gif';
			break;
		case 1:
			$bitmap = 'wireless.gif';
	}

	if ($isnetlink) {
		$srcradiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['srcradiosector']));
		$dstradiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['dstradiosector']));
	} else
		$radiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['radiosector']));

	$content1 = ($link['technology'] ? $LINKTECHNOLOGIES[$link['type']][$link['technology']]
			. (!$isnetlink ? ($radiosectorname ? " ($radiosectorname)" : '')
				: ($srcradiosectorname || $dstradiosectorname ? ' ('
					. ($srcradiosectorname ? $srcradiosectorname : '-')
					. '/' . ($dstradiosectorname ? $dstradiosectorname : '-') . ')' : ''))
			: '')
		. '<br>' . $LINKSPEEDS[$link['speed']];

	$content2 = "<IMG src=\"img/" . $bitmap
			. "\" alt=\"[ " . trans("Change connection properties") . " ]\" title=\"[ " . trans("Change connection properties") . " ]\""
			. " onmouseover=\"popup('<span class=&quot;nobr;&quot;>" . trans("Link type:") . " " . $LINKTYPES[$link['type']] . "<br>"
			. (!$isnetlink ? ($radiosectorname ? trans("Radio sector:") . " " . $radiosectorname . "<br>" : '')
				: ($srcradiosectorname ? trans("Radio sector:") . " " . $srcradiosectorname . "<br>" : '')
					. ($dstradiosectorname ? trans("Destination radio sector:") . " " . $dstradiosectorname . "<br>" : ''))
			. ($link['technology'] ? trans("Link technology:") . " " . $LINKTECHNOLOGIES[$link['type']][$link['technology']] . "<br>" : '')
			. trans("Link speed:") . " " . $LINKSPEEDS[$link['speed']]
			. "</span>');\" onmouseout=\"pophide();\">";

	$result->call('update_netlink_info', $content1, $content2);

	return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('update_netlink_properties');
$SMARTY->assign('xajax', $LMS->RunXajax());

$layout['pagetitle'] = trans('Select link properties');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$devid = isset($_GET['devid']) ? intval($_GET['devid']) : 0;
$isnetlink = isset($_GET['isnetlink']) ? intval($_GET['isnetlink']) : 0;

if ($isnetlink)
	$link = $LMS->GetNetDevLinkType($id, $devid);
else
	$link = $DB->GetRow("SELECT linktype AS type, linktechnology AS technology,
		linkspeed AS speed, linkradiosector AS radiosector FROM nodes
		WHERE netdev = ? AND id = ?", array($id, $devid));

$link['id'] = $id;
$link['devid'] = $devid;
$link['isnetlink'] = $isnetlink;

$SMARTY->assign('link', $link);
if ($isnetlink)
	$radiosectors = GetNetLinkRadioSectors($id, $devid);
else
	$radiosectors = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ? ORDER BY name', array($id));
$SMARTY->assign('radiosectors', $radiosectors);
$SMARTY->display('netdev/netlinkproperties.html');

?>
