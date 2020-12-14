<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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
    const SOFTWARE_NAME = 'LMS';
    const SOFTWARE_VERSION = '26-git';
    const SOFTWARE_REVISION = '$Format:%cI$'; // %H for last commit checksum

    public $DB;   // database object
    public $AUTH;   // object from Session.class.php (session management)
    public $SYSLOG;
    public $cache;  // internal cache
    public $hooks = array(); // registered plugin hooks
    public $xajax;  // xajax object
    private $mail_object = null;
    private static $lms = null;
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
    protected $net_node_manager;
    protected $helpdesk_manager;
    protected $finance_manager;
    protected $event_manager;
    protected $document_manager;
    protected $message_manager;
    protected $config_manager;
    protected $user_group_manager;
    protected $division_manager;
    protected $project_manager;
    protected $file_manager;

    const DB_DUMP_MULTI_RECORD_LIMIT = 500;

    public function __construct(&$DB, &$AUTH, &$SYSLOG)
    {
        // class variables setting
        $this->DB = &$DB;
        $this->AUTH = &$AUTH;
        $this->SYSLOG = &$SYSLOG;

        $this->cache = new LMSCache();

        if (!isset(self::$lms)) {
            self::$lms = $this;
        }
    }

    public static function getSoftwareRevision()
    {
        if (preg_match('/.+Format:.+/', self::SOFTWARE_REVISION)) {
            return '';
        } else {
            return self::SOFTWARE_REVISION;
        }
    }

    public function _postinit()
    {
        return true;
    }

    public static function getInstance()
    {
        return self::$lms;
    }


    public function InitUI()
    {
        // set current user
        switch (ConfigHelper::getConfig('database.type')) {
            case 'postgres':
                $this->DB->Execute('SELECT set_config(\'lms.current_user\', ?, false)', array(strval(Auth::GetCurrentUser())));
                break;
            case 'mysql':
            case 'mysqli':
                $this->DB->Execute('SET @lms_current_user=?', array(Auth::GetCurrentUser()));
                break;
        }
    }

    public function InitXajax()
    {
        if (!$this->xajax) {
            require(LIB_DIR . DIRECTORY_SEPARATOR . 'xajax' . DIRECTORY_SEPARATOR . 'xajax_core' . DIRECTORY_SEPARATOR . 'xajax.inc.php');
            $this->xajax = new xajax();
            $this->xajax->configure('errorHandler', true);
            $this->xajax->configure('javascript Dir', SYS_DIR . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'xajax_js');
            $this->xajax->configure('javascript URI', 'js/xajax_js');
            //$this->xajax->configure('deferScriptGeneration', false);
        }
    }

    public function RunXajax()
    {
        $xajax_js = null;
        if ($this->xajax) {
            $xajax_js = $this->xajax->getJavascript();
            $this->xajax->processRequest();
        }
        return $xajax_js;
    }

    public function RegisterXajaxFunction($funcname)
    {
        if ($this->xajax) {
            if (is_array($funcname)) {
                foreach ($funcname as $func) {
                    $this->xajax->register(XAJAX_FUNCTION, $func);
                }
            } else {
                $this->xajax->register(XAJAX_FUNCTION, $funcname);
            }
        }
    }

    /*
     *  Logging
     *  0 - disabled
     *  1 - system log in and modules calls without access privileges
     *  2 - as above, addition and deletion
     *  3 - as above, and changes
     *  4 - as above, and all modules calls (paranoid)
     */
    /*
      public function Log($loglevel=0, $message=NULL)
      {
      if( $loglevel <= ConfigHelper::getConfig('phpui.loglevel') && $message )
      {
      $this->DB->Execute('INSERT INTO syslog (time, userid, level, message)
      VALUES (?NOW?, ?, ?, ?)', array(Auth::GetCurrentUser(), $loglevel, $message));
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
        if (!empty($this->plugin_manager)) {
            return $this->plugin_manager->executeHook($hook_name, $hook_data);
        } else {
            return $hook_data;
        }
    }

    /*
     *  Database functions (backups)
     */

    public function DBDump($filename = null, $gzipped = false, $stats = false)
    {
        // dump database to file
        $dbtype = ConfigHelper::getConfig('database.type');

        if (!$filename) {
            return false;
        }

        if ($gzipped && extension_loaded('zlib')) {
            $dumpfile = gzopen($filename, 'w');
        } else {
            $dumpfile = fopen($filename, 'w');
        }

        if ($dumpfile) {
            $tables = $this->DB->ListTables();

            switch ($dbtype) {
                case 'postgres':
                    fputs($dumpfile, "SET CONSTRAINTS ALL DEFERRED;\n");
                    $value_prefix = 'E';
                    break;
                case 'mysql':
                case 'mysqli':
                    fputs($dumpfile, "SET foreign_key_checks = 0;\n");
                    $value_prefix = '';
                    break;
            }

            foreach ($tables as $tablename) {
                // skip sessions table for security
                if ($tablename == 'sessions' || ($tablename == 'stats' && $stats == false)) {
                    continue;
                }

                fputs($dumpfile, "DELETE FROM $tablename;\n");
            }

            // Since we're using foreign keys, order of tables is important
            // Note: add all referenced tables to the list
            $order = array(
                'users', 'twofactorauthcodehistory', 'twofactorauthtrusteddevices',
                'countries', 'location_states', 'location_districts', 'location_boroughs',
                'location_cities', 'location_street_types', 'location_streets',
                'location_buildings', 'addresses', 'divisions', 'customers',
                'customerconsents', 'customernotes', 'numberplans', 'states', 'zipcodes',
                'customer_addresses', 'documents', 'documentcontents', 'documentattachments',
                'cashregs', 'receiptcontents', 'taxes', 'voipaccounts', 'voip_rule_groups',
                'voip_prefix_groups', 'voip_rules', 'voip_tariffs', 'voip_rule_states',
                'voip_prefixes', 'voip_cdr', 'voip_price_groups', 'tariffs', 'voip_numbers',
                'voip_pool_numbers', 'voip_emergency_numbers', 'liabilities', 'assignments',
                'voip_number_assignments', 'invoicecontents', 'debitnotecontents',
                'cashsources', 'sourcefiles', 'cashimport', 'customerbalances', 'cash', 'pna',
                'ewx_channels', 'ewx_stm_channels', 'hosts', 'networks', 'invprojects',
                'netnodes', 'netdeviceproducers', 'netdevicemodels', 'netdevices',
                'netradiosectors', 'nodes', 'ewx_stm_nodes', 'nodelocks', 'macs', 'nodegroups',
                'nodegroupassignments', 'nodeassignments', 'tarifftags', 'tariffassignments',
                'promotions', 'promotionschemas', 'promotionassignments', 'payments',
                'numberplanassignments', 'customergroups', 'customerassignments',
                'nodesessions', 'stats', 'netlinks', 'rtqueues', 'rttickets',
                'rtticketlastview', 'rtmessages', 'rtrights', 'rtattachments', 'rtcategories',
                'rtcategoryusers', 'rtticketcategories', 'rtqueuecategories', 'domains',
                'passwd', 'records', 'domainmetadata', 'supermasters', 'comments',
                'cryptokeys', 'tsigkeys', 'aliases', 'aliasassignments', 'uiconfig', 'events',
                'eventassignments', 'sessions', 'daemoninstances', 'daemonconfig', 'docrights',
                'cashrights', 'cashreglog', 'ewx_pt_config', 'dbinfo', 'customercontacts',
                'excludedgroups', 'messages', 'messageitems', 'nastypes', 'managementurls',
                'logtransactions', 'logmessages', 'logmessagekeys', 'logmessagedata',
                'templates', 'rttemplatetypes', 'rttemplatequeues', 'usergroups',
                'userassignments', 'userdivisions', 'passwdhistory', 'filecontainers', 'files',
                'up_rights', 'up_rights_assignments', 'up_customers', 'up_help',
                'up_info_changes'
            );

            foreach ($tables as $idx => $table) {
                if (in_array($table, $order)) {
                    unset($tables[$idx]);
                }
            }

            $tables = array_merge($order, $tables);

            foreach ($tables as $tablename) {
                // skip sessions table for security
                if ($tablename == 'sessions' || ($tablename == 'stats' && $stats == false)) {
                    continue;
                }

                $record = $this->DB->GetRow('SELECT * FROM ' . $tablename . ' LIMIT 1');
                if (empty($record)) {
                    continue;
                }
                $fields = array_keys($record);

                $query = 'INSERT INTO ' . $tablename . ' (' . implode(',', $fields) . ') VALUES ';
                $record_limit = self::DB_DUMP_MULTI_RECORD_LIMIT;
                $records = array();
                $this->DB->Execute('SELECT * FROM ' . $tablename . (isset($record['id']) ? ' ORDER BY id' : ''));
                while ($row = $this->DB->_driver_fetchrow_assoc()) {
                    $values = array();
                    foreach ($row as $value) {
                        if (isset($value)) {
                            $values[] = $value_prefix . "'" . addcslashes($value, "\r\n\'\"\\") . "'";
                        } else {
                            $values[] = 'NULL';
                        }
                    }
                    $records[] = '(' . implode(', ', $values) . ')';
                    $record_limit--;
                    if (!$record_limit) {
                        fputs($dumpfile, $query . implode(',', $records) . ";\n");
                        $records = array();
                        $record_limit = self::DB_DUMP_MULTI_RECORD_LIMIT;
                    }
                }
                if ($record_limit < self::DB_DUMP_MULTI_RECORD_LIMIT) {
                    fputs($dumpfile, $query . implode(',', $records) . ";\n");
                }
            }

            if (preg_match('/^mysqli?$/', $dbtype)) {
                fputs($dumpfile, "SET foreign_key_checks = 1;\n");
            }

            if ($gzipped && extension_loaded('zlib')) {
                gzclose($dumpfile);
            } else {
                fclose($dumpfile);
            }
        } else {
            return false;
        }
    }

    public function DatabaseCreate($gzipped = false, $stats = false)
    {
        // create database backup
        $basename = 'lms-' . time() . '-' . DBVERSION;
        if (($gzipped) && (extension_loaded('zlib'))) {
            $filename = $basename . '.sql.gz';
            $res = $this->DBDump(ConfigHelper::getConfig('directories.backup_dir') . DIRECTORY_SEPARATOR . $filename, true, $stats);
        } else {
            $filename = $basename . '.sql';
            $res = $this->DBDump(ConfigHelper::getConfig('directories.backup_dir') . DIRECTORY_SEPARATOR . $filename, false, $stats);
        }
        if ($this->SYSLOG) {
            $this->SYSLOG->AddMessage(SYSLOG::RES_DBBACKUP, SYSLOG::OPER_ADD, array('filename' => $filename));
        }
        return $res;
    }

    /*
     * Users
     */

    public function SetUserPassword($id, $passwd)
    {
        $manager = $this->getUserManager();
        return $manager->setUserPassword($id, $passwd);
    }

    public function forcePasswordChange($id)
    {
        $manager = $this->getUserManager();
        return $manager->forcePasswordChange($id);
    }

    public function SetUserAuthentication($id, $twofactorauth, $twofactorauthsecretkey)
    {
        $manager = $this->getUserManager();
        return $manager->SetUserAuthentication($id, $twofactorauth, $twofactorauthsecretkey);
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

    public function getUserNamesIndexedById()
    {
        $manager = $this->getUserManager();
        return $manager->getUserNamesIndexedById();
    }

    public function GetUserList($params = array())
    {
        $manager = $this->getUserManager();
        return $manager->getUserList($params);
    }

    public function GetUsers($params = array())
    {
        $manager = $this->getUserManager();
        return $manager->getUsers($params);
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

    public function PasswdExistsInHistory($id, $passwd)
    {
        $manager = $this->getUserManager();
        return $manager->PasswdExistsInHistory($id, $passwd);
    }

    public function checkPassword($password)
    {
        $manager = $this->getUserManager();
        return $manager->checkPassword($password);
    }

    /*
     *  Customers functions
     */

    public function GetCustomerName($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerName($id);
    }

    public function GetCustomerEmail($id, $requiredFlags = 0, $forbiddenFlags = 0)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerEmail($id, $requiredFlags, $forbiddenFlags);
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

    public function CashImportParseFile($filename, $contents, $patterns, $quiet = true, $filemtime = null)
    {
        $manager = $this->getCashManager();
        return $manager->CashImportParseFile($filename, $contents, $patterns, $quiet, $filemtime);
    }

    public function CashImportCommit()
    {
        $manager = $this->getCashManager();
        return $manager->CashImportCommit();
    }

    public function GetCustomerStatus($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerStatus($id);
    }

    public function getCustomerConsents($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerConsents($id);
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

    public function getCustomerList($params)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerList($params);
    }

    public function GetCustomerNodes($id, $count = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNodes($id, $count);
    }

    public function getCustomerNetDevNodes($id, $count = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNetDevNodes($id, $count);
    }

    public function GetCustomerNetDevs($customer_id)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerNetDevs($customer_id);
    }

    public function GetCustomerNetworks($id, $count = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerNetworks($id, $count);
    }

    public function GetCustomerBalance($id, $totime = null, $expired = false)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerBalance($id, $totime, $expired);
    }

    public function GetCustomerBalanceList($id, $totime = null, $direction = 'ASC', $aggregate_documents = false)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerBalanceList($id, $totime, $direction, $aggregate_documents);
    }

    public function GetCustomerShortBalanceList($customerid, $limit = 10, $order = 'DESC')
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerShortBalanceList($customerid, $limit, $order);
    }

    public function getLastNInTable($body, $customerid, $format, $aggregate_documents = false)
    {
        $manager = $this->getCustomerManager();
        return $manager->getLastNInTable($body, $customerid, $format, $aggregate_documents);
    }

    public function CustomerStats()
    {
        $manager = $this->getCustomerManager();
        return $manager->customerStats();
    }

    public function updateCustomerConsents($customerid, $current_consents, $new_consents)
    {
        $manager = $this->getCustomerManager();
        return $manager->updateCustomerConsents($customerid, $current_consents, $new_consents);
    }

    public function checkCustomerAddress($a_id, $c_id)
    {
        $manager = $this->getCustomerManager();
        return $manager->checkCustomerAddress($a_id, $c_id);
    }

    public function determineDefaultCustomerAddress(array &$caddr)
    {
        $manager = $this->getCustomerManager();
        return $manager->determineDefaultCustomerAddress($caddr);
    }

    public function getCustomerAddresses($id, $hide_deleted = false)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerAddresses($id, $hide_deleted);
    }

    public function getAddressForCustomerStuff($customer_id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getAddressForCustomerStuff($customer_id);
    }

    public function getFullAddressForCustomerStuff($customer_id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getFullAddressForCustomerStuff($customer_id);
    }

    public function isTerritAddress($address_id)
    {
        $manager = $this->getCustomerManager();
        return $manager->isTerritAddress($address_id);
    }

    public function GetCustomerContacts($id, $mask = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerContacts($id, $mask);
    }

    public function GetCustomerDivision($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerDivision($id);
    }

    public function isSplitPaymentSuggested($customerid, $cdate, $value)
    {
        $manager = $this->getCustomerManager();
        return $manager->isSplitPaymentSuggested($customerid, $cdate, $value);
    }

    public function isTelecomServiceSuggested($customerid)
    {
        $manager = $this->getCustomerManager();
        return $manager->isTelecomServiceSuggested($customerid);
    }

    public function getCustomerSMSOptions()
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerSMSOptions();
    }

    public function GetCustomerAddressesWithoutEndPoints($customerid)
    {
        $manager = $this->getCustomerManager();
        return $manager->GetCustomerAddressesWithoutEndPoints($customerid);
    }

    public function checkCustomerTenExistence($customerid, $ten, $divisionid = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->checkCustomerTenExistence($customerid, $ten, $divisionid);
    }

    public function checkCustomerSsnExistence($customerid, $ssn, $divisionid = null)
    {
        $manager = $this->getCustomerManager();
        return $manager->checkCustomerSsnExistence($customerid, $ssn, $divisionid);
    }

    public function checkCustomerConsent($customerid, $consent)
    {
        $manager = $this->getCustomerManager();
        return $manager->checkCustomerConsent($customerid, $consent);
    }

    public function customerNotificationReplaceSymbols($string, $data)
    {
        $manager = $this->getCustomerManager();
        return $manager->customerNotificationReplaceSymbols($string, $data);
    }

    public function addCustomerConsents($customerid, $consents)
    {
        $manager = $this->getCustomerManager();
        return $manager->addCustomerConsents($customerid, $consents);
    }

    public function removeCustomerConsents($customerid, $consents)
    {
        $manager = $this->getCustomerManager();
        return $manager->removeCustomerConsents($customerid, $consents);
    }

    public function addCustomerContactFlags($customerid, $type, $flags)
    {
        $manager = $this->getCustomerManager();
        return $manager->addCustomerContactFlags($customerid, $type, $flags);
    }

    public function removeCustomerContactFlags($customerid, $type, $flags)
    {
        $manager = $this->getCustomerManager();
        return $manager->removeCustomerContactFlags($customerid, $type, $flags);
    }

    public function getCustomerNotes($cid)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNotes($cid);
    }

    public function getCustomerNote($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerNote($id);
    }

    public function addCustomerNote($params)
    {
        $manager = $this->getCustomerManager();
        return $manager->addCustomerNote($params);
    }

    public function delCustomerNote($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->delCustomerNote($id);
    }

    public function raiseCustomerKarma($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->raiseCustomerKarma($id);
    }

    public function lowerCustomerKarma($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->lowerCustomerKarma($id);
    }

    public function getCustomerPin($id)
    {
        $manager = $this->getCustomerManager();
        return $manager->getCustomerPin($id);
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

    public function CustomergroupGet($id, $network = null)
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

    public function GetCustomerWithoutGroupNames($groupid, $network = null)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->GetCustomerWithoutGroupNames($groupid, $network);
    }

    public function getAllCustomerGroups()
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->getAllCustomerGroups();
    }

    public function getExcludedCustomerGroups($userid)
    {
        $manager = $this->getCustomerGroupManager();
        return $manager->getExcludedCustomerGroups($userid);
    }

    /*
     *  Nodes functions
     */

    public function GetNodeOwner($id)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeOwner($id);
    }

    public function NodeUpdate($nodedata, $deleteassignments = false)
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

    public function GetNodeIDByNetName($name)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeIDByNetName($name);
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

    public function GetNodeList(array $params = array())
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeList($params);
    }

    public function NodeSet($id, $access = -1)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSet($id, $access);
    }

    public function NodeSetU($id, $access = false)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSetU($id, $access);
    }

    public function NodeSetWarn($id, $warning = false)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSetWarn($id, $warning);
    }

    public function NodeSwitchWarn($id)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSwitchWarn($id);
    }

    public function NodeSetWarnU($id, $warning = false)
    {
        $manager = $this->getNodeManager();
        return $manager->NodeSetWarnU($id, $warning);
    }

    public function IPSetU($netdev, $access = false)
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

    public function GetNodesWithoutGroup($groupid, $network = null)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodesWithoutGroup($groupid, $network);
    }

    public function GetNodesWithGroup($groupid, $network = null)
    {
        $manager = $this->getNodeGroupManager();
        return $manager->GetNodesWithGroup($groupid, $network);
    }

    public function GetNodeGroup($id, $network = null)
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

    public function NetDevLinkNode($id, $devid, $link = null)
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

    public function updateNodeField($nodeid, $field, $value)
    {
        $manager = $this->getNodeManager();
        return $manager->updateNodeField($nodeid, $field, $value);
    }

    public function GetUniqueNodeLocations($customerid)
    {
        $manager = $this->getNodeManager();
        return $manager->GetUniqueNodeLocations($customerid);
    }

    public function GetNodeLocations($customerid, $address_id = null)
    {
        $manager = $this->getNodeManager();
        return $manager->GetNodeLocations($customerid, $address_id);
    }

    public function getNodeCustomerAssignments($nodeid, $assignments)
    {
        $manager = $this->getNodeManager();
        return $manager->getNodeCustomerAssignments($nodeid, $assignments);
    }

    public function getNodeRoutedNetworks($nodeid)
    {
        $manager = $this->getNodeManager();
        return $manager->getNodeRoutedNetworks($nodeid);
    }

    public function getNodeNotRoutedNetworks($nodeid)
    {
        $manager = $this->getNodeManager();
        return $manager->getNodeNotRoutedNetworks($nodeid);
    }

    public function addNodeRoutedNetworks(array $params)
    {
        $manager = $this->getNodeManager();
        return $manager->addNodeRoutedNetworks($params);
    }

    public function deleteNodeRoutedNetworks(array $params)
    {
        $manager = $this->getNodeManager();
        return $manager->deleteNodeRoutedNetworks($params);
    }

    /*
     *  Tarrifs and finances
     */

    public function GetPromotionNameBySchemaID($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPromotionNameBySchemaID($id);
    }

    public function GetPromotionNameByID($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPromotionNameByID($id);
    }

    public function GetCustomerTariffsValue($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetCustomerTariffsValue($id);
    }

    public function GetCustomerAssignmentValue($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetCustomerAssignmentValue($id);
    }

    public function GetCustomerAssignments($id, $show_expired = false, $show_approved = true)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetCustomerAssignments($id, $show_expired, $show_approved);
    }

    public function GetCustomerServiceSummary($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetCustomerServiceSummary($id);
    }

    public function DeleteAssignment($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->DeleteAssignment($id);
    }

    public function AddAssignment($data)
    {
        $manager = $this->getFinanceManager();
        return $manager->AddAssignment($data);
    }

    public function ValidateAssignment($data)
    {
        $manager = $this->getFinanceManager();
        return $manager->ValidateAssignment($data);
    }

    public function CheckSchemaModifiedValues($data)
    {
        $manager = $this->getFinanceManager();
        return $manager->CheckSchemaModifiedValues($data);
    }

    public function UpdateExistingAssignments($data)
    {
        $manager = $this->getFinanceManager();
        return $manager->UpdateExistingAssignments($data);
    }

    public function SuspendAssignment($id, $suspend = true)
    {
        $manager = $this->getFinanceManager();
        return $manager->SuspendAssignment($id, $suspend);
    }

    public function GetInvoiceList(array $params)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetInvoiceList($params);
    }

    public function AddInvoice($invoice)
    {
        $manager = $this->getFinanceManager();
        return $manager->AddInvoice($invoice);
    }

    public function InvoiceDelete($invoiceid)
    {
        $manager = $this->getFinanceManager();
        return $manager->InvoiceDelete($invoiceid);
    }

    public function InvoiceContentDelete($invoiceid, $itemid = 0)
    {
        $manager = $this->getFinanceManager();
        return $manager->InvoiceContentDelete($invoiceid, $itemid);
    }

    public function GetTradeDocumentArchiveStats($ids)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetTradeDocumentArchiveStats($ids);
    }

    public function DeleteArchiveTradeDocument($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->DeleteArchiveTradeDocument($id);
    }

    public function ArchiveTradeDocument($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->ArchiveTradeDocument($id);
    }

    public function GetTradeDocument($doc)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetTradeDocument($doc);
    }

    public function GetInvoiceContent($invoiceid, $detail_level = LMSFinanceManager::INVOICE_CONTENT_DETAIL_ALL)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetInvoiceContent($invoiceid, $detail_level);
    }

    public function GetNoteList(array $params)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetNoteList($params);
    }

    public function GetNoteContent($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetNoteContent($id);
    }

    public function TariffAdd($tariff)
    {
        $manager = $this->getFinanceManager();
        return $manager->TariffAdd($tariff);
    }

    public function TariffUpdate($tariff)
    {
        $manager = $this->getFinanceManager();
        return $manager->TariffUpdate($tariff);
    }

    public function TariffDelete($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->TariffDelete($id);
    }

    public function GetTariff($id, $network = null)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetTariff($id, $network);
    }

    public function GetTariffs($forced_id = null)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetTariffs($forced_id);
    }

    public function TariffSet($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->TariffSet($id);
    }

    public function TariffExists($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->TariffExists($id);
    }

    public function ReceiptDelete($docid)
    {
        $manager = $this->getFinanceManager();
        return $manager->ReceiptDelete($docid);
    }

    public function ReceiptContentDelete($docid, $itemid = 0)
    {
        $manager = $this->getFinanceManager();
        return $manager->ReceiptContentDelete($docid, $itemid);
    }

    public function DebitNoteDelete($noteid)
    {
        $manager = $this->getFinanceManager();
        return $manager->DebitNoteDelete($noteid);
    }

    public function DebitNoteContentDelete($docid, $itemid = 0)
    {
        $manager = $this->getFinanceManager();
        return $manager->DebitNoteContentDelete($docid, $itemid);
    }

    public function AddBalance($addbalance)
    {
        $manager = $this->getFinanceManager();
        return $manager->AddBalance($addbalance);
    }

    public function GetBalanceList(array $params)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetBalanceList($params);
    }

    public function DelBalance($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->DelBalance($id);
    }

    public function PreserveProforma($docid)
    {
        $manager = $this->getFinanceManager();
        return $manager->PreserveProforma($docid);
    }

    /*
     *   Payments
     */

    public function GetPaymentList()
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPaymentList();
    }

    public function GetPayment($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPayment($id);
    }

    public function GetPaymentName($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPaymentName($id);
    }

    public function GetPaymentIDByName($name)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPaymentIDByName($name);
    }

    public function PaymentExists($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->PaymentExists($id);
    }

    public function PaymentAdd($paymentdata)
    {
        $manager = $this->getFinanceManager();
        return $manager->PaymentAdd($paymentdata);
    }

    public function PaymentDelete($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->PaymentDelete($id);
    }

    public function PaymentUpdate($paymentdata)
    {
        $manager = $this->getFinanceManager();
        return $manager->PaymentUpdate($paymentdata);
    }

    public function ScanNodes()
    {
        $manager = $this->getNetworkManager();
        return $manager->ScanNodes();
    }
    public function GetNetworkPageForIp($netid, $ip)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkPageForIp($netid, $ip);
    }

    public function GetPublicNetworkID($netid)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetPublicNetworkID($netid);
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

    public function GetNetworkList(array $search)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkList($search);
    }

    public function IsIPValid($ip, $checkbroadcast = false, $ignoreid = 0)
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

    public function MoveHostsBetweenNetworks($src, $dst)
    {
        $manager = $this->getNetworkManager();
        return $manager->MoveHostsBetweenNetworks($src, $dst);
    }

    public function GetNetworkRecord($id, $page = 0, $plimit = 4294967296, $firstfree = false)
    {
        $manager = $this->getNetworkManager();
        return $manager->GetNetworkRecord($id, $page, $plimit, $firstfree);
    }

    public function GetFirstFreeAddress($netid)
    {
        $manager = $this->getNetworkManager();
        return $manager->getFirstFreeAddress($netid);
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

    public function GetNetDevName($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetNetDevName($id);
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

    public function GetProducers()
    {
        $manager = $this->getNetDevManager();
        return $manager->GetProducers();
    }

    public function GetModels($producerid = null)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetModels($producerid);
    }

    public function GetModelList($pid = null)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetModelList($pid);
    }

    public function GetRadioSectors($netdevid, $technology = 0)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetRadioSectors($netdevid, $technology);
    }

    public function AddRadioSector($netdevid, array $radiosector)
    {
        $manager = $this->getNetDevManager();
        return $manager->AddRadioSector($netdevid, $radiosector);
    }

    public function DeleteRadioSector($id)
    {
        $manager = $this->getNetDevManager();
        return $manager->DeleteRadioSector($id);
    }

    public function UpdateRadioSector($id, array $radiosector)
    {
        $manager = $this->getNetDevManager();
        return $manager->UpdateRadioSector($id, $radiosector);
    }

    public function GetManagementUrls($type, $id)
    {
        $manager = $this->getNetDevManager();
        return $manager->GetManagementUrls($type, $id);
    }

    public function AddManagementUrl($type, $id, array $url)
    {
        $manager = $this->getNetDevManager();
        return $manager->AddManagementUrl($type, $id, $url);
    }

    public function DeleteManagementUrl($type, $id)
    {
        $manager = $this->getNetDevManager();
        return $manager->DeleteManagementUrl($type, $id);
    }

    public function updateManagementUrl($type, $id, array $url)
    {
        $manager = $this->getNetDevManager();
        return $manager->updateManagementUrl($type, $id, $url);
    }

    public function getNetDevCustomerAssignments($netdevid, $assignments)
    {
        $manager = $this->getNetDevManager();
        return $manager->getNetDevCustomerAssignments($netdevid, $assignments);
    }

    public function getNetDevOwnerByNodeId($nodeid)
    {
        $manager = $this->getNetDevManager();
        return $manager->getNetDevOwnerByNodeId($nodeid);
    }

    public function GetNetNode($id)
    {
        $manager = $this->getNetNodeManager();
        return $manager->GetNetNode($id);
    }

    public function GetNetNodeName($id)
    {
        $manager = $this->getNetNodeManager();
        return $manager->GetNetNodeName($id);
    }

    public function GetNetNodes()
    {
        $manager = $this->getNetNodeManager();
        return $manager->GetNetNodes();
    }

    public function GetCustomerNetNodes($id)
    {
        $manager = $this->getNetNodeManager();
        return $manager->GetCustomerNetNodes($id);
    }

    public function GetNetNodeList($search, $order)
    {
        $manager = $this->getNetNodeManager();
        return $manager->GetNetNodeList($search, $order);
    }

    public function NetNodeAdd($netnodedata)
    {
        $manager = $this->getNetNodeManager();
        return $manager->NetNodeAdd($netnodedata);
    }

    public function NetNodeExists($id)
    {
        $manager = $this->getNetNodeManager();
        return $manager->NetNodeExists($id);
    }

    public function NetNodeDelete($id)
    {
        $manager = $this->getNetNodeManager();
        return $manager->NetNodeDelete($id);
    }

    public function NetNodeUpdate($netnodedata)
    {
        $manager = $this->getNetNodeManager();
        return $manager->NetNodeUpdate($netnodedata);
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

    public function GetQueue($id, $short = false)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueue($id, $short);
    }

    public function GetQueueContents(array $params)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueContents($params);
    }

    public function GetUserRightsRT($user, $queue, $ticket = null)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetUserRightsRT($user, $queue, $ticket);
    }

    public function GetQueueList($params)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueList($params);
    }

    public function GetQueueNames()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueNames();
    }

    public function GetMyQueues()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetMyQueues();
    }

    public function QueueExists($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->QueueExists($id);
    }

    public function GetQueueIdByName($queue)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueIdByName($queue);
    }

    public function GetQueueVerifier($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueVerifier($id);
    }

    public function GetQueueNameByTicketId($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueNameByTicketId($id);
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

    public function GetCategoryName($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetCategoryName($id);
    }

    public function GetUserRightsToCategory($user, $category, $ticket = null)
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

    public function GetUserCategories($userid = null)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetUserCategories($userid);
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

/*
    public function SaveTicketMessageAttachments($ticketid, $messageid, $files, $cleanup = false) {
        $manager = $this->getHelpdeskManager();
        return $manager->SaveTicketMessageAttachments($ticketid, $messageid, $files, $cleanup);
    }
*/

    public function TicketMessageAdd($message, $files = null)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketMessageAdd($message, $files);
    }

    public function TicketAdd($ticket, $files = null)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketAdd($ticket, $files);
    }

    public function GetLastMessageID()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetLastMessageID();
    }

    public function LimitQueuesToUserpanelEnabled($queuelist, $queueid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->LimitQueuesToUserpanelEnabled($queuelist, $queueid);
    }

    public function GetTicketContents($id, $short = false)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetTicketContents($id, $short);
    }

    public function TicketChange($ticketid, array $props)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketChange($ticketid, $props);
    }

    public function GetQueueCategories($queueid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetQueueCategories($queueid);
    }

    public function GetMessage($id)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetMessage($id);
    }

    public function GetFirstMessage($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetFirstMessage($ticketid);
    }

    public function GetLastMessage($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetLastMessage($ticketid);
    }

    public function ReplaceNotificationSymbols($text, array $params)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->ReplaceNotificationSymbols($text, $params);
    }

    public function ReplaceNotificationCustomerSymbols($text, array $params)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->ReplaceNotificationCustomerSymbols($text, $params);
    }

    public function NotifyUsers($params)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->NotifyUsers($params);
    }

    public function CleanupTicketLastView()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->CleanupTicketLastView();
    }

    public function MarkQueueAsRead($queueid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->MarkQueueAsRead($queueid);
    }

    public function MarkTicketAsRead($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->MarkTicketAsRead($ticketid);
    }

    public function MarkTicketAsUnread($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->MarkTicketAsUnread($ticketid);
    }

    public function GetIndicatorStats()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetIndicatorStats();
    }

    public function DetermineSenderEmail($queue_email, $ticket_email, $user_email, $forced_order = null)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->DetermineSenderEmail($queue_email, $ticket_email, $user_email, $forced_order);
    }

    public function GetTicketRequestorMail($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetTicketRequestorMail($ticketid);
    }

    public function GetTicketRequestorPhone($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetTicketRequestorPhone($ticketid);
    }

    public function CheckTicketAccess($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->CheckTicketAccess($ticketid);
    }

    public function GetRelatedTickets($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetRelatedTickets($ticketid);
    }

    public function GetChildTickets($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetChildTickets($ticketid);
    }

    public function getTickets($ticketids)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->getTickets($ticketids);
    }

    public function GetTicketParentID($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetTicketParentID($ticketid);
    }

    public function IsTicketLoop($ticketid, $parentid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->IsTicketLoop($ticketid, $parentid);
    }

    public function GetRTSmtpOptions()
    {
        $manager = $this->getHelpdeskManager();
        return $manager->GetRTSmtpOptions();
    }

    public function CopyQueuePermissions($src_userid, $dst_userid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->CopyQueuePermissions($src_userid, $dst_userid);
    }

    public function CopyCategoryPermissions($src_userid, $dst_userid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->CopyCategoryPermissions($src_userid, $dst_userid);
    }

    public function TicketIsAssigned($ticketid)
    {
        $manager = $this->getHelpdeskManager();
        return $manager->TicketIsAssigned($ticketid);
    }

    public function getTicketImageGalleries(&$ticket)
    {
        $manager = $this->getHelpdeskManager();
        $manager->getTicketImageGalleries($ticket);
    }

    /*
     *  LMS-UI configuration
     */

    public function ConfigOptionExists($params)
    {
        $manager = $this->getConfigManager();
        return $manager->ConfigOptionExists($params);
    }

    public function GetConfigDefaultType($option)
    {
        $manager = $this->getConfigManager();
        return $manager->GetConfigDefaultType($option);
    }

    public function CheckOption($option, $value, $type)
    {
        $manager = $this->getConfigManager();
        return $manager->CheckOption($option, $value, $type);
    }

    public function GetConfigVariable($config_id)
    {
        $manager = $this->getConfigManager();
        return $manager->GetConfigVariable($config_id);
    }

    public function cloneConfigs($params)
    {
        $manager = $this->getConfigManager();
        return $manager->cloneConfigs($params);
    }

    public function importConfigs($params)
    {
        $manager = $this->getConfigManager();
        return $manager->importConfigs($params);
    }

    public function DeleteConfigOption($id)
    {
        $manager = $this->getConfigManager();
        return $manager->DeleteConfigOption($id);
    }

    public function getRelatedUsers($id, $divisionid = null)
    {
        $manager = $this->getConfigManager();
        return $manager->getRelatedUsers($id, $divisionid);
    }

    public function getRelatedDivisions($id)
    {
        $manager = $this->getConfigManager();
        return $manager->getRelatedDivisions($id);
    }

    public function getRelatedOptions($id)
    {
        $manager = $this->getConfigManager();
        return $manager->getRelatedOptions($id);
    }

    public function getOptionHierarchy($id)
    {
        $manager = $this->getConfigManager();
        return $manager->getOptionHierarchy($id);
    }

    public function addConfigOption($option)
    {
        $manager = $this->getConfigManager();
        return $manager->addConfigOption($option);
    }

    public function editConfigOption($option)
    {
        $manager = $this->getConfigManager();
        return $manager->editConfigOption($option);
    }

    public function getParentOption($id)
    {
        $manager = $this->getConfigManager();
        return $manager->getParentOption($id);
    }

    public function toggleConfigOption($id)
    {
        $manager = $this->getConfigManager();
        return $manager->toggleConfigOption($id);
    }

    /*
     *  Miscalenous
     */

    public function GetHostingLimits($customerid)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetHostingLimits($customerid);
    }

    public function GetRemoteMACs($host = '127.0.0.1', $port = 1029)
    {
        $inputbuf = '';
        $result = array();

        if ($socket = socket_create(AF_INET, SOCK_STREAM, 0)) {
            if (@socket_connect($socket, $host, $port)) {
                while ($input = socket_read($socket, 2048)) {
                    $inputbuf .= $input;
                }
                socket_close($socket);
            }
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
        } else {
            switch (PHP_OS) {
                case 'Linux':
                    if (@is_readable('/proc/net/arp')) {
                        $file = fopen('/proc/net/arp', 'r');
                    } else {
                        break;
                    }
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

    public function CheckUpdates($force = false)
    {
        $uiid = $this->GetUniqueInstallationID();
        $time = $this->DB->GetOne('SELECT ?NOW?');
        $content = false;
        if ($force == true) {
            $lastcheck = 0;
        } elseif (!($lastcheck = $this->DB->GetOne('SELECT keyvalue FROM dbinfo WHERE keytype=?', array('last_check_for_updates_timestamp')))) {
            $lastcheck = 0;
        }
        if ($lastcheck + ConfigHelper::getConfig('phpui.check_for_updates_period') < $time) {
            list($v, ) = explode(' ', self::SOFTWARE_VERSION);

            if ($content = fetch_url('http://register.lms.org.pl/update.php?uiid=' . $uiid . '&v=' . $v)) {
                if ($lastcheck == 0) {
                    $this->DB->Execute('INSERT INTO dbinfo (keyvalue, keytype) VALUES (?NOW?, ?)', array('last_check_for_updates_timestamp'));
                } else {
                    $this->DB->Execute('UPDATE dbinfo SET keyvalue=?NOW? WHERE keytype=?', array('last_check_for_updates_timestamp'));
                }

                $content = unserialize((string) $content);
                $content['regdata'] = unserialize((string) $content['regdata']);

                if (is_array($content['regdata'])) {
                    $this->DB->Execute('DELETE FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'));

                    foreach (array('id', 'name', 'url', 'hidden') as $key) {
                        $this->DB->Execute('INSERT INTO dbinfo (keytype, keyvalue) VALUES (?, ?)', array('regdata_' . $key, $content['regdata'][$key]));
                    }
                }
            }
        }

        return $content;
    }

    public function GetRegisterData()
    {
        if ($regdata = $this->DB->GetAll('SELECT * FROM dbinfo WHERE keytype LIKE ?', array('regdata_%'))) {
            foreach ($regdata as $regline) {
                $registerdata[str_replace('regdata_', '', $regline['keytype'])] = $regline['keyvalue'];
            }
            return $registerdata;
        }
        return null;
    }

    public function UpdateRegisterData($name, $url, $hidden)
    {
        $name = rawurlencode($name);
        $url = rawurlencode($url);
        $uiid = $this->GetUniqueInstallationID();
        $url = 'http://register.lms.org.pl/register.php?uiid=' . $uiid . '&name=' . $name . '&url=' . $url . ($hidden == true ? '&hidden=1' : '');

        if (fetch_url($url) !== false) {
            // ok, update done, so, let we fall asleep for at least 2 seconds, let's viper put our
            // registration data into database. in future we should read info from register.php,
            // ie. 'Password' incorrect if we protect each installation with password (but then
            // we should use https)

            sleep(5);
            $this->DB->Execute('DELETE FROM dbinfo WHERE keytype = ?', array('last_check_for_updates_timestamp'));
            $this->CheckUpdates(true);
            return true;
        }

        return false;
    }

    public function SendMail($recipients, $headers, $body, $files = null, $persist = null, $smtp_options = null)
    {
        $persist = is_null($persist) ? ConfigHelper::getConfig('mail.smtp_persist', true) : $persist;
        $debug_level = intval(ConfigHelper::getConfig('mail.debug_level', 2));
        if (!$debug_level) {
            $debug_level = 2;
        }

        if (ConfigHelper::getConfig('mail.backend') == 'pear') {
            if (!is_object($this->mail_object) || !$persist) {
                $params['host'] = (!isset($smtp_options['host']) ? ConfigHelper::getConfig('mail.smtp_host') : $smtp_options['host']);
                $params['port'] = (!isset($smtp_options['port']) ? ConfigHelper::getConfig('mail.smtp_port') : $smtp_options['port']);
                $smtp_username = ConfigHelper::getConfig('mail.smtp_username');
                if (!empty($smtp_username) || isset($smtp_options['user'])) {
                    $params['auth'] = (!isset($smtp_options['auth']) ? ConfigHelper::getConfig('mail.smtp_auth_type', true) : $smtp_options['auth']);
                    if ($params['auth'] == 'false') {
                        $params['auth'] = false;
                    }
                    $params['username'] = (!isset($smtp_options['user']) ? $smtp_username : $smtp_options['user']);
                    $params['password'] = (!isset($smtp_options['pass']) ? ConfigHelper::getConfig('mail.smtp_password') : $smtp_options['pass']);
                } else {
                    $params['auth'] = false;
                }
                $params['persist'] = $persist;

                $error = $this->mail_object = Mail::factory('smtp', $params);
                if (is_a($error, 'PEAR_Error')) {
                    return $error->getMessage();
                }
            }

            $headers['X-Mailer'] = 'LMS-' . self::SOFTWARE_VERSION;
            if (!ConfigHelper::checkConfig('mail.hide_sensitive_headers')) {
                if (!empty($_SERVER['REMOTE_ADDR'])) {
                    $headers['X-Remote-IP'] = $_SERVER['REMOTE_ADDR'];
                }
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $headers['X-HTTP-User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
                }
            }
            $headers['Mime-Version'] = '1.0';
            $headers['Subject'] = qp_encode($headers['Subject']);
            $headers['Precedence'] = 'bulk';

            $debug_email = ConfigHelper::getConfig('mail.debug_email');
            if (!empty($debug_email)) {
                $recipients = ConfigHelper::getConfig('mail.debug_email');
                $headers['To'] = '<' . $recipients . '>';
            } else {
                if (isset($headers['Cc'])) {
                    $recipients .= ',' . $headers['Cc'];
                }
                if (isset($headers['Bcc'])) {
                    $recipients .= ',' . $headers['Bcc'];
                }
            }

            if (empty($headers['Date'])) {
                $headers['Date'] = date('r');
            }

            if ($files || $headers['X-LMS-Format'] == 'html') {
                $boundary = '-LMS-' . str_replace(' ', '.', microtime());
                $headers['Content-Type'] = "multipart/mixed;\n  boundary=\"" . $boundary . '"';
                $buf = "\nThis is a multi-part message in MIME format.\n\n";
                $buf .= '--' . $boundary . "\n";
                $buf .= "Content-Type: text/" . ($headers['X-LMS-Format'] == 'html' ? "html" : "plain") . "; charset=UTF-8\n\n";
                if ($headers['X-LMS-Format'] == 'html') {
                    $buf .= preg_replace('/\r?\n/', '', $body) . "\n";
                } else {
                    $buf .= $body . "\n";
                }
                if ($files) {
                    foreach ($files as $chunk) {
                        $buf .= '--' . $boundary . "\n";
                        $buf .= "Content-Transfer-Encoding: base64\n";
                        $buf .= "Content-Type: " . $chunk['content_type'] . "; name=\"" . $chunk['filename'] . "\"\n";
                        $buf .= "Content-Description:\n";
                        if ($headers['X-LMS-Format'] == 'html' && isset($chunk['content-id'])) {
                            $buf .= "Content-ID: <" . $chunk['content-id'] . ">\n";
                            $buf .= "Content-Disposition: inline; filename=\"" . $chunk['filename'] . "\"\n\n";
                        } else {
                            $buf .= "Content-Disposition: attachment; filename=\"" . $chunk['filename'] . "\"\n\n";
                        }
                        $buf .= chunk_split(base64_encode($chunk['data']), 60, "\n");
                    }
                }
                $buf .= '--' . $boundary . '--';
            } else {
                $headers['Content-Type'] = 'text/plain; charset=UTF-8';
                $buf = $body;
            }

            $this->executeHook('email_before_send', array('email' => $this->mail_object, 'backend' => 'pear'));

            $error = $this->mail_object->send($recipients, $headers, $buf);
            //if (PEAR::isError($error))
            if (is_a($error, 'PEAR_Error')) {
                return $error->getMessage();
            } else {
                return MSG_SENT;
            }
        } elseif (ConfigHelper::getConfig('mail.backend') == 'phpmailer') {
            $this->mail_object = new \PHPMailer\PHPMailer\PHPMailer();
            $this->mail_object->isSMTP();

            $this->mail_object->SMTPKeepAlive = $persist;

            $this->mail_object->Host = (!isset($smtp_options['host']) ? ConfigHelper::getConfig('mail.smtp_host') : $smtp_options['host']);
            $this->mail_object->Port = (!isset($smtp_options['port']) ? ConfigHelper::getConfig('mail.smtp_port') : $smtp_options['port']);
            $smtp_username = ConfigHelper::getConfig('mail.smtp_username');
            if (!empty($smtp_username) || isset($smtp_options['user'])) {
                $this->mail_object->Username = (!isset($smtp_options['user']) ? $smtp_username : $smtp_options['user']);
                $this->mail_object->Password = (!isset($smtp_options['pass']) ? ConfigHelper::getConfig('mail.smtp_password') : $smtp_options['pass']);
                $auth_type = isset($smtp_options['auth']) ? $smtp_options['auth'] : ConfigHelper::getConfig('mail.smtp_auth_type', true);
                if (is_bool($auth_type)) {
                    $this->mail_object->SMTPAuth = $auth_type;
                } elseif ($auth_type == 'false') {
                    $this->mail_object->SMTPAuth = false;
                } else {
                    $this->mail_object->SMTPAuth = true;
                    $this->mail_object->AuthType = $auth_type;
                }
            } else {
                $this->mail_object->SMTPAuth = false;
            }

            $this->mail_object->SMTPSecure = (!isset($smtp_options['secure'])
                ? ConfigHelper::getConfig('mail.smtp_secure', '', true)
                : $smtp_options['secure']);
            if ($this->mail_object->SMTPSecure == 'false') {
                $this->mail_object->SMTPSecure = '';
                $this->mail_object->SMTPAutoTLS = false;
            }

            $this->mail_object->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => isset($smtp_options['ssl_verify_peer']) ? $smtp_options['ssl_verify_peer']
                        : ConfigHelper::checkValue(ConfigHelper::getConfig('mail.smtp_ssl_verify_peer', false, true)),
                    'verify_peer_name' => isset($smtp_options['ssl_verify_peer_name']) ? $smtp_options['ssl_verify_peer_name']
                        : ConfigHelper::checkValue(ConfigHelper::getConfig('mail.smtp_ssl_verify_peer_name', false, true)),
                    'allow_self_signed' => isset($smtp_options['ssl_allow_self_signed']) ? $smtp_options['ssl_allow_self_signed']
                        : ConfigHelper::checkValue(ConfigHelper::checkConfig('mail.smtp_ssl_allow_self_signed', true)),
                )
            );

            $this->mail_object->XMailer = 'LMS-' . self::SOFTWARE_VERSION;
            if (!ConfigHelper::checkConfig('mail.hide_sensitive_headers')) {
                if (!empty($_SERVER['REMOTE_ADDR'])) {
                    $this->mail_object->addCustomHeader('X-Remote-IP: '.$_SERVER['REMOTE_ADDR']);
                }
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $this->mail_object->addCustomHeader('X-HTTP-User-Agent: '.$_SERVER['HTTP_USER_AGENT']);
                }
            }

            foreach (array('References', 'In-Reply-To', 'Message-ID') as $header_name) {
                if (isset($headers[$header_name])) {
                    if ($header_name == 'Message-ID') {
                        $this->mail_object->MessageID = $headers[$header_name];
                    } else {
                        $this->mail_object->addCustomHeader($header_name . ': ' . $headers[$header_name]);
                    }
                }
            }
            $this->mail_object->addCustomHeader('Precedence: bulk');

            if (isset($headers['Disposition-Notification-To'])) {
                $this->mail_object->ConfirmReadingTo = $headers['Disposition-Notification-To'];
            } elseif (isset($headers['Return-Receipt-To'])) {
                $this->mail_object->ConfirmReadingTo = $headers['Return-Receipt-To'];
            }

            $this->mail_object->dsn = isset($headers['Delivery-Status-Notification-To']) ? 'SUCCESS,FAILURE' : '';

            preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $headers['From'], $from);
            $this->mail_object->setFrom($from['mail'], isset($from['name']) ? trim($from['name'], "\"") : '');
            $this->mail_object->addReplyTo($headers['Reply-To']);
            $this->mail_object->CharSet = 'UTF-8';
            $this->mail_object->Subject = $headers['Subject'];

            $debug_email = ConfigHelper::getConfig('mail.debug_email');
            if (!empty($debug_email)) {
                $this->mail_object->SMTPDebug = $debug_level;
                $recipients = ConfigHelper::getConfig('mail.debug_email');
            } else {
                if (isset($headers['Cc'])) {
                    foreach (explode(',', $headers['Cc']) as $cc) {
                        preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $cc, $m);
                        $this->mail_object->addCC($m['mail'], isset($m['name']) ? trim($m['name'], "\"") : '');
                    }
                }
                if (isset($headers['Bcc'])) {
                    foreach (explode(',', $headers['Bcc']) as $bcc) {
                        preg_match('/^(?:(?<name>.*) )?<?(?<mail>[a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})>?$/iA', $bcc, $m);
                        $this->mail_object->addBCC($m['mail'], isset($m['name']) ? trim($m['name'], "\"") : '');
                    }
                }
            }

            if (empty($headers['Date'])) {
                $headers['Date'] = date('r');
            }

            if ($files) {
                foreach ($files as $chunk) {
                    if ($headers['X-LMS-Format'] == 'html' && isset($chunk['content-id'])) {
                        $this->mail_object->addStringEmbeddedImage(
                            $chunk['data'],
                            $chunk['content-id'],
                            $chunk['filename'],
                            \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                            $chunk['content_type']
                        );
                    } else {
                        $this->mail_object->AddStringAttachment(
                            $chunk['data'],
                            $chunk['filename'],
                            \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                            $chunk['content_type']
                        );
                    }
                }
            }

            if ($headers['X-LMS-Format'] == 'html') {
                $this->mail_object->isHTML(true);
                $this->mail_object->AltBody = trans("To view the message, please use an HTML compatible email viewer");
                $this->mail_object->msgHTML(preg_replace('/\r?\n/', "\n", $body));
            } else {
                $this->mail_object->isHTML(false);
                $this->mail_object->Body = $body;
            }

            foreach (explode(",", $recipients) as $recipient) {
                $this->mail_object->addAddress($recipient, isset($headers['Recipient-Name']) ? $headers['Recipient-Name'] : '');
            }

            if (isset($headers['X-Priority']) && intval($headers['X-Priority'])) {
                $this->mail_object->Priority = intval($headers['X-Priority']);
                unset($headers['X-Priority']);
            }

            foreach ($headers as $name => $value) {
                if (is_array($value)) {
                    $value = reset($value);
                }
                if (strpos(strtolower($name), 'x') === 0) {
                    $this->mail_object->addCustomHeader($name, $value);
                }
            }

            // setup your cert & key file
            $cert = LIB_DIR . DIRECTORY_SEPARATOR . 'lms-mail.cert';
            $key = LIB_DIR . DIRECTORY_SEPARATOR . 'lms.key';

            // set email digital signature
            if (file_exists($cert) && file_exists($key)) {
                $this->mail_object->sign($cert, $key, null);
            }

            $this->executeHook('email_before_send', array('email' => $this->mail_object, 'backend' => 'phpmailer'));

            if (!$this->mail_object->Send()) {
                return "Mailer Error: " . $this->mail_object->ErrorInfo;
            } else {
                return MSG_SENT;
            }
        }
    }

    public function SendSMS($number, $message, $messageid = null, $sms_options = null)
    {
        $msg_len = mb_strlen($message);

        if (!$msg_len) {
            return trans('SMS message is empty!');
        }

        $debug_phone = isset($sms_options['debug_phone']) ? $sms_options['debug_phone'] : ConfigHelper::getConfig('sms.debug_phone');
        if (!empty($debug_phone)) {
            $number = $debug_phone;
        }

        $prefix = isset($sms_options['prefix']) ? $sms_options['prefix'] : ConfigHelper::getConfig('sms.prefix', '');
        $number = preg_replace('/[^0-9]/', '', $number);
        $number = preg_replace('/^0+/', '', $number);

        $phone_number_validation_pattern = isset($sms_options['phone_number_validation_pattern'])
            ? $sms_options['phone_number_validation_pattern']
            : ConfigHelper::getConfig('sms.phone_number_validation_pattern', '', true);
        if (!empty($phone_number_validation_pattern) && !preg_match('/' . $phone_number_validation_pattern . '/', $number)) {
            return trans('Phone number validation failed!');
        }

        // add prefix to the number if needed
        if ($prefix && substr($number, 0, strlen($prefix)) != $prefix) {
            $number = $prefix . $number;
        }

        // message ID must be unique
        if (!$messageid) {
            $messageid = '0.' . time();
        }

        $message = preg_replace("/\r/", "", $message);

        $message = str_replace(
            array('%body'),
            array($message),
            isset($sms_options['message_template'])
                ? $sms_options['message_template']
                : ConfigHelper::getConfig('sms.message_template', '%body')
        );

        $transliterate_message = isset($sms_options['transliterate_message']) ? $sms_options['transliterate_message']
            : ConfigHelper::getConfig('sms.transliterate_message', 'false');
        if (ConfigHelper::checkValue($transliterate_message)) {
            $message = iconv('UTF-8', 'ASCII//TRANSLIT', $message);
        }

        $max_length = isset($sms_options['max_length']) ? $sms_options['max_length']
            : ConfigHelper::getConfig('sms.max_length');
        if (!empty($max_length) && intval($max_length) > 6 && $msg_len > intval($max_length)) {
            $message = mb_substr($message, 0, $max_length - 6) . ' [...]';
        }

        $service = isset($sms_options['service']) ? $sms_options['service'] : ConfigHelper::getConfig('sms.service');
        if (empty($service)) {
            return trans('SMS "service" not set!');
        }

        $errors = array();
        foreach (explode(',', $service) as $service) {
            $data = array(
                'number' => $number,
                'message' => $message,
                'messageid' => $messageid,
                'service' => $service,
                'transliterate_message' => $transliterate_message,
                'sms_options' => $sms_options,
            );

            // call external SMS handler(s)
            $data = $this->ExecHook('send_sms_before', $data);
            $data = $this->executeHook('send_sms_before', $data);

            if ($data['abort']) {
                if (is_string($data['result'])) {
                    $errors[] = $data['result'];
                    continue;
                } elseif (is_array($data['result'])) {
                    $errors = array_merge($errors, $data['result']);
                    continue;
                } else {
                    return $data['result'];
                }
            }

            $number = $data['number'];
            $message = $data['message'];
            $messageid = $data['messageid'];

            switch ($service) {
                case 'smstools':
                    $dir = isset($sms_options['smstools_outdir']) ? $sms_options['smstools_outdir']
                        : ConfigHelper::getConfig('sms.smstools_outdir', DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'spool' . DIRECTORY_SEPARATOR . 'sms' . DIRECTORY_SEPARATOR . 'outgoing');

                    if (!file_exists($dir)) {
                        $errors[] = trans('SMSTools outgoing directory not exists ($a)!', $dir);
                        continue 2;
                    }
                    if (!is_writable($dir)) {
                        $errors[] = trans('Unable to write to SMSTools outgoing directory ($a)!', $dir);
                        continue 2;
                    }

                    $filename = $dir . DIRECTORY_SEPARATOR . 'lms-' . $messageid . '-' . $number;

                    $headers = array();
                    $headers['To'] = $number;

                    $latin1 = iconv('UTF-8', 'ASCII', $message);
                    if (strlen($latin1) != mb_strlen($message, 'UTF-8')) {
                        $headers['Alphabet'] = 'UCS2';
                        $message = iconv('UTF-8', 'UNICODEBIG', $message);
                    }

                    $queue = isset($sms_options['queue']) ? $sms_options['queue']
                        : ConfigHelper::getConfig('sms.queue', '', true);
                    if (!empty($queue)) {
                        $headers['Queue'] = $queue;
                    }

                    $delivery_reports = isset($sms_options['delivery_reports']) ? $sms_options['delivery_reports']
                        : ConfigHelper::getConfig('sms.delivery_reports', 'false');
                    if (ConfigHelper::checkValue($delivery_reports)) {
                        $headers['Report'] = 'yes';
                    }

                    $header = '';
                    array_walk($headers, function ($value, $key) use (&$header) {
                        $header .= $key . ': ' . $value . "\n";
                    });

                    //$message = clear_utf($message);
                    $file = sprintf("%s\n%s", $header, $message);

                    if ($fp = fopen($filename, 'w')) {
                        fwrite($fp, $file);
                        fclose($fp);
                    } else {
                        $errors[] = trans('Unable to create file $a!', $filename);
                        continue 2;
                    }

                    return MSG_NEW;
                default:
                    $errors[] = trans('Unknown SMS service!');
                    continue 2;
            }
        }
        return implode(', ', $errors);
    }

    public function GetMessages($customerid, $limit = null)
    {
        $manager = $this->getMessageManager();
        return $manager->GetMessages($customerid, $limit);
    }

    public function GetDocuments($customerid = null, $limit = null)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetDocuments($customerid, $limit);
    }

    public function GetDocumentList(array $params)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetDocumentList($params);
    }

    public function GetTaxes($from = null, $to = null)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetTaxes($from, $to);
    }

    public function EventAdd($event)
    {
        $manager = $this->getEventManager();
        return $manager->EventAdd($event);
    }

    public function EventUpdate($event)
    {
        $manager = $this->getEventManager();
        return $manager->EventUpdate($event);
    }

    public function EventDelete($id)
    {
        $manager = $this->getEventManager();
        return $manager->EventDelete($id);
    }

    public function GetEvent($id)
    {
        $manager = $this->getEventManager();
        return $manager->GetEvent($id);
    }

    public function EventSearch($search, $order = 'date,asc', $simple = false)
    {
        $manager = $this->getEventManager();
        return $manager->EventSearch($search, $order, $simple);
    }

    public function GetEventList(array $params)
    {
        $manager = $this->getEventManager();
        return $manager->GetEventList($params);
    }

    public function GetCustomerIdByTicketId($id)
    {
        $manager = $this->getEventManager();
        return $manager->GetCustomerIdByTicketId($id);
    }

    public function EventOverlaps(array $params)
    {
        $manager = $this->getEventManager();
        return $manager->EventOverlaps($params);
    }

    public function AssignUserToEvent($id, $userid)
    {
        $manager = $this->getEventManager();
        return $manager->AssignUserToEvent($id, $userid);
    }

    public function UnassignUserFromEvent($id, $userid)
    {
        $manager = $this->getEventManager();
        return $manager->UnassignUserFromEvent($id, $userid);
    }

    public function MoveEvent($id, $delta)
    {
        $manager = $this->getEventManager();
        return $manager->MoveEvent($id, $delta);
    }

    public function GetEventsByTicketId($id)
    {
         $manager = $this->getHelpdeskManager();
         return $manager->GetEventsByTicketId($id);
    }

    public function GetNumberPlans($properties)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetNumberPlans($properties);
    }

    public function GetNewDocumentNumber($properties)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetNewDocumentNumber($properties);
    }

    public function DocumentExists($properties)
    {
        $manager = $this->getDocumentManager();
        return $manager->DocumentExists($properties);
    }

    public function CommitDocuments(array $ids, $userpanel = false)
    {
        $manager = $this->getDocumentManager();
        return $manager->CommitDocuments($ids, $userpanel);
    }

    public function NewDocumentCustomerNotifications(array $document)
    {
        $manager = $this->getDocumentManager();
        return $manager->NewDocumentCustomerNotifications($document);
    }

    public function ArchiveDocuments(array $ids)
    {
        $manager = $this->getDocumentManager();
        return $manager->ArchiveDocuments($ids);
    }

    public function UpdateDocumentPostAddress($docid, $customerid)
    {
        $manager = $this->getDocumentManager();
        return $manager->UpdateDocumentPostAddress($docid, $customerid);
    }

    public function DeleteDocumentAddresses($docid)
    {
        $manager = $this->getDocumentManager();
        return $manager->DeleteDocumentAddresses($docid);
    }

    public function isArchiveDocument($id)
    {
        $manager = $this->getDocumentManager();
        return $manager->isArchiveDocument($id);
    }

    public function AddArchiveDocument($docid, $file)
    {
        $manager = $this->getDocumentManager();
        return $manager->AddArchiveDocument($docid, $file);
    }

    public function GetArchiveDocument($docid)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetArchiveDocument($docid);
    }

    public function AddDocumentFileAttachments(array $files)
    {
        $manager = $this->getDocumentManager();
        return $manager->AddDocumentFileAttachments($files);
    }

    public function AddDocumentAttachments($documentid, array $files)
    {
        $manager = $this->getDocumentManager();
        return $manager->AddDocumentAttachments($documentid, $files);
    }

    public function AddDocumentScans($documentid, array $files)
    {
        $manager = $this->getDocumentManager();
        return $manager->AddDocumentScans($documentid, $files);
    }

    public function DocumentAttachmentExists($md5sum)
    {
        $manager = $this->getDocumentManager();
        return $manager->DocumentAttachmentExists($md5sum);
    }

    public function GetDocumentFullContents($id)
    {
        $manager = $this->getDocumentManager();
        return $manager->GetDocumentFullContents($id);
    }

    public function SendDocuments($docs, $type, $params)
    {
        $manager = $this->getDocumentManager();
        return $manager->SendDocuments($docs, $type, $params);
    }

    public function DeleteDocument($docid)
    {
        $manager = $this->getDocumentManager();
        return $manager->DeleteDocument($docid);
    }

    public function CopyDocumentPermissions($src_userid, $dst_userid)
    {
        $manager = $this->getDocumentManager();
        return $manager->CopyDocumentPermissions($src_userid, $dst_userid);
    }

    public function getDocumentsByFullNumber($full_number, $all_types = false)
    {
        $manager = $this->getDocumentManager();
        return $manager->getDocumentsByFullNumber($full_number, $all_types);
    }

    public function getDocumentsByChecksum($checksum, $all_types = false)
    {
        $manager = $this->getDocumentManager();
        return $manager->getDocumentsByChecksum($checksum, $all_types);
    }

    public function isDocumentAccessible($docid)
    {
        $manager = $this->getDocumentManager();
        return $manager->isDocumentAccessible($docid);
    }

    /*
     *  Location
     */

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

    public function DeleteAddress($address_id)
    {
        $manager = $this->getLocationManager();
        return $manager->DeleteAddress($address_id);
    }

    public function InsertAddress($args)
    {
        $manager = $this->getLocationManager();
        return $manager->InsertAddress($args);
    }

    public function InsertCustomerAddress($customer_id, $args)
    {
        $manager = $this->getLocationManager();
        return $manager->InsertCustomerAddress($customer_id, $args);
    }

    public function UpdateAddress($args)
    {
        $manager = $this->getLocationManager();
        return $manager->UpdateAddress($args);
    }

    public function UpdateCustomerAddress($customer_id, $args)
    {
        $manager = $this->getLocationManager();
        return $manager->UpdateCustomerAddress($customer_id, $args);
    }

    public function ValidAddress($args)
    {
        $manager = $this->getLocationManager();
        return $manager->ValidAddress($args);
    }

    public function CopyAddress($address_id)
    {
        $manager = $this->getLocationManager();
        return $manager->CopyAddress($address_id);
    }

    public function GetAddress($address_id)
    {
        $manager = $this->getLocationManager();
        return $manager->GetAddress($address_id);
    }

    public function GetCustomerAddress($customer_id, $type = BILLING_ADDRESS)
    {
        $manager = $this->getLocationManager();
        return $manager->GetCustomerAddress($customer_id, $type);
    }

    public function TerytToLocation($terc, $simc, $ulic)
    {
        $manager = $this->getLocationManager();
        return $manager->TerytToLocation($terc, $simc, $ulic);
    }

    public function GetZipCode(array $params)
    {
        $manager = $this->getLocationManager();
        return $manager->GetZipCode($params);
    }

    public function GetCitiesWithSections()
    {
        $manager = $this->getLocationManager();
        return $manager->GetCitiesWithSections();
    }

    public function getCountryCodeById($countryid)
    {
        $manager = $this->getLocationManager();
        return $manager->getCountryCodeById($countryid);
    }

    public function isTerritState($state)
    {
        $manager = $this->getLocationManager();
        return $manager->isTerritState($state);
    }

    public function GetNAStypes()
    {
        return $this->DB->GetAllByKey('SELECT id, name FROM nastypes ORDER BY name', 'id');
    }

    public function CalcAt($period, $date)
    {
        $manager = $this->getFinanceManager();
        return $manager->CalcAt($period, $date);
    }

    public function PublishDocuments($ids)
    {
        $manager = $this->getFinanceManager();
        return $manager->PublishDocuments($ids);
    }

    public function isDocumentPublished($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->isDocumentPublished($id);
    }

    public function isDocumentReferenced($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->isDocumentReferenced($id);
    }

    public function MarkDocumentsAsSent($ids)
    {
        $manager = $this->getFinanceManager();
        return $manager->MarkDocumentsAsSent($ids);
    }

    public function GetReceiptList(array $params)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetReceiptList($params);
    }

    public function AddReceipt(array $receipt)
    {
        $manager = $this->getFinanceManager();
        return $manager->AddReceipt($receipt);
    }

    public function GetCashRegistries($cid = null)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetCashRegistries($cid);
    }

    public function GetOpenedLiabilities($customerid)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetOpenedLiabilities($customerid);
    }

    public function GetPromotions()
    {
        $manager = $this->getFinanceManager();
        return $manager->GetPromotions();
    }

    public function AggregateDocuments($list)
    {
        $manager = $this->getFinanceManager();
        return $manager->AggregateDocuments($list);
    }

    public function GetDocumentsForBalanceRecords($ids, $doctypes)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetDocumentsForBalanceRecords($ids, $doctypes);
    }

    public function GetDocumentLastReference($docid)
    {
        $manager = $this->getFinanceManager();
        return $manager->GetDocumentLastReference($docid);
    }

    public function CheckNodeTariffRestrictions($aid, $nodes, $datefrom, $dateto)
    {
        $manager = $this->getFinanceManager();
        return $manager->CheckNodeTariffRestrictions($aid, $nodes, $datefrom, $dateto);
    }

    public function getCurrencyValue($currency, $date = null)
    {
        $manager = $this->getFinanceManager();
        return $manager->getCurrencyValue($currency, $date);
    }

    public function CopyCashRegistryPermissions($src_userid, $dst_userid)
    {
        $manager = $this->getFinanceManager();
        return $manager->CopyCashRegistryPermissions($src_userid, $dst_userid);
    }

    public function CopyPromotionTariffPermissions($src_userid, $dst_userid)
    {
        $manager = $this->getFinanceManager();
        return $manager->CopyPromotionTariffPermissions($src_userid, $dst_userid);
    }

    public function transformProformaInvoice($docid)
    {
        $manager = $this->getFinanceManager();
        return $manager->transformProformaInvoice($docid);
    }

    public function isInvoiceEditable($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->isInvoiceEditable($id);
    }

    public function isTariffEditable($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->isTariffEditable($id);
    }

    public function getPromotionSchema($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->getPromotionSchema($id);
    }

    public function getPromotion($id)
    {
        $manager = $this->getFinanceManager();
        return $manager->getPromotion($id);
    }

    /**
     * VoIP functions
     */
    public function GetVoipAccountList($order = 'login,asc', $search = null, $sqlskey = 'AND')
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

    public function getVoipBillings(array $params)
    {
        $manager = $this->getVoipAccountManager();
        return $manager->getVoipBillings($params);
    }

    public function getVoipTariffs()
    {
        return $this->getVoipAccountManager()->getVoipTariffs();
    }

    public function getVoipTariffRuleGroups()
    {
        return $this->getVoipAccountManager()->getVoipTariffRuleGroups();
    }

    /**
     * End VoIP functions
     */

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
        $nodesessions = $this->DB->GetAll(
            'SELECT INET_NTOA(ipaddr) AS ipaddr, mac, start, stop,
		download, upload, terminatecause, type
		FROM nodesessions WHERE nodeid = ? ORDER BY stop DESC LIMIT ' . intval(ConfigHelper::getConfig('phpui.nodesession_limit', 10)),
            array($nodeid)
        );
        if (!empty($nodesessions)) {
            foreach ($nodesessions as $idx => $session) {
                list ($number, $unit) = setunits($session['download']);
                $nodesessions[$idx]['download'] = round($number, 2) . ' ' . $unit;
                list ($number, $unit) = setunits($session['upload']);
                $nodesessions[$idx]['upload'] = round($number, 2) . ' ' . $unit;
                $nodesessions[$idx]['duration'] = $session['stop']
                ? ($session['stop'] - $session['start'] < 60 ? trans('shorter than minute') : uptimef($session['stop'] - $session['start']))
                : '-';
            }
        }
        return $nodesessions;
    }

    public function MessageTemplateExists($type, $name)
    {
        $manager = $this->getMessageManager();
        return $manager->MessageTemplateExists($type, $name);
    }

    public function AddMessageTemplate($type, $name, $subject, $helpdesk_queues, $helpdesk_message_types, $message)
    {
        $manager = $this->getMessageManager();
        return $manager->AddMessageTemplate($type, $name, $subject, $helpdesk_queues, $helpdesk_message_types, $message);
    }

    public function UpdateMessageTemplate($id, $type, $name, $subject, $helpdesk_queues, $helpdesk_message_types, $message)
    {
        $manager = $this->getMessageManager();
        return $manager->UpdateMessageTemplate($id, $type, $name, $subject, $helpdesk_queues, $helpdesk_message_types, $message);
    }

    public function DeleteMessageTemplates(array $ids)
    {
        $manager = $this->getMessageManager();
        return $manager->DeleteMessageTemplates($ids);
    }

    public function GetMessageTemplates($type = 0)
    {
        $manager = $this->getMessageManager();
        return $manager->GetMessageTemplates($type);
    }

    public function GetMessageTemplatesByQueueAndType($queueid, $type)
    {
        $manager = $this->getMessageManager();
        return $manager->GetMessageTemplatesByQueueAndType($queueid, $type);
    }

    public function GetMessageList(array $params)
    {
        $manager = $this->getMessageManager();
        return $manager->GetMessageList($params);
    }

    public function addMessage(array $params)
    {
        $manager = $this->getMessageManager();
        return $manager->addMessage($params);
    }

    public function updateMessageItems(array $params)
    {
        $manager = $this->getMessageManager();
        return $manager->updateMessageItems($params);
    }

    public function getSingleMessage($id, $details = false)
    {
        $manager = $this->getMessageManager();
        return $manager->getSingleMessage($id, $details);
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
     * Returns net node manager
     *
     * @return LMSNetNodeManagerInterface Net node manager
     */
    protected function getNetNodeManager()
    {
        if (!isset($this->net_node_manager)) {
            $this->net_node_manager = new LMSNetNodeManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->net_node_manager;
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
    protected function getFinanceManager()
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


    /**
     * Returns user group manager
     *
     * @return LMSUserGroupManagerInterface User group manager
     */
    protected function getUserGroupManager()
    {
        if (!isset($this->user_group_manager)) {
            $this->user_group_manager = new LMSUserGroupManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->user_group_manager;
    }

    public function UsergroupGetId($name)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupGetId($name);
    }

    public function UsergroupAdd($usergroupdata)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupAdd($usergroupdata);
    }

    public function UsergroupGetList()
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupGetList();
    }

    public function UsergroupGet($id)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupGet($id);
    }

    public function UsergroupExists($id)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupExists($id);
    }

    public function GetUserWithoutGroupNames($groupid)
    {
        $manager = $this->getUserGroupManager();
        return $manager->GetUserWithoutGroupNames($groupid);
    }

    public function UserassignmentDelete($userassignmentdata)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UserassignmentDelete($userassignmentdata);
    }

    public function UserassignmentExist($groupid, $userid)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UserassignmentExist($groupid, $userid);
    }

    public function UserassignmentAdd($userassignmentdata)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UserassignmentAdd($userassignmentdata);
    }

    public function getUserAssignments($userid)
    {
        $manager = $this->getUserGroupManager();
        return $manager->getUserAssignments($userid);
    }

    public function UsergroupDelete($id)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupDelete($id);
    }

    public function UsergroupUpdate($usergroupdata)
    {
        $manager = $this->getUserGroupManager();
        return $manager->UsergroupUpdate($usergroupdata);
    }

    public function getAllUserGroups()
    {
        $manager = $this->getUserGroupManager();
        return $manager->getAllUserGroups();
    }

    /**
     * Returns tariff tag manager
     *
     * @return LMSTariffTagManagerInterface Tariff tag manager
     */
    protected function getTariffTagManager()
    {
        if (!isset($this->tariff_tag_manager)) {
            $this->tariff_tag_manager = new LMSTariffTagManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->tariff_tag_manager;
    }

    public function TarifftagGetId($name)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TariffTagGetId($name);
    }

    public function TarifftagAdd($tarifftagdata)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagAdd($tarifftagdata);
    }

    public function TarifftagGetList()
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagGetList();
    }

    public function TarifftagGet($id)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagGet($id);
    }

    public function TarifftagExists($id)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagExists($id);
    }

    public function GetTariffWithoutTagNames($tagid)
    {
        $manager = $this->getTariffTagManager();
        return $manager->GetTariffWithoutTagNames($tagid);
    }

    public function TariffassignmentDelete($tariffassignmentdata)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TariffassignmentDelete($tariffassignmentdata);
    }

    public function TariffassignmentExist($tagid, $tariffid)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TariffassignmentExist($tagid, $tariffid);
    }

    public function TariffassignmentAdd($tariffassignmentdata)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TariffassignmentAdd($tariffassignmentdata);
    }

    public function TarifftagDelete($id)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagDelete($id);
    }

    public function TarifftagUpdate($tarifftagdata)
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagUpdate($tarifftagdata);
    }

    public function TarifftagGetAll()
    {
        $manager = $this->getTariffTagManager();
        return $manager->TarifftagGetAll();
    }

    public function getTariffTagsForTariff($tariffid)
    {
        $manager = $this->getTariffTagManager();
        return $manager->getTariffTagsForTariff($tariffid);
    }

    public function updateTariffTagsForTariff($tariffid, $tags)
    {
        $manager = $this->getTariffTagManager();
        return $manager->updateTariffTagsForTariff($tariffid, $tags);
    }

    /*
     * divisions
     */
    protected function getDivisionManager()
    {
        if (!isset($this->division_manager)) {
            $this->division_manager = new LMSDivisionManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->division_manager;
    }

    public function GetDivision($id)
    {
        $manager = $this->getDivisionManager();
        return $manager->GetDivision($id);
    }

    public function GetDivisionByName($name)
    {
        $manager = $this->getDivisionManager();
        return $manager->GetDivisionByName($name);
    }

    public function getDivisionIdByShortName($shortname)
    {
        $manager = $this->getDivisionManager();
        return $manager->getDivisionIdByShortName($shortname);
    }

    public function GetDivisions($params = array())
    {
        $manager = $this->getDivisionManager();
        return $manager->GetDivisions($params);
    }

    public function getDivisionList($params = array())
    {
        $manager = $this->getDivisionManager();
        return $manager->getDivisionList($params);
    }

    public function AddDivision($division)
    {
        $manager = $this->getDivisionManager();
        return $manager->AddDivision($division);
    }

    public function DeleteDivision($id)
    {
        $manager = $this->getDivisionManager();
        return $manager->DeleteDivision($id);
    }

    public function UpdateDivision($division)
    {
        $manager = $this->getDivisionManager();
        return $manager->UpdateDivision($division);
    }

    public function CheckDivisionsAccess($params)
    {
        $manager = $this->getDivisionManager();
        return $manager->checkDivisionsAccess($params);
    }

    /*
     * projects
     */
    protected function getProjectManager()
    {
        if (!isset($this->project_manager)) {
            $this->project_manager = new LMSProjectManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->project_manager;
    }

    public function CleanupProjects()
    {
        $manager = $this->getProjectManager();
        $manager->CleanupProjects();
    }

    public function GetProjects()
    {
        $manager = $this->getProjectManager();
        return $manager->GetProjects();
    }

    public function GetProject($id)
    {
        $manager = $this->getProjectManager();
        return $manager->GetProject($id);
    }

    public function GetProjectName($id)
    {
        $manager = $this->getProjectManager();
        return $manager->GetProjectName($id);
    }

    public function GetProjectByName($name)
    {
        $manager = $this->getProjectManager();
        return $manager->GetProjectByName($name);
    }

    public function ProjectByNameExists($name)
    {
        $manager = $this->getProjectManager();
        return $manager->ProjectByNameExists($name);
    }

    public function AddProject($project)
    {
        $manager = $this->getProjectManager();
        return $manager->AddProject($project);
    }

    public function DeleteProject($id)
    {
        $manager = $this->getProjectManager();
        return $manager->DeleteProject($id);
    }

    public function UpdateProject($id, $project)
    {
        $manager = $this->getProjectManager();
        return $manager->UpdateProject($id, $project);
    }

    public function GetProjectType($id)
    {
        $manager = $this->getProjectManager();
        return $manager->GetProjectType($id);
    }

        // files
    protected function getFileManager()
    {
        if (!isset($this->file_manager)) {
            $this->file_manager = new LMSFileManager($this->DB, $this->AUTH, $this->cache, $this->SYSLOG);
        }
        return $this->file_manager;
    }

    public function GetFileContainers($type, $id)
    {
        $manager = $this->getFileManager();
        return $manager->GetFileContainers($type, $id);
    }

    public function GetFile($id)
    {
        $manager = $this->getFileManager();
        return $manager->GetFile($id);
    }

    public function GetZippedFileContainer($id)
    {
        $manager = $this->getFileManager();
        return $manager->GetZippedFileContainer($id);
    }

    public function AddFileContainer(array $params)
    {
        $manager = $this->getFileManager();
        return $manager->AddFileContainer($params);
    }

    public function UpdateFileContainer(array $params)
    {
        $manager = $this->getFileManager();
        return $manager->UpdateFileContainer($params);
    }

    public function DeleteFileContainer($id)
    {
        $manager = $this->getFileManager();
        return $manager->DeleteFileContainer($id);
    }

    public function DeleteFileContainers($type, $resourceid)
    {
        $manager = $this->getFileManager();
        return $manager->DeleteFileContainers($type, $resourceid);
    }

    public function FileExists($md5sum)
    {
        $manager = $this->getFileManager();
        return $manager->FileExists($md5sum);
    }

    public function SendInvoices($docs, $type, $params)
    {
        extract($params);

        if ($type == 'frontend') {
            $eol = '<br>';
        } else {
            $eol = PHP_EOL;
        }

        $month = sprintf('%02d', intval(date('m', $currtime)));
        $day = sprintf('%02d', intval(date('d', $currtime)));
        $year = sprintf('%04d', intval(date('Y', $currtime)));

        $from = $sender_email;

        if (!empty($sender_name)) {
            $from = qp_encode($sender_name) . " <$from>";
        }

        foreach ($docs as $doc) {
            $doc['invoice_filename'] = $invoice_filename;
            $doc['dnote_filename'] = $dnote_filename;
            $doc['which'] = $which;
            $doc['duplicate-date'] = $duplicate_date;

            if (!$no_attachments) {
                $document = $this->GetTradeDocument($doc);
                $filename = $document['filename'];
            }

            $custemail = (!empty($debug_email) ? $debug_email : $doc['email']);
            $invoice_number = (!empty($doc['template']) ? $doc['template'] : '%N/LMS/%Y');
            if (!is_null($mail_body)) {
                if (is_readable($mail_body) && ($mail_body[0] == DIRECTORY_SEPARATOR)) {
                    $body = file_get_contents($mail_body);
                } else {
                    $body = $mail_body;
                }
            }
            $subject = $mail_subject;

            $invoice_number = docnumber(array(
                'number' => $doc['number'],
                'template' => $invoice_number,
                'cdate' => $doc['cdate'] + date('Z'),
                'customerid' => $doc['customerid'],
            ));
            $body = preg_replace('/%invoice/', $invoice_number, $body);
            $body = preg_replace('/%balance/', moneyf($this->GetCustomerBalance($doc['customerid']), Localisation::getCurrentCurrency()), $body);
            $body = preg_replace('/%today/', $year . '-' . $month . '-' . $day, $body);
            $body = str_replace('\n', "\n", $body);
            $subject = preg_replace('/%invoice/', $invoice_number, $subject);
            $doc['name'] = '"' . $doc['name'] . '"';

            $body = preg_replace(
                '/%bankaccount/',
                format_bankaccount(bankaccount($doc['customerid'], $document['document']['account'])),
                $body
            );
            $deadline = $doc['cdate'] + $document['document']['paytime'] * 86400;
            $body = preg_replace('/%deadline-y/', strftime("%Y", $deadline), $body);
            $body = preg_replace('/%deadline-m/', strftime("%m", $deadline), $body);
            $body = preg_replace('/%deadline-d/', strftime("%d", $deadline), $body);
            $body = preg_replace('/%deadline_month_name/', strftime("%B", $deadline), $body);
            $body = preg_replace('/%pin/', $document['document']['customerpin'], $body);
            $body = preg_replace('/%cid/', $doc['customerid'], $body);
            // invoices, debit notes
            $body = preg_replace('/%value/', moneyf($document['document']['total'], $document['document']['currency']), $body);
            $body = preg_replace('/%cdate-y/', strftime("%Y", $document['document']['cdate']), $body);
            $body = preg_replace('/%cdate-m/', strftime("%m", $document['document']['cdate']), $body);
            $body = preg_replace('/%cdate-d/', strftime("%d", $document['document']['cdate']), $body);
            list ($now_y, $now_m) = explode('/', strftime("%Y/%m", time()));
            $body = preg_replace('/%lastday/', strftime("%d", mktime(12, 0, 0, $now_m + 1, 0, $now_y)), $body);

            $body = $this->getLastNInTable($body, $doc['customerid'], $mail_format, $aggregate_documents);

            $mailto = array();
            $mailto_qp_encoded = array();
            foreach (explode(',', $custemail) as $email) {
                $mailto[] = $doc['name'] . " <$email>";
                $mailto_qp_encoded[] = qp_encode($doc['name']) . " <$email>";
            }
            $mailto = implode(', ', $mailto);
            $mailto_qp_encoded = implode(', ', $mailto_qp_encoded);

            if (!$quiet || $test) {
                switch ($doc['doctype']) {
                    case DOC_DNOTE:
                        $msg = trans('Debit Note No. $a for $b', $invoice_number, $mailto);
                        break;
                    case DOC_CNOTE:
                        $msg = trans('Credit Note No. $a for $b', $invoice_number, $mailto);
                        break;
                    case DOC_INVOICE:
                        $msg = trans('Invoice No. $a for $b', $invoice_number, $mailto);
                        break;
                    case DOC_INVOICE_PRO:
                        $msg = trans('Pro Forma Invoice No. $a for $b', $invoice_number, $mailto);
                        break;
                }
                if ($type == 'frontend') {
                    echo htmlspecialchars($msg) . $eol;
                    flush();
                    ob_flush();
                } else {
                    echo $msg . $eol;
                }
            }

            if (!$test) {
                $files = array();

                if (!$no_attachments) {
                    $files[] = array(
                        'content_type' => $document['content-type'],
                        'filename' => $filename,
                        'data' => $document['data'],
                    );

                    if ($extrafile) {
                        $files[] = array(
                            'content_type' => mime_content_type($extrafile),
                            'filename' => basename($extrafile),
                            'data' => file_get_contents($extrafile)
                        );
                    }
                }

                $headers = array(
                    'From' => empty($dsn_email) ? $from : $dsn_email,
                    'To' => $mailto_qp_encoded,
                    'Subject' => $subject,
                    'Reply-To' => empty($reply_email) ? $sender_email : $reply_email,
                );

                if (!empty($mdn_email)) {
                    $headers['Return-Receipt-To'] = $mdn_email;
                    $headers['Disposition-Notification-To'] = $mdn_email;
                }

                if (!empty($dsn_email)) {
                    $headers['Delivery-Status-Notification-To'] = $dsn_email;
                }

                if (!empty($notify_email)) {
                    $headers['Cc'] = $notify_email;
                }

                if (isset($mail_format) && $mail_format == 'html') {
                    $headers['X-LMS-Format'] = 'html';
                    $content_type = 'text/html';
                    $body = str_replace('<eol>', '<br>', $body);
                } else {
                    $content_type = 'text/plain';
                    $body = str_replace('<eol>', "\n", $body);
                }

                $data = array(
                    'body' => $body,
                    'doc' => $doc,
                    'mail_format' => $mail_format,
                    'headers' => $headers
                );
                $data = $this->executeHook('invoice_email_before_send', $data);
                $body = $data['body'];
                $headers = $data['headers'];

                if ($add_message) {
                    $this->DB->Execute(
                        'INSERT INTO messages (subject, body, cdate, type, userid, contenttype)
						VALUES (?, ?, ?NOW?, ?, ?, ?)',
                        array($subject, $body, MSG_MAIL, Auth::GetCurrentUser(), $content_type)
                    );
                    $msgid = $this->DB->GetLastInsertID('messages');

                    if ($message_attachments) {
                        if (!empty($files)) {
                            foreach ($files as &$file) {
                                $file['name'] = $file['filename'];
                                $file['type'] = $file['content_type'];
                            }
                            unset($file);
                            $this->AddFileContainer(array(
                                'description' => 'message-' . $msgid,
                                'files' => $files,
                                'type' => 'messageid',
                                'resourceid' => $msgid,
                            ));
                        }
                    }

                    foreach (explode(',', $custemail) as $email) {
                        $this->DB->Execute(
                            'INSERT INTO messageitems (messageid, customerid, destination, lastdate, status)
							VALUES (?, ?, ?, ?NOW?, ?)',
                            array($msgid, $doc['customerid'], $email, MSG_NEW)
                        );
                        $msgitemid = $this->DB->GetLastInsertID('messageitems');
                        if (!isset($msgitems[$doc['customerid']])) {
                            $msgitems[$doc['customerid']] = array();
                        }
                        $msgitems[$doc['customerid']][$email] = $msgitemid;
                    }
                }

                foreach (explode(',', $custemail) as $email) {
                    if ($add_message && (!empty($dsn_email) || !empty($mdn_email))) {
                        $headers['X-LMS-Message-Item-Id'] = $msgitems[$doc['customerid']][$email];
                        $headers['Message-ID'] = '<messageitem-' . $headers['X-LMS-Message-Item-Id'] . '@rtsystem.' . gethostname() . '>';
                    }

                    $res = $this->SendMail(
                        $email,
                        $headers,
                        $body,
                        $files,
                        null,
                        (isset($smtp_options) ? $smtp_options : null)
                    );

                    if (is_string($res)) {
                        $msg = trans('Error sending mail: $a', $res);
                        if ($type == 'backend') {
                            fprintf(STDERR, $msg . $eol);
                        } else {
                            echo '<span class="red">' . htmlspecialchars($msg) . '</span>' . $eol;
                            flush();
                        }
                        $status = MSG_ERROR;
                    } else {
                        $status = MSG_SENT;
                        $res = null;
                    }

                    if ($status == MSG_SENT) {
                        $this->PublishDocuments($doc['id']);
                        $this->MarkDocumentsAsSent($doc['id']);
                        $published = true;
                    }

                    if ($add_message) {
                        $this->DB->Execute('UPDATE messageitems SET status = ?, error = ?
							WHERE id = ?', array($status, $res, $msgitems[$doc['customerid']][$email]));
                    }

                    if (isset($interval) && !empty($interval)) {
                        if ($interval == -1) {
                            $delay = mt_rand(500, 5000);
                        } else {
                            $delay = intval($interval) * 1000;
                        }
                        usleep($delay);
                    }
                }
            }
        }
    }

    public function CopyPermissions($src_userid, $dst_userid, $permissions = null)
    {
        if (!isset($permissions)) {
            $this->CopyQueuePermissions($src_userid, $dst_userid);
            $this->CopyCategoryPermissions($src_userid, $dst_userid);
            $this->CopyDocumentPermissions($src_userid, $dst_userid);
            $this->CopyCashRegistryPermissions($src_userid, $dst_userid);
            $this->CopyPromotionTariffPermissions($src_userid, $dst_userid);
        } else {
            if (empty($permissions)) {
                return;
            }
            foreach ($permissions as $permission) {
                switch ($permission) {
                    case 'helpdesk-queues':
                        $this->CopyQueuePermissions($src_userid, $dst_userid);
                        break;
                    case 'helpdesk-categories':
                        $this->CopyCategoryPermissions($src_userid, $dst_userid);
                        break;
                    case 'documents':
                        $this->CopyDocumentPermissions($src_userid, $dst_userid);
                        break;
                    case 'cash-registries':
                        $this->CopyCashRegistryPermissions($src_userid, $dst_userid);
                        break;
                    case 'promotion-tariffs':
                        $this->CopyPromotionTariffPermissions($src_userid, $dst_userid);
                        break;
                }
            }
        }
    }
}
