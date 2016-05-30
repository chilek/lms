<?php
	include 'lms_stub.php';
	include 'functions.php';

	$tariffs = array();
	$parameters = array('a:' => 'action:',
								 'd' => 'debug',
								 'e:' => 'callee:',
								 'f:' => 'file:',
								 'l:' => 'calltime:',
								 'o:' => 'totaltime:',
								 'r:' => 'caller:',
								 's:' => 'startcall:',
								 't:' => 'type:',
								 'u:' => 'status:');

	foreach ($parameters as $key => $val) {
		$val = preg_replace('/:/', '', $val);
		$newkey = preg_replace('/:/', '', $key);
		$short_to_longs[$newkey] = $val;
	}

	$options = getopt(implode('', array_keys($parameters)), $parameters);
	foreach ($short_to_longs as $short => $long)
		if (array_key_exists($short, $options)) {
			$options[$long] = $options[$short];
			unset($options[$short]);
		}

	$options['action'] = (isset($options['action'])) ? $options['action'] : '';

	// valid parameters
	$param_err = validParamters($options);
	if ($param_err !== TRUE)
		die($param_err);

	switch (strtolower($options['action'])) {
		case 'estimate':
			if (empty($options['caller']))
				die('Caller phone number is not set. Please use --caller [phone_number].' . PHP_EOL);

			if (empty($options['callee']))
				die('Callee phone number is not set. Please use --callee [phone_number].' . PHP_EOL);

			// get maximum call time in seconds
			$call_time = getMaxCallTime($options['caller'], $options['callee']);

			// if debug mode is set print value else change to miliseconds before print
			echo (array_key_exists('debug', $options)) ? $call_time.PHP_EOL : $call_time*1000;
		break;

		case 'account':
			if (isset($options['caller'])) {
				if (empty($options['caller']))
					die('Caller phone number is not set. Please use --caller [phone_number].' . PHP_EOL);

				if (empty($options['callee']))
					die('Callee phone number is not set. Please use --callee [phone_number].' . PHP_EOL);

				if (empty($options['startcall']))
					die('Call start is not set. Please use --startcall [unix_timestamp].' . PHP_EOL);

				if (empty($options['totaltime']))
					die('Time start to end of call is not set. Please use --totaltime [number_of_seconds].' . PHP_EOL);

				if (empty($options['calltime']))
					die('Time answer to end of call is not set. Please use --calltime [number_of_seconds].' . PHP_EOL);

				if (empty($options['type']))
					die('Call type is not set. Please use --type (incoming|outgoing).' . PHP_EOL);

				if (empty($options['status']))
					die('Call status is not set. Please use --status (busy|answered|no answer).' . PHP_EOL);

				// get customer and include tariff
				$customer = getCustomerByPhone($options['caller']);
				include_tariff($customer['tariffid']);

				// get first letter of type
				$call_type = strtolower($options['type'][0]);

				// no payments for incoming call else calculate cost for call
				$call_cost = getCost($options['caller'], $options['callee'], $customer['tariffid']);
				$price = ($call_type == 'o') ? round(ceil($options['calltime']/$call_cost['unitSize']) * $call_cost['costPerUnit'], 5) : 0;

				// insert cdr record to database
				$query = sprintf("INSERT INTO
											 voip_cdr (caller, callee, call_start_time, time_start_to_end, time_answer_to_end, price, status, type, voipaccountid)
										 VALUES
											 ('%s', '%s', %s, %d, %d, %f, '%s', '%s', %d);", $options['caller'], $options['callee'], $options['startcall'], $options['totaltime'], $options['calltime'], $price, strtolower($options['status']), $call_type, $customer['voipaccountid']);

				$DB->Execute($query);
			} else {
				$fh = (isset($options['file'])) ? fopen($options['file'], 'r') : fopen('php://stdin', 'r');
				$customer_list = getCustomerList();
				$error = array();
				$i=0;

				while($f_line = fgets($fh)) {
					// increment file line counter
					++$i;

					// change line to associative array
					$cdr = parseRow($f_line);

					// check values of cdr array
					$cdr_error = validCDR($cdr);

					if ($cdr_error === TRUE) {
						$tariff_id = $customer_list[$cdr['caller']]['tariffid'];

						//include customer tariff
						if (!isset($tariffs[$tariff_id])) {
							include_tariff($tariff_id);

							if (!isset($tariffs[$tariff_id])) {
								$error['errors'][] = array('line'=>$i, 'line_content'=>$f_line, 'error'=>'Cant find tariff ' . $tariff_id . ' in tariff files.');
								continue;
							}
						}

						// get first letter of type
						$call_type = strtolower($cdr['call_type'][0]);

						// generate unix timestamp
						$call_start = mktime($cdr['call_start_hour'], $cdr['call_start_min'], $cdr['call_start_sec'], $cdr['call_start_month'], $cdr['call_start_day'], $cdr['call_start_year']);

						// no payments for incoming call else calculate cost for call
						$call_cost = getCost($cdr['caller'], $cdr['callee'], $tariff_id);
						$price = ($call_type == 'o') ? round(ceil($cdr['time_answer_to_end']/$call_cost['unitSize']) * $call_cost['costPerUnit'], 5) : 0;

						// insert cdr record to database
						$query = sprintf("INSERT INTO
													voip_cdr (caller, callee, call_start_time, time_start_to_end, time_answer_to_end, price, status, type, voipaccountid)
												VALUES
													('%s', '%s', %d, %d, %d, %f, '%s', '%s', %d);", $cdr['caller'], $cdr['callee'], $call_start, $cdr['time_start_to_end'], $cdr['time_answer_to_end'], $price, strtolower($cdr['call_status']), $call_type, $customer_list[$cdr['caller']]['voipaccountid']);

						$DB->Execute($query);
					} else {
						$error['errors'][] = array('line'=>$i, 'line_content'=>$f_line, 'error'=>$cdr_error);
						continue;
					}
				}

				fclose($fh);
			}
		break;
	}
?>