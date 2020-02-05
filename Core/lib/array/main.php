<?php
use Df\Core\Exception as DFE;

/**
 * @param mixed|mixed[] $v
 * @return mixed[]|string[]|float[]|int[]
 */
function df_array($v) {return is_array($v) ? $v : [$v];}

/**
 * 2015-02-11
 * Аналог @see array_column() для коллекций.
 * Ещё один аналог: @see \Magento\Framework\Data\Collection::getColumnValues(),
 * но его результат — не ассоциативный.
 * 2016-07-31 При вызове с 2-мя параметрами эта функция идентична функции @see df_each()
 * 2017-07-09
 * Now the function accepts an array as $object.
 * Even in this case it differs from @see array_column():
 * array_column() misses the keys: https://3v4l.org/llMrL
 * df_column() preserves the keys.
 * @used-by df_index()
 * @used-by \Wolf\Filter\Block\Navigation::hDropdowns()
 * @param \Traversable|array(int|string => _DO|array(string => mixed)) $c
 * @param string|\Closure $fv
 * @param string|null $fk [optional]
 * @return array(int|string => mixed)
 */
function df_column($c, $fv, $fk = null) {return df_map_kr($c, function($k, $v) use($fv, $fk) {return [
	!$fk ? $k : df_call($v, $fk), df_call($v, $fv)
];});}

/**
 * 2016-11-08
 * Отличия этой функции от @uses array_filter():
 * 1) работает не только с массивами, но и с @see \Traversable
 * 2) принимает аргументы в произвольном порядке.
 * Третий параметр — $flag — намеренно не реализовал,
 * потому что вроде бы для @see \Traversable он особого смысла не имеет,
 * а если у нас гарантирвоанно не @see \Traversable, а ассоциативный массив,
 * то мы можем использовать array_filter вместо df_filter.
 * @used-by \Frugue\Core\Plugin\Sales\Model\Quote::afterGetAddressesCollection()
 * @param callable|array(int|string => mixed)|array[]\Traversable $a
 * @param callable|array(int|string => mixed)|array[]|\Traversable $b
 * @return array(int|string => mixed)
 */
function df_filter($a, $b) {return array_filter(...(
	is_callable($a) ? [df_ita($b), $a] : [df_ita($a), $b]
));}

/**
 * 2016-10-25 Оказалось, что в ядре нет такой функции.
 * @used-by df_handle_prefix()
 * @used-by df_oq_sa()
 * @used-by df_sales_email_sending()
 * @used-by ikf_oi_pid()
 * @used-by mnr_recurring()
 * @used-by \Df\Framework\Plugin\View\Layout::afterIsCacheable()
 * @used-by \Df\Payment\Info\Report::addAfter()
 * @used-by \Df\Payment\Method::amountFactor()
 * @used-by \Df\Payment\TM::confirmed()
 * @used-by \Dfe\Stripe\Method::cardType()
 * @used-by \Frugue\Core\Plugin\Sales\Model\Quote::afterGetAddressesCollection()
 * @used-by \Inkifi\Mediaclip\API\Entity\Order\Item::mProduct()
 * @used-by \Inkifi\Mediaclip\Event::_areAllOIAvailableForDownload()
 * @used-by \Inkifi\Mediaclip\Event::oi()
 * @param array|callable|\Traversable $a1
 * @param array|callable|\Traversable $a2
 * @param mixed|mixed[] $pAppend [optional]
 * @param mixed|mixed[] $pPrepend [optional]
 * @param int $keyPosition [optional]
 * @return mixed|null
 * @throws DFE
 */
function df_find($a1, $a2, $pAppend = [], $pPrepend = [], $keyPosition = 0) {
	list($a, $f) = dfaf($a1, $a2); /** @var array|\Traversable $a */ /** @var callable $f */
	$pAppend = df_array($pAppend); $pPrepend = df_array($pPrepend);
	$r = null; /** @var mixed|null $r */
	foreach ($a as $k => $v) {/** @var int|string $k */ /** @var mixed $v */ /** @var mixed[] $primaryArgument */
		switch ($keyPosition) {
			case DF_BEFORE:
				$primaryArgument = [$k, $v];
				break;
			case DF_AFTER:
				$primaryArgument = [$v, $k];
				break;
			default:
				$primaryArgument = [$v];
		}
		if ($fr = call_user_func_array($f, array_merge($pPrepend, $primaryArgument, $pAppend))) {
			$r = !is_bool($fr) ? $fr : $v;
			break;
		}
	}
	return $r;
}

