<?php
namespace Df\Sentry;
use \Exception as E;
use Df\Core\Exception as DFE;
final class Client {
	/**
	 * 2020-06-27
	 * @used-by df_sentry_m()
	 * @param int $projectId
	 * @param string $keyPublic
	 * @param string $keyPrivate
	 */
	function __construct($projectId, $keyPublic, $keyPrivate) {
		$this->_projectId = $projectId;
		$this->_keyPublic = $keyPublic;
		$this->_keyPrivate = $keyPrivate;
		$this->_pending_events = [];
		$this->_user = null;
		$this->breadcrumbs = new Breadcrumbs;
		$this->context = new Context;
		$this->curl_path = 'curl';
		$this->error_types = null;
		$this->extra_data = [];
		$this->logger = 'php';
		$this->severity_map = null;
		$this->site = $this->_server_variable('SERVER_NAME');
		$this->tags = [];
		$this->timeout = 2;
		$this->trace = true;
		$this->trust_x_forwarded_proto = null;
		// 2020-06-27 This prefix will be removed from all filesystem paths in logs
		$this->prefix = $this->_convertPath(BP . DS);
		$this->sdk = ['name' => 'mage2.pro', 'version' => df_core_version()];
		$this->serializer = new Serializer;
		$this->transaction = new TransactionStack;
		if (!df_is_cli() && isset($_SERVER['PATH_INFO'])) {
			$this->transaction->push($_SERVER['PATH_INFO']);
		}
		$this->registerDefaultBreadcrumbHandlers();
		register_shutdown_function(function() {
			if (!defined('RAVEN_CLIENT_END_REACHED')) {
				define('RAVEN_CLIENT_END_REACHED', true);
			}
			foreach ($this->_pending_events as $data) {
				$this->send($data);
			}
			$this->_pending_events = [];
			if ($this->store_errors_for_bulk_send) {
				//in case an error occurs after this is called, on shutdown, send any new errors.
				$this->store_errors_for_bulk_send = !defined('RAVEN_CLIENT_END_REACHED');
			}
		});
	}

	/**
	 * 2020-06-27
	 * @used-by df_sentry()
	 * @param string $m
	 * @param array $d
	 */
	function captureMessage($m, array $d) {$this->capture([
		'message' => $m, 'sentry.interfaces.Message' => ['formatted' => $m, 'message' => $m, 'params' => []]
	] + $d);}

	/**
	 * 2020-06-27
	 * @used-by __construct()
	 * @used-by setAppPath()
	 * @param string $v
	 * @return false|string
	 */
	private function _convertPath($v) {
		$r = @realpath($v); /** @var string $r */
		if ($r === false) {
			$r = $v;
		}
		// 2016-12-22
		// https://github.com/getsentry/sentry-php/issues/392
		// «The method Client::_convertPath() works incorrectly on Windows»
		if (
			(substr($r, 0, 1) === '/' || (1 < strlen($r) && ':' === $r[1]))
			&& DIRECTORY_SEPARATOR !== substr($r, -1, 1)
		) {
			$r .= DIRECTORY_SEPARATOR;
		}
		return $r;
	}

	/**
	 * 2020-06-27
	 * @used-by df_sentry_m() 
	 * @param string $v
	 */
	function setAppPath($v) {$this->app_path = $this->_convertPath($v);}

