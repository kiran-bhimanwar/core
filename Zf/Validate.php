<?php
namespace Df\Zf;
abstract class Validate implements \Zend_Validate_Interface {
	/** @return string */
	abstract protected function getMessageInternal();

	/** @param array(string => mixed) $params */
	function __construct(array $params = []) {$this->_params = $params;}

	/**
	 * Этот метод присутствует для совместимости c устаревшими версиями Zend Framework
	 * (в частности, с версией 1.9.6, которая используется в Magento CE 1.4.0.1)
	 * @deprecated Since 1.5.0
	 * @override
	 * @return array(string => string)
	 */
	function getErrors() {return array_keys($this->getMessages());}

	/**
	 * @override
	 * @return string
	 */
	function getMessage() {
		if (!isset($this->_message)) {
			$this->_message = $this->getMessageInternal();
			if ($this->getExplanation()) {
				$this->_message .= ("\n" . $this->getExplanation());
			}
		}
		return $this->_message;
	}

	/**
	 * @override
	 * @return array(string => string)
	 */
	function getMessages() {return [__CLASS__ => $this->getMessage()];}

	/**
	 * @param string $paramName
	 * @param mixed $d [optional]
	 * @return mixed
	 */
	final protected function cfg($paramName, $d = null) {return dfa($this->_params, $paramName, $d);}

	/** @return string|null */
	protected function getExplanation() {return $this->cfg(self::$PARAM__EXPLANATION);}

	/** @return mixed */
	protected function getValue() {return $this->cfg(self::$PARAM__VALUE);}

	/**
	 * @param mixed $v
	 */
	protected function prepareValidation($v) {$this->setValue($v);}

	/** @used-by setValue() */
	protected function reset() {
		unset($this->_message);
		/**
		 * Раньше тут стоял код $this->_params = []
		 * который сбрасывает сразу все значения параметров.
		 * Однако этот код неверен!
		 * Негоже родительскому классу безапелляционно решать за потомков,
		 * какие данные им сбрасывать.
		 * Например, потомок @see \Df\Zf\Validate\Class
		 * хранит в параметре @see \Df\Zf\Validate\Class::$PARAM__CLASS
		 * требуемый класс результата,
		 * и сбрасывать это значение между разными валидациями не нужно!
		 * Вместо сброса значения между разными валидациями
		 * класс @see \Df\Zf\Validate\Class ведёт статический кэш своих экземпляров
		 * для каждого требуемого класса результата:
		 * @see \Df\Zf\Validate\Class::s().
		 * Сброс значения параметра @see \Df\Zf\Validate\Class::$PARAM__CLASS
		 * не только не нужен, но и приведёт к сбою!
		 * Пусть потомки сами решают
		 * посредством перекрытия метода @see \Df\Zf\Validate\Type::reset(),
		 * значения каких параметров им надо сбрасывать между разными валидациями.
		 */
		unset($this->_params[self::$PARAM__VALUE]);
		unset($this->_params[self::$PARAM__EXPLANATION]);
	}

	/**
	 * @param string $value
	 */
	protected function setExplanation($value) {$this->_params[self::$PARAM__EXPLANATION] = $value;}

	/**
	 * @param string $message
	 */
	protected function setMessage($message) {$this->_message = $message;}

	/**
	 * @param mixed $value
	 */
	private function setValue($value) {
		$this->reset();
		$this->_params[self::$PARAM__VALUE] = $value;
	}

	/** @var string */
	private $_message;
	/** @var array(string => mixed) */
	private $_params = [];

	/** @var string */
	private static $PARAM__EXPLANATION = 'explanation';
	/** @var string */
	private static $PARAM__VALUE = 'value';
}