/**
 * 2015-12-30 Преобразует коллекцию или массив в карту.
 * @used-by \Df\Config\A::get()
 * @param string|\Closure $k
 * @param \Traversable|array(int|string => _DO) $items
 * @return mixed[]
 */
function df_index($k, $a) {return array_combine(df_column($a, $k), $a);}

/**
 * 2015-02-11
 * Эта функция отличается от @see iterator_to_array() тем, что допускает в качестве параметра
 * не только @see \Traversable, но и массив.
 * @used-by df_map()
 * @param \Traversable|array $t
 * @return array
 */
function df_ita($t) {return is_array($t) ? $t : iterator_to_array($t);}

/**
 * @used-by Df_InTime_Api::call()
 * http://stackoverflow.com/a/18576902
 * @param mixed $value
 * @return array
 * @throws DFE
 */
function df_stdclass_to_array($value) {return df_json_decode(json_encode($value));}

/**
 * http://en.wikipedia.org/wiki/Tuple
 * @param array $arrays
 * @return array
 */
function df_tuple(array $arrays) {
	/** @var array $result */
	$result = [];
	/** @var int $count */
	$countItems = max(array_map('count', $arrays));
	for ($ordering = 0; $ordering < $countItems; $ordering++) {
		/** @var array $item */
		$item = [];
		foreach ($arrays as $arrayName => $array) {
			$item[$arrayName]= dfa($array, $ordering);
		}
		$result[$ordering] = $item;
	}
	return $result;
}

/**
 * 2017-02-18
 * [array|callable, array|callable] => [array, callable]
 * @used-by df_find()
 * @used-by df_map()
 * @used-by dfak_transform()
 * @param array|callable|\Traversable $a
 * @param array|callable|\Traversable $b
 * @return array(int|string => mixed)
 */
function dfaf($a, $b) {return is_callable($a)
	? [df_assert_traversable($b), $a]
	: [df_assert_traversable($a), df_assert_callable($b)]
;}

/**
 * 2019-01-28
 * @used-by \Dfe\Vantiv\API\Client::_construct()
 * @param array(int|string => mixed) $a
 * @param string[] $k
 * @param mixed|null $d [optional]
 * @return mixed|null
 */
function dfa_seq(array $a, array $k, $d = null) {
	$r = null; /** @var @var mixed|null $r */
	foreach ($k as $ki) { /** @var string $ki */
		$r = dfa($a, $ki);
		if (!is_null($r)) {
			break;
		}
	}
	return is_null($r) ? $d : $r;
}

/**
 * 2018-04-24
 * @used-by \Doormall\Shipping\Partner\Entity::locations()
 * @param array(int|string => mixed) $a
 * @param string|int $k
 * @return array(int|string => array(int|string => mixed))
 */
function dfa_group(array $a, $k) {
	$r = []; /** @var array(int|string => array(int|string => mixed)) $r */
	$isInt = is_int($k); /** @var bool $isInt */
	foreach ($a as $v) { /** @var mixed $v */
		$index = $v[$k]; /** @var string $index */
		if (!isset($r[$index])) {
			$r[$index] = [];
		}
		unset($v[$k]);
		$r[$index][] = 1 === count($v) ? df_first($v) : (!$isInt ? $v : array_values($v));
	}
	return $r;
}

/**
 * 2016-09-07
 * 2017-03-06
 * @uses mb_substr() корректно работает с $length = null
 * @used-by \Df\Payment\Charge::metadata()
 * @param string[] $a
 * @param int|null $length
 * @return string[]
 */
function dfa_chop(array $a, $length) {return df_map('mb_substr', $a, [0, $length]);}

