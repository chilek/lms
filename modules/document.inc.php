<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

// support for dynamic loading of plugin javascript code
if (isset($_GET['template'])) {
	foreach ($documents_dirs as $doc)
		if (file_exists($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $_GET['template'])) {
			$doc_dir = $doc;
			continue;
		}
	// read template information
	if (file_exists($file =  $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
		. $_GET['template']  . DIRECTORY_SEPARATOR . 'info.php')) {
		include($file);
		if (file_exists($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
			. $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
			header('Content-Type: text/javascript');
			echo file_get_contents($file);
		}
	}
	die;
}

function _plugin($template, $customer, $JSResponse) {
	global $documents_dirs;
	
	$result = '';

	foreach ($documents_dirs as $doc)
		if (file_exists($doc . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template)) {
			$doc_dir = $doc;
			continue;
		}

	// read template information
	if (file_exists($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
		. $template . DIRECTORY_SEPARATOR . 'info.php'))
		include($file);

	// call plugin
	if (!empty($engine['plugin'])) {
		if (file_exists($file = $doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
			. $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.php'))
			include($file);
		if (file_exists($doc_dir . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
			. $engine['name'] . DIRECTORY_SEPARATOR . $engine['plugin'] . '.js')) {
			$JSResponse->removeScript($_SERVER['REQUEST_URI'] . '&template=' . $template);
			$JSResponse->includeScript($_SERVER['REQUEST_URI'] . '&template=' . $template);
		}
	}

	$JSResponse->assign('plugin', 'innerHTML', $result);
	$JSResponse->assign('title', 'value', isset($engine['form_title']) ? $engine['form_title'] : $engine['title']);
}

function plugin($template, $customer) {
	$JSResponse = new xajaxResponse();

	_plugin($template, $customer, $JSResponse);

	return $JSResponse;
}

function GetDocumentTemplates($rights, $type = NULL) {
	global $documents_dirs;

	$docengines = array();

	if (!$type)
		$types = $rights;
	elseif (in_array($type, $rights))
		$types = array($type);
	else
		return NULL;

	ob_start();
	foreach ($documents_dirs as $doc_dir){
		if ($dirs = getdir($doc_dir . '/templates', '^[a-z0-9_-]+$'))
			foreach ($dirs as $dir) {
				$infofile = $doc_dir . '/templates/' . $dir . '/info.php';
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
	}
	ob_end_clean();

	if (!empty($docengines))
		ksort($docengines);

	return $docengines;
}

function GetTemplates($doctype, $doctemplate, $JSResponse) {
	global $SMARTY;

	$DB = LMSDB::getInstance();
	$rights = $DB->GetCol('SELECT doctype FROM docrights WHERE userid = ? AND (rights & 2) = 2', array(Auth::GetCurrentUser()));
	$docengines = GetDocumentTemplates($rights, $doctype);
	$SMARTY->assign('docengines', $docengines);
	$SMARTY->assign('doctemplate', $doctemplate);
	$contents = $SMARTY->fetch('document/documenttemplateoptions.html');

	$JSResponse->assign('templ', 'innerHTML', $contents);
	if (!empty($doctype))
		$JSResponse->call('show_templates');
}

function GetDocumentNumberPlans($doctype, $customerid = null) {
	global $LMS;

	$DB = LMSDB::getInstance();

	if (!empty($doctype)) {
		$args = array(
			'doctype' => $doctype,
		);
		if (!empty($customerid)) {
			$args['customerid'] = array(
				'customerid' => $customerid,
			);
			$divisionid = $DB->GetOne('SELECT divisionid FROM customers WHERE id = ?', array($customerid));
			if (!empty($divisionid))
				$args['division'] = $divisionid;
		}
		$numberplans = $LMS->GetNumberPlans($args);
		if (empty($numberplans))
			$numberplans = array();
	} else
		$numberplans = array();

	return $numberplans;
}

function _GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse) {
	global $SMARTY;

	$numberplans = GetDocumentNumberPlans($doctype, $customerid);

	$SMARTY->assign('numberplans', $numberplans);
	$SMARTY->assign('numberplanid', $numberplanid);
	$SMARTY->assign('customerid', $customerid);
	$contents = $SMARTY->fetch('document/documentnumberplans.html');

	$JSResponse->assign('numberplans', 'innerHTML', $contents);
	$JSResponse->assign('numberplans', 'style', empty($numberplans) ? 'display: none' : 'display: inline');
}

function GetNumberPlans($doctype, $numberplanid, $customerid) {
	$JSResponse = new XajaxResponse();

	_GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse);

	return $JSResponse;
}

function GetReferenceDocuments($customerid, $JSResponse) {
	global $SMARTY, $LMS;

	$SMARTY->assign('cid', $customerid);
	$SMARTY->assign('document', array('reference' => ''));
	$SMARTY->assign('references', $LMS->GetDocuments($customerid));
	$template = $SMARTY->fetch('document/documentreference.html');

	$JSResponse->assign('referencedocument', 'innerHTML', $template);
}

function CustomerChanged($doctype, $doctemplate, $numberplanid, $customerid) {
	$JSResponse = new XajaxResponse();

	_plugin($doctemplate, $customerid, $JSResponse);
	_GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse);
	GetTemplates($doctype, $doctemplate, $JSResponse);
	GetReferenceDocuments($customerid, $JSResponse);

	return $JSResponse;
}

function DocTypeChanged($doctype, $doctemplate, $numberplanid, $customerid) {
	$JSResponse = new XajaxResponse();

	_GetNumberPlans($doctype, $numberplanid, $customerid, $JSResponse);
	GetTemplates($doctype, $doctemplate, $JSResponse);

	return $JSResponse;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('plugin', 'GetNumberPlans', 'DocTypeChanged', 'CustomerChanged'));
$SMARTY->assign('xajax', $LMS->RunXajax());

?>
