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

$layout['pagetitle'] = trans('Remove queue ID: $a',sprintf("%04d",$_GET['id']));

if(!$LMS->QueueExists($_GET['id']))
{
	$body = '<P>'.trans('Specified ID is not proper or does not exist!').'</P>';
}
else
{

//        if($_GET['is_sure']!=1)
// 	       {
// 		               $body = '<P>'.trans('Do you want to remove queue called $a?',$LMS->GetQueueName($_GET['id'])).'</P>';
// 		               $body .= '<P>'.trans('All tickets and messages in queue will be lost.').'</P>';
// 		               $body .= '<P><A HREF="?m=rtqueuedel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I know what I do.').'</A>&nbsp;';
// 		               $body .= '<A HREF="?'.$SESSION->get('backto').'">'.trans('No, I\'ve changed my mind.').'</A></P>';
// 		       }
//        else
// 	       {
// 		               $queue = intval($_GET['id']);
		
// 		       $mail_dir = ConfigHelper::getConfig('rt.mail_dir');
// 		        if (!empty($mail_dir)) {
// 			            // remove attachment files
// 			            if ($tickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid = ?', array($queue)))
// 				            {
// 					                foreach ($tickets as $ticket) {
// 						                    rrmdir($mail_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticket));
// 						                }
// 						            }
// 			        }
			
// 			        $DB->Execute('DELETE FROM rtqueues WHERE id=?', array($queue));
			
// 			               $SESSION->redirect('?m=rtqueuelist');
// 			       }

	$queue = intval($_GET['id']);

	$ticket = $DB->GetOne('SELECT id FROM rttickets WHERE queueid = ?', array($queue));
	$del = 1;
	$nodel = 0;
	$deltime = time();
	$DB->BeginTrans();
	$DB->Execute('UPDATE rtqueues SET deleted=?, deltime=?, deluserid=? WHERE id = ?', array($del, $deltime, $AUTH->id, $queue));
	$DB->Execute('UPDATE rttickets SET deleted=?, deluserid=? WHERE deleted=? and queueid = ?', array($del, $AUTH->id, $nodel, $queue));
	$DB->Execute('UPDATE rtmessages SET deleted=?, deluserid=? WHERE deleted=? and ticketid = ?', array($del, $AUTH->id, $nodel, $ticket));
	$DB->CommitTrans();

	$SESSION->redirect('?m=rtqueuelist');
}

$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');

?>
