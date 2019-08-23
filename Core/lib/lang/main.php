<?php
use Closure as F;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * 2015-12-25
 * Этот загадочный метод призван заменить код вида:
 * is_array($arguments) ? $arguments : func_get_args()
 * Теперь можно писать так: df_args(func_get_args())
 * @used-by dfa_combine_self()
 * @param mixed[] $a
 * @return mixed[]
 */
function df_args(array $a) {return !$a || !is_array($a[0]) ? $a : $a[0];}

/**
 * 2017-02-07
 * @used-by \Df\Payment\Block\Info::extended()
 * @used-by \Df\Payment\Method::test()
 * @used-by \Dfe\Moip\FE\Webhooks::onFormInitialized()
 * @used-by \Dfe\Paymill\Settings::test3DS()
 * @param mixed[] $args
 * $args — массив либо пустой, либо из 2 элементов с целочисленными индексами 0 и 1.
 * Если массив $args пуст, то функция возвращает $r.
 * Если массив $args непуст, то функция возвращает:
 * 		$args[0] при истинности $r
 *		$args[1] при ложности $r
 * @param bool $r
 * @return mixed
 */
function df_b(array $args, $r) {return !$args ? $r : $args[intval(!$r)];}

/**
 * 2017-04-26
 * @used-by df_ci_get()
 * @used-by df_oi_add()
 * @used-by df_oi_get()
 * @used-by df_primary_key()
 * @used-by df_trd()    
 * @used-by ikf_oi_pid()
 * @used-by \Df\API\Facade::p()
 * @used-by \Df\Config\Backend\Serialized::valueUnserialize()
 * @used-by \Df\Config\Settings::json()
 * @used-by \Df\Customer\Plugin\Block\Form\Register::afterGetFormData()
 * @used-by \Df\Xml\Parser\Collection::findByNameAll()
 * @used-by \Df\Xml\X::importString()
 * @used-by \Df\Zf\Validate\ArrayT::filter()
 * @used-by \Dfe\AllPay\Total\Quote::iiAdd()
 * @used-by \Dfe\AllPay\Total\Quote::iiGet()
 * @used-by \Dfe\CheckoutCom\Method::disableEvent()
 * @used-by \Dfe\Markdown\Plugin\Ui\Component\Form\Element\Wysiwyg::beforePrepare()
 * @used-by \Dfe\Square\Facade\Customer::cardsData()
 * @param mixed|null $v
 * @return mixed[]
 */
function df_eta($v) {
	if (!is_array($v)) {
		df_assert(empty($v));
		$v = [];
	}
	return $v;
}

/**
 * 2016-02-09
 * Осуществляет ленивое ветвление только для первой ветки.
 * @param bool $condition
 * @param mixed|callable $onTrue
 * @param mixed|null $onFalse [optional]
 * @return mixed
 */
function df_if1($condition, $onTrue, $onFalse = null) {return
	$condition ? df_call_if($onTrue) : $onFalse
;}

/**
 * 2016-02-09
 * Осуществляет ленивое ветвление только для второй ветки.
 * @param bool $condition
 * @param mixed $onTrue
 * @param mixed|null|callable $onFalse [optional]
 * @return mixed
 */
function df_if2($condition, $onTrue, $onFalse = null) {return
	$condition ? $onTrue : df_call_if($onFalse)
;}

/**
 * Осуществляет ленивое ветвление.
 * @param bool $cond
 * @param mixed|callable $onTrue
 * @param mixed|null|callable $onFalse [optional]
 * @return mixed
 */
function df_if($cond, $onTrue, $onFalse = null) {return
	$cond ? df_call_if($onTrue) : df_call_if($onFalse)
;}

/**
 * @param mixed|string $v
 * @return mixed|null
 */
function df_n_get($v) {return 'df-null' === $v ? null : $v;}

/**
 * @param mixed|null $v
 * @return mixed|string
 */
function df_n_set($v) {return is_null($v) ? 'df-null' : $v;}

