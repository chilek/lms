<?php
/**
 * @author Maciej_Wawryk
 */

interface LMSTariffTagManagerInterface{
    
    public function TarifftagGetId($name);
    
    public function TarifftagAdd($tarifftagdata);
    
    public function TarifftagGetList();
    
    public function TarifftagGet($id);
    
    public function TarifftagExists($id);
    
    public function GetTariffWithoutTagNames($tagid);
    
    public function TariffassignmentDelete($tariffassignmentdata);
    
    public function TariffassignmentAdd($tariffassignmentdata);
    
    public function TariffassignmentExist($tagid, $tariffid);
    
    public function TarifftagDelete($id);
    
    public function TarifftagWithTariffGet($id);
    
    public function TarifftagUpdate($tarifftagdata);
    
    public function TarifftagGetAll();
    
}
