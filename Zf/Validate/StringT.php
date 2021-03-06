<?php
namespace Df\Zf\Validate;
use Magento\Framework\Phrase;
class StringT extends Type implements \Zend_Filter_Interface {
	/**
	 * @override
	 * @param mixed $v
	 * @throws \Zend_Filter_Exception
	 * @return string|mixed
	 */
	function filter($v) {return is_null($v) || is_int($v) ? strval($v) : $v;}

	/**
	 * @override
	 * @see \Zend_Validate_Interface::isValid()
	 * @param mixed $v
	 * @return bool
	 */
	function isValid($v) {
		$this->prepareValidation($v);
		/**
		 * 2015-02-16
		 * Раньше здесь стояло просто is_string($value)
		 * Однако интерпретатор PHP способен неявно и вполне однозначно
		 * (без двусмысленностей, как, скажем, с вещественными числами)
		 * конвертировать целые числа и null в строки,
		 * поэтому пусть целые числа и null всегда проходят валидацию как строки.
		 * 2016-07-01 Добавил «|| $value instanceof Phrase»
		 * 2017-01-13 Добавил «|| is_bool($value)»
		 */
		return is_string($v) || is_int($v) || is_null($v) || is_bool($v) || $v instanceof Phrase;
	}

	/**
	 * @override
	 * @return string
	 */
	protected function getExpectedTypeInAccusativeCase() {return 'строку';}

	/**
	 * @override
	 * @return string
	 */
	protected function getExpectedTypeInGenitiveCase() {return 'строки';}

	/** @return self */
	static function s() {static $r; return $r ? $r : $r = new self;}
}