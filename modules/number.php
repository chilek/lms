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

if($doc = $DB->GetRow('SELECT number, cdate, type FROM documents WHERE id = ?', array($_GET['id'])))
{
	switch($doc['type'])
	{
		case 1:
			$ntempl = trans('Invoice No. $0',$CONFIG['invoices']['number_template']);
		break;
		case 2:
			$ntempl = trans('Cash Receipt No. $0',$CONFIG['receipts']['number_template']);
		break;
		default:
			$ntempl = '%N';
		break;
	}
	
	$ntempl = str_replace('%N',$doc['number'],$ntempl);
	$ntempl = str_replace('%M',date('m',$doc['cdate']),$ntempl);
	$ntempl = str_replace('%Y',date('Y',$doc['cdate']),$ntempl);

	$SMARTY->assign('content', '<NOBR>'.$ntempl.'</NOBR>');
	$SMARTY->display('dynpopup.html');
}

?>
