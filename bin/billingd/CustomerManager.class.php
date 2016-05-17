<?php

class CustomerManager {
	private $phone_to_clientid = array();
	private $accountList = array();

	/*!
	 * \brief Get account balance by phone number.
	 *
	 * \param string phone number
	 * \return float current account balance
	 */ 
	public function getAccountBalanceByPhone($TEL_NUMBER) {
		return 1000;
	}
	
	/*!
	 * \brief Get customer tariff id by phone number.
	 *
	 * \param string phone number
	 * \return int tariff ID
	 */
	public function getClientTariffIdByPhone($TEL_NUMBER) {
		return 4;
	}
	
	public function setPhoneToCustomerArray( $array ) {
		$this->phone_to_clientid = $array;
	}
}

?>