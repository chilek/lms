<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

// LMS Class - contains internal LMS database functions used
// to fetch data like customer names, searching for mac's by ID, etc..

class LMS
{

    public $DB;   // database object
    public $AUTH;   // object from Session.class.php (session management)
    public $SYSLOG;
    public $cache;  // internal cache
    public $hooks = array(); // registered plugin hooks
    public $xajax;  // xajax object
    public $_version = '1.11-git'; // class version
    public $_revision = '$Format:%cI$'; // %H for last commit checksum
    private $mail_object = NULL;
    protected $plugin_manager;
    protected $user_manager;
    protected $customer_manager;
    protected $voip_account_manager;
    protected $location_manager;
    protected $cash_manager;
    protected $customer_group_manager;
    protected $network_manager;
    protected $node_manager;
    protected $node_group_manager;
    protected $net_dev_manager;
    protected $helpdesk_manager;
    protected $finance_manager;
    protected $event_manager;
    protected $document_manager;
    protected $massage_manager;
    protected $config_manager;

    public function __construct(&$DB, &$AUTH, &$SYSLOG)
    { // class variables setting
        $this->DB = &$DB;
        $this->AUTH = &$AUTH;
        $this->SYSLOG = &$SYSLOG;

        $this->cache = new LMSCache();

	if (preg_match('/.+Format:.+/', $this->_revision))
		$this->_revision = '';
    }

    public function _postinit()
    {
        return TRUE;
    }

    public function InitUI()
    {
        // set current user
        switch (ConfigHelper::getConfig('database.type')) {
            case 'postgres':
                $this->DB->Execute('SELECT set_config(\'lms.current_user\', ?, false)', array($this->AUTH->id));
                break;
            case 'mysql':
            case 'mysqli':
                $this->DB->Execute('SET @lms_current_user=?', array($this->AUTH->id));
                break;
        }
    }

    public function InitXajax()
    {
        if (!$this->xajax) {
            require(LIB_DIR . DIRECTORY_SEPARATOR . 'xajax' . DIRECTORY_SEPARATOR . 'xajax_core' . DIRECTORY_SEPARATOR . 'xajax.inc.php');
            $this->xajax = new xajax();
            $this->xajax->configure('errorHandler', true);
            $this->xajax->configure('javascript URI', 'img');
        }
    }

    public function RunXajax()
    {
        $xajax_js = NULL;
        if ($this->xajax) {
            $xajax_js = $this->xajax->getJavascript();
            $this->xajax->processRequest();
        }
        return $xajax_js;
    }

    public function RegisterXajaxFunction($funcname)
    {
        if ($this->xajax) {
            if (is_array($funcname))
                foreach ($funcname as $func)
                    $this->xajax->register(XAJAX_FUNCTION, $func);
            else
                $this->xajax->register(XAJAX_FUNCTION, $funcname);
        }
    }

    /*
     *  Logging
     * 	0 - disabled
     * 	1 - system log in and modules calls without access privileges
     * 	2 - as above, addition and deletion
     * 	3 - as above, and changes
     * 	4 - as above, and all modules calls (paranoid)
     */
    /*
      public function Log($loglevel=0, $message=NULL)
      {
      if( $loglevel <= ConfigHelper::getConfig('phpui.loglevel') && $message )
      {
      $this->DB->Execute('INSERT INTO syslog (time, userid, level, message)
      VALUES (?NOW?, ?, ?, ?)', array($this->AUTH->id, $loglevel, $message));
      }
      }
     */

    /*
     * Plugins
     */

    public function RegisterHook($hook_name, $callback)
    {
        $this->hooks[] = array(
            'name' => $hook_name,
            'callback' => $callback,
        );
    }

    public function ExecHook($hook_name, $vars = null)
    {
        foreach ($this->hooks as $hook) {
            if ($hook['name'] == $hook_name) {
                $vars = call_user_func($hook['callback'], $vars);
            }
        }

        return $vars;
    }

    /**
     * Sets plugin manager
     * 
     * @param LMSPluginManager $plugin_manager Plugin manager
     */
    public function setPluginManager(LMSPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * Executes hook
     * 
     * @param string $hook_name Hook name
     * @param mixed $hook_data Hook data
     * @return mixed Modfied hook data
     */
    public function executeHook($hook_name, $hook_data = null)
    {
        return $this->plugin_manager->executeHook($hook_name, $hook_data);
    }

    /*
     *  Database functions (backups)
     */

    public function DBDump($filename = NULL, $gzipped = FALSE, $stats = FALSE)
    { // dump database to file
        if (!$filename)
            return FALSE;

        if ($gzipped && extension_loaded('zlib'))
            $dumpfile = gzopen($filename, 'w');
        else
            $dumpfile = fopen($filename, 'w');

        if ($dumpfile) {
            $tables = $this->DB->ListTables();

            switch (ConfigHelper::getConfig('database.type')) {
                case 'postgres':
                    fputs($dumpfile, "SET CONSTRAINTS ALL DEFERRED;\n");
                    break;
                case 'mysql':
                case 'mysqli':
                    fputs($dumpfile, "SET foreign_key_checks = 0;\n");
                    break;
            }

            foreach ($tables as $tablename) {
                // skip sessions table for security
                if ($tablename == 'sessions' || ($tablename == 'stats' && $stats == FALSE))
                    continue;

                fputs($dumpfile, "DELETE FROM $tablename;\n");
            }

            // Since we're using foreign keys, order of tables is important
            // Note: add all referenced tables to the list
            $order = array('users', 'customers', 'customergroups', 'nodes', 'numberplans',
                'assignments', 'rtqueues', 'rttickets', 'rtmessages', 'domains',
                'cashsources', 'sourcefiles', 'ewx_channels', 'hosts');

            foreach ($tables as $idx => $table) {
                if (in_array($table, $order)) {
                    unset($tables[$idx]);
                }
            }

            $tables = array_merge($order, $tables);

            foreach ($tables as $tablename) {
                // skip sessions table for security
                if ($tablename == 'sessions' || ($tablename == 'stats' && $stats == FALSE))
                    continue;

                $this->DB->Execute('SELECT * FROM ' . $tablename);
                while ($row = $this->DB->_driver_fetchrow_assoc()) {
                    fputs($dumpfile, "INSERT INTO $tablename (");
                    foreach ($row as $field => $value) {
                        $fields[] = $field;
                        if (isset($value))
                            $values[] = "'" . addcslashes($value, "\r\n\'\"\\") . "'";
                        else
                            $values[] = 'NULL';
                    }
                    fputs($dumpfile, implode(', ', $fields));
                    fputs($dumpfile, ') VALUES (');
                    fputs($dumpfile, implode(', ', $values));
                    fputs($dumpfile, ");\n");
                    unset($fields);
                    unset($values);
                }
            }

            if (preg_match('/^mysqli?$/', ConfigHelper::getConfig('database.type')))
                fputs($dumpfile, "SET foreign_key_checks = 1;\n");

            if ($gzipped && extension_loaded('zlib'))
                gzclose($dumpfile);
            else
                fclose($dumpfile);
        } else
            return FALSE;
    }

    public function DatabaseCreate($gzipped = FALSE, $stats = FALSE)
    { // create database backup
        $basename = 'lms-' . time() . '-' . DBVERSION;
        if (($gzipped) && (extension_loaded('zlib'))) {
            $filename = $basename . '.sql.gz';
            $res = $this->DBDump(ConfigHelper::getConfig('directories.backup_dir') . DIRECTORY_SEPARATOR . $filename, TRUE, $stats);
        } else {
            $filename = $basename . '.sql';
            $res = $this->DBDump(ConfigHelper::getConfig('directories.backup_dir') . DIRECTORY_SEPARATOR . $filename, FALSE, $stats);
        }
        if ($this->SYSLOG)
            $this->SYSLOG->AddMessage(SYSLOG_RES_DBBACKUP, SYSLOG_OPER_ADD, array('filename' => $filename), null);
        return $res;
    }

	public function CleanupInvprojects() {
		if (ConfigHelper::checkValue(ConfigHelper::getConfig('phpui.auto_remove_investment_project', true)))
			$this->DB->Execute("DELETE FROM invprojects WHERE type <> ? AND id NOT IN
				(SELECT DISTINCT invprojectid FROM netdevices WHERE invprojectid IS NOT NULL
					UNION SELECT DISTINCT invprojectid FROM vnodes WHERE invprojectid IS NOT NULL
					UNION SELECT DISTINCT invprojectid FROM netnodes WHERE invprojectid IS NOT NULL)",
				array(INV_PROJECT_SYSTEM));
	}

    /*
     * Users
     */

    public function SetUserPassword($id, $passwd)
    {
        $manager = $this->getUserManager();
        return $manager->setUserPassword($id, $passwd);
    }

    public function GetUserName($id = null)
    {
        $manager = $this->getUserManager();
        return $manager->getUserName($id);
    }

    public function GetUserNames()
    {
        $manager = $this->getUserManager();
        return $manager->getUserNames();
    }

    public function GetUserList()
    {
        $manager = $this->getUserManager();
        return $manager->getUserList();
    }

    public function GetUserIDByLogin($login)
    {
        $manager = $this->getUserManager();
        return $manager->getUserIDByLogin($login);
    }

    public function UserAdd($user)
    {
        $manager = $this->getUserManager();
        return $manager->userAdd($user);
    }

    public function UserDelete($id)
    {
        $manager = $this->getUserManager();
        return $manager->userDelete($id);
    }

    public function UserExists($id)
    {
        $manager = $this->getUserManager();
        return $manager->userExists($id);
    }

    public function UserAccess($id, $access)
    {
        $manager = $this->getUserManager();
        return $manager->userAccess($id, $access);
    }

    public function GetUserInfo($id)
    {
        $manager = $this->getUserManager();
        return $manager->getUserInfo($id);
    }

    public function UserUpdate($user)
    {
        $manager = $this->getUserManager();
        return $manager->userUpdate($user);
    }

    public function GetUserRights($id)
    {
        $manager = $this->getUserManager();
        return $manager->getUserRights($id);
    }

    /*
     *  Customers functions
     */

    public function GetCustomerName($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerName($id);
    }

    public function GetCustomerEmail($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerEmail($id);
    }

    public function CustomerExists($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->customerExists($id);
    }

    public function CustomerAdd($customeradd)
    {
        $manager = $this->getCustomerManager();
        return $manager->CustomerAdd($customeradd);
    }

    public function DeleteCustomer($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->DeleteCustomer($id);
    }
    
    public function DeleteCustomerPermanent($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->deleteCustomerPermanent($id);
    }

    public function CustomerUpdate($customerdata)
    {
        $manager = $this->getCustomerManager();
        return $manager->CustomerUpdate($customerdata);
    }

    public function GetCustomerNodesNo($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNodesNo($id);
    }

    public function GetCustomerIDByIP($ipaddr)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerIDByIP($ipaddr);
    }

