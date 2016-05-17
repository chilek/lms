<?php

class Customer {
	private $lastMonthCostSummary = 20; // srednia kosztow z X ostatnich miesiecy
	private $tel_number = '';
	private $rules = array( 1 => array( 'seconds'=>3600, 'salePrice'=>0, 'prefixes' => array('10900'=>1, '10901'=>1, '10902'=>1) ),		
									 2 => array( 'seconds'=>3600, 'salePrice'=>0.1, 'prefixes' => array('11200'=>1, '11201'=>1, '11202'=>1) ) );
								  
	public function __construct($tel_number) {
		$this->tel_number = $tel_number;
	}				

	public function getNumber() {
		return $this->tel_number;
	}							  
								  
	public function deceraseRule($rule_id, $seconds) {
		$this->rules[$rule_id]['seconds'] -= $seconds;
	}
	
	public function printRule($rule_id) {
		print_r($this->rules[$rule_id]);
	}
}

// $c1 = new Customer('234234234');
// $c1->printRule(1);
// $c1->deceraseRule(1, 120);

// echo '<br>';

// $c1->printRule(1);

?>