	/**
	 * 2020-06-28
	 * @used-by captureLastError()
	 * @used-by df_sentry()
	 * @used-by \Df\Sentry\ErrorHandler::handleException()
	 * @param E|DFE $e
	 * @param array $data
	 */
	function captureException(E $e, $data=null, $logger=null, $vars=null) {
		if ($data === null) {
			$data = [];
		}
		$eOriginal = $e; /** @var E $eOriginal */
		do {
			$isDFE = $e instanceof DFE;
			$exc_data = [
				'type' => $isDFE ? $e->sentryType() : get_class($e)
				,'value' => $this->serializer->serialize($isDFE ? $e->messageSentry() : $e->getMessage())
			];
			/**'exception'
			 * Exception::getTrace doesn't store the point at where the exception
			 * was thrown, so we have to stuff it in ourselves. Ugh.
			 */
			$trace = $e->getTrace();
			/**
			 * 2016-12-22 Убираем @see \Magento\Framework\App\ErrorHandler
			 * 2016-12-23 И @see Breadcrumbs\ErrorHandler тоже убираем.
			 */
			$needAddFakeFrame = !self::needSkipFrame($trace[0]); /** @var bool $needAddFaceFrame */
			while (self::needSkipFrame($trace[0])) {
				array_shift($trace);
			}
			if ($needAddFakeFrame) {
				$frame_where_exception_thrown = array(
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				);
				array_unshift($trace, $frame_where_exception_thrown);
			}
			$exc_data['stacktrace'] = array(
				'frames' => Stacktrace::get_stack_info(
					$trace, $this->trace, $vars, self::MESSAGE_LIMIT, [$this->prefix],
					$this->app_path
				),
			);
			$exceptions[] = $exc_data;
		} while ($e = $e->getPrevious());
		$data['exception'] = array('values' => array_reverse($exceptions));
		if ($logger !== null) {
			$data['logger'] = $logger;
		}
		if (empty($data['level'])) {
			if (method_exists($eOriginal, 'getSeverity')) {
				$data['level'] = $this->translateSeverity($eOriginal->getSeverity());
			}
			else {
				$data['level'] = self::ERROR;
			}
		}
		return $this->capture($data, $trace, $vars);
	}


	/**
	 * Capture the most recent error (obtained with ``error_get_last``).
	 */
	function captureLastError()
	{
		if (null === $error = error_get_last()) {
			return;
		}

		$e = new \ErrorException(
			@$error['message'], 0, @$error['type'],
			@$error['file'], @$error['line']
		);

		return $this->captureException($e);
	}

	/**
	 * 2020-06-28
	 * @used-by __construct()
	 */
	private function registerDefaultBreadcrumbHandlers() {$this->_prevErrorHandler = set_error_handler(
		/**
		 * 2017-07-10
		 * @param int $code
		 * @param string $m
		 * @param string $file
		 * @param int $line
		 * @param array $context
		 * @return bool|mixed
		 */
		function($code, $m, $file = '', $line = 0, $context=[]) {
			// 2017-07-10
			// «Magento 2.1 php7.1 will not be supported due to mcrypt deprecation»
			// https://github.com/magento/magento2/issues/5880
			// [PHP 7.1] How to fix the «Function mcrypt_module_open() is deprecated» bug?
			// https://mage2.pro/t/2392
			if (E_DEPRECATED !== $code || !df_contains($m, 'mcrypt') && !df_contains($m, 'mdecrypt')) {
				$this->breadcrumbs->record([
					'category' => 'error_reporting',
					'message' => $m,
					'level' => $this->translateSeverity($code),
					'data' => ['code' => $code, 'line' => $line, 'file' => $file]
				]);
			}
			return !$this->_prevErrorHandler ? false : call_user_func(
				$this->_prevErrorHandler, $code, $m, $file, $line, $context
			);
		}, E_ALL
	);}