/**
 * @used-by \Df\Core\Format\Html\Tag::getOpenTagWithAttributesAsText()
 * @param mixed $argument
 * @return mixed
 */
function df_nop($argument) {return $argument;}

/**
 * 2019-04-05
 * @used-by \Df\API\Client::logging()
 * @used-by \Df\API\FacadeOptions::resC()
 * @used-by \Df\API\FacadeOptions::silent()
 * @used-by \Inkifi\Pwinty\API\Entity\Image::attributes()
 * @used-by \Inkifi\Pwinty\API\Entity\Image::copies()
 * @used-by \Inkifi\Pwinty\API\Entity\Image::sizing()
 * @used-by \Inkifi\Pwinty\API\Entity\Image::type()
 * @used-by \Inkifi\Pwinty\API\Entity\Image::url()
 * @used-by \Inkifi\Pwinty\API\Entity\Order::magentoOrder()
 * @param object|\ArrayAccess $o
 * @param mixed|null $v
 * @param string|mixed|null $d [optional]
 * @param string|null $type [optional]
 * @return mixed|object|\ArrayAccess
 */
function df_prop($o, $v = null, $d = null, $type = null) {
	if ('int' === $d) {
		$type = $d; $d = null;
	}
	$k = df_caller_f(); /** @var string $k */
	/** @var object|mixed $r */
	if ($o instanceof \ArrayAccess) {
		if (is_null($v)) {
			$r = !$o->offsetExists($k) ? $d : $o->offsetGet($k);
		}
		else {
			$o->offsetSet($k, $v);
			$r = $o;
		}
	}
	else {
		$a = '_' . __FUNCTION__; /** @var string $a */
		if (!isset($o->$a)) {
			$o->$a = [];
		}
		if (is_null($v)) {
			$r = dfa($o->$a, $k, $d);
		}
		else {
			($o->$a)[$k] = $v;
			$r = $o;
		}
	}
	return is_null($v) && 'int' === $type ? intval($r) : $r;
}

/**
 * 2015-12-06
 * @param string|object $id
 * @param callable $job
 * @param float $interval [optional]
 * @return mixed
 */
function df_sync($id, callable $job, $interval = 0.1) {
	/** @var int $intervalI */
	$intervalI = round(1000000 * $interval);
	/** @var string $nameShort */
	$nameShort = 'df-core-sync-' . md5(is_object($id) ? get_class($id) : $id) . '.lock';
	/** @var string $name */
	$name = df_path_absolute(DirectoryList::TMP, $nameShort);
	/** @var mixed $result */
	while(file_exists($name)) {
		usleep($intervalI);
	}
	try {
		df_file_write($name, '');
		$result = $job();
	}
	finally {
		df_fs_w(DirectoryList::TMP)->delete($nameShort);
	}
	return $result;	
}

/**
 * 2017-04-15
 * @used-by df_cms_block_content()
 * @used-by df_currency_convert_safe()
 * @used-by df_customer()
 * @used-by df_intl_dic_read()
 * @used-by df_layout_update()
 * @used-by df_magento_version_remote()
 * @used-by df_phone()
 * @used-by dfp_refund()
 * @used-by \Df\Payment\BankCardNetworks::url()
 * @used-by \Dfe\Moip\Facade\Customer::_get()
 * @used-by \Dfe\Moip\FE\Webhooks::onFormInitialized()
 * @used-by \Dfe\Square\Facade\Customer::_get()
 * @used-by \Doormall\Shipping\Partner\Entity::locationM()
 * @used-by \Inkifi\Mediaclip\API\Facade\User::projects()
 * @param F $try
 * @param F|bool|mixed $onError [optional]
 * @return mixed
 * @throws \Exception
 */
function df_try(F $try, $onError = null) {
	try {return $try();}
	catch(\Exception $e) {return $onError instanceof F ? $onError($e) : (
		true === $onError ? df_error($e) : $onError
	);}
}