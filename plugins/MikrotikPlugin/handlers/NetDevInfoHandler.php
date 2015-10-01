<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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

/**
 * NetdevicesDisplay
 *
 * @author Jarosław Dziubek <yaro@perfect.net.pl>
 */
class NetDevInfoHandler {
/**
 * Example handler that does nothing
 * 
 * @param mixed $hook_data
 */
	public function netDevInfoBeforeDisplay(array $hook_data) {
		global $LMS;

		$SMARTY=$hook_data['smarty'];
		#echo '<PRE>';var_dump($SMARTY);echo '</PRE>';
		$netdevinfo=$SMARTY->getTemplateVars('netdevinfo');
		$netdevips=$SMARTY->getTemplateVars('netdevips');
		if ($netdevinfo['producer']!='Mikrotik') {
			return($hook_data);
		}
		
		$mtip=$netdevips[0]['ip'];
		if ($mtip=='') return($hook_data);
		$mt=new Mikrotik($mtip);

		if (preg_match('/-cl/i',$netdevinfo['name'])) {
			return $hook_data;
		} elseif ($mt->wireless()) {
			#preg_match('/(sxt|rb[3479][13][123])/i',$hook_data['netdevinfo']['model'])
			$hook_data=self::readMikrotikWireless($mtip,$hook_data);
			$SMARTY->assign('type','wireless');
		} else {
			#} elseif (preg_match('/750up|2011l/i',$hook_data['netdevinfo']['model'])) {
			$hook_data=self::readMikrotikPOE($mtip,$hook_data); 
			$SMARTY->assign('type','wired');
		}
		return $hook_data;
	}
	private function readMikrotikPOE($mtip,$hook_data) {
		global $LMS;
		$mt=new Mikrotik($mtip);
		if (is_array($hook_data['netdevconnected'])) foreach ($hook_data['netdevconnected'] AS $id => $netdev) {
			$ether=$mt->get_ether_stats($netdev['srcport']);
			$hook_data['netdevconnected'][$id]['mt']['speed']=$ether[0]['speed'];
			$hook_data['netdevconnected'][$id]['mt']['full-duplex']=$ether[0]['full-duplex'];
			$hook_data['netdevconnected'][$id]['mt']['auto-negotiation']=$ether[0]['auto-negotiation'];
			if ($ether[0]['running']=='false') {
				$hook_data['netdevconnected'][$id]['mt']['status']=0;
			} elseif ($netdev['linkspeed']==$ether[0]['speed']*1000) {
				$hook_data['netdevconnected'][$id]['mt']['status']=2;
			} else {
				$hook_data['netdevconnected'][$id]['mt']['status']=1;
			}
		}
		if (is_array($hook_data['netcomplist'])) foreach ($hook_data['netcomplist'] AS $id => $netcomp) {
			$ether=$mt->get_ether_stats($netcomp['port']);
			$hook_data['netcomplist'][$id]['mt']['speed']=$ether[0]['speed'];
			$hook_data['netcomplist'][$id]['mt']['full-duplex']=$ether[0]['full-duplex'];
			$hook_data['netcomplist'][$id]['mt']['auto-negotiation']=$ether[0]['auto-negotiation'];
			if ($ether[0]['running']=='false') {
				$hook_data['netcomplist'][$id]['mt']['status']=0;
			} elseif ($netdev['linkspeed']==$ether[0]['speed']*1000) {
				$hook_data['netcomplist'][$id]['mt']['status']=2;
			} else {
				$hook_data['netcomplist'][$id]['mt']['status']=1;
			}
		}
		return($hook_data);
	}
	private function readMikrotikWireless($mtip,$hook_data) {
		global $LMS,$DB;
		$mt=new Mikrotik($mtip);
		if (is_array($hook_data['netdevconnected'])) foreach ($hook_data['netdevconnected'] AS $id => $netdev) {
			if ($netdev['linktype']==0) {
				$ether=$mt->get_ether_stats($netdev['srcport']);
				#$hook_data['netdevconnected'][$id]['mt']['tx_rate']=$ether[0]['speed'];
				if ($netdev['linkspeed']==$ether[0]['speed']*1000)
					$hook_data['netdevconnected'][$id]['mt']['status']=2;
				else
					$hook_data['netdevconnected'][$id]['mt']['status']=0;
			} else {
				$ip=$LMS->GetNetDevIPs($netdev['id']);
				if (is_array($ip)) {
					$hook_data['netdevconnected'][$id]['mt']['status']=0;
					$hook_data['netdevconnected'][$id]['ip']=$ip[0];
					$mac[$ip[0]['mac']]=$id;
				} else {
					$ip=$LMS->GetNetDevLinkedNodes($netdev['id']);
					if (is_array($ip)) {
						$hook_data['netdevconnected'][$id]['mt']['status']=0;
						$hook_data['netdevconnected'][$id]['ip']=$ip[0];
						$mac[$LMS->GetNodeMACByID($ip[0]['id'])]=$id;
					}
				}
			}
		}
		if (is_array($hook_data['netcomplist'])) foreach ($hook_data['netcomplist'] AS $id => $netcomp) {
			if ($netcomp['linktype']==0) {
				$ether=$mt->get_ether_stats($netcomp['port']);
				#$hook_data['netdevconnected'][$id]['mt']['tx_rate']=$ether[0]['speed'];
				if ($netdev['linkspeed']==$ether[0]['speed']*1000)
					$hook_data['netcomplist'][$id]['mt']['status']=2;
				else
					$hook_data['netcomplist'][$id]['mt']['status']=0;
			} else {
				$ip=$netcomp['ip'];
				$hook_data['netcomplist'][$id]['mt']['status']=3;
				$mac1[$LMS->GetNodeMACByID($netcomp['id'])]=$id;
			}
		}
		$connected=$mt->get_connected();
		foreach ($connected AS $i => $data) {
			$table='';
			if (isset($mac[$data['mac-address']])) {
				$table='netdevconnected';
				$id=$mac[$data['mac-address']];
				unset($mac[$data['mac-address']]);
			} elseif (isset($mac1[$data['mac-address']])) {
				$table='netcomplist';
				$id=$mac1[$data['mac-address']];
				unset($mac1[$data['mac-address']]);
			}
			if ($table<>'') {
				$hook_data[$table][$id]['mt']['rx_rate']=$data['rx-rate'];
				$hook_data[$table][$id]['mt']['tx_rate']=$data['tx-rate'];
				if (preg_match('/^(.*)@/',$data['signal-strength'],$xx)) {
					$hook_data[$table][$id]['mt']['rx_signal']=$xx[1];
				} else {
					$hook_data[$table][$id]['mt']['rx_signal']=$data['signal-strength'];
				}
				$hook_data[$table][$id]['mt']['tx_signal']=$data['tx-signal-strength'];
				if (isset($data['routeros-version'])) {
					$hook_data[$table][$id]['mt']['tx_ccq']=$data['tx-ccq'];
					$hook_data[$table][$id]['mt']['rx_ccq']=$data['rx-ccq'];
				} else {
					$hook_data[$table][$id]['mt']['rx_ccq']=$data['tx-ccq'];
				}
				$hook_data[$table][$id]['mt']['routeros_version']=$data['routeros-version'];
				$hook_data[$table][$id]['mt']['uptime']=$data['uptime'];
				if (preg_match('/^(.*),(.*)$/',$data['bytes'],$xx)) {
					list($data,$units)=setunits($xx['1']);
					$hook_data[$table][$id]['mt']['rx_bytes']=number_format($data,2,',',' ').' '.$units;
					list($data,$units)=setunits($xx['2']);
					$hook_data[$table][$id]['mt']['tx_bytes']=number_format($data,2,',',' ').' '.$units;
				}
				$x=$hook_data[$table][$id]['mt'];
				if ($x['rx_signal']<-80 AND $x['tx_signal']<-80)
					$hook_data[$table][$id]['mt']['status']=0;
				elseif ($x['rx_signal']<-70 AND $x['tx_signal']<-70)
					$hook_data[$table][$id]['mt']['status']=1;
				else
					$hook_data[$table][$id]['mt']['status']=2;
			}
		}
		if (is_array($mac)) foreach ($mac AS $x => $y) {
			$signal=$DB->GetAll("SELECT * FROM signals WHERE nodeid=".$hook_data['netdevconnected'][$y]['ip']['id']." ORDER BY date DESC LIMIT 0,1");
			$signal=$signal[0];
			$hook_data['netdevconnected'][$y]['mt']['status']=3;
			$hook_data['netdevconnected'][$y]['mt']['tx_rate']=$signal['txrate'].'Mbps';
			$hook_data['netdevconnected'][$y]['mt']['rx_rate']=$signal['rxrate'].'Mbps';
			$hook_data['netdevconnected'][$y]['mt']['tx_signal']=$signal['txsignal'];
			$hook_data['netdevconnected'][$y]['mt']['rx_signal']=$signal['rxsignal'];
			$hook_data['netdevconnected'][$y]['mt']['tx_ccq']=$signal['txccq'];
			$hook_data['netdevconnected'][$y]['mt']['rx_ccq']=$signal['rxccq'];
			$hook_data['netdevconnected'][$y]['mt']['tx_bytes']='';
			$hook_data['netdevconnected'][$y]['mt']['rx_bytes']='';
			$hook_data['netdevconnected'][$y]['mt']['routeros_version']=$signal['software'];
			$date1=new DateTime($signal['date']);
			$date2=new Datetime();
			$interval = $date1->diff($date2);
			$hook_data['netdevconnected'][$y]['mt']['uptime']=$interval->format('%ad%hh%mm%ss');;
		}
		if (is_array($mac1)) foreach ($mac1 AS $x => $y) {
			$signal=$DB->GetAll("SELECT * FROM signals WHERE nodeid=".$hook_data['netcomplist'][$y]['id']." ORDER BY date DESC LIMIT 0,1");
			$signal=$signal[0];
			$hook_data['netcomplist'][$y]['mt']['status']=3;
			$hook_data['netcomplist'][$y]['mt']['tx_rate']=$signal['txrate'].'Mbps';
			$hook_data['netcomplist'][$y]['mt']['rx_rate']=$signal['rxrate'].'Mbps';
			$hook_data['netcomplist'][$y]['mt']['tx_signal']=$signal['txsignal'];
			$hook_data['netcomplist'][$y]['mt']['rx_signal']=$signal['rxsignal'];
			$hook_data['netcomplist'][$y]['mt']['tx_ccq']=$signal['txccq'];
			$hook_data['netcomplist'][$y]['mt']['rx_ccq']=$signal['rxccq'];
			$hook_data['netcomplist'][$y]['mt']['tx_bytes']='';
			$hook_data['netcomplist'][$y]['mt']['rx_bytes']='';
			$hook_data['netcomplist'][$y]['mt']['routeros_version']=$signal['software'];
			$date1=new DateTime($signal['date']);
			$date2=new Datetime();
			$interval = $date1->diff($date2);
			$hook_data['netcomplist'][$y]['mt']['uptime']=$interval->format('%ad%hh%mm%ss');;
		}
		
		return($hook_data);
	}	
}
