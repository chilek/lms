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

if (isset($_GET['nodegroups'])) {
	$nodegroups = $LMS->GetNodeGroupNamesByNode(intval($_GET['id']));

	$SMARTY->assign('nodegroups', $nodegroups);
	$SMARTY->assign('total', sizeof($nodegroups));
	$SMARTY->display('node/nodegrouplistshort.html');
	die;
}

if (!preg_match('/^[0-9]+$/', $_GET['id'])) {
	$SESSION->redirect('?m=nodelist');
}
else
	$nodeid = $_GET['id'];

if (!$LMS->NodeExists($nodeid)) {
	if (isset($_GET['ownerid']))
		$SESSION->redirect('?m=customerinfo&id=' . $_GET['ownerid']);
	else if ($DB->GetOne('SELECT 1 FROM nodes WHERE id = ? AND ownerid = 0', array($nodeid)))
		$SESSION->redirect('?m=netdevinfo&ip=' . $nodeid . '&id=' . $LMS->GetNetDevIDByNode($nodeid));
	else
		$SESSION->redirect('?m=nodelist');
}

if (isset($_GET['devid'])) {
	$error['netdev'] = trans('It scans for free ports in selected device!');
	$SMARTY->assign('error', $error);
	$SMARTY->assign('netdevice', $_GET['devid']);
}

$nodeinfo = $LMS->GetNode($nodeid);
$nodegroups = $LMS->GetNodeGroupNamesByNode($nodeid);
$othernodegroups = $LMS->GetNodeGroupNamesWithoutNode($nodeid);
$customerid = $nodeinfo['ownerid'];

include(MODULES_DIR . '/customer.inc.php');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if (!isset($_GET['ownerid']))
	$SESSION->save('backto', $SESSION->get('backto') . '&ownerid=' . $customerid);

if ($nodeinfo['netdev'] == 0)
	$netdevices = $LMS->GetNetDevNames();
else
	$netdevices = $LMS->GetNetDev($nodeinfo['netdev']);

$layout['pagetitle'] = trans('Node Info: $a', $nodeinfo['name']);

include(MODULES_DIR . '/nodexajax.inc.php');

$nodeinfo = $LMS->ExecHook('node_info_init', $nodeinfo);

$SMARTY->assign('xajax', $LMS->RunXajax());

$nodeinfo['projectname'] = trans('none');
if ($nodeinfo['invprojectid']) {
	$prj = $DB->GetRow("SELECT * FROM invprojects WHERE id=?", array($nodeinfo['invprojectid']));
	if ($prj) {
		if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id']==1)) {
			/* inherited */ 
			if ($nodeinfo['netdev']) {
				$prj = $DB->GetRow("SELECT * FROM invprojects WHERE id=?",
					array($netdevices['invprojectid']));
				if ($prj) {
					if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id'])==1) {
						/* inherited */
						if ($netdevices['netnodeid']) {
							$prj = $DB->GetRow("SELECT p.*, n.name AS nodename FROM invprojects p
								JOIN netnodes n ON n.invprojectid = p.id
								WHERE n.id=?",
								array($netdevices['netnodeid']));
							if ($prj)
								$nodeinfo['projectname'] = trans('$a (from network node $b)', $prj['name'], $prj['nodename']);
						}
					} else
						$nodeinfo['projectname'] = trans('$a (from network device $b)', $prj['name'], $netdevices['name']);
				}
			}
		} else
			$nodeinfo['projectname'] = $prj['name'];
	}
}
$nodeauthtype = array();
$authtype = $nodeinfo['authtype'];
if ($authtype != 0) {
	$nodeauthtype['pppoe'] = ($authtype & 1);
	$nodeauthtype['dhcp'] = ($authtype & 2);
	$nodeauthtype['eap'] = ($authtype & 4);
}

// REDBACK CLIPS
    $nas = $CONFIG['redback']['Identifier'];
    $context = $CONFIG['redback']['ClipsContext'];
    $clipsIP = $CONFIG['redback']['clipsip'];
    $pass = $CONFIG['redback']['pass'];
    $perlCOA =  $CONFIG['redback']['perlCOA'];

