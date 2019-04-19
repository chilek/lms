<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if (isset($_GET['id'])) {
	if (is_array($_GET['id']))
		$ids = $_GET['id'];
	else
		$ids = array($_GET['id']);
	$ids = Utils::filterIntegers($ids);
} elseif (isset($_GET['marks']) && isset($_POST['marks'])) {
	if ($_GET['marks'] == 'invoice')
		$marks = $_POST['marks'];
	else {
		$marks = array();
		if (isset($_POST['marks']['invoice']))
			$marks = $_POST['marks']['invoice'];
		if (isset($_POST['marks']['note']))
			$marks = array_merge($marks, $_POST['marks']['note']);
	}

	$ids = Utils::filterIntegers($marks);
	if (!empty($ids) && (!isset($_GET['marks']) || $_GET['marks'] == 'cash'))
		$ids = $LMS->GetDocumentsForBalanceRecords($ids, array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO, DOC_DNOTE));
}

if (empty($ids))
	$SESSION->redirect($_SERVER['HTTP_REFERER']);

foreach ($ids as $id)
	$LMS->DeleteArchiveTradeDocument($id);

$SESSION->redirect($_SERVER['HTTP_REFERER']);
