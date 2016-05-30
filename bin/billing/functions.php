<?php

	/*!
	 * \brief Change text to asociative.
	 *
	 * \param string $row single row to parse
	 * \return array associative array with paremeters
	 */
	function parseRow($row) {
		$pattern = '/^"(?<caller>[0-9]*)","([0-9]*)","(?<callee>[0-9]*)","(?<call_type>(?:incoming|outgoing))","([0-9]*)","(.*)","(.*)","(.*)","(.*)","(?P<call_start>(?<call_start_year>[0-9]{4})-(?<call_start_month>[0-9]{2})-(?<call_start_day>[0-9]{2}) (?<call_start_hour>[0-9]{2}):(?<call_start_min>[0-9]{2}):(?<call_start_sec>[0-9]{2}))","(?<call_answer>(?<call_answer_year>[0-9]{4})-(?<call_answer_month>[0-9]{2})-(?<call_answer_day>[0-9]{2}) (?<call_answer_hour>[0-9]{2}):(?<call_answer_min>[0-9]{2}):(?<call_answer_sec>[0-9]{2}))","(?<call_end>(?<call_end_year>[0-9]{4})-(?<call_end_month>[0-9]{2})-(?<call_end_day>[0-9]{2}) (?<call_end_hour>[0-9]{2}):(?<call_end_min>[0-9]{2}):(?<call_end_sec>[0-9]{2}))",(?<time_start_to_end>[0-9]*),(?<time_answer_to_end>[0-9]*),"(?<call_status>.*)","(.*)",""/';

		preg_match($pattern, $row, $matches);

		foreach ($matches as $k=>$v)
			if (is_numeric($k))
				unset($matches[$k]);

		return $matches;
	}

	/*!
	 * \brief Get customer list.
	 *
	 * \return array array of customers with base kay as phone number
	 */
	function getCustomerList() {
		global $DB;

		return $DB->GetAllByKey('SELECT
												va.id as voipaccountid, va.phone, t.id as tariffid
											 FROM
												voipaccounts va left join assignments a on va.ownerid = a.customerid left join tariffs t on t.id = a.tariffid
											 WHERE
												t.type = 4', 'phone');
	}

	/*!
	 * \brief Valid array with cdr data.
	 *
	 * \param array cdr record
	 * \return boolean when all good
	 * \return string first founded error description
	 */
	function validCDR($cdr) {
		if (!is_numeric($cdr['caller']))
			return 'caller not found or isn\'t a number';

		if (!is_numeric($cdr['callee']))
			return 'callee not found or isn\'t a number';

		if ($cdr['call_type'] != 'outgoing' && $cdr['call_type'] != 'incoming')
			return 'call type not found or is not correct';

		if (!is_numeric($cdr['call_start_year']) || !is_numeric($cdr['call_start_month']) || !is_numeric($cdr['call_start_day']) || !is_numeric($cdr['call_start_hour']) || !is_numeric($cdr['call_start_min']) || !is_numeric($cdr['call_start_sec']))
			return 'call start time is not set or isn\'t correct';

		if (!is_numeric($cdr['call_answer_year']) || !is_numeric($cdr['call_answer_month']) || !is_numeric($cdr['call_answer_day']) || !is_numeric($cdr['call_answer_hour']) || !is_numeric($cdr['call_answer_min']) || !is_numeric($cdr['call_answer_sec']))
			return 'call answer time is not set or isn\'t correct';

		if (!is_numeric($cdr['call_end_year']) || !is_numeric($cdr['call_end_month']) || !is_numeric($cdr['call_end_day']) || !is_numeric($cdr['call_end_hour']) || !is_numeric($cdr['call_end_min']) || !is_numeric($cdr['call_end_sec']))
			return 'call end time is not set or isn\'t correct';

		if (!is_numeric($cdr['time_start_to_end']))
			return 'time start to end not found or isn\'t a integer';

		if (!is_numeric($cdr['time_answer_to_end']))
			return 'time answer to end not found or isn\'t a integer';

		return true;
	}

	/*!
	 * \brief Return maximum call time in seconds between two persons.
	 *
	 * \param string $from caller phone number
	 * \param string $to callee phone number
	 * \return int max call time in seconds
	 */
	function getMaxCallTime($from, $to) {
		$customer = getCustomerByPhone($from);
		include_tariff($customer['tariffid']);

		$call_cost = getCost($from, $to, $customer['tariffid']);

		return floor($customer['balance'] / $call_cost['costPerUnit']) * $call_cost['unitSize'];
	}

	/*!
	 * \brief Get informations about customer by phone number.
	 *
	 * \param string $phone_number customer phone number
	 * \return array informations about customer (voip account id, phone number, account balance, tariff id)
	 */
	function getCustomerByPhone($phone_number) {
		global $DB;

		$customer = $DB->GetRow('SELECT
												va.id as voipaccountid, va.phone, va.balance, t.id as tariffid
											 FROM
												voipaccounts va left join assignments a on va.ownerid = a.customerid left join tariffs t on t.id = a.tariffid
											 WHERE
												va.phone ?LIKE? ? and
												t.type = 4', array($phone_number));

		if (!$customer)
			die('Caller number phone "' . $phone_number . '" not found.' . PHP_EOL);

		return $customer;
	}

	/*!
	 * \brief Get cost per unit for call.
	 *
	 * \param string $from caller phone number
	 * \param string $to callee phone number
	 * \return array informations about price (unit size and cost per one unit)
	 */
	function getCost($from, $to, $t_id) {
		global $tariffs;

		$discount = findBestPrice($from, $to, $t_id);
		$prefix = findLongestPrefix($to, $t_id);

		switch($discount) {
			case '-1': // no promotion
				$price = $tariffs[$t_id]['prefixes'][$prefix]['sale_price'];
			break;

			default: // new price
				$price = $discount;
			break;
		}

		// change price per minute to price per second
		$price = ($price*100) / 60;

		// get cost per one unit
		$unitSize = $tariffs[$t_id]['prefixes'][$prefix]['unit_size'];
		$costPerUnit = ($price * $unitSize) / 100;

		return array('unitSize' => $unitSize, 'costPerUnit' => $costPerUnit);
	}

	/*!
	 * \brief Find most suited prefix for current call.
	 *
	 * \param string $to callee phone number
	 * \return string longest matched prefix
	 */
	function findLongestPrefix($to, $t_id) {
		global $tariffs;

		while (strlen($to) && !isset($tariffs[$t_id]['prefixes'][$to])) {
			$to = substr($to, 0, -1);
		}

		if (!isset($tariffs[$t_id]['prefixes'][$to]))
			die("Cant match prefix for callee number." . PHP_EOL);

		return $to;
	}

	/*!
	 * \brief Find best discount for call.
	 *
	 * \param string $from caller phone number
	 * \param string $to callee phone number
	 * \param string $t_id tariff id
	 * \return float call cost per unit, when function return -1 then not match any rule for call
	 */
	function findBestPrice($from, $to, $t_id) {
		global $tariffs;
		$cost = -1;

		foreach($tariffs[$t_id]['rules'] as $singleRule) {
			$to_tmp = $to;

			while (strlen($to_tmp) && !isset($singleRule['prefixes'][$to_tmp]))
				$to_tmp = substr($to_tmp, 0, -1);

			if (isset($singleRule['prefixes'][$to_tmp]) && ($cost == -1 || ($cost != -1 && $singleRule['sale_price'] < $cost)))
				$cost = $singleRule['sale_price'];
		}

		return $cost;
	}

	/*!
	 * \brief Include tariff by id.
	 *
	 * \param int $t_id tariff id
	 */
	function include_tariff($tariff_id) {
		global $tariffs;
		$file = 'tariff_cache/tariff_' . $tariff_id . '.php';

		if (!file_exists($file))
			die('Tariff file "' . $file . '" doesnt exists.' . PHP_EOL);

		include_once $file;
	}

	/*!
	 * \brief Valid array parameters.
	 *
	 * \param array parameters
	 * \return boolean if all is correct return true
	 * \return string first founded error description
	 */
	function validParamters($params) {

		foreach ($params as $k=>$v) {
			$k = strtolower($k);

			switch ($k) {
				case 'caller':
				case 'callee':
				case 'calltime':
				case 'totaltime':
				case 'startcall':
					if (!is_numeric($v))
						return ucfirst($k) . ' is not a number.' . PHP_EOL;
				break;

				case 'action':
					switch ($v) {
						case 'account':
						case 'estimate':
						break;

						default:
							return 'Action not found.' . PHP_EOL;
					}
				break;

				case 'file':
					if (!file_exists($v))
						return 'File "' . $v . '" doesnt exists.' . PHP_EOL;
				break;

				case 'status':
					switch ($v) {
						case 'answered':
						case 'no answer':
						case 'busy':
						break;

						default:
							return 'Call status is not correct. Choose one of values: busy, answered, no answer.' . PHP_EOL;
					}
				break;

				case 'type':
					switch ($v) {
						case 'incoming':
						case 'outgoing':
						break;

						default:
							return 'Call type is not correct. Choose incoming or outgoing' . PHP_EOL;
					}
				break;
			}
		}

		return true;
	}

?>