<?
/*
 * LMS version 1.1-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

# funkcja do zamiany jednostek
function ZmJedn($dane)
{
    if ($dane < (1024*1024*1000) )
    {
	$ile = $dane / (1024*1024);
	$jedn = "MB";
    } else
    {
	$ile = $dane / (1024*1024*1024);
	$jedn = "GB";
    }
    return array($ile, $jedn);
}

function ArrayToTimestamp($dane)
{
    return mktime($dane[hour],$dane[minute],0,$dane[month],$dane[day],$dane[year]);   
}

$dane = 1;
switch($_GET['bar'])
{
  case "hour":
	$query = "SELECT nodeid, ipaddr, name, sum(upload) as upload, sum(download) as download FROM stats JOIN nodes ON stats.nodeid=nodes.id WHERE dt < ?NOW? AND dt > ?NOW?-(60*60) GROUP BY nodeid, name, ipaddr ORDER BY download DESC";
	break;
  case "day":
	$query = "SELECT nodeid, ipaddr, name, sum(upload) as upload, sum(download) as download FROM stats JOIN nodes ON stats.nodeid=nodes.id WHERE dt < ?NOW? AND dt > ?NOW?-(60*60*24) GROUP BY nodeid, name, ipaddr ORDER BY download DESC";
	break;
  case "month":
	$query = "SELECT nodeid, ipaddr, name, sum(upload) as upload, sum(download) as download FROM stats JOIN nodes ON stats.nodeid=nodes.id WHERE dt < ?NOW? AND dt > ?NOW?-(60*60*24*30)  GROUP BY nodeid, name, ipaddr ORDER BY download DESC";
	break;
  case "year":
	$query = "SELECT nodeid, ipaddr, name, sum(upload) as upload, sum(download) as download FROM stats JOIN nodes ON stats.nodeid=nodes.id WHERE dt < ?NOW? AND dt > ?NOW?-(60*60*24*365) GROUP BY nodeid, name, ipaddr ORDER BY download DESC";
	break;
  case "user": ##########################################################
	$from = ArrayToTimestamp($_POST['from']);
        $to = ArrayToTimestamp($_POST['to']);
	$net = $_POST['net'];
	if ($net != "allnets")   #je¶li ograniczamy do danej sieci
	{			 #to potrzebujemy ma³e z³±czonko
	    $params = $LMS->GetNetworkParams($net);
	    $ipfrom = $params['address']+1;
	    $ipto = $params['broadcast']-1;
	    $query = "SELECT nodeid, name, ipaddr, sum(upload) as upload, sum(download) as download FROM stats JOIN nodes ON stats.nodeid=nodes.id WHERE (ipaddr>$ipfrom AND ipaddr<$ipto) AND (dt > $from AND dt < $to) GROUP BY nodeid, name, ipaddr";
	} else 
	    $query = "SELECT nodeid, name, ipaddr, sum(upload) as upload, sum(download) as download FROM stats JOIN nodes ON stats.nodeid=nodes.id WHERE dt > $from AND dt < $to GROUP BY nodeid, name, ipaddr";
	# sortujemy wyniki
	switch ($_POST['order'])  
	{
	    case "nodeid"   : $query = $query." ORDER BY nodeid"; 	 break;
	    case "download" : $query = $query." ORDER BY download DESC"; break; 	
	    case "upload"   : $query = $query." ORDER BY upload DESC"; 	 break;
	    case "name"     : $query = $query." ORDER BY name"; 	 break;
	    case "ip"       : $query = $query." ORDER BY ipaddr"; 	 break;
	}
	# limitujemy wyniki
	if( $_POST['limit'] > 0 ) $query = $query." LIMIT ".$_POST['limit'];
	break;
  default: ################################################################
	$query = "";
	# ustawiamy daty do pol wyboru
	$now[rok]   = date("Y");
	$now[mies]  = date("n");
	$now[dzien] = date("j");
	$now[godz]  = date("H");
	$now[min]   = date("i");
	for($x=1990; $x<=$now[rok]; $x++) $data[year][]=$x;
	for($x=1; $x<=12; $x++) $data[month][]=$x;
	for($x=1; $x<=31; $x++) $data[day][]=$x;
	for($x=0; $x<=23; $x++) $data[hour][] = $x;
	for($x=0; $x<=59; $x++) $data[minute][] = $x;
	$SMARTY->assign("data",$data);
	$SMARTY->assign("now",$now);
	$SMARTY->assign("netlist",$LMS->GetNetworks());
	$SMARTY->assign("nodelist",$LMS->GetNodeList());
	$dane = 0;
	break;
}
###########################################################################
# <debugowanie>
#echo $LMS->ADB->_query_parser($query);
# </debugowanie>

if ($traffic = $LMS->DB->GetAll($query))
{
 foreach ($traffic as $idx => $row)
    {
    //$nodename = $LMS->GetNodeName($row[nodeid]);
    $upload[dane]	[] = $row[upload];
    $download[dane]	[] = $row[download];
    $upload[name]	[] = $row[name];	//$nodename;
    $download[name]	[] = $row[name];	//$nodename;
    $upload[ipaddr]	[] = long2ip($row[ipaddr]);
    $download[ipaddr]	[] = long2ip($row[ipaddr]);
    $down_sum += $row[download];
    $up_sum += $row[upload];
    }

 $maks = max($download[dane]);
 if ($maks < max($upload[dane])) $maks = max($upload[dane]); 
 if($maks == 0) $maks = 1;   # co by nie dzieliæ przez zero
 $x = 0;
 foreach ($download[dane] as $dane)
    {
    $download[bar]	[] = round($dane * 150 / $maks);
    list($download[dane][$x], $download[jedn][$x]) = ZmJedn($dane);
    $x++;
    } 
 list($download_sum[dane], $download_sum[jedn]) = ZmJedn($down_sum); 
 $x = 0;
 foreach ($upload[dane] as $dane)
    {
    $upload[bar]	[] = round($dane * 150 / $maks);
    list($upload[dane][$x], $upload[jedn][$x]) = ZmJedn($dane);
    $x++;
    } 
 list($upload_sum[dane], $upload_sum[jedn]) = ZmJedn($up_sum);
}

$layout[pagetitle]="Statystyki wykorzystania ³±cza";

$SMARTY->assign("showips",$_POST['showips']);
$SMARTY->assign("download_sum",$download_sum);
$SMARTY->assign("upload_sum",$upload_sum);
$SMARTY->assign("layout",$layout);
$SMARTY->assign("version",$version);
$SMARTY->assign("download",$download);
$SMARTY->assign("upload",$upload);
$SMARTY->assign("dane",$dane);
$SMARTY->display("traffic.html");

?>