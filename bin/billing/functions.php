<?php

	/*!
	 * \brief Change text to asociative.
	 *
	 * \param string $row single row to parse
	 * \return array associative array with paremeters
	 */
	function parseRow($row) {
		$pattern = '/^"(?<caller>(?:\+?[0-9]*|unavailable.*|anonymous.*))",' .
		               '"(.*)",' .
					   '"(?<callee>[0-9]*)",' .
					   '"(?<call_type>(?:incoming.*|outgoing.*))",' .
					   '"(.*)",' .
					   '"(.*)",' .
					   '"(.*)",' .
					   '"(.*)",' .
					   '"(.*)",' .
					   '"(?P<call_start>(?<call_start_year>[0-9]{4})-(?<call_start_month>[0-9]{2})-(?<call_start_day>[0-9]{2}) (?<call_start_hour>[0-9]{2}):(?<call_start_min>[0-9]{2}):(?<call_start_sec>[0-9]{2}))",' .
					   '(?:"(?<call_answer>(?<call_answer_year>[0-9]{4})-(?<call_answer_month>[0-9]{2})-(?<call_answer_day>[0-9]{2}) (?<call_answer_hour>[0-9]{2}):(?<call_answer_min>[0-9]{2}):(?<call_answer_sec>[0-9]{2}))")?,' .
					   '"(?<call_end>(?<call_end_year>[0-9]{4})-(?<call_end_month>[0-9]{2})-(?<call_end_day>[0-9]{2}) (?<call_end_hour>[0-9]{2}):(?<call_end_min>[0-9]{2}):(?<call_end_sec>[0-9]{2}))",(?<time_start_to_end>[0-9]*),(?<time_answer_to_end>[0-9]*),' .
					   '"(?<call_status>.*)",' .
					   '"(.*)",' .
					   '"(?<uniqueid>.*)".*/';

		preg_match($pattern, $row, $matches);

		foreach ($matches as $k=>$v) {
			if (is_numeric($k))
				unset($matches[$k]);
			else if (!$matches[$k])
				$matches[$k] = 0;
		}

		return $matches;
	}

	/*!
	 * \brief Get customer list.
	 *
	 * \return array array of customers with base kay as phone number
	 */
	function getCustomerList() {
		$DB = LMSDB::getInstance();

		return $DB->GetAllByKey('SELECT
												va.id as voipaccountid, va.phone, t.id as tariffid, va.flags
											 FROM
												voipaccounts va left join assignments a on va.ownerid = a.customerid left join tariffs t on t.id = a.tariffid
											 WHERE
												t.type = ?', 'phone', array(TARIFF_PHONE));
	}

	/*!
	 * \brief Get customer list.
	 *
	 * \return array array of customers with base kay as phone number
	 */
	function getPrefixList() {
		$DB = LMSDB::getInstance();

		return $DB->GetAllByKey('SELECT
												prefix, name
											FROM
												voip_prefixes p left join voip_prefix_groups g on p.groupid = g.id', 'prefix');
	}

	/*!
	 * \brief Valid array with cdr data.
	 *
	 * \param array cdr record
	 * \return boolean when all good
	 * \return string first founded error description
	 */
	function validCDR($cdr) {
		if (!preg_match("/([0-9]+|anonymous|unavailable)/", $cdr['caller']))
			return 'caller not found or isn\'t a number';

		if (!is_numeric($cdr['callee']))
			return 'callee not found or isn\'t a number';

		if (!preg_match("/(incoming|outgoing)/i", $cdr['call_type']))
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
		$DB = LMSDB::getInstance();

		$customer = $DB->GetRow('SELECT
												va.id as voipaccountid, va.phone, va.balance, t.id as tariffid, va.flags
											 FROM
												voipaccounts va
												left join assignments a on va.ownerid = a.customerid
												left join tariffs t on t.id = a.tariffid
											 WHERE
												va.phone ?LIKE? ? and
												t.type = ?', array($phone_number, TARIFF_PHONE));

		return (!$customer) ? NULL : $customer;
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
	 * \brief Find most suited prefix for phone number.
	 *
	 * \param string $to callee phone number
	 * \return string longest matched prefix
	 */
	function findLongestPrefix($number, $t_id) {
		global $tariffs;

		while (strlen($number) && !isset($tariffs[$t_id]['prefixes'][$number]))
			$number = substr($number, 0, -1);

		if (!isset($tariffs[$t_id]['prefixes'][$number]))
			return NULL;

		return $number;
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
	 * \brief Change call type (string) to defined number (int).
	 *
	 * \param string $type call type
	 * \return int number assigned to call type
	 * \return php_function die when can't match string to call type
	 */
	function parseCallType($type) {
		if (preg_match("/incoming/i", $type))
			return CALL_INCOMING;

		if (preg_match("/outgoing/i", $type))
			return CALL_OUTGOING;

		die('Call type is not correct. Please use incoming or outgoing.' . PHP_EOL);
	}

	/*!
	 * \brief Change call status (string) to defined number (int).
	 *
	 * \param string $type call type
	 * \return int number assigned to call status
	 * \return php_function die when can't match string to call type
	 */
	function parseCallStatus($type) {
		if (preg_match("/busy/i", $type))
			return CALL_BUSY;

		if (preg_match("/answered/i", $type))
			return CALL_ANSWERED;

		if (preg_match("/(noanswer|no answer)/i", $type))
			return CALL_NO_ANSWER;

		die('Call status is not correct. Please use busy, answered or noanswer.' . PHP_EOL);
	}

	/*!
	 * \brief Include tariff by id.
	 *
	 * \param int $t_id tariff id
	 */
	function include_tariff($tariff_id) {
		global $tariffs;
		$file = VOIP_CACHE_DIR . DIRECTORY_SEPARATOR . 'tariff_' . $tariff_id . '.php';

		if (!file_exists($file))
			die('Tariff file "' . $file . '" doesn\'t exists.' . PHP_EOL);

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
				case 'record':
					if (!preg_match("/^[0-1]*$/", $v))
						return "Recording options contains incorrect values.";
				break;

				case 'uniqueid':
					if (!preg_match("/^[0-9]+\.[0-9]+$/", $v))
						return "Asterisk call unique id is not correct.";
				break;

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
						case 'gencache':
						break;

						default:
							return 'Action not found.' . PHP_EOL;
					}
				break;

				case 'file':
					if (!file_exists($v))
						return "File $v doesn't exists.". PHP_EOL;
				break;

				case 'status':
					switch ($v) {
						case 'answered':
						case 'no answer':
						case 'busy':
						break;

						default:
							return 'Call status is not correct. Choose one of values: busy, answered, noanswer.' . PHP_EOL;
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
