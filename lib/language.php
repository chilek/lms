<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

function trans()
{
	global $_LANG;

	$args = func_get_args();
	$content = array_shift($args);

	if (is_array($content)) {
		$args = array_values($content);
		$content = array_shift($args);
	}

	if (isset($_LANG[$content]))
		$content = trim($_LANG[$content]);

	for ($i = 1, $len = count($args); $i <= $len; $i++) {
		$content = str_replace('$'.chr(97+$i-1), $args[$i-1], $content);
	}

	return $content;
}

$LANGDEFS = array(
		'pl' => array(
			'name' => 'Polish',
			'orig' => 'Polski',
			'locale' => 'pl_PL.UTF-8',
			'charset' => 'UTF-8',
			'html' => 'pl',
			'money_format' => '%01.2f zł',
//			'mobile' => '(88[0-9]|5[01][0-9]|6[069][0-9]|7[2789][0-9])[0-9]{6}',
			),
		'lt' => array(
			'name' => 'Lithuanian',
			'orig' => 'Litewski',
			'locale' => 'lt_LT.UTF-8',
			'charset' => 'UTF-8',
			'html' => 'lt',
			'money_format' => '%01.2f LT',
//			'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
			),
		'en' => array(
			'name' => 'English',
			'orig' => 'English',
			'locale' => 'en_US.UTF-8',
			'charset' => 'UTF-8',
			'html' => 'en',
			'money_format' => '$ %01.2f',
//			'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
			),
		'sk' => array(
			'name' => 'Slovak',
			'orig' => 'Slovenský',
			'locale' => 'sk_SK.UTF-8',
			'charset' => 'UTF-8',
			'html' => 'sk',
			'money_format' => '%01.2f EUR',
//			'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
			),
		'ro' => array(
			'name' => 'Romanian',
			'orig' => 'Romana',
			'locale' => 'ro_RO.UTF-8',
			'charset' => 'UTF-8',
			'html' => 'ro',
			'money_format' => '%01.2f RON',
//			'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
			),
		'cz' => array(
			'name' => 'Czech',
			'orig' => 'Česky',
			'locale' => 'cs_CZ.UTF-8',
			'charset' => 'UTF-8',
			'html' => 'cz',
			'money_format' => '%01.2f Kč',
//			'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
			),
		);

// UI language
if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
	$langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
else
	$langs = '';

$langs = explode(',', $langs);

foreach ($langs as $val)
{
	$val = substr($val, 0, 2);
	switch ($val)
	{
		case 'pl':
		case 'lt':
		case 'sk':
		case 'ro':
		case 'en':
		case 'cz':
			$_ui_language = $val;
			break 2;
	}
}

// System language
if(!empty($CONFIG['phpui']['lang']))
	$_language = $CONFIG['phpui']['lang'];
else if (!empty($_ui_language))
	$_language = $_ui_language;
else
	$_language = 'en'; // default language

// Use system lang for UI if any of browser langs isn't supported
// or browser langs aren't set
if (empty($_ui_language))
	$_ui_language = $_language;
$_LANG = array();

if (@is_readable(LIB_DIR.'/locale/'.$_ui_language.'/strings.php'))
	include(LIB_DIR.'/locale/'.$_ui_language.'/strings.php');
if (@is_readable(LIB_DIR.'/locale/'.$_ui_language.'/ui.php'))
	include(LIB_DIR.'/locale/'.$_ui_language.'/ui.php');
if (@is_readable(LIB_DIR.'/locale/'.$_language.'/system.php'))
	include(LIB_DIR.'/locale/'.$_language.'/system.php');

setlocale(LC_COLLATE, $LANGDEFS[$_language]['locale']);
setlocale(LC_CTYPE, $LANGDEFS[$_language]['locale']);
setlocale(LC_TIME, $LANGDEFS[$_language]['locale']);
setlocale(LC_NUMERIC, $LANGDEFS[$_language]['locale']);

mb_internal_encoding('UTF-8');

?>
