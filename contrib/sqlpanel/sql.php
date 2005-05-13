<?php

/*
 * LMS version 1.7-cvs
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

include('sqllang.php');
 
$layout['pagetitle'] = trans('SQL');

if($query = $_POST['query'])
{
	$pagelimit = ( $_CONFIG['phpui']['sqlpanel_pagelimit'] ? $_CONFIG['lmsui']['sqlpanel_pagelimit'] : 50 );
	$page = (! $_GET['page'] ? 1 : $_GET['page']); 
	$start = ($page - 1) * $pagelimit;
	$words = array('SELECT','EXPLAIN','SHOW','DESCRIBE','ANALYZE','CHECK','OPTIMIZE','REPAIR','VACUUM');

	$t = getmicrotime();
	$rows = $LMS->DB->Execute($query);
	$duration = getmicrotime() - $t;

	if(sizeof($LMS->DB->errors)) 
	{
		$error['query'] = trans('Query is not correct!');
		$SMARTY->assign('error', $error);
		$SMARTY->assign('query', $query);
		$SMARTY->display('sql.html');	
		die;
	}
		
	list($firstword) = explode(' ', trim($query));

	if( ! in_array(strtoupper($firstword), $words) )
	{
		$nrows = $rows;
	} 
	else
	{
		unset($result);

		switch($_CONFIG['database']['type'])
		{
		case 'postgres':
			$cols = pg_num_fields($LMS->DB->_result);
			for($i=0; $i < $cols; $i++)
				$colnames[] = pg_field_name($LMS->DB->_result, $i);
		break;
		case 'mysql':
			$cols = mysql_num_fields($LMS->DB->_result);
			for($i=0; $i < $cols; $i++)
				$colnames[] = mysql_field_name($LMS->DB->_result, $i);
		break;
		case 'sqlite':
			$cols = sqlite_num_fields($LMS->DB->_result);
			for($i=0; $i < $cols; $i++)
				$colnames[] = sqlite_field_name($LMS->DB->_result, $i);
		break;
		}

		$i = 0;
		while($row = $LMS->DB->_driver_fetchrow_assoc())
		{
			$i++;
			if ( $i > $start && $i < ($start+$pagelimit+1) )
			{
				$result .= '<TR CLASS="LIGHT"><TD CLASS="BLEND">'.$i.'</TD>';
				foreach($colnames as $column)	
				{
					$result .= '<TD>'.nl2br(htmlspecialchars($row[$column])).'</TD>';
				}
				$result .= '</TR>';
			}
		}
		$nrows = $i;
	}

	$SMARTY->assign('start', $start);
	$SMARTY->assign('page', $page);
	$SMARTY->assign('pagelimit', $pagelimit);
	$SMARTY->assign('nrows', $nrows);
	$SMARTY->assign('ncols', $cols+1);
	$SMARTY->assign('colnames', $colnames);
	$SMARTY->assign('executetime', $duration);
	$SMARTY->assign('result', $result);
	$layout['pagetitle'] = trans('SQL - Query Results');
}

$SMARTY->assign('query', $query);

if($_GET['print'])
	$SMARTY->display('sqlprint.html');
else
	$SMARTY->display('sql.html');

?>


