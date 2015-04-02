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

	$radiosectorname = $DB->GetOne('SELECT name FROM netradiosectors WHERE id = ?', array($link['radiosector']));

	$contents = "<IMG src=\"img/" . $bitmap
			. "\" alt=\"[ " . trans("Change connection properties") . " ]\" title=\"[ " . trans("Change connection properties") . " ]\""
			. " onmouseover=\"popup('<span class=&quot;nobr;&quot;>" . trans("Link type:") . " " . $LINKTYPES[$link['type']] . "<br>"
			. (!$isnetlink && $radiosectorname ? trans("Radio sector:") . " " . $radiosectorname . "<br>" : '')
			. ($link['technology'] ? trans("Link technology:") . " " . $LINKTECHNOLOGIES[$link['type']][$link['technology']] . "<br>" : '')
			. trans("Link speed:") . " " . $LINKSPEEDS[$link['speed']]
			. "</span>');\" onmouseout=\"pophide();\">";
	$result->call('update_netlink_info', $contents);

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
$radiosectors = ($isnetlink ? NULL :
	$DB->GetAll('SELECT id, name FROM netradiosectors WHERE netdev = ?', array($id)));
$SMARTY->assign('radiosectors', $radiosectors);
$SMARTY->display('netdev/netlinkproperties.html');

?>
