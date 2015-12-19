<?php
/**
 * @author Maciej_Wawryk
 */

interface LMSUserGroupManagerInterface{
    
    public function UsergroupGetId($name);
    
    public function UsergroupAdd($usergroupdata);
    
    public function UsergroupGetList();
    
    public function UsergroupGet($id);
    
    public function UsergroupExists($id);
    
    public function GetUserWithoutGroupNames($groupid);
    
    public function UserassignmentDelete($userassignmentdata);
    
    public function UserassignmentAdd($userassignmentdata);
    
    public function UserassignmentExist($groupid, $userid);
    
    public function UsergroupDelete($id);
    
    public function UsergroupWithUserGet($id);
    
    public function UsergroupUpdate($usergroupdata);
    
    public function UsergroupGetAll();
    
}