    public function GetCashByID($id)
    {
        $manager = $this->getCashManager();
        return $manager->GetCashByID($id);
    }

    public function GetCustomerStatus($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerStatus($id);
    }

    public function GetCustomer($id, $short = false)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomer($id, $short);
    }

    public function GetCustomerNames()
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNames();
    }

    public function GetAllCustomerNames()
    {
        $manager = $this->getCustomerManager();
        return $manager->getAllCustomerNames();
    }

    public function GetCustomerNodesAC($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerNodesAC($id);
    }

    public function GetCustomerList($order = 'customername,asc', $state = null, $network = null, $customergroup = null, $search = null, $time = null, $sqlskey = 'AND', $nodegroup = null, $division = null, $limit = null, $offset = null, $count = false)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerList($order, $state, $network, $customergroup, $search, $time, $sqlskey, $nodegroup, $division, $limit, $offset, $count);
    }

    public function GetCustomerNodes($id, $count = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNodes($id, $count);
    }

    public function GetCustomerNetworks($id, $count = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerNetworks($id, $count);
    }

    public function GetCustomerBalance($id, $totime = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerBalance($id, $totime);
    }

    public function GetCustomerBalanceList($id, $totime = null, $direction = 'ASC')
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerBalanceList($id, $totime, $direction);
    }

    public function CustomerStats()
    {
        $manager = $this->getCustomerManager();
        return $manager->customerStats();
    }

    /*
     * Customer groups
     */

    public function CustomergroupWithCustomerGet($id)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupWithCustomerGet();
    }

    public function CustomergroupAdd($customergroupdata)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupAdd($customergroupdata);
    }

    public function CustomergroupUpdate($customergroupdata)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupUpdate($customergroupdata);
    }

    public function CustomergroupDelete($id)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupDelete($id);
    }

    public function CustomergroupExists($id)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupExists($id);
    }

    public function CustomergroupGetId($name)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupGetId($name);
    }

    public function CustomergroupGetName($id)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupGetName($id);
    }

    public function CustomergroupGetAll()
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupGetAll();
    }

    public function CustomergroupGet($id, $network = NULL)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupGet($id, $network);
    }

    public function CustomergroupGetList()
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupGetList();
    }

    public function CustomergroupGetForCustomer($id)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomergroupGetForCustomer($id);
    }

    public function GetGroupNamesWithoutCustomer($customerid)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->GetGroupNamesWithoutCustomer($customerid);
    }

    public function CustomerassignmentGetForCustomer($id)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomerassignmentGetForCustomer($id);
    }

    public function CustomerassignmentDelete($customerassignmentdata)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomerassignmentDelete($customerassignmentdata);
    }

    public function CustomerassignmentAdd($customerassignmentdata)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomerassignmentAdd($customerassignmentdata);
    }

    public function CustomerassignmentExist($groupid, $customerid)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->CustomerassignmentExist($groupid, $customerid);
    }

    public function GetCustomerWithoutGroupNames($groupid, $network = NULL)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->GetCustomerWithoutGroupNames($groupid, $network);
    }

    /*
     *  Nodes functions
     */

    public function GetNodeOwner($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeOwner($id);
    }

    public function NodeUpdate($nodedata, $deleteassignments = FALSE)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeUpdate($nodedata, $deleteassignments);
    }

    public function DeleteNode($id)
    {
        $manager = $this->getNodeManager();
        return $manager->DeleteNode($id);
    }

    public function GetNodeNameByMAC($mac)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeNameByMAC($mac);
    }

    public function GetNodeIDByIP($ipaddr)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeIDByIP($ipaddr);
    }

    public function GetNodeIDByMAC($mac)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeIDByMAC($mac);
    }

    public function GetNodeConnType($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeConnType($id);
    }
    public function GetNodeIDByName($name)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeIDByName($name);
    }

    public function GetNodeIPByID($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeIPByID($id);
    }

    public function GetNodePubIPByID($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodePubIPByID($id);
    }

    public function GetNodeMACByID($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeMACByID($id);
    }

    public function GetNodeName($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeName($id);
    }

    public function GetNodeNameByIP($ipaddr)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeNameByIP($ipaddr);
    }

    public function GetNode($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNode($id);
    }

    public function GetNodeList($order = 'name,asc', $search = NULL, $sqlskey = 'AND', $network = NULL, $status = NULL, $customergroup = NULL, $nodegroup = NULL, $limit = null, $offset = null, $count = false)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeList($order, $search, $sqlskey, $network, $status, $customergroup, $nodegroup, $limit, $offset, $count);
    }

    public function NodeSet($id, $access = -1)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSet($id, $access);
    }

    public function NodeSetU($id, $access = FALSE)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSetU($id, $access);
    }

    public function NodeSetWarn($id, $warning = FALSE)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSetWarn($id, $warning);
    }

    public function NodeSwitchWarn($id)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSwitchWarn($id);
    }

    public function NodeSetWarnU($id, $warning = FALSE)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSetWarnU($id, $warning);
    }

    public function IPSetU($netdev, $access = FALSE)
    {
        $manager = $this->getNodeManager();
        return $manager->IPSetU($netdev, $access);
    }

    public function NodeAdd($nodedata)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeAdd($nodedata);
    }

    public function NodeExists($id)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeExists($id);
    }

    public function NodeStats()
    {
        $manager = $this->getNodeManager();
        return $manager->NodeStats();
    }

    public function GetNodeGroupNames()
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodeGroupNames();
    }

    public function GetNodeGroupNamesByNode($nodeid)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodeGroupNamesByNode($nodeid);
    }

    public function GetNodeGroupNamesWithoutNode($nodeid)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodeGroupNamesWithoutNode($nodeid);
    }

    public function GetNodesWithoutGroup($groupid, $network = NULL)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodesWithoutGroup($groupid, $network);
    }

    public function GetNodesWithGroup($groupid, $network = NULL)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodesWithGroup($groupid, $network);
    }

    public function GetNodeGroup($id, $network = NULL)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodeGroup($id, $network);
    }

    public function CompactNodeGroups()
    {
        $manager = $this->getNodeGroupManager();
        return $manager->CompactNodeGroups();
    }

    public function GetNetDevLinkedNodes($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevLinkedNodes($id);
    }

    public function NetDevLinkNode($id, $devid, $link = NULL)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevLinkNode($id, $devid, $link);
    }

    public function SetNetDevLinkType($dev1, $dev2, $link)
    {
        $manager = $this->getNetDevManager();
        return $manager->SetNetDevLinkType($dev1, $dev2, $link);
    }

    public function SetNodeLinkType($node, $link)
    {
        $manager = $this->getNodeManager();
        return $manager->SetNodeLinkType($node, $link);
    }

    /*
     *  Tarrifs and finances
     */

    public function GetCustomerTariffsValue($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetCustomerTariffsValue($id);
    }

    public function GetCustomerAssignments($id, $show_expired = false)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetCustomerAssignments($id, $show_expired);
    }

    public function DeleteAssignment($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->DeleteAssignment($id);
    }

    public function AddAssignment($data)
    {
        $manager = $this->getFinanaceManager();
        return $manager->AddAssignment($data);
    }

    public function SuspendAssignment($id, $suspend = TRUE)
    {
        $manager = $this->getFinanaceManager();
        return $manager->SuspendAssignment($id, $suspend);
    }

    public function AddInvoice($invoice)
    {
        $manager = $this->getFinanaceManager();
        return $manager->AddInvoice($invoice);
    }

    public function InvoiceDelete($invoiceid)
    {
        $manager = $this->getFinanaceManager();
        return $manager->InvoiceDelete($invoiceid);
    }

    public function InvoiceContentDelete($invoiceid, $itemid = 0)
    {
        $manager = $this->getFinanaceManager();
        return $manager->InvoiceContentDelete($invoiceid, $itemid);
    }

    public function GetInvoiceContent($invoiceid)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetInvoiceContent($invoiceid);
    }

    public function GetNoteContent($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetNoteContent($id);
    }

    public function TariffAdd($tariff)
    {
        $manager = $this->getFinanaceManager();
        return $manager->TariffAdd($tariff);
    }

    public function TariffUpdate($tariff)
    {
        $manager = $this->getFinanaceManager();
        return $manager->TariffUpdate($tariff);
    }

    public function TariffDelete($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->TariffDelete($id);
    }

    public function GetTariff($id, $network = NULL)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetTariff($id, $network);
    }

    public function GetTariffs()
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetTariffs();
    }

    public function TariffSet($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->TariffSet($id);
    }

    public function TariffExists($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->TariffExists($id);
    }

    public function ReceiptContentDelete($docid, $itemid = 0)
    {
        $manager = $this->getFinanaceManager();
        return $manager->ReceiptContentDelete($docid, $itemid);
    }

    public function DebitNoteContentDelete($docid, $itemid = 0)
    {
        $manager = $this->getFinanaceManager();
        return $manager->DebitNoteContentDelete($docid, $itemid);
    }

    public function AddBalance($addbalance)
    {
        $manager = $this->getFinanaceManager();
        return $manager->AddBalance($addbalance);
    }

    public function DelBalance($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->DelBalance($id);
    }

    /*
     *   Payments
     */

    public function GetPaymentList()
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetPaymentList();
    }

    public function GetPayment($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetPayment($id);
    }

    public function GetPaymentName($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetPaymentName($id);
    }

    public function GetPaymentIDByName($name)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetPaymentIDByName($name);
    }

    public function PaymentExists($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->PaymentExists($id);
    }

    public function PaymentAdd($paymentdata)
    {
        $manager = $this->getFinanaceManager();
        return $manager->PaymentAdd($paymentdata);
    }

    public function PaymentDelete($id)
    {
        $manager = $this->getFinanaceManager();
        return $manager->PaymentDelete($id);
    }

    public function PaymentUpdate($paymentdata)
    {
        $manager = $this->getFinanaceManager();
        return $manager->PaymentUpdate($paymentdata);
    }

    public function ScanNodes()
    {
        $manager = $this->getNetworkManager();
        return $manager->ScanNodes();
    }

    /*
     *  IP Networks
     */

    public function NetworkExists($id)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkExists($id);
    }

    public function NetworkSet($id, $disabled = -1)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkSet($id, $disabled);
    }

    public function IsIPFree($ip, $netid = 0)
    {
        $manager = $this->getNetworkManager();
        return $manager->IsIPFree($ip, $netid);
    }

    public function IsIPInNetwork($ip, $netid)
    {
        $manager = $this->getNetworkManager();
        return $manager->IsIPInNetwork($ip, $netid);
    }

    public function IsIPGateway($ip)
    {
        $manager = $this->getNetworkManager();
        return $manager->IsIPGateway($ip);
    }

    public function GetPrefixList()
    {
        $manager = $this->getNetworkManager();
        return $manager->GetPrefixList();
    }

    public function NetworkAdd($netadd)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkAdd($netadd);
    }

    public function NetworkDelete($id)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkDelete($id);
    }

    public function GetNetworkName($id)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkName($id);
    }

    public function GetNetIDByIP($ipaddr)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetIDByIP($ipaddr);
    }

    public function GetNetworks($with_disabled = true)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworks($with_disabled);
    }

    public function GetNetworkParams($id)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkParams($id);
    }

    public function GetNetworkList($order = 'id,asc')
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkList($order);
    }

    public function IsIPValid($ip, $checkbroadcast = FALSE, $ignoreid = 0)
    {
        $manager = $this->getNetworkManager();
        return $manager->IsIPValid($ip, $checkbroadcast, $ignoreid);
    }

    public function NetworkOverlaps($network, $mask, $hostid, $ignorenet = 0)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkOverlaps($network, $mask, $hostid, $ignorenet);
    }

    public function NetworkShift($netid, $network = '0.0.0.0', $mask = '0.0.0.0', $shift = 0)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkShift($netid, $network, $mask, $shift);
    }

    public function NetworkUpdate($networkdata)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkUpdate($networkdata);
    }

    public function NetworkCompress($id, $shift = 0)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkCompress($id, $shift);
    }

    public function NetworkRemap($src, $dst)
    {
        $manager = $this->getNetworkManager();
        return $manager->NetworkRemap($src, $dst);
    }

    public function GetNetworkRecord($id, $page = 0, $plimit = 4294967296, $firstfree = false)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkRecord($id, $page, $plimit, $firstfree);
    }

    /*
     *   Network Devices
     */

    public function NetDevExists($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevExists($id);
    }

    public function GetNetDevIDByNode($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevIDByNode($id);
    }

    public function CountNetDevLinks($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->CountNetDevLinks($id);
    }

    public function GetNetDevLinkType($dev1, $dev2)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevLinkType($dev1, $dev2);
    }

    public function GetNetDevConnectedNames($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevConnectedNames($id);
    }

    public function GetNetDevList($order = 'name,asc', $search = array())
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevList($order, $search);
    }

    public function GetNetDevNames()
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevNames();
    }

    public function GetNotConnectedDevices($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNotConnectedDevices($id);
    }

    public function GetNetDev($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDev($id);
    }

    public function NetDevDelLinks($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevDelLinks($id);
    }

    public function DeleteNetDev($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->DeleteNetDev($id);
    }

    public function NetDevAdd($data)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevAdd($data);
    }

    public function NetDevUpdate($data)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevUpdate($data);
    }

    public function IsNetDevLink($dev1, $dev2)
    {
        $manager = $this->getNetDevManager();
        return $manager->IsNetDevLink($dev1, $dev2);
    }

    public function NetDevLink($dev1, $dev2, $link)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevLink($dev1, $dev2, $link);
    }

    public function NetDevUnLink($dev1, $dev2)
    {
        $manager = $this->getNetDevManager();
        return $manager->NetDevUnLink($dev1, $dev2);
    }

    public function GetUnlinkedNodes()
    {
        $manager = $this->getNetworkManager();
        return $manager->GetUnlinkedNodes();
    }

    public function GetNetDevIPs($id)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetDevIPs($id);
    }

    /*
     *   Request Tracker (Helpdesk)
     */

    public function GetQueue($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueue($id);
    }

    public function GetQueueContents($ids, $order = 'createtime,desc', $state = NULL, $owner = 0, $catids = NULL)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueContents($ids, $order, $state, $owner, $catids);
    }

    public function GetUserRightsRT($user, $queue, $ticket = NULL)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetUserRightsRT($user, $queue, $ticket);
    }

    public function GetQueueList($stats = true)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueList($stats);
    }

    public function GetQueueNames()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueNames();
    }

    public function QueueExists($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->QueueExists($id);
    }

    public function GetQueueIdByName($queue)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueIdByName($id);
    }

    public function GetQueueName($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueName($id);
    }

    public function GetQueueEmail($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueEmail($id);
    }

    public function GetQueueStats($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueStats($id);
    }

    public function GetCategory($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetCategory($id);
    }

    public function GetUserRightsToCategory($user, $category, $ticket = NULL)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetUserRightsToCategory($user, $category, $ticket);
    }

    public function GetCategoryList($stats = true)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetCategoryList($stats);
    }

    public function GetCategoryStats($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetCategoryStats($id);
    }

    public function CategoryExists($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->CategoryExists($id);
    }

    public function GetCategoryIdByName($category)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetCategoryIdByName($category);
    }

    public function GetCategoryListByUser($userid = NULL)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetCategoryListByUser($userid);
    }

    public function RTStats()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->RTStats();
    }

    public function GetQueueByTicketId($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueByTicketId($id);
    }

    public function TicketExists($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketExists($id);
    }

    public function TicketAdd($ticket, $files = NULL)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketAdd($ticket, $files);
    }

    public function GetTicketContents($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetTicketContents($id);
    }

    public function TicketChange($ticketid, array $props)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketChange($ticketid, $props);
    }

    public function GetMessage($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetMessage($id);
    }

    /*
     * Konfiguracja LMS-UI
     */

    public function GetConfigOptionId($var, $section)
    {
        $manager = $this->getConfigManager();
        return $manager->GetConfigOptionId($var, $section);
    }

    public function CheckOption($var, $value)
    {
        $manager = $this->getConfigManager();
        return $manager->CheckOption($var, $value);
    }

    /*
     *  Miscalenous
     */

    public function GetHostingLimits($customerid)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetHostingLimits($customerid);
    }

    public function GetRemoteMACs($host = '127.0.0.1', $port = 1029)
    {
        $inputbuf = '';
        $result = array();

        if ($socket = socket_create(AF_INET, SOCK_STREAM, 0))
            if (@socket_connect($socket, $host, $port)) {
                while ($input = socket_read($socket, 2048))
                    $inputbuf .= $input;
                socket_close($socket);
            }
        if ($inputbuf) {
            foreach (explode("\n", $inputbuf) as $line) {
                list($ip, $hwaddr) = explode(' ', $line);
                if (check_mac($hwaddr)) {
                    $result['mac'][] = $hwaddr;
                    $result['ip'][] = $ip;
                    $result['longip'][] = ip_long($ip);
                    $result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
                }
            }
        }

        return $result;
    }

    public function GetMACs()
    {
        $result = array();
        if (ConfigHelper::getConfig('phpui.arp_table_backend') != '') {
            exec(ConfigHelper::getConfig('phpui.arp_table_backend'), $result);
            foreach ($result as $arpline) {
                list($ip, $mac) = explode(' ', $arpline);
                $result['mac'][] = $mac;
                $result['ip'][] = $ip;
                $result['longip'][] = ip_long($ip);
                $result['nodename'][] = $this->GetNodeNameByMAC($mac);
            }
        } else
            switch (PHP_OS) {
                case 'Linux':
                    if (@is_readable('/proc/net/arp'))
                        $file = fopen('/proc/net/arp', 'r');
                    else
                        break;
                    while (!feof($file)) {
                        $line = fgets($file, 4096);
                        $line = preg_replace('/[\t ]+/', ' ', $line);
                        if (preg_match('/[0-9]/', $line)) { // skip header line
                            list($ip, $hwtype, $flags, $hwaddr, $mask, $device) = explode(' ', $line);
                            if ($flags != '0x6' && $hwaddr != '00:00:00:00:00:00' && check_mac($hwaddr)) {
                                $result['mac'][] = $hwaddr;
                                $result['ip'][] = $ip;
                                $result['longip'][] = ip_long($ip);
                                $result['nodename'][] = $this->GetNodeNameByMAC($hwaddr);
                            }
                        }
                    }
                    fclose($file);
                    break;
                default:
                    exec('arp -an|grep -v incompl', $result);
                    foreach ($result as $arpline) {
                        list($fqdn, $ip, $at, $mac, $hwtype, $perm) = explode(' ', $arpline);
                        $ip = str_replace('(', '', str_replace(')', '', $ip));
                        if ($perm != "PERM") {
                            $result['mac'][] = $mac;
                            $result['ip'][] = $ip;
                            $result['longip'][] = ip_long($ip);
                            $result['nodename'][] = $this->GetNodeNameByMAC($mac);
                        }
                    }
                    break;
            }

        return $result;
    }

    public function GetUniqueInstallationID()
    {
        if (!($uiid = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('unique_installation_id')))) {
            list($usec, $sec) = explode(' ', microtime());
            $uiid = md5(uniqid(rand(), true)) . sprintf('%09x', $sec) . sprintf('%07x', ($usec * 10000000));
            $this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('unique_installation_id', $uiid));
        }
        return $uiid;
    }

    public function CheckUpdates($force = FALSE)
    {
        $uiid = $this->GetUniqueInstallationID();
        $time = $this->DB->GetOne('SELECT ?NOW?');
        $content = FALSE;
        if ($force == TRUE)
            $lastcheck = 0;
        elseif (!($lastcheck = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('last_check_for_updates_timestamp'))))
            $lastcheck = 0;
        if ($lastcheck + ConfigHelper::getConfig('phpui.check_for_updates_period') < $time) {
            list($v, ) = explode(' ', $this->_version);

            if ($content = fetch_url('http://register.lms.org.pl/update.php?uiid=' . $uiid . '&v=' . $v)) {
                if ($lastcheck == 0)
                    $this->DB->Execute('INSERT INTO dbinfo (keyvalue, keytype) VALUES (?NOW?, ?)', array('last_check_for_updates_timestamp'));
                else
                    $this->DB->Execute('UPDATE dbinfo SET keyvalue=?NOW? WHERE keytype=?', array('last_check_for_updates_timestamp'));

                $content = unserialize((string) $content);
                $content['regdata'] = unserialize((string) $content['regdata']);

                if (is_array($content['regdata'])) {
                    $this->DB->Execute('DELETE FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'));

                    foreach (array('id', 'name', 'url', 'hidden') as $key)
                        $this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('regdata_' . $key, $content['regdata'][$key]));
                }
            }
        }

        return $content;
    }

    public function GetRegisterData()
    {
        if ($regdata = $this->DB->GetAll('SELECT * FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'))) {
            foreach ($regdata as $regline)
                $registerdata[str_replace('regdata_', '', $regline['keytype'])] = $regline['keyvalue'];
            return $registerdata;
        }
        return NULL;
    }

    public function UpdateRegisterData($name, $url, $hidden)
    {
        $name = rawurlencode($name);
        $url = rawurlencode($url);
        $uiid = $this->GetUniqueInstallationID();
        $url = 'http://register.lms.org.pl/register.php?uiid=' . $uiid . '&name=' . $name . '&url=' . $url . ($hidden == TRUE ? '&hidden=1' : '');

        if (fetch_url($url) !== FALSE) {
            // ok, update done, so, let we fall asleep for at least 2 seconds, let's viper put our
            // registration data into database. in future we should read info from register.php,
            // ie. 'Password' incorrect if we protect each installation with password (but then
            // we should use https)

            sleep(5);
            $this->DB->Execute('DELETE FROM dbinfo WHERE keytype = ?', array('last_check_for_updates_timestamp'));
            $this->CheckUpdates(TRUE);
            return TRUE;
        }

        return FALSE;
    }

    public function SendMail($recipients, $headers, $body, $files = NULL, $host = null, $port = null, $user = null, $pass = null, $auth = null, $persist = null)
    {
        @include_once('Mail.php');
        if (!class_exists('Mail'))
            return trans('Can\'t send message. PEAR::Mail not found!');

        $persist = is_null($persist) ? ConfigHelper::getConfig('mail.smtp_persist', true) : $persist;
        if (!is_object($this->mail_object) || !$persist) {
            $params['host'] = (!$host ? ConfigHelper::getConfig('mail.smtp_host') : $host);
            $params['port'] = (!$port ? ConfigHelper::getConfig('mail.smtp_port') : $port);
            $smtp_username = ConfigHelper::getConfig('mail.smtp_username');
            if (!empty($smtp_username) || $user) {
                $params['auth'] = (!$auth ? ConfigHelper::getConfig('mail.smtp_auth_type', true) : $auth);
                $params['username'] = (!$user ? $smtp_username : $user);
                $params['password'] = (!$pass ? ConfigHelper::getConfig('mail.smtp_password') : $pass);
            } else
                $params['auth'] = false;
            $params['persist'] = $persist;

            $error = $this->mail_object = & Mail::factory('smtp', $params);
            //if (PEAR::isError($error))
            if (is_a($error, 'PEAR_Error'))
                return $error->getMessage();
        }

        $headers['X-Mailer'] = 'LMS-' . $this->_version;
        if (!empty($_SERVER['REMOTE_ADDR']))
            $headers['X-Remote-IP'] = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $headers['X-HTTP-User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
        $headers['Mime-Version'] = '1.0';
        $headers['Subject'] = qp_encode($headers['Subject']);

        $debug_email = ConfigHelper::getConfig('mail.debug_email');
        if (!empty($debug_email)) {
            $recipients = ConfigHelper::getConfig('mail.debug_email');
            $headers['To'] = '<' . $recipients . '>';
        }

        if (empty($headers['Date']))
            $headers['Date'] = date('r');

        if ($files || $headers['X-LMS-Format'] == 'html') {
            $boundary = '-LMS-' . str_replace(' ', '.', microtime());
            $headers['Content-Type'] = "multipart/mixed;\n  boundary=\"" . $boundary . '"';
            $buf = "\nThis is a multi-part message in MIME format.\n\n";
            $buf .= '--' . $boundary . "\n";
            $buf .= "Content-Type: text/" . ($headers['X-LMS-Format'] == 'html' ? "html" : "plain") . "; charset=UTF-8\n\n";
            $buf .= $body . "\n";
            if ($files)
                while (list(, $chunk) = each($files)) {
                    $buf .= '--' . $boundary . "\n";
                    $buf .= "Content-Transfer-Encoding: base64\n";
                    $buf .= "Content-Type: " . $chunk['content_type'] . "; name=\"" . $chunk['filename'] . "\"\n";
                    $buf .= "Content-Description:\n";
                    $buf .= "Content-Disposition: attachment; filename=\"" . $chunk['filename'] . "\"\n\n";
                    $buf .= chunk_split(base64_encode($chunk['data']), 60, "\n");
                }
            $buf .= '--' . $boundary . '--';
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
            $buf = $body;
        }


        $error = $this->mail_object->send($recipients, $headers, $buf);
        //if (PEAR::isError($error))
        if (is_a($error, 'PEAR_Error'))
            return $error->getMessage();
        else
            return MSG_SENT;
    }

    public function SendSMS($number, $message, $messageid = 0, $script_service = null)
    {
        $msg_len = mb_strlen($message);

        if (!$msg_len) {
            return trans('SMS message is empty!');
        }

        $debug_phone = ConfigHelper::getConfig('sms.debug_phone');
        if (!empty($debug_phone)) {
            $number = ConfigHelper::getConfig('sms.debug_phone');
        }

        $prefix = ConfigHelper::getConfig('sms.prefix', '');
        $number = preg_replace('/[^0-9]/', '', $number);
        $number = preg_replace('/^0+/', '', $number);

        // add prefix to the number if needed
        if ($prefix && substr($number, 0, strlen($prefix)) != $prefix)
            $number = $prefix . $number;

        // message ID must be unique
        if (!$messageid) {
            $messageid = '0.' . time();
        }

        $message = preg_replace("/\r/", "", $message);

		if (ConfigHelper::checkConfig('sms.transliterate_message'))
			$message = iconv('UTF-8', 'ASCII//TRANSLIT', $message);

        $max_length = ConfigHelper::getConfig('sms.max_length');
        if (!empty($max_length) && intval($max_length) > 6 && $msg_len > intval($max_length))
            $message = mb_substr($message, 0, $max_length - 6) . ' [...]';

        $service = ConfigHelper::getConfig('sms.service');
        if ($script_service) {
            $service = $script_service;
        } elseif (empty($service))
            return trans('SMS "service" not set!');

	$errors = array();
	foreach (explode(',', $service) as $service) {

        $data = array(
            'number' => $number,
            'message' => $message,
            'messageid' => $messageid,
            'service' => $service,
        );

        // call external SMS handler(s)
        $data = $this->ExecHook('send_sms_before', $data);

	if ($data['abort'])
		if (is_string($data['result'])) {
			$errors[] = $data['result'];
			continue;
		} else
			return $data['result'];

        $number = $data['number'];
        $message = $data['message'];
        $messageid = $data['messageid'];


        if (in_array($service, array('smscenter', 'serwersms', 'smsapi'))) {
            if (!function_exists('curl_init')) {
                $errors[] = trans('Curl extension not loaded!');
                continue;
            }
            $username = ConfigHelper::getConfig('sms.username');
            if (empty($username)) {
                $errors[] = trans('SMSCenter username not set!');
                continue;
            }
            $password = ConfigHelper::getConfig('sms.password');
            if (empty($password)) {
                $errors[] = trans('SMSCenter username not set!');
                continue;
            }
            $from = ConfigHelper::getConfig('sms.from');
            if (empty($from)) {
                $errors[] = trans('SMS "from" not set!');
                continue;
            }

            if (strlen($number) > 16 || strlen($number) < 4) {
                $errors[] = trans('Wrong phone number format!');
                continue;
            }
        }

        switch ($service) {
            case 'smscenter':
                if ($msg_len < 160)
                    $type_sms = 'sms';
                else if ($msg_len <= 459)
                    $type_sms = 'concat';
                else {
			$errors[] = trans('SMS Message too long!');
			continue 2;
                }

                $type = ConfigHelper::getConfig('sms.smscenter_type', 'dynamic');
                $message .= ($type == 'static') ? "\n\n" . $from : '';

                $args = array(
                    'user' => ConfigHelper::getConfig('sms.username'),
                    'pass' => ConfigHelper::getConfig('sms.password'),
                    'type' => $type_sms,
                    'number' => $number,
                    'text' => $message,
                    'from' => $from
                );

                $encodedargs = array();
                foreach (array_keys($args) as $thiskey)
                    array_push($encodedargs, urlencode($thiskey) . "=" . urlencode($args[$thiskey]));
                $encodedargs = implode('&', $encodedargs);

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'http://api.statsms.net/send.php');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedargs);
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);

                $page = curl_exec($curl);
                if (curl_error($curl)) {
                    $errors[] = 'SMS communication error. ' . curl_error($curl);
                    continue 2;
                }

                $info = curl_getinfo($curl);
                if ($info['http_code'] != '200') {
                    $errors[] = 'SMS communication error. Http code: ' . $info['http_code'];
                    continue 2;
                }

                curl_close($curl);
                $smsc = explode(', ', $page);
                $smsc_result = array();

                foreach ($smsc as $element) {
                    $tmp = explode(': ', $element);
                    array_push($smsc_result, $tmp[1]);
                }

                switch ($smsc_result[0]) {
                    case '002':
                    case '003':
                    case '004':
                    case '008':
                    case '011':
                        return MSG_SENT;
                    case '001':
                        $errors[] = 'Smscenter error 001, Incorrect login or password';
                        continue 3;
                    case '009':
                        $errors[] = 'Smscenter error 009, GSM network error (probably wrong prefix number)';
                        continue 3;
                    case '012':
                        $errors[] = 'Smscenter error 012, System error please contact smscenter administrator';
                        continue 3;
                    case '104':
                        $errors[] = 'Smscenter error 104, Incorrect sender field or field empty';
                        continue 3;
                    case '201':
                        $errors[] = 'Smscenter error 201, System error please contact smscenter administrator';
                        continue 3;
                    case '202':
                        $errors[] = 'Smscenter error 202, Unsufficient funds on account to send this text';
                        continue 3;
                    case '204':
                        $errors[] = 'Smscenter error 204, Account blocked';
                        continue 3;
                    default:
                        $errors[] = 'Smscenter error ' . $smsc_result[0] . '. Please contact smscenter administrator';
                        continue 3;
                }
                break;
            case 'smstools':
                $dir = ConfigHelper::getConfig('sms.smstools_outdir', DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'spool' . DIRECTORY_SEPARATOR . 'sms' . DIRECTORY_SEPARATOR . 'outgoing');

                if (!file_exists($dir)) {
                    $errors[] = trans('SMSTools outgoing directory not exists ($a)!', $dir);
                    continue 2;
                }
                if (!is_writable($dir)) {
                    $errors[] = trans('Unable to write to SMSTools outgoing directory ($a)!', $dir);
                    continue 2;
                }

                $filename = $dir . DIRECTORY_SEPARATOR . 'lms-' . $messageid . '-' . $number;
                $latin1 = iconv('UTF-8', 'ASCII', $message);
                $alphabet = '';
                if (strlen($latin1) != mb_strlen($message, 'UTF-8')) {
                    $alphabet = "Alphabet: UCS2\n";
                    $message = iconv('UTF-8', 'UNICODEBIG', $message);
                }
                //$message = clear_utf($message);
                $file = sprintf("To: %s\n%s\n%s", $number, $alphabet, $message);

                if ($fp = fopen($filename, 'w')) {
                    fwrite($fp, $file);
                    fclose($fp);
                } else {
                    $errors[] = trans('Unable to create file $a!', $filename);
                    continue 2;
                }

                return MSG_NEW;
            case 'serwersms':
                $args = array(
                    'akcja' => 'wyslij_sms',
                    'login' => ConfigHelper::getConfig('sms.username'),
                    'haslo' => ConfigHelper::getConfig('sms.password'),
                    'numer' => $number,
                    'wiadomosc' => $message,
                    'nadawca' => $from,
                );
                if ($messageid)
                    $args['usmsid'] = $messageid;
                $fast = ConfigHelper::getConfig('sms.fast');
                if (!empty($fast))
                    $args['speed'] = 1;

                $encodedargs = http_build_query($args);

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'https://api1.serwersms.pl/zdalnie/index.php');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedargs);
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);

                $page = curl_exec($curl);
                if (curl_error($curl)) {
                    $errors[] = 'SMS communication error. ' . curl_error($curl);
                    continue 2;
                }

                $info = curl_getinfo($curl);
                if ($info['http_code'] != '200') {
                    $errors[] = 'SMS communication error. Http code: ' . $info['http_code'];
                    continue 2;
                }

                curl_close($curl);

                $lines = explode("\n", $page);
                foreach ($lines as $lineidx => $line)
                    $lines[$lineidx] = trim($line);
                $page = implode('', $lines);

                if (preg_match('/<Blad>([^<]*)<\/Blad>/i', $page, $matches)) {
                    $errors[] = 'Serwersms error: ' . $matches[1];
                    continue 2;
                }

                if (!preg_match('/<Skolejkowane><SMS id="[^"]+" numer="[^"]+" godzina_skolejkowania="[^"]+"\/><\/Skolejkowane>/', $page)) {
                    $errors[] = 'Serwersms error: message has not been sent!';
                    continue 2;
                }

                return MSG_SENT;
            case 'smsapi':
                $args = array(
                    'username' => ConfigHelper::getConfig('sms.username'),
                    'password' => md5(ConfigHelper::getConfig('sms.password')),
                    'to' => $number,
                    'message' => $message,
                    'from' => !empty($from) ? $from : 'ECO',
                );
                $fast = ConfigHelper::getConfig('sms.fast');
                if (!empty($fast))
                    $args['fast'] = 1;
                if ($messageid)
                    $args['idx'] = $messageid;

                $encodedargs = http_build_query($args);

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, 'https://ssl.smsapi.pl/sms.do');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedargs);
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);

                $page = curl_exec($curl);
                if (curl_error($curl)) {
                    $errors[] = 'SMS communication error. ' . curl_error($curl);
                    continue 2;
                }

                $info = curl_getinfo($curl);
                if ($info['http_code'] != '200') {
                    $errors[] = 'SMS communication error. Http code: ' . $info['http_code'];
                    continue 2;
                }

                curl_close($curl);

                if (preg_match('/^OK:/', $page))
                    return MSG_SENT;
                if (preg_match('/^ERROR:([0-9]+)/', $page, $matches)) {
                    $errors[] = 'Smsapi error: ' . $matches[1];
                    continue 2;
                }

                $errors[] = 'Smsapi error: message has not been sent!';
                continue 2;
            default:
                $errors[] = trans('Unknown SMS service!');
                continue 2;
        }

        }
        return implode(', ', $errors);
    }

    public function GetMessages($customerid, $limit = NULL)
    {
        $manager = $this->getMessageManager();
        return $manager->GetMessages($customerid, $limit);
    }

    public function GetDocuments($customerid = NULL, $limit = NULL)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetDocuments($customerid, $limit);
    }

    public function GetTaxes($from = NULL, $to = NULL)
    {
        $manager = $this->getFinanaceManager();
        return $manager->GetTaxes($from, $to);
    }

    public function EventSearch($search, $order = 'date,asc', $simple = false)
    {
        $manager = $this->getEventManager();
        return $manager->EventSearch($search, $order, $simple);
    }

    public function GetNumberPlans($doctype = NULL, $cdate = NULL, $division = NULL, $next = true)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetNumberPlans($doctype, $cdate, $division, $next);
    }

    public function GetNewDocumentNumber($doctype = NULL, $planid = NULL, $cdate = NULL)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetNewDocumentNumber($doctype, $planid, $cdate);
    }

    public function DocumentExists($number, $doctype = NULL, $planid = 0, $cdate = NULL)
    {
        $manager = $this->getDocumentManager();
        return $manager->DocumentExists($number, $doctype, $planid, $cdate);
    }

    public function GetCountryStates()
    {
        $manager = $this->getLocationManager();
        return $manager->GetCountryStates();
    }

    public function GetCountries()
    {
        $manager = $this->getLocationManager();
        return $manager->GetCountries();
    }

    public function GetCountryName($id)
    {
        $manager = $this->getLocationManager();
        return $manager->GetCountryName($id);
    }

    public function UpdateCountryState($zip, $stateid)
    {
        $manager = $this->getLocationManager();
        return $manager->UpdateCountryState($zip, $stateid);
    }

    public function GetNAStypes()
    {
        return $this->DB->GetAllByKey('SELECT id, name FROM nastypes ORDER BY name', 'id');
    }

    public function CalcAt($period, $date)
    {
        $manager = $this->getFinanaceManager();
        return $manager->CalcAt($period, $date);
    }

    /**
     * VoIP functions
     */
    public function GetVoipAccountList($order = 'login,asc', $search = NULL, $sqlskey = 'AND')
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getVoipAccountList($order, $search, $sqlskey);
    }

    public function VoipAccountSet($id, $access = -1)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->voipAccountSet($id, $access);
    }

    public function VoipAccountSetU($id, $access = false)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->voipAccountSetU($id, $access);
    }

    public function VoipAccountAdd($voipaccountdata)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->VoipAccountAdd($voipaccountdata);
    }

    public function VoipAccountExists($id)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->voipAccountExists($id);
    }

    public function GetVoipAccountOwner($id)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getVoipAccountOwner($id);
    }

    public function GetVoipAccount($id)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getVoipAccount($id);
    }

    public function GetVoipAccountIDByLogin($login)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->GetVoipAccountIDByLogin($login);
    }

    public function GetVoipAccountIDByPhone($phone)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getVoipAccountIDByPhone($phone);
    }

    public function GetVoipAccountLogin($id)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getVoipAccountLogin($id);
    }

    public function DeleteVoipAccount($id)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->deleteVoipAccount($id);
    }

    public function VoipAccountUpdate($voipaccountdata)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->voipAccountUpdate($voipaccountdata);
    }

    public function GetCustomerVoipAccounts($id)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getCustomerVoipAccounts($id);
    }

    public function GetConfigSections()
    {
        $manager = $this->getConfigManager();
        return $manager->GetConfigSections();
    }

    public function GetNodeSessions($nodeid)
    {
        $nodesessions = $this->DB->GetAll('SELECT INET_NTOA(ipaddr) AS ipaddr, mac, start, stop,
		download, upload, terminatecause, type
		FROM nodesessions WHERE nodeid = ? ORDER BY stop DESC LIMIT ' . intval(ConfigHelper::getConfig('phpui.nodesession_limit', 10)),
			array($nodeid));
        if (!empty($nodesessions))
            foreach ($nodesessions as $idx => $session) {
                list ($number, $unit) = setunits($session['download']);
                $nodesessions[$idx]['download'] = round($number, 2) . ' ' . $unit;
                list ($number, $unit) = setunits($session['upload']);
                $nodesessions[$idx]['upload'] = round($number, 2) . ' ' . $unit;
                $nodesessions[$idx]['duration'] = uptimef($session['stop'] - $session['start']);
            }
        return $nodesessions;
    }

    public function AddMessageTemplate($type, $name, $subject, $message)
    {
        $manager = $this->getMessageManager();
        return $manager->AddMessageTemplate($type, $name, $subject, $message);
    }

    public function UpdateMessageTemplate($id, $type, $name, $subject, $message)
    {
        $manager = $this->getMessageManager();
        return $manager->UpdateMessageTemplate($id, $type, $name, $subject, $message);
    }

    public function GetMessageTemplates($type)
    {
        $manager = $this->getMessageManager();
        return $manager->GetMessageTemplates($type);
    }

    /**
     * Returns user manager
     * 
     * @return \LMSUserManagerInterface User manager
     */
    protected function getUserManager()
    {
        if (!isset($this->user_manager)) {
            $this->user_manager = new LMSUserManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->user_manager;
    }

    /**
     * Returns customer manager
     * 
     * @return \LMSCustomerManagerInterface Customer manager
     */
    protected function getCustomerManager()
    {
        if (!isset($this->customer_manager)) {
            $this->customer_manager = new LMSCustomerManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->customer_manager;
    }

    /**
     * Returns VoIP account manager
     * 
     * @return LMSVoipAccountManagerInterface VoIP account manager
     */
    protected function getVoipAccountManager()
    {
        if (!isset($this->voip_account_manager)) {
            $this->voip_account_manager = new LMSVoipAccountManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->voip_account_manager;
    }

    /**
     * Returns location manager
     * 
     * @return LMSLocationManagerInterface Location manager
     */
    protected function getLocationManager()
    {
        if (!isset($this->location_manager)) {
            $this->location_manager = new LMSLocationManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->location_manager;
    }

    /**
     * Returns cash manager
     * 
     * @return LMSCashManagerInterface Cash manager
     */
    protected function getCashManager()
    {
        if (!isset($this->cash_manager)) {
            $this->cash_manager = new LMSCashManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->cash_manager;
    }

    /**
     * Returns customer group manager
     * 
     * @return LMSCustomerGroupManagerInterface Customer group manager
     */
    protected function getCustomerGroupManager()
    {
        if (!isset($this->customer_group_manager)) {
            $this->customer_group_manager = new LMSCustomerGroupManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->customer_group_manager;
    }

    /**
     * Returns network manager
     * 
     * @return LMSNetworkManagerInterface Network manager
     */
    protected function getNetworkManager()
    {
        if (!isset($this->network_manager)) {
            $this->network_manager = new LMSNetworkManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->network_manager;
    }

    /**
     * Returns node manager
     * 
     * @return LMSNodeManagerInterface Node manager
     */
    protected function getNodeManager()
    {
        if (!isset($this->node_manager)) {
            $this->node_manager = new LMSNodeManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->node_manager;
    }

    /**
     * Returns node group manager
     * 
     * @return LMSNodeGroupManagerInterface Node group manager
     */
    protected function getNodeGroupManager()
    {
        if (!isset($this->node_group_manager)) {
            $this->node_group_manager = new LMSNodeGroupManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->node_group_manager;
    }

    /**
     * Returns net dev manager
     * 
     * @return LMSNetDevManagerInterface Net dev manager
     */
    protected function getNetDevManager()
    {
        if (!isset($this->net_dev_manager)) {
            $this->net_dev_manager = new LMSNetDevManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->net_dev_manager;
    }

    /**
     * Returns helpdesk manager
     * 
     * @return LMSHelpdeskManagerInterface Helpdesk manager
     */
    protected function getHelpdeskManager()
    {
        if (!isset($this->helpdesk_manager)) {
            $this->helpdesk_manager = new LMSHelpdeskManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->helpdesk_manager;
    }

    /**
     * Returns finance manager
     * 
     * @return LMSFinanceManagerInterface Finance manager
     */
    protected function getFinanaceManager()
    {
        if (!isset($this->finance_manager)) {
            $this->finance_manager = new LMSFinanceManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->finance_manager;
    }

    /**
     * Returns event manager
     * 
     * @return LMSEventManagerInterface Event manager
     */
    protected function getEventManager()
    {
        if (!isset($this->event_manager)) {
            $this->event_manager = new LMSEventManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->event_manager;
    }

    /**
     * Returns document manager
     * 
     * @return LMSDocumentManagerInterface Document manager
     */
    protected function getDocumentManager()
    {
        if (!isset($this->document_manager)) {
            $this->document_manager = new LMSDocumentManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->document_manager;
    }

    /**
     * Returns message manager
     * 
     * @return LMSMessageManagerInterface Message manager
     */
    protected function getMessageManager()
    {
        if (!isset($this->message_manager)) {
            $this->message_manager = new LMSMessageManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->message_manager;
    }

    /**
     * Returns config manager
     * 
     * @return LMSConfigManagerInterface Message manager
     */
    protected function getConfigManager()
    {
        if (!isset($this->config_manager)) {
            $this->config_manager = new LMSConfigManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->config_manager;
    }
    
    /**
     * Returns database handler
     * 
     * @return LMSDBInterface Database handler
     */
    public function getDb()
    {
        return $this->DB;
    }
    
    /**
     * Returns authorization handler
     * 
     * @return Auth Authorization handler
     */
    public function getAuth()
    {
        return $this->AUTH;
    }
    
    /**
     * Returns internal cache handler
     * 
     * @return LMSCache Internal cache handler
     */
    public function getCache()
    {
        return $this->cache;
    }
    
    /**
     * Returns syslog
     * 
     * @return Syslog Syslog
     */
    public function getSyslog()
    {
        return $this->SYSLOG;
    }

    /**
     * Sets user manager
     * 
     * @param LMSUserManagerInterface $manager Manager
     */
    public function setUserManager(LMSUserManagerInterface $manager)
    {
        $this->user_manager = $manager;
    }

    /**
     * Sets customer manager
     * 
     * @param LMSCustomerManagerInterface $manager Manager
     */
    public function setCustomerManager(LMSCustomerManagerInterface $manager)
    {
        $this->customer_manager = $manager;
    }

    /**
     * Sets customer manager
     * 
     * @param LMSCustomerGroupManagerInterface $manager Manager
     */
    public function setCustomerGroupManager(LMSCustomerGroupManagerInterface $manager)
    {
        $this->customer_group_manager = $manager;
    }

    /**
     * Sets cash manager
     * 
     * @param LMSCashManagerInterface $manager Manager
     */
    public function setCashManager(LMSCashManagerInterface $manager)
    {
        $this->cash_manager = $manager;
    }

    /**
     * Sets network manager
     * 
     * @param LMSNetworkManagerInterface $manager Manager
     */
    public function setNetworkManager(LMSNetworkManagerInterface $manager)
    {
        $this->network_manager = $manager;
    }

    /**
     * Sets voip account manager
     * 
     * @param LMSVoipAccountManagerInterface $manager Manager
     */
    public function setVoipAccountManager(LMSVoipAccountManagerInterface $manager)
    {
        $this->voip_account_manager = $manager;
    }

    /**
     * Sets location manager
     * 
     * @param LMSLocationManagerInterface $manager Manager
     */
    public function setLocationManager(LMSLocationManagerInterface $manager)
    {
        $this->location_manager = $manager;
    }

    /**
     * Sets node manager
     * 
     * @param LMSNodeManagerInterface $manager Manager
     */
    public function setNodeManager(LMSNodeManagerInterface $manager)
    {
        $this->node_manager = $manager;
    }

    /**
     * Sets node group manager
     * 
     * @param LMSNodeGroupManagerInterface $manager Manager
     */
    public function setNodeGroupManager(LMSNodeGroupManagerInterface $manager)
    {
        $this->node_group_manager = $manager;
    }

    /**
     * Sets net dev manager
     * 
     * @param LMSNetDevManagerInterface $manager Manager
     */
    public function setNetDevManager(LMSNetDevManagerInterface $manager)
    {
        $this->net_dev_manager = $manager;
    }

    /**
     * Sets helpdesk manager
     * 
     * @param LMSHelpdeskManagerInterface $manager Manager
     */
    public function setHelpdeskManager(LMSHelpdeskManagerInterface $manager)
    {
        $this->helpdesk_manager = $manager;
    }

    /**
     * Sets fianance manager
     * 
     * @param LMSFinanceManagerInterface $manager Manager
     */
    public function setFinanaceManager(LMSFinanceManagerInterface $manager)
    {
        $this->finance_manager = $manager;
    }

    /**
     * Sets event manager
     * 
     * @param LMSEventManagerInterface $manager Manager
     */
    public function setEventManager(LMSEventManagerInterface $manager)
    {
        $this->event_manager = $manager;
    }

    /**
     * Sets document manager
     * 
     * @param LMSDocumentManagerInterface $manager Manager
     */
    public function setDocumentManager(LMSDocumentManagerInterface $manager)
    {
        $this->document_manager = $manager;
    }

    /**
     * Sets message manager
     * 
     * @param LMSMessageManagerInterface $manager Manager
     */
    public function setMessageManager(LMSMessageManagerInterface $manager)
    {
        $this->message_manager = $manager;
    }

    /**
     * Sets config manager
     * 
     * @param LMSConfigManagerInterface $manager Manager
     */
    public function setConfigManager(LMSConfigManagerInterface $manager)
    {
        $this->config_manager = $manager;
    }
    
    /**
     * Sets database connection handler
     * 
     * @param LMSDBInterface $db Database connection handler
     */
    public function setDb(LMSDBInterface $db)
    {
        $this->DB = $db;
    }
    
    /**
     * Sets authorization handler
     * 
     * @param AUTH $auth Authorization handler
     */
    public function setAuth(AUTH $auth)
    {
        $this->AUTH = $auth;
    }
    
    /**
     * Sets internal cache handler
     * 
     * @param LMSCache $cache Internal cache handler
     */
    public function setCache(LMSCache $cache)
    {
        $this->cache = $cache;
    }
    
    /**
     * Sets syslog
     * 
     * @param Syslog $syslog Syslog
     */
    public function setSyslog(Syslog $syslog)
    {
        $this->SYSLOG = $syslog;
    }

}
