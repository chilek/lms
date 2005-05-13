<?php

/*
 * LMS version 1.6-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
	global $_LANG, $SMARTY;

	$content = func_get_arg(0);

	if(isset($_LANG[$content]))
		$content = trim($_LANG[$content]);
	else
		if(!in_array($content, (array) $SMARTY->_tpl_vars['missing_strings']) && $content !='')
			$SMARTY->_tpl_vars['missing_strings'][] = $content;

	$argc = func_num_args();
	for($i = 1; $i < $argc; $i++)
	{
		$arg = func_get_arg($i);
		$content = str_replace('$'.($i-1), $arg, $content);
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
			'money_format' => '%01.2f zł'
			),
		'en' => array(
			'name' => 'English',
			'orig' => 'English',
			'locale' => 'en_US',
			'charset' => 'UTF-8',
			'html' => 'en',
			'money_format' => '$ %01.2f'
			),
		);

$_language = 'en'; // default language

$langs = explode(',', ($_CONFIG['phpui']['lang'] ? $_CONFIG['phpui']['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE']));

foreach ($langs as $val) 
{
	switch (substr($val, 0, 2))
	{
		case 'pl':
			$_language = 'pl';
    			break 2;
		case 'en':
			$_language = 'en';
			break 2;
	}
}

$_LANG = array();

@include($_LIB_DIR.'/locale/'.$_language.'/strings.php');
@include($_LIB_DIR.'/locale/'.$_language.'/functions.php');

setlocale(LC_COLLATE, $LANGDEFS[$_language]['locale']);
setlocale(LC_CTYPE, $LANGDEFS[$_language]['locale']);
setlocale(LC_TIME, $LANGDEFS[$_language]['locale']);
setlocale(LC_NUMERIC, $LANGDEFS[$_language]['locale']);

?>
