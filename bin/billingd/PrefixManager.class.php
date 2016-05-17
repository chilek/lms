<?php

class PrefixManager {
	const PURCHASE_PRICE = 'purchasePrice';
	const SALE_PRICE = 'salePrice';
	const UNIT_SIZE = 'unitSize';

	private $costTable = array();

	public function __construct() {
	}

	/*!
	 * \brief Get cost per unit for call.
	 *
	 * \param string $from caller phone number
	 * \param string $to calee phone number
	 * \param int $tariff_id tariff id 
	 * \return array informations about price (unit size and cost per one unit)
	 */
	public function getCost($from, $to, $tariff_id) {

		if (!is_numeric($from) || !is_numeric($to))
			throw new Exception('Incorect character was found!');
		
		$discount = $this->findBestPrice($from, $to, $tariff_id);
		
		echo $discount;
		
		$prefix = $this->findLongestPrefix($to, $tariff_id);

		if (!$prefix)
			throw new Exception('Caller not found!');

		switch($discount) {
			case '-1': //no promotion
				$price = $this->costTable[$tariff_id]['prefixes'][$prefix][self::SALE_PRICE];
			break;

			default: //new price
				$price = $discount;
			break;
		}
		
		//change price per minute to price per second
			$price = ($price*100) / 60;
		
		//get cost per one unit
			$unitSize = $this->costTable[$tariff_id]['prefixes'][$prefix][self::UNIT_SIZE];
			$costPerUnit = $price * $unitSize;
			
			$result = array('unitSize' => $unitSize, 'costPerUnit' => $costPerUnit);
			
		return $result;
	}


	/*!
	 * \brief Find best discount for current call.
	 *
	 * \param string $from caller phone number
	 * \param string $to calee phone number
	 * \param int $tariff_id tariff id 
	 * \return float call cost per unit
	 */ 
	public function findBestPrice($from, $to, $tariff_id) {
		$cost = -1;

		foreach($this->costTable[$tariff_id]['rules'] as $singleRule) {
			$to_tmp = $to;

			while (strlen($to_tmp) && !isset($singleRule['prefixes'][$to_tmp]))
				$to_tmp = substr($to_tmp, 0, -1);

			if (isset($singleRule['prefixes'][$to_tmp]) && ($cost == -1 || ($cost != -1 && $singleRule['cost'] < $cost)))
				$cost = $singleRule['cost'];
		}

		return $cost;
	}

	/*!
	 * \brief Find most suited prefix for current call.
	 *
	 * \param string $to calee phone number 
	 * \param int $tariff_id tariff id 
	 * \return boolean success or failure
	 */  
	public function findLongestPrefix($to, $tariff_id) {
		while (strlen($to) && !isset($this->costTable[$tariff_id]['prefixes'][$to]))
			$to = substr($to, 0, -1);

		return $to;
	}

	/*!
	 * \brief Load/reload cost table.
	 *
	 * \param array $list prefix table
	 */
	public function setCostTable($list) {
		$this->costTable = $list;
	}
}
?>