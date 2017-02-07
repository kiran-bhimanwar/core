<?php
namespace Df\Payment\Settings;
class BankCard extends \Df\Payment\Settings {
	/**
	 * 2016-11-10
	 * «Prefill the Payment Form with Test Data?» 
	 * @used-by \Df\Payment\ConfigProvider\BankCard::config()
	 * @see \Dfe\CheckoutCom\Settings::prefill()
	 * @see \Dfe\Paymill\Settings::prefill()
	 * @return string|false|null|array(string => string)
	 */
	public function prefill() {return $this->bv();}
}