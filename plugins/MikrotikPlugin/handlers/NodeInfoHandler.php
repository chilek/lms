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
 * NodeInfoHandler
 *
 * @author Jarosław Dziubek <yaro@perfect.net.pl>
 */
class NodeInfoHandler {
/**
 * Example handler that does nothing
 * 
 * @param mixed $hook_data
 */
	public function nodeInfoBeforeDisplay(array $hook_data) {
		global $LMS,$DB,$SESSION;		
		$SMARTY=$hook_data['smarty'];
		$nodeid=$hook_data['nodeinfo']['id'];
		#echo $nodeid.'<BR>';
		
		$nodesignals=$DB->GetAll('SELECT * FROM signals WHERE nodeid='.$nodeid.' ORDER BY date DESC LIMIT 0,10');
		if (is_array($nodesignals)) foreach ($nodesignals as $idx => $row) {
			$netdev=$LMS->GetNetDev($row['netdev']);
			$nodesignals[$idx]['ap']=$netdev['name'];
			list($data,$units)=setunits($row['rxbytes']);
			$nodesignals[$idx]['rxbytes']=number_format($data,2,',',' ').' '.$units;
			list($data,$units)=setunits($row['txbytes']);
			$nodesignals[$idx]['txbytes']=number_format($data,2,',',' ').' '.$units;
			$nodesignals[$idx]['date']=substr($row['date'],0,16);
		}

		$hook_data['nodeinfo']['nodesignals']=$nodesignals;
		$hook_data['nodeinfo']['listdata']=$listdata;
		return $hook_data;
	}
}