/**               
 * 2016-11-25
 * @used-by \Df\Config\Source\SizeUnit::map()
 * @used-by \Df\Core\Validator::byName()
 * @used-by \Dfe\AmazonLogin\Source\Button\Native\Size::map()
 * @used-by \Dfe\CheckoutCom\Source\Prefill::map()
 * @used-by \Dfe\FacebookLogin\Source\Button\Size::map()
 * @used-by \Dfe\SMTP\Source\Service::map()
 * @used-by \Dfe\ZohoCRM\Source\Domain::map() 
 * @used-by \KingPalm\B2B\Source\Type::map()
 * @used-by df_a_to_options()
 * @param string[]|int[] ...$a
 * @return array(int|string => int|string)
 */
function dfa_combine_self(...$a) {$a = df_args($a); return array_combine($a, $a);}

/**
 * Эта функция отличается от @uses array_fill() только тем,
 * что разрешает параметру $length быть равным нулю.
 * Если $length = 0, то функция возвращает пустой массив.
 * @uses array_fill() разрешает параметру $num (аналог $length)
 * быть равным нулю только начиная с PHP 5.6:
 * http://php.net/manual/function.array-fill.php
 * «5.6.0	num may now be zero. Previously, num was required to be greater than zero»
 * @param int $startIndex
 * @param int $length
 * @param mixed $value
 * @return mixed[]
 */
function dfa_fill($startIndex, $length, $value) {return !$length ? [] : 
	array_fill($startIndex, $length, $value)
;}

/**
 * 2016-03-25 http://stackoverflow.com/a/1320156
 * @used-by df_cc_class()
 * @used-by df_cc_class_uc()
 * @used-by df_mail()
 * @used-by dfa_unpack()
 * @used-by \Inkifi\Pwinty\AvailableForDownload::_p()
 * @param array $a
 * @return mixed[]
 */
function dfa_flatten(array $a) {
	$r = []; /** @var mixed[] $r */
	array_walk_recursive($a, function($a) use(&$r) {$r[]= $a;});
	return $r;
}

/**
 * 2016-07-31
 * @param \Traversable|array(int|string => _DO) $collection
 * @return int[]|string[]
 */
function dfa_ids($collection) {return df_each($collection, 'getId');}

/**
 * 2016-07-31
 * Возвращает повторяющиеся элементы исходного массива (не повторяя их). https://3v4l.org/YEf5r
 * В алгоритме пользуемся тем, что @uses array_unique() сохраняет ключи исходного массива.
 * 2020-01-29 dfa_repeated([1,2,2,2,2,3,3,3,4]) => [2,3]
 * @used-by \Df\Config\Backend\ArrayT::processI()
 * @param array $a
 * @return array
 */
function dfa_repeated(array $a) {return array_values(array_unique(array_diff_key($a, array_unique($a))));}

/**
 * Работает в разы быстрее, чем @see array_unique()
 * «Just found that array_keys(array_flip($array)); is amazingly faster than array_unique();.
  * About 80% faster on 100 element array,
  * 95% faster on 1000 element array
  * and 99% faster on 10000+ element array.»
 * http://stackoverflow.com/questions/5036504/php-performance-question-faster-to-leave-duplicates-in-array-that-will-be-searc#comment19991540_5036538
 * http://www.php.net/manual/en/function.array-unique.php#70786
 * 2015-02-06
 * Обратите внимание, что т.к. алгоритм @see dfa_unique_fast() использует @uses array_flip(),
 * то @see dfa_unique_fast() можно применять только в тех ситуациях,
 * когда массив содержит только строки и целые числа,
 * иначе вызов @uses array_flip() завершится сбоем уровня E_WARNING:
 * «array_flip(): Can only flip STRING and INTEGER values»
 * http://magento-forum.ru/topic/4695/
 * В реальной практике сбой случается, например, когда массив содержит значение null:
 * http://3v4l.org/bat52
 * Пример кода, приводящего к сбою: dfa_unique_fast(array(1, 2, 2, 3, null))
 * В то же время, несмотря на E_WARNING, метод всё-таки возвращает результат,
 * правда, без недопустимых значений:
 * при подавлении E_WARNING dfa_unique_fast(array(1, 2, 2, 3, null)) вернёт:
 * array(1, 2, 3).
 * Более того, даже если сбойный элемент содержится в середине исходного массива,
 * то результат при подавлении сбоя E_WARNING будет корректным (без недопустимых элементов):
 * dfa_unique_fast(array(1, 2, null,  2, 3)) вернёт тот же результат array(1, 2, 3).
 * http://3v4l.org/uvJoI
 * По этой причине добавил оператор @ перед @uses array_flip()
 * @param array(int|string => int|string) $a
 * @return array(int|string => int|string)
 */