function NodeLastPPPoESession($id) {
    global $DB;
    if ($PPPoESession = $DB->GetRow('SELECT callingstationid, nasipaddress, acctstarttime, acctstoptime, acctsessiontime, acctinputoctets, acctoutputoctets, framedipaddress FROM radacct WHERE username=? ORDER BY radacctid DESC LIMIT 1', array($id))) {
 
        list($PPPoESession['upload'], $PPPoESession['uploadunit']) = setunits($PPPoESession['acctinputoctets']);
        list($PPPoESession['download'], $PPPoESession['downloadunit']) = setunits($PPPoESession['acctoutputoctets']);
        $PPPoESession['acctsessiontimeconv'] = date("z \d\\n\i H:i:s", -3600 + $PPPoESession['acctsessiontime']);
        if ($PPPoESession['acctstoptime'] == 0)
            $PPPoESession['acctstoptime'] = 'TRWA';
        else
            $PPPoESession['acctstoptime'] = "zakonczona: " . $PPPoESession['acctstoptime'];
    }
    return $PPPoESession;
}
 
$nodeinfo = $LMS->GetNode($nodeid);
$lastPPPoEsession = NodeLastPPPoESession($nodeinfo['macs'][0]['mac']);
 
if (isset($_GET['killredback'])) {
    global $CONFIG;
    $mac = strtolower($nodeinfo['macs'][0]['mac']);
    $nas = $CONFIG[redback][Identifier];
    $context = $CONFIG[redback][ClipsContext];
    $clipsIP = $CONFIG['redback']['clipsip'];
    $pass = $CONFIG['redback']['pass'];
    echo $cmd = "echo \"User-Name=\"$mac\",NAS-Identifier=\"$nas\",Context-Name=\"$context\"\" | radclient -x $clipsIP:3799 disconnect $pass";
    exec($cmd);
    global $DB;
    $DB->exec("update radacct set acctstoptime=NOW(), acctterminatecause='STATE-CLEARED' where acctstoptime is NULL and username = UPPER(?);", array($mac));
    $SESSION->redirect('?m=nodeinfo&id=' . $_GET['id']);
}
 
function GetClipsInfo($mac) {
    global $CONFIG;
    $ip = $CONFIG['redback']['ip'];
    $user = $CONFIG['redback']['username'];
    $pass = $CONFIG['redback']['pass'];
    $port = $CONFIG['redback']['port'];
 
    $cmd = $CONFIG['redback']['info'] . " " . strtolower($mac);
    if (isset($CONFIG['redback']['pass'])) {
        $methods = array(
            'kex' => 'diffie-hellman-group1-sha1',
            'hostkey' => 'ssh-dss',
            'client_to_server' => array(
                'crypt' => '3des-cbc',
                'mac' => 'hmac-md5',
                'comp' => 'none'),
            'server_to_client' => array(
                'crypt' => '3des-cbc',
                'mac' => 'hmac-md5',
                'comp' => 'none'));
 
        $conn = ssh2_connect($ip, $port, $methods);
        $test = ssh2_auth_password($conn, $user, $pass);
        $stream = ssh2_exec($conn, $cmd);
        stream_set_blocking($stream, true);
        $return = stream_get_contents($stream);
        fclose($stream);
    }
    return $return;
}
 
function ClearHost($ip) {
    global $CONFIG;
    $ip = $CONFIG['redback']['ip'];
    $user = $CONFIG['redback']['username'];
    $pass = $CONFIG['redback']['pass'];
    $port = $CONFIG['redback']['port'];
 
    $cmd1 = "context CLIPS";
    $cmd2 = "clear dhcp host  " . $ip;
    if (isset($CONFIG['redback']['pass'])) {
        $methods = array(
            'kex' => 'diffie-hellman-group1-sha1',
            'hostkey' => 'ssh-dss',
            'client_to_server' => array(
                'crypt' => '3des-cbc',
                'mac' => 'hmac-md5',
                'comp' => 'none'),
            'server_to_client' => array(
                'crypt' => '3des-cbc',
                'mac' => 'hmac-md5',
                'comp' => 'none'));
 
        $conn = ssh2_connect($ip, $port, $methods);
        $test = ssh2_auth_password($conn, $user, $pass);
        $stream = ssh2_exec($conn, $cmd1);
        $stream = ssh2_exec($conn, $cmd2);
        stream_set_blocking($stream, true);
        $return = stream_get_contents($stream);
        fclose($stream);
    }
    return $return;
}
 
