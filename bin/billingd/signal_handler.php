<?php

	function sig_handler($signo)
	{
		global $logfh, $prefixManager, $customerManager, $adminPool, $lock_fh, $unlock_fh;
		$signature = date("G:i:s d-M-Y | ");

		switch ($signo) {
			case SIGTERM:
				fprintf($logfh, $signature . "received SIGTERM" . PHP_EOL);
			break;

			case SIGHUP:
				fprintf($logfh, $signature . "received SIGHUP" . PHP_EOL);
				
				// reload config
				$adminPool->submit(new reloadConfiguration());

//				flock($unlock_fh, LOCK_UN);
//				flock($lock_fh, LOCK_UN);
//				flock($unlock_fh, LOCK_EX);
			break;
		}
	}

	$res = pcntl_signal(SIGTERM, "sig_handler");
	$res = pcntl_signal(SIGHUP, "sig_handler");

?>
