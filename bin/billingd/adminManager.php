<?php

class reloadConfiguration extends Threaded {
	
	public function __construct($socket) {
		global $logfh;
		
		fprintf($logfh, $signature . "reload daemon configuration start" . PHP_EOL, print_r($signo, true));
		$prefixManager->reload();
		$adminPoolManager->submit();
		//$customerManager->reload();
		fprintf($logfh, $signature . "reload daemon configuration end" . PHP_EOL, print_r($signo, true));
	}
}

?>