function SetNewSpeed($mac) {
    global $DB, $CONFIG;
    if (!$nodeParam = $DB->GetAll('SELECT vclips.id, vclips.mac, vclips.ip, vclips.redirect, vclips.download, vclips.upload FROM vclips WHERE mac=?', array($mac))) {
        return "nodeParam query faild";
    }
//echo "<p>redirect:".$nodeParam[0][redirect]."</p>";
    if ($nodeParam[0][redirect] == 0) {
        $forwardpolicy = "in:CLIPS-DEFAULT";
        $httpredirect = "";
    } else {
        $forwardpolicy = "in:REDIRECT";
        $httpredirect = "KOMUNIKAT";
    }
//echo $nodeParam[0][ip];
    $cmd = "$perlCOA -d $clipsIP -e $pass -v \"User-Name=" . $nodeParam[0][mac] . "; NAS-Identifier=ALFASYSTEM; Redback:Qos-Rate-Outbound=" . $nodeParam[0][download] . "; Redback:Qos-Rate-Inbound=" . $nodeParam[0][upload] . "; Redback:Forward-Policy=" . $forwardpolicy . "; Redback:HTTP-Redirect-Profile-Name=" . $httpredirect . "; Redback:Context-Name=CLIPS\" ";
 
 
    $nas = $CONFIG[redback][Identifier];
    $context = $CONFIG[redback][ClipsContext];
    $mac = $nodeParam[0][mac];
    $dl = $nodeParam[0][download];
    $up = $nodeParam[0][upload];
//    "HTTP-Redirect-Profile-Name=\"$httpredirect\",";
//$cmd="echo \"User-Name=\"$mac\",NAS-Identifier=\"$nas\",Qos-Rate-Outbound=\"$dl\", Qos-Rate-Inbound=\"$up\",Forward-Policy=\"$forwardpolicy\", HTTP-Redirect-Profile-Name=\"$httpredirect\", Context-Name=\"$context\"\" | radclient -x $CONFIG['redback']['clipsip']:3799 coa $CONFIG['redback']['pass']";
//    $cmd="echo \"User-Name=\"$mac\",NAS-Identifier=\"$nas\",Qos-Rate-Outbound=\"$dl\", Qos-Rate-Inbound=\"$up\",Forward-Policy=\"$forwardpolicy\", $redirect Context-Name=\"$context\"\" | radclient -x $CONFIG['redback']['clipsip']:3799 coa $CONFIG['redback']['pass']";
//exec($cmd);
 
    echo "<pre>";
    print_r($cmd);
    echo "</pre>";
 
    if (!exec($cmd, $result)) {
        print($result);
        return "cmd not executed: ";
    }
    return $nodeParam;
}
 
if (isset($_GET['clear'])) {
    echo $_GET['host'];
//    $SMARTY->assign('ClearHost', ClearHost(long2ip($nodeinfo['ipaddr_pub'])));
    $SMARTY->assign('ClearHost', ClearHost($_GET['host']));
}
 
 
if (isset($_GET['clips'])) {
    $SMARTY->assign('ClipsInfo', GetClipsInfo($nodeinfo['macs'][0]['mac']));
}
 
if (isset($_GET['updatespeed'])) {
    $SMARTY->assign('NewSpeed', SetNewSpeed(strtolower($nodeinfo['macs'][0]['mac'])));
}
 
$SMARTY->assign('lastpppoesession', $lastPPPoEsession);
// END REDBACK CLIPS 

$SMARTY->assign('nodesessions', $LMS->GetNodeSessions($nodeid));
$SMARTY->assign('netdevices', $netdevices);
$SMARTY->assign('nodeauthtype', $nodeauthtype);
$SMARTY->assign('nodegroups', $nodegroups);
$SMARTY->assign('othernodegroups', $othernodegroups);
$SMARTY->assign('nodeinfo', $nodeinfo);
$SMARTY->assign('objectid', $nodeinfo['id']);
$SMARTY->display('node/nodeinfo.html');

?>
