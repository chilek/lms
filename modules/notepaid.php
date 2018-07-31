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

$SESSION->restore('ilm', $ilm);
$SESSION->remove('ilm');

if(count($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$ilm[$id] = $mark;

if(count($ilm))
	foreach($ilm as $mark)
		$ids[] = intval($mark);

if(count($ids))
{
	foreach($ids as $noteid)
	{
		list ($cid, $value, $closed) = array_values($DB->GetRow('SELECT customerid, 
			(SELECT SUM(value) FROM debitnotecontents
				WHERE docid = d.id) AS value, closed
			FROM documents d
			WHERE id = ?', array($noteid)));
		// add payment
		if (ConfigHelper::checkConfig('phpui.note_check_payment') && !$closed) {
			if ($value != 0)
				$LMS->AddBalance(array(
					'type' => 1,
					'time' => time(),
					'value' => $value,
					'customerid' => $cid,
					'comment' => trans('Accounted'),
				));
		}

		if ($SYSLOG) {
			$args = array(
				SYSLOG::RES_DOC => $noteid,
				SYSLOG::RES_CUST => $cid,
				'closed' => intval(!$closed),
			);
			$SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
		}
		$DB->Execute('UPDATE documents SET closed = 
			(CASE closed WHEN 0 THEN 1 ELSE 0 END)
			WHERE id = ?', array($noteid));
	}
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
