<?php

class reloadConfiguration extends Threaded {
	private static $DB;

	public function __construct() {
	}

	/*!
	 * \brief Reload configuration main function.
	 */
	public function run() {
		$this->save_to_log('load configuration start');
		
		if ($this->init_database_connection() == FALSE) {
			$this->save_to_log('load configuration end');
			exit();
		}
			
		// create shortcut phone_number => custome_id	
			$phone_to_clientid = self::$DB->GetAllByKey('SELECT phone, ownerid FROM voipaccounts', 'phone');
			$this->worker->CustomerManager->setPhoneToCustomerArray($phone_to_clientid);
		
		// create prefix tree
			$rows = self::$DB->GetAll('SELECT
													t.tariffid, p.prefix, t.price, t.unitsize
												FROM
													voip_prefix_group_assignments a left join voip_prefix p on a.prefixid = p.id 
													left join voip_prefix_group g on a.groupid = g.id
													left join voip_tariff t on a.groupid = t.groupid;');
			
			$result = array();	
			foreach ($rows as $single_row)
				$result[$single_row['tariffid']]['prefixes'][$single_row['prefix']] = array(PrefixManager::SALE_PRICE => $single_row['price'], PrefixManager::UNIT_SIZE => $single_row['unitsize']);
				
			$this->worker->PrefixManager->setCostTable($result);
			
		$this->save_to_log('load configuration end');
	}
	
	/*!
	 * \brief Create and test connection to database.
	 *
	 * \return boolean success or failure
	 */ 
	public function init_database_connection() {
		for ($i=0; $i<3; ++$i) {
			self::$DB = LMSDB::getInstance();

			if($result = self::$DB->GetAll('SELECT 1')) {
				$this->save_to_log('connected to database');
				return TRUE;
			}
			
			self::$DB = NULL;
			sleep(1);
		}
		
		$this->save_to_log('cant connect to database, thread exit');
		return FALSE;
	}
	
	/*!
	 * \brief Save text to log file.
	 *
	 * \param string $text text to save
	 */ 
	private function save_to_log($text) {
		if ($text)
			fprintf($this->worker->log_file, date("G:i:s d-M-Y | ") . $text . PHP_EOL);
	}
}

?>