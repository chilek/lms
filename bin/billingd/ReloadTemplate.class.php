<?php

class ReloadTemplate extends Worker {
	public $PrefixManager;
	public $CustomerManager;
	public $log_file;

	public function __construct(PrefixManager $po, CustomerManager $cm, $fh) {
		$this->PrefixManager = $po;
		$this->CustomerManager = $cm;
		$this->log_file = $fh;
	}
	
	public function run() {
	}
}

?>