<?php

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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
function microtime_diff($a, $b)
{
	list($a_usec, $a_sec) = explode(' ', $a);
	list($b_usec, $b_sec) = explode(' ', $b);
	return $b_sec - $a_sec + $b_usec - $a_usec;
}
 
$layout['pagetitle'] = 'SQL';

if($query = $_POST['query'])
{
	$t = microtime();
	$rows = $LMS->DB->Execute($query);
	$duration = microtime_diff($t, microtime());
	
	if(sizeof($LMS->DB->errors)) 
	{
		$error['query'] = 'Zapytanie nie jest poprawne!';
		$SMARTY->assign('error', $error);
		$SMARTY->assign('query', $query);
		$SMARTY->display('sql.html');	
		die;
	}
	
	if( ! eregi('^SELECT',$query) && ! eregi('^EXPLAIN',$query))
	{
		$SMARTY->display('header.html');
		$SMARTY->display('adminheader.html');
		echo '<H1>SQL - Wyniki zapytania</H1>';		
		echo '<B>Zapytanie dotyczy³o '.$rows.' wiersza(y).</B><BR>';
		printf('<B>Czas wykonania: %0.3f sek.</B>',$t);
		$SMARTY->display('footer.html');
		die;
	}

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
	}

	$SMARTY->display('header.html');
	$SMARTY->display('adminheader.html');
	echo '<H1>SQL - Wyniki zapytania</H1>';		
	echo '<P><TABLE CELLPADDING="3"><TR><TD CLASS="fall">';
	echo '<TABLE CELLPADDING="3">';
	echo '<TR CLASS="DARK">';
	foreach($colnames as $column)	
	{
		echo "<TD ALIGN=\"center\"><B>$column</B></TD>";
	}
	echo '</TR>';
	
	while($row = $LMS->DB->_driver_fetchrow_assoc())
	{
		echo '<TR CLASS="LIGHT">';
		foreach($colnames as $column)	
		{
			echo '<TD>'.$row[$column].'</TD>';
		}
		echo '</TR>';
	}
	
	echo '</TABLE>';
	echo '</TD></TR></TABLE></P>';
	printf('<B>Czas wykonania: %0.3f sek.</B>',$t);
	$SMARTY->display('footer.html');
	die;
}

$SMARTY->assign('query', $query);
$SMARTY->display('sql.html');

?>