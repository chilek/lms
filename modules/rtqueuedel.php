<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

$layout['pagetitle'] = trans('Remove queue ID: $0',sprintf("%04d",$_GET['id']));

if (!$LMS->QueueExists($_GET['id']))
{
	$body = '<H1>'.$layout['pagetitle'].'</H1><P>'.trans('Specified ID is not proper or does not exist!').'</P>';
}else{

	if($_GET['is_sure']!=1)
	{
		$body = '<H1>'.$layout['pagetitle'].'</H1>';
		$body .= '<P>'.trans('Do you want to remove queue called $0?',$LMS->GetQueueName($_GET['id'])).'</P>'; 
		$body .= '<P>'.trans('All tickets and messages in queue will be lost.').'</P>';
		$body .= '<P><A HREF="?m=rtqueuedel&id='.$_GET['id'].'&is_sure=1">'.trans('Yes, I know what I do.').'</A>&nbsp;';
		$body .= '<A HREF="?'.$SESSION->get('backto').'">'.trans('No, I\'ve changed my mind.').'</A></P>';
	}
	else
	{
		//$body = "<H1>".$layout['pagetitle']."</H1>";
		//$body .= "<P>Kolejka ".$LMS->GetQueueName($_GET['id'])." zosta�a usuni�ta.</P>";
		$queue = intval($_GET['id']);
		
                if($DB->Execute('DELETE FROM rtqueues WHERE id=?', array($queue)))
			$LMS->SetTS('rtqueues');
		
		if($DB->Execute('DELETE FROM rtrights WHERE queueid=?', array($queue)))
		        $LMS->SetTS('rtrights');
		
		if($tickets = $DB->GetCol('SELECT id FROM rttickets WHERE queueid=?', array($queue)))
		{
		        foreach($tickets as $id)
		                $DB->Execute('DELETE FROM rtmessages WHERE ticketid=?', array($id));
		        $LMS->SetTS('rtmessages');
		        
			$DB->Execute('DELETE FROM rttickets WHERE queueid=?', array($queue));
		        $LMS->SetTS('rttickets');
		}
		
		$SESSION->redirect('?m=rtqueuelist');
	}
}

$SMARTY->display('header.html');
$SMARTY->assign('body',$body);
$SMARTY->display('dialog.html');
$SMARTY->display('footer.html');

?>
