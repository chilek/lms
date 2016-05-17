<?php

class WorkerTemplate extends Worker {  

	public function __construct(PrefixManager $po, CustomerManager $cm) {
		$this->PrefixManager = $po;
		$this->CustomerManager = $cm;
	}
	
	public function run() {}
}

?>
