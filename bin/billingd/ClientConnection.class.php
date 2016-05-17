<?php

class ClientConnection extends Threaded {

	public function __construct($socket) {
		$this->socket = $socket;
	}

	/*!
	 * Main function.
	 */
	public function run() {
		if (!isset($this->worker->PrefixManager) || !isset($this->worker->CustomerManager))
			$this->endThread();

		$pm = $this->worker->PrefixManager;
		$cm = $this->worker->CustomerManager;	
		$taskString = '';

		while (($buffer = socket_read($this->socket, 512, PHP_NORMAL_READ)) !== false) {
			$buffer = trim($buffer);

			if (!strlen($buffer))
				continue;

			$taskString .= $buffer;
			if (strpos($taskString, PHP_EOL) !== false)
				continue;

			$exec = explode(' ', $taskString);

			switch(strtolower($exec[0])) {
				case 'estimate':
					try 
					{
						if (count($exec) < 3)
							throw new Exception('Function estimate expect two arguments.');
						
						$unitCost = $pm->getCost($exec[1], $exec[2], $cm->getClientTariffIdByPhone($exec[1]));			
						$accountBalance = $cm->getAccountBalanceByPhone($exec[1]);
						
						//max call time in seconds rounded down to unit size 
						$maxCallTime = floor($accountBalance / $unitCost['costPerUnit']) * $unitCost['unitSize'];
						
						socket_write($this->socket, $maxCallTime . PHP_EOL);
					}
					catch (Exception $e) 
					{
						socket_write($this->socket, $e->getMessage() . PHP_EOL);
					}
				break;

				case 'account':
				case 'help':
					socket_write($this->socket, "LMS DAEMON HELP" . PHP_EOL . PHP_EOL);
					socket_write($this->socket, "ESTIMATE [phone_number_1] [phone_number_2]\n\tReturn maximum call time in seconds between two persons.\n\t[phone_number_1] - caller phone number\n\t[phone_number_2] - target phone number" . PHP_EOL . PHP_EOL);
					socket_write($this->socket, "ACCOUNT [phone_number_1] [phone_number_2] [time]\n\t" . PHP_EOL);
					socket_write($this->socket, "EXIT, QUIT, END\n\tEnd current connection with daemon." . PHP_EOL);
				break;

				case 'end':
				case 'quit':
				case 'exit':
					$this->endThread();
				break;

				default:
					socket_write($this->socket, "function '" . $exec[0] . "' not found, use 'help' for more informations" . PHP_EOL);
				break;
			}

			$taskString = '';
		}

		$this->endThread();
	}

	/*!
	 * End current thread.
	 */
	public function endThread() {
		socket_shutdown($this->socket, 2);
		socket_close($this->socket);
		exit();
	}
}
?>