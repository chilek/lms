<?php

/*
 * LMS version 1.11-cvs
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

if(!empty($_POST['marks']))
{
	$marks = array();
        foreach($_POST['marks'] as $id => $mark)
    		$marks[] = intval($mark);

        if ($list = $DB->GetAll('SELECT c.filename, c.md5sum, c.contenttype
		FROM documentcontents c
		JOIN documents d ON (d.id = c.docid)
		JOIN docrights r ON (r.doctype = d.type)
		WHERE c.docid IN ('.implode(',', $marks).')
			AND r.userid = ? AND (r.rights & 1) = 1', array($AUTH->id)))
	{
		$ctype = $list[0]['contenttype'];

		if (!preg_match('/^text/i', $ctype))
		{
			if (sizeof($list))
				    die('Currently you can only print many documents of type text/html!');

			header('Content-Disposition: attachment; filename='.$list[0]['filename']);
			header('Pragma: public');
		}
		header('Content-Type: '.$ctype);
		
		$i = 0;
		foreach ($list as $doc)
		{
			// we can display only documents with the same content type
			if($doc['contenttype'] != $ctype)
				continue;
			
			$filename = DOC_DIR.'/'.substr($doc['md5sum'],0,2).'/'.$doc['md5sum'];
			if(file_exists($filename))
			{
				if ($i && preg_match('/html/i', $doc['contenttype']))
					echo '<div style="page-break-after: always;">&nbsp;</div>';
				
				readfile($filename);
			}
			$i++;
		}
		die;
	}
} elseif($doc = $DB->GetRow('SELECT c.filename, c.md5sum, c.contenttype
	FROM documentcontents c
	JOIN documents d ON (d.id = c.docid)
	JOIN docrights r ON (r.doctype = d.type)
	WHERE c.docid = ? AND r.userid = ? AND (r.rights & 1) = 1', array($_GET['id'], $AUTH->id)))
{
	$filename = DOC_DIR.'/'.substr($doc['md5sum'],0,2).'/'.$doc['md5sum'];
	if(file_exists($filename))
	{	
		header('Content-Type: '.$doc['contenttype']);
	
		if(!preg_match('/^text/i', $doc['contenttype']) || !empty($_GET['save']))
		{
			header('Content-Disposition: attachment; filename='.$doc['filename']);
			header('Pragma: public');
		}
		
		readfile($filename);
	}
	die;
}

$SMARTY->display('noaccess.html');

?>