	private function get_http_data()
	{
		$headers = [];

		foreach ($_SERVER as $key => $value) {
			if (0 === strpos($key, 'HTTP_')) {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
			} elseif (in_array($key, array('CONTENT_TYPE', 'CONTENT_LENGTH')) && $value !== '') {
				$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))))] = $value;
			}
		}

		$result = array(
			'method' => $this->_server_variable('REQUEST_METHOD'),
			'url' => $this->get_current_url(),
			'query_string' => $this->_server_variable('QUERY_STRING'),
		);

		// dont set this as an empty array as PHP will treat it as a numeric array
		// instead of a mapping which goes against the defined Sentry spec
		if (!empty($post = df_request_o()->getPost()->toArray())) {
			$result['data'] = $post;
		}
		// 2017-01-03 Мне пока куки не нужны.
		if (false) {
			if (!empty($_COOKIE)) {
				$result['cookies'] = $_COOKIE;
			}
		}
		// 2017-01-03
		// Отсюда куки тоже нужно удалить, потому что Sentry пытается их отсюда взять.
		unset($headers['Cookie']);
		if (!empty($headers)) {
			$result['headers'] = $headers;
		}

		return array(
			'request' => $result,
		);
	}

	/**
	 * 2020-06-27
	 * @used-by capture()
	 * @return array|array[]|null[]
	 */
	private function get_user_data() {
		$user = $this->context->user;
		if ($user === null) {
			if (!function_exists('session_id') || !session_id()) {
				return [];
			}
			/**
			 * 2017-09-27
			 * Previously, it was the following code here:
			 *	if (!empty($_SESSION)) {
			 *		$user['data'] = $_SESSION;
			 *	}
			 * I have removed it because of «Direct use of $_SESSION Superglobal detected»:
			 * https://github.com/mage2pro/core/issues/31
			 * I think, I do not need to log the session at all.
			 */
			$user = ['id' => session_id()];
		}
		return ['user' => $user];
	}

	/**
	 * 2017-04-08
	 * @used-by captureException()
	 * @used-by captureMessage()
	 * @param mixed $data
	 * @param mixed[] $trace [optional]
	 * @param mixed $vars [optional]
	 * @return mixed
	 */
	private function capture($data, array $trace = [], $vars = null) {
		$data += [
			'culprit' => $this->transaction->peek()
			,'event_id' => $this->uuid4()
			,'extra' => []
			,'level' => self::ERROR
			,'message' => substr($data['message'], 0, self::MESSAGE_LIMIT)
			,'platform' => 'php'
			,'project' => $this->_projectID
			,'sdk' => $this->sdk
			,'site' => $this->site
			,'tags' => $this->tags
			,'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
		];
		if (!df_is_cli()) {
			$data += $this->get_http_data();
		}
		$data += $this->get_user_data();
		/**
		 * 2017-01-10
		 * 1) $this->tags — это теги, которые были заданы в конструкторе:
		 * @see \Df\Sentry\Client::__construct()
		 * Они имеют наинизший приоритет.
		 * 2) Намеренно использую здесь + вместо @see df_extend(),
		 * потому что массив tags должен быть одномерным (и поэтому для него + достаточно),
		 * а массив extra хоть и может быть многомерен, однако вряд ли для нас имеет смысл
		 * слияние его элементов на внутренних уровнях вложенности.
		 */
		$data['tags'] += $this->context->tags + $this->tags;
		/** @var array(string => mixed) $extra */
		$extra = $data['extra'] + $this->context->extra + $this->extra_data;
		// 2017-01-03
		// Этот полный JSON в конце массива может быть обрублен в интерфейсе Sentry
		// (и, соответственно, так же обрублен при просмотре события в формате JSON
		// по ссылке в шапке экрана события в Sentry),
		// однако всё равно удобно видеть данные в JSON, пусть даже в обрубленном виде.
		$data['extra'] = Extra::adjust($extra) + ['_json' => df_json_encode($extra)];
		$data = df_clean($data);
		if (!$this->breadcrumbs->is_empty()) {
			$data['breadcrumbs'] = $this->breadcrumbs->fetch();
		}
		if ($trace && !isset($data['stacktrace']) && !isset($data['exception'])) {
			$data['stacktrace'] = array(
				'frames' => Stacktrace::get_stack_info(
					$trace, $this->trace, $vars, self::MESSAGE_LIMIT, [$this->prefix],
					$this->app_path
				),
			);
		}
		$this->sanitize($data);
		if (!$this->store_errors_for_bulk_send) {
			$this->send($data);
		}
		else {
			$this->_pending_events[] = $data;
		}
		return $data['event_id'];
	}

	/**
	 * 2020-06-27
	 * @used-by send()
	 * @param array(string => mixed) $data
	 * @return string
	 */
	private function encode(&$data) {
		$r = df_json_encode($data);
		if (function_exists('gzcompress')) {
			$r = gzcompress($r);
		}
		$r = base64_encode($r);
		return $r;
	}

	/**
	 * 2020-06-27
	 * @used-by __construct()
	 * @used-by capture()
	 * @param array $data
	 */
	private function send(&$data) {
		$domain = 1000 > $this->_projectId ? 'log.mage2.pro' : 'sentry.io'; /** @var string $domain */ // 2018-08-25
		$this->send_http("https://$domain/api/{$this->_projectId}/store/", $this->encode($data), [
			'Content-Type' => 'application/octet-stream'
			,'User-Agent' => $this->getUserAgent()
			,'X-Sentry-Auth' => 'Sentry ' . df_csv_pretty(df_map_k(df_clean([
				'sentry_timestamp' => sprintf('%F', microtime(true))
				,'sentry_client' => $this->getUserAgent()
				,'sentry_version' => self::PROTOCOL
				,'sentry_key' => $this->_keyPublic
				,'sentry_secret' => $this->_keyPrivate
			]), function($k, $v) {return "$k=$v";}))
		]);
	}

	/**
	 * 2020-06-27
	 * @used-by send()
	 * @param string $url
	 * @param array $data
	 * @param array $headers
	 */
	private function send_http($url, $data, $headers = []) {
		$c = curl_init($url); /** @var resource $c */
		try {
			curl_setopt($c, CURLOPT_HTTPHEADER, df_map_k(
				// 2020-06-28 The `Expect` headers prevents the `100-continue` response form server (Fixes GH-216)
				function($k, $v) {return df_kv([$k => $v]);}, $headers + ['Expect' => ''])
			);
			curl_setopt($c, CURLOPT_POST, 1);
			curl_setopt($c, CURLOPT_POSTFIELDS, $data);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			curl_setopt_array($c, $this->get_curl_options());
			curl_exec($c);
			$errno = curl_errno($c);
			// CURLE_SSL_CACERT || CURLE_SSL_CACERT_BADFILE
			if ($errno == 60 || $errno == 77) {
				curl_setopt($c, CURLOPT_CAINFO, df_module_file($this, 'cacert.pem'));
				curl_exec($c);
			}
		}
		finally {
			curl_close($c);
		}
	}

	/**
	 * 2020-06-27
	 * @used-by send_http()
	 * @return array(string => mixed)
	 */
	private function get_curl_options() {
		$r = [
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
			,CURLOPT_SSL_VERIFYHOST => 2
			,CURLOPT_SSL_VERIFYPEER => true
			,CURLOPT_USERAGENT => $this->getUserAgent()
			,CURLOPT_VERBOSE => false
		];
		/** @var int $t */
		if (!defined('CURLOPT_TIMEOUT_MS')) {
			// fall back to the lower-precision timeout.
			$t = max(1, ceil($this->timeout));
			$r += [CURLOPT_CONNECTTIMEOUT => $t, CURLOPT_TIMEOUT => $t];
		}
		else {
			// MS is available in curl >= 7.16.2
			$t = max(1, ceil(1000 * $this->timeout));
			// some versions of PHP 5.3 don't have this defined correctly
			if (!defined('CURLOPT_CONNECTTIMEOUT_MS')) {
				//see http://stackoverflow.com/questions/9062798/php-curl-timeout-is-not-working/9063006#9063006
				define('CURLOPT_CONNECTTIMEOUT_MS', 156);
			}
			$r += [CURLOPT_CONNECTTIMEOUT_MS => $t, CURLOPT_TIMEOUT_MS => $t];
		}
		return $r;
	}

	/**
	 * Generate an uuid4 value
	 *
	 * @return string
	 */
	private function uuid4()
	{
		$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		return str_replace('-', '', $uuid);
	}

	/**
	 * Return the URL for the current request
	 *
	 * @return string|null
	 */
	private function get_current_url()
	{
		// When running from commandline the REQUEST_URI is missing.
		if (!isset($_SERVER['REQUEST_URI'])) {
			return null;
		}

		// HTTP_HOST is a client-supplied header that is optional in HTTP 1.0
		$host = (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
			: (!empty($_SERVER['LOCAL_ADDR'])  ? $_SERVER['LOCAL_ADDR']
			: (!empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '')));

		$httpS = $this->isHttps() ? 's' : '';
		return "http{$httpS}://{$host}{$_SERVER['REQUEST_URI']}";
	}

	/**
	 * Was the current request made over https?
	 *
	 * @return bool
	 */
	private function isHttps()
	{
		if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
			return true;
		}

		if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
			return true;
		}

		if (!empty($this->trust_x_forwarded_proto) &&
			!empty($_SERVER['X-FORWARDED-PROTO']) &&
			$_SERVER['X-FORWARDED-PROTO'] === 'https') {
			return true;
		}

		return false;
	}

	/**
	 * Get the value of a key from $_SERVER
	 *
	 * @param string $key       Key whose value you wish to obtain
	 * @return string           Key's value
	 */
	private function _server_variable($key)
	{
		if (isset($_SERVER[$key])) {
			return $_SERVER[$key];
		}

		return '';
	}

	/**
	 * @used-by captureException()
	 * @used-by \Df\Sentry\Breadcrumbs\ErrorHandler::install()
	 * @param string $severity  PHP E_$x error constant
	 * @return string           Sentry log level group
	 */
	function translateSeverity($severity) {
		if (is_array($this->severity_map) && isset($this->severity_map[$severity])) {
			return $this->severity_map[$severity];
		}
		switch ($severity) {
			case E_COMPILE_ERROR:      return Client::ERROR;
			case E_COMPILE_WARNING:    return Client::WARN;
			case E_CORE_ERROR:         return Client::ERROR;
			case E_CORE_WARNING:       return Client::WARN;
			case E_ERROR:              return Client::ERROR;
			case E_NOTICE:             return Client::INFO;
			case E_PARSE:              return Client::ERROR;
			case E_RECOVERABLE_ERROR:  return Client::ERROR;
			case E_STRICT:             return Client::INFO;
			case E_USER_ERROR:         return Client::ERROR;
			case E_USER_NOTICE:        return Client::INFO;
			case E_USER_WARNING:       return Client::WARN;
			case E_WARNING:            return Client::WARN;
		}
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			switch ($severity) {
			case E_DEPRECATED:         return Client::WARN;
			case E_USER_DEPRECATED:    return Client::WARN;
		  }
		}
		return Client::ERROR;
	}

	/**
	 * Provide a map of PHP Error constants to Sentry logging groups to use instead
	 * of the defaults in translateSeverity()
	 *
	 * @param array $map
	 */
	function registerSeverityMap($map)
	{
		$this->severity_map = $map;
	}

	/**
	 * @used-by df_sentry_m()
	 * @used-by \Dfe\CheckoutCom\Controller\Index\Index::webhook()
	 * @used-by \Df\Payment\W\Handler::log()
	 * @param array(string => mixed) $data
	 * @param bool $merge [optional]
	 */
	function user_context(array $d, $merge = true) {
		$this->context->user = $d + (!$merge || !$this->context->user ? [] : $this->context->user);
	}

	/**
	 * 2017-01-10 К сожалению, использовать «/» в имени тега нельзя.
	 * 2017-02-09
	 * Иероглифы использовать тоже нельзя:
	 * попытка использовать тег «歐付寶 O'Pay (allPay)» приводит к сбою
	 * «Discarded invalid value for parameter 'tags'».
	 * @used-by df_sentry_tags()
	 * @uses df_translit_url()
	 * @param array(string => string) $a
	 */
	final function tags_context(array $a) {
		$this->context->tags = dfak_transform($a, 'df_translit_url') + $this->context->tags;
	}

	/**
	 * 2017-01-10
	 * 2019-05-20
	 * I intentionally use array_merge_recursive() instead of @see df_extend()
	 * because I want values to be merged for a duplicate key.
	 * I is needed for @see df_sentry_extra_f()
	 * @used-by df_sentry_extra_f()
	 * @param array(string => mixed) $a
	 */
	final function extra_context(array $a) {
		$this->context->extra = array_merge_recursive($this->context->extra, $a);
	}

	/**
	 * 2016-12-23
	 * @used-by get_curl_options()
	 * @return string
	 */
	private function getUserAgent() {return 'mage2.pro/' . df_core_version();}

	/**
	 * 2016-12-23
	 * @used-by captureException()
	 * @param array(string => string|int|array) $frame
	 * @return bool
	 */
	private static function needSkipFrame(array $frame) {return
		\Magento\Framework\App\ErrorHandler::class === dfa($frame, 'class')
		|| df_ends_with(df_path_n(dfa($frame, 'file')), 'Sentry/Breadcrumbs/ErrorHandler.php')
	;}

	/**
	 * 2020-06-27
	 * @used-by capture()
	 * @param $data
	 */
	private function sanitize(&$data) {
		if (!empty($data['request'])) {
			$data['request'] = $this->serializer->serialize($data['request']);
		}
		if (!empty($data['user'])) {
			$data['user'] = $this->serializer->serialize($data['user'], 3);
		}
		if (!empty($data['extra'])) {
			$data['extra'] = $this->serializer->serialize($data['extra']);
		}
		if (!empty($data['tags'])) {
			foreach ($data['tags'] as $key => $value) {
				$data['tags'][$key] = @(string)$value;
			}
		}
		if (!empty($data['contexts'])) {
			$data['contexts'] = $this->serializer->serialize($data['contexts'], 5);
		}
	}

	/**
	 * 2020-06-27
	 * @used-by \Df\Sentry\Breadcrumbs\ErrorHandler::install()
	 * @var Breadcrumbs
	 */
	public $breadcrumbs;
	public $context;
	public $extra_data;
	public $severity_map;
	public $store_errors_for_bulk_send = false;
	/**
	 * 2020-06-27
	 * @used-by capture()
	 * @used-by captureException()
	 * @used-by setAppPath()
	 * @var string|null
	 */
	private $app_path;
	private $error_types;
	/**
	 * 2020-06-28
	 * @used-by __construct()
	 * @var string
	 */
	private $_keyPrivate;
	/**
	 * 2020-06-28
	 * @used-by __construct()
	 * @used-by send()
	 * @var string
	 */
	private $_keyPublic;
	/**
	 * 2020-06-28
	 * @used-by __construct()
	 * @used-by capture()
	 * @var string[]
	 */
	private $_pending_events;
	/**
	 * 2020-06-27
	 * @used-by __construct()
	 * @used-by capture()
	 * @used-by captureException()
	 * @var string
	 */
	private $prefix;
	/**
	 * 2020-06-28
	 * @used-by registerDefaultBreadcrumbHandlers()
	 * @var callable
	 */
	private $_prevErrorHandler;
	/**
	 * 2020-06-28
	 * @used-by __construct()
	 * @used-by capture()
	 * @used-by send()
	 * @var int
	 */
	private $_projectId;
	private $reprSerializer;
	private $serializer;
	const DEBUG = 'debug';
	const ERROR = 'error';
	const FATAL = 'fatal';
	const INFO = 'info';
	/**
	 * 2020-06-28
	 * @used-by capture()
	 * @used-by captureException()
	 * @used-by \Df\Sentry\Stacktrace::get_default_context()
	 * @used-by \Df\Sentry\Stacktrace::get_frame_context()
	 * @used-by \Df\Sentry\Stacktrace::get_stack_info()
	 */
	const MESSAGE_LIMIT = 1024;
	const PROTOCOL = '6';
	const WARN = 'warning';
	const WARNING = 'warning';
}