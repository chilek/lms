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
 * LMSNetworkManager
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
class LMSNetworkManager extends LMSManager
{
    /**
     * Returns network parameters
     * 
     * @param int $id Network id
     * @return array Network parameters
     */
    public function getNetworkParams($id)
    {
        return $this->db->GetRow(
            'SELECT *, inet_ntoa(address) AS netip, broadcast(address, inet_aton(mask)) AS broadcast
            FROM networks WHERE id = ?', 
            array($id)
        );
    }
    
    /**
     * Returns networks
     * 
     * @param boolean $with_disabled With disabled (default true)
     * @return array Networks
     */
    public function GetNetworks($with_disabled = true)
    {
        if ($with_disabled == false)
            return $this->db->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks WHERE disabled=0 ORDER BY name');
        else
            return $this->db->GetAll('SELECT id, name, inet_ntoa(address) AS address, 
				address AS addresslong, mask, mask2prefix(inet_aton(mask)) AS prefix, disabled 
				FROM networks ORDER BY name');
    }

}
