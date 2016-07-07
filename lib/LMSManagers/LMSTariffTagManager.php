<?php
/**
 * @author Maciej_Wawryk
 */

class LMSTariffTagManager extends LMSManager implements LMSTariffTagManagerInterface{
    
    public function TarifftagGetId($name){
        return $this->db->GetOne('SELECT id FROM tarifftags WHERE name=?', array($name));
    }
    
    public function TarifftagAdd($tarifftagdata){
	global $SYSLOG_RESOURCE_KEYS;
        if ($this->db->Execute('INSERT INTO tarifftags (name, description) VALUES (?, ?)', array($tarifftagdata['name'], $tarifftagdata['description']))) {
            $id = $this->db->GetLastInsertID('tarifftags');
            if ($this->syslog) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG] => $id,
                    'name' => $tarifftagdata['name'],
                    'description' => $tarifftagdata['description']
                );
                $this->syslog->AddMessage(SYSLOG_RES_TARIFFTAG, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG]));
            }
            return $id;
        } else {
            return FALSE;
        }
    }
    
    public function TarifftagGetList(){
        if ($tarifftaglist = $this->db->GetAll('SELECT id, name, description,
				(SELECT COUNT(*)
					FROM tariffassignments 
					WHERE tarifftagid = tarifftags.id
				) AS tariffscount
				FROM tarifftags ORDER BY name ASC')) {
            $totalcount = 0;

            foreach ($tarifftaglist as $idx => $row) {
                $totalcount += $row['tariffscount'];
            }

            $tarifftaglist['total'] = sizeof($tarifftaglist);
            $tarifftaglist['totalcount'] = $totalcount;
        }

        return $tarifftaglist;
    }
    
    public function TarifftagGet($id){
        $result = $this->db->GetRow('SELECT id, name, description FROM tarifftags WHERE id=?', array($id));
        $result['tariffs'] = $this->db->GetAll('SELECT t.id AS id, t.name AS tariffname FROM tariffassignments, tariffs t '
                . 'WHERE t.id = tariffid AND tarifftagid = ? '
                . ' GROUP BY t.id, t.name ORDER BY t.name', array($id));

        $result['tariffscount'] = sizeof($result['tariffs']);
        $result['count'] = $result['tariffscount'];
        return $result;
    }
    
    public function TarifftagExists($id){
        return ($this->db->GetOne('SELECT id FROM tarifftags WHERE id=?', array($id)) ? TRUE : FALSE);
    }
    
    public function GetTariffWithoutTagNames($tagid){
        return $this->db->GetAll('SELECT t.id AS id, t.name AS tariffname FROM tariffs t WHERE t.disabled = 0 
	    AND t.id NOT IN (
		SELECT tariffid FROM tariffassignments WHERE tarifftagid = ?) 
	    GROUP BY t.id, t.name
	    ORDER BY t.name', array($tagid));
    }
    
    public function TariffassignmentDelete($tariffassignmentdata){
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog){
            $assign = $this->db->GetRow('SELECT tariffid FROM tariffassignments WHERE tarifftagid = ? AND tariffid = ?', array($tariffassignmentdata['tarifftagid'], $tariffassignmentdata['tariffid']));
            if ($assign) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFASSIGN] => $assign['id'],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $assign['tariffid'],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG] => $tariffassignmentdata['tarifftagid']
                );
                $this->syslog->AddMessage(SYSLOG_RES_TARIFFASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
        }
        return $this->db->Execute('DELETE FROM tariffassignments WHERE tarifftagid=? AND tariffid=?', array($tariffassignmentdata['tarifftagid'], $tariffassignmentdata['tariffid']));
    }
    
    public function TariffassignmentExist($tagid, $tariffid){
        return $this->db->GetOne('SELECT 1 FROM tariffassignments WHERE tarifftagid=? AND tariffid=?', array($tagid, $tariffid));
    }
    
    public function TariffassignmentAdd($tariffassignmentdata){
        global $SYSLOG_RESOURCE_KEYS;
        $res = $this->db->Execute('INSERT INTO tariffassignments (tarifftagid, tariffid) VALUES (?, ?)', array($tariffassignmentdata['tarifftagid'], $tariffassignmentdata['tariffid']));
        if ($this->syslog && $res) {
            $id = $this->db->GetLastInsertID('tariffassignments');
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFASSIGN] => $id,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tariffassignmentdata['tariffid'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG] => $tariffassignmentdata['tarifftagid']
            );
            $this->syslog->AddMessage(SYSLOG_RES_TARIFFASSIGN, SYSLOG_OPER_ADD, $args, array_keys($args));
        }
        return $res;
    }
    
    public function TarifftagDelete($id){
        global $SYSLOG_RESOURCE_KEYS;
        if (!$this->TarifftagWithTariffGet($id)) {
            if ($this->syslog) {
                $tariffassigns = $this->db->Execute('SELECT tariffid, tarifftagid FROM tariffassignments WHERE tarifftagid = ?', array($id));
                if (!empty($tariffassigns))
                    foreach ($tariffassigns as $tariffassign) {
                        $args = array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFASSIGN] => $tariffassign['tariffid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFF] => $tariffassign['tariffid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG] => $tariffassign['tarifftagid']
                        );
                        $this->syslog->AddMessage(SYSLOG_RES_TARIFFASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                    }
                $this->syslog->AddMessage(SYSLOG_RES_TARIFFTAG, SYSLOG_OPER_DELETE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG] => $id), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG]));
            }
            $this->db->Execute('DELETE FROM tarifftags WHERE id=?', array($id));
            return TRUE;
        } 
	else
	    return FALSE;
    }
    
    public function TarifftagWithTariffGet($id){
        return $this->db->GetOne('SELECT COUNT(*) FROM tariffassignments WHERE tarifftagid = ?', array($id));
    }
    
    public function TarifftagUpdate($tarifftagdata){
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $tarifftagdata['name'],
            'description' => $tarifftagdata['description'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG] => $tarifftagdata['id']
        );
        if ($this->syslog)
            $this->syslog->AddMessage(SYSLOG_RES_TARIFFTAG, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_TARIFFTAG]));
        return $this->db->Execute('UPDATE tarifftags SET name=?, description=? WHERE id=?', array_values($args));
    }
    
    public function TarifftagGetAll(){
        return $this->db->GetAll('SELECT g.id, g.name, g.description FROM tarifftags g ORDER BY g.name ASC');
    }
    
}

