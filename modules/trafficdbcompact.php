<?php

/*
 * LMS version 1.2-cvs
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

$delete = $_POST['delete'];
$level = $_POST['level'];
$removedeleted = $_POST['removedeleted'];

if (!($level || $delete || $removedeleted))
{
    $SMARTY->display('traffic.html');
    die;
}

$SMARTY->display('header.html');
$SMARTY->display('trafficheader.html');

echo '<PRE><B>'._('Stats database compactiong').'</B><BR>';
printf(_("Before compacting in database was %d records.<BR>"),$LMS->DB->GetOne("SELECT COUNT(*) FROM stats"));

if($delete)
{
    $yeardeleted = $LMS->DB->Execute("DELETE FROM stats where dt < ?NOW? - 365*24*60*60");
    echo "Usuniêto ponadrocznych ".$yeardeleted." rekordów.\n";
}

if($removedeleted)
{
    if($nodes_from_stats = $LMS->DB->GetCol("SELECT DISTINCT nodeid FROM stats")) 
    {
	$nodes = $LMS->DB->GetCol("SELECT id FROM nodes");
	foreach($nodes_from_stats as $node)
	{
	    if(!in_array($node,$nodes))
		if($LMS->DB->Execute("DELETE FROM stats WHERE nodeid = ".$node))
		    printf(_("Removed stats for node with ID: %d\n"),$node);
	}
    }
}

if($level)
{
    $time = time();
    switch($level)
    {
	case '1' : $period = $time-24*60*60; $step = 24*60*60; break; //1 dzieñ, dzieñ  
	case '2' : $period = $time-30*24*60*60; $step = 24*60*60; break;//mies, dzieñ
	case '3' : $period = $time-365*24*60*60; $step = 60*60; break; //po miesi±c, godz	
    }
    
    if($mintime = $LMS->DB->GetOne("SELECT MIN(dt) FROM stats"))
    {
	$nodes = $LMS->DB->GetAll("SELECT id, name FROM nodes ORDER BY name");
	foreach($nodes as $node)
	{
    	    echo "'".$node['name']."'\t: "; 
	    $deleted = 0;
	    $inserted = 0;
	    $LMS->DB->BeginTrans();
	    $maxtime = $period;
	    while($maxtime > $mintime)
	    {
		$data = $LMS->DB->GetRow("SELECT sum(upload) as upload, sum(download) as download FROM stats WHERE dt >= $maxtime - $step AND dt < $maxtime AND nodeid=? GROUP BY nodeid", array($node['id']));
		$deleted += $LMS->DB->Execute("DELETE FROM stats WHERE nodeid=? AND dt >= $maxtime - $step AND dt < $maxtime", array($node['id'])); 
		$download = ($data['download']?$data['download']:0);
		$upload = ($data['upload']?$data['upload']:0);
		if($download || $upload)
		    $inserted += $LMS->DB->Execute("INSERT INTO stats (nodeid, dt, upload, download) VALUES (?, $maxtime, $upload, $download )", array($node['id']));
		$maxtime -= $step;
	    }
	
	    $LMS->DB->CommitTrans();
	    echo ($deleted?$deleted:0)._(" - removed, ").($inserted?$inserted:0)._(" - insertedh\n");
	}
    }
}
printf(_("After compacting in database is %d records."), $LMS->DB->GetOne("SELECT COUNT(*) FROM stats"));

$SMARTY->display('footer.html');

?>