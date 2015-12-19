<?php
/**
 * @author Maciej_Wawryk
 */

class LMSUserGroupManager extends LMSManager implements LMSUserGroupManagerInterface{
    
    public function UsergroupGetId($name){
        return $this->db->GetOne('SELECT id FROM usergroups WHERE name=?', array($name));
    }
    
    public function UsergroupAdd($usergroupdata){
	global $SYSLOG_RESOURCE_KEYS;
        if ($this->db->Execute('INSERT INTO usergroups (name, description) VALUES (?, ?)', array($usergroupdata['name'], $usergroupdata['description']))) {
            $id = $this->db->GetLastInsertID('usergroups');
            if ($this->syslog) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP] => $id,
                    'name' => $usergroupdata['name'],
                    'description' => $usergroupdata['description']
                );
                $this->syslog->AddMessage(SYSLOG_RES_USERGROUP, SYSLOG_OPER_ADD, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP]));
            }
            return $id;
        } else {
            return FALSE;
        }
    }
    
    public function UsergroupGetList(){
        if ($usergrouplist = $this->db->GetAll('SELECT id, name, description,
				(SELECT COUNT(*)
					FROM userassignments 
					WHERE usergroupid = usergroups.id
				) AS userscount
				FROM usergroups ORDER BY name ASC')) {
            $totalcount = 0;

            foreach ($usergrouplist as $idx => $row) {
                $totalcount += $row['userscount'];
            }

            $usergrouplist['total'] = sizeof($usergrouplist);
            $usergrouplist['totalcount'] = $totalcount;
        }

        return $usergrouplist;
    }
    
    public function UsergroupGet($id){
        $result = $this->db->GetRow('SELECT id, name, description FROM usergroups WHERE id=?', array($id));
        $result['users'] = $this->db->GetAll('SELECT u.id AS id, u.name AS username FROM userassignments, users u '
                . 'WHERE u.id = userid AND usergroupid = ? '
                . ' GROUP BY u.id, u.name ORDER BY u.name', array($id));

        $result['userscount'] = sizeof($result['users']);
        $result['count'] = $result['userscount'];
        return $result;
    }
    
    public function UsergroupExists($id){
        return ($this->db->GetOne('SELECT id FROM usergroups WHERE id=?', array($id)) ? TRUE : FALSE);
    }
    
    public function GetUserWithoutGroupNames($groupid){
        return $this->db->GetAll('SELECT u.id AS id, u.name AS username FROM users u WHERE u.deleted = 0 
	    AND u.id NOT IN (
		SELECT userid FROM userassignments WHERE usergroupid = ?) 
	    GROUP BY u.id, u.name
	    ORDER BY u.name', array($groupid));
    }
    
    public function UserassignmentDelete($userassignmentdata){
        global $SYSLOG_RESOURCE_KEYS;
        if ($this->syslog){
            $assign = $this->db->GetRow('SELECT id, userid FROM userassignments WHERE usergroupid = ? AND userid = ?', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
            if ($assign) {
                $args = array(
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERASSIGN] => $assign['id'],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $assign['userid'],
                    $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP] => $userassignmentdata['usergroupid']
                );
                $this->syslog->AddMessage(SYSLOG_RES_USERASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
            }
        }
        return $this->db->Execute('DELETE FROM userassignments WHERE usergroupid=? AND userid=?', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
    }
    
    public function UserassignmentExist($groupid, $userid){
        return $this->db->GetOne('SELECT 1 FROM userassignments WHERE usergroupid=? AND userid=?', array($groupid, $userid));
    }
    
    public function UserassignmentAdd($userassignmentdata){
        global $SYSLOG_RESOURCE_KEYS;
        $res = $this->db->Execute('INSERT INTO userassignments (usergroupid, userid) VALUES (?, ?)', array($userassignmentdata['usergroupid'], $userassignmentdata['userid']));
        if ($this->syslog && $res) {
            $id = $this->db->GetLastInsertID('userassignments');
            $args = array(
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERASSIGN] => $id,
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $userassignmentdata['userid'],
                $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP] => $userassignmentdata['usergroupid']
            );
            $this->syslog->AddMessage(SYSLOG_RES_USERASSIGN, SYSLOG_OPER_ADD, $args, array_keys($args));
        }
        return $res;
    }
    
    public function UsergroupDelete($id){
        global $SYSLOG_RESOURCE_KEYS;
        if (!$this->UsergroupWithUserGet($id)) {
            if ($this->syslog) {
                $userassigns = $this->db->Execute('SELECT id, userid, usergroupid FROM userassignments WHERE usergroupid = ?', array($id));
                if (!empty($userassigns))
                    foreach ($userassigns as $userassign) {
                        $args = array(
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERASSIGN] => $userassign['id'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USER] => $userassign['userid'],
                            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP] => $userassign['usergroupid']
                        );
                        $this->syslog->AddMessage(SYSLOG_RES_USERASSIGN, SYSLOG_OPER_DELETE, $args, array_keys($args));
                    }
                $this->syslog->AddMessage(SYSLOG_RES_USERGROUP, SYSLOG_OPER_DELETE, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP] => $id), array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP]));
            }
            $this->db->Execute('DELETE FROM usergroups WHERE id=?', array($id));
            return TRUE;
        } 
	else
	    return FALSE;
    }
    
    public function UsergroupWithUserGet($id){
        return $this->db->GetOne('SELECT COUNT(*) FROM userassignments WHERE usergroupid = ?', array($id));
    }
    
    public function UsergroupUpdate($usergroupdata){
        global $SYSLOG_RESOURCE_KEYS;
        $args = array(
            'name' => $usergroupdata['name'],
            'description' => $usergroupdata['description'],
            $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP] => $usergroupdata['id']
        );
        if ($this->syslog)
            $this->syslog->AddMessage(SYSLOG_RES_USERGROUP, SYSLOG_OPER_UPDATE, $args, array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_USERGROUP]));
        return $this->db->Execute('UPDATE usergroups SET name=?, description=? WHERE id=?', array_values($args));
    }
    
    public function UsergroupGetAll(){
        return $this->db->GetAll('SELECT g.id, g.name, g.description FROM usergroups g ORDER BY g.name ASC');
    }
    
}

