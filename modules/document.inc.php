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

function plugin($template, $customer) {
	$result = '';

	// xajax response object, can be used in the plugin
	$JSResponse = new xajaxResponse();

	// read template information
	if (file_exists($file = DOC_DIR . '/templates/' . $template . '/info.php'))
		include($file);
	// call plugin
	if (!empty($engine['plugin']) && file_exists($file = DOC_DIR . '/templates/' . $engine['name'] . '/' . $engine['plugin'] . '.php'))
		include($file);

	$JSResponse->assign('plugin', 'innerHTML', $result);
	return $JSResponse;
}

function GetDocumentTemplates($rights, $type = NULL) {
	$docengines = array();

	if (!$type)
		$types = $rights;
	elseif (in_array($type, $rights))
		$types = array($type);
	else
		return NULL;

	if ($dirs = getdir(DOC_DIR . '/templates', '^[a-z0-9_-]+$'))
		foreach ($dirs as $dir) {
			$infofile = DOC_DIR . '/templates/' . $dir . '/info.php';
			if (file_exists($infofile)) {
				unset($engine);
				include($infofile);
				if (isset($engine['type'])) {
					if (!is_array($engine['type']))
						$engine['type'] = array($engine['type']);
					$intersect = array_intersect($engine['type'], $types);
					if (!empty($intersect))
						$docengines[$dir] = $engine;
				} else
					$docengines[$dir] = $engine;
			}
		}

	if (!empty($docengines))
		ksort($docengines);

	return $docengines;
}

function GetTemplates($type) {
	global $SMARTY, $DB, $AUTH;

	$rights = $DB->GetCol('SELECT doctype FROM docrights WHERE userid = ? AND (rights & 2) = 2', array($AUTH->id));
	$docengines = GetDocumentTemplates($rights, $type);
	$SMARTY->assign('docengines', $docengines);
	$contents = $SMARTY->fetch('documenttemplateoptions.html');

	$JSResponse = new xajaxResponse();
	$JSResponse->assign('template', 'innerHTML', $contents);

	return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('plugin', 'GetTemplates'));
$SMARTY->assign('xajax', $LMS->RunXajax());

?>