function dfa_unique_fast(array $a) {return
	/** @noinspection PhpUsageOfSilenceOperatorInspection */ array_keys(@array_flip($a))
;}

/**
 * 2020-01-29
 * @see df_args()
 * [$v] => $v
 * [[$v]] => [$v]
 * [[$v1, $v2]] => [$v1, $v2]
 * [$v1, $v2] => [$v1, $v2]
 * [$v1, $v2, [$v3]] => [$v1, $v2, $v3]
 * @used-by dfp_iia()
 * @param mixed[] $a
 * @return mixed|mixed[]
 */
function dfa_unpack(array $a) {return !($c = count($a)) ? null : (1 === $c ? $a[0] : dfa_flatten($a));}

/**
 * 2016-09-02
 * @see dfa_deep_unset()
 * @uses array_flip() корректно работает с пустыми массивами.
 * 2019-11-15
 * Previously, it was used as:
 * 		$this->_data = dfa_unset($this->_data, 'can_use_default_value', 'can_use_website_value', 'scope');
 * I replaced it with:
 * 		$this->unsetData(['can_use_default_value', 'can_use_website_value', 'scope']);
 * @used-by \Df\Config\Backend::value()
 * @used-by \Df\Config\Backend\ArrayT::processI()
 * @used-by \Df\Framework\Request::clean()
 * @used-by \Dfe\Markdown\Observer\Catalog\ControllerAction::processPost()
 * @param array(string => mixed) $a
 * @param string[] ...$keys
 * @return array(string => mixed)
 */
function dfa_unset(array $a, ...$keys) {return array_diff_key($a, array_flip(df_args($keys)));}

/**
 * Алгоритм взят отсюда:
 * http://php.net/manual/function.array-unshift.php#106570
 * @param array(string => mixed) $a
 * @param string $k
 * @param mixed $v
 */
function dfa_unshift_assoc(&$a, $k, $v)  {
	$a = array_reverse($a, $preserve_keys = true);
	$a[$k] = $v;
	$a = array_reverse($a, $preserve_keys = true);
}

/**
 * 2016-09-05
 * @used-by df_cfg_save()
 * @used-by df_url_bp()
 * @used-by ikf_pw_country()
 * @used-by \Df\Directory\FE\Currency::v()
 * @used-by \Df\GingerPaymentsBase\Block\Info::prepareCommon()
 * @used-by \Df\GingerPaymentsBase\Choice::title()
 * @used-by \Df\GingerPaymentsBase\Method::optionE()
 * @used-by \Df\GingerPaymentsBase\Method::optionI()
 * @used-by \Df\Payment\BankCardNetworkDetector::label()
 * @used-by \Df\PaypalClone\W\Event::statusT()
 * @used-by \Dfe\AllPay\W\Reader::te2i()
 * @used-by \Dfe\IPay88\W\Event::optionTitle()
 * @used-by \Dfe\Moip\Facade\Card::brand()
 * @used-by \Dfe\Moip\Facade\Card::logoId()
 * @used-by \Dfe\Moip\Facade\Card::numberLength()
 * @used-by \Dfe\Paymill\Facade\Card::brand()
 * @used-by \Dfe\PostFinance\W\Event::optionTitle()
 * @used-by \Dfe\Robokassa\W\Event::optionTitle()
 * @used-by \Dfe\Square\Facade\Card::brand()
 * @used-by \Dfe\Stripe\FE\Currency::getComment()
 * @used-by \Dfe\Stripe\Init\Action::redirectUrl()
 * @used-by \Dfe\Vantiv\Facade\Card::brandCodeE()
 * @used-by \Frugue\Store\Block\Switcher::map()
 * @used-by \Frugue\Store\Block\Switcher::name()
 * @param int|string $v
 * @param array(int|string => mixed) $map
 * @return int|string|mixed
 */
function dftr($v, array $map) {return dfa($map, $v, $v);}