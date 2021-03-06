<?php
use Df\Framework\W\Result as wResult;
use Magento\Framework\App\Action\Action as Controller;
use Magento\Framework\App\ActionFlag as AF;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Response\HttpInterface as IHttpResponse;
use Magento\Framework\App\Response\RedirectInterface as IResponseRedirect;
use Magento\Framework\App\ResponseInterface as IResponse;
use Magento\Framework\Authorization as Auth;
use Magento\Framework\AuthorizationInterface as IAuth;
use Magento\Framework\Controller\ResultInterface as IResult;
use Magento\Store\App\Response\Redirect as ResponseRedirect;

/**
 * 2020-02-24 https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#5xx_Server_Error
 * @used-by ikf_endpoint()	inkifi.com
 * @used-by \Dfe\CheckoutCom\Handler::p()
 * @used-by \Dfe\Sift\Controller\Index\Index::execute()
 * @used-by \Dfe\TwoCheckout\Handler::p()
 */
function df_500() {return df_response_code(500);}

/**
 * 2019-10-23
 * I use the `_f` suffix to distinguish @see \Magento\Framework\Authorization
 * and @see \Magento\Backend\Model\Auth
 * @used-by \PPCs\Core\Plugin\Catalog\Controller\Adminhtml\Product::aroundDispatch()
 * @return IAuth|Auth
 */
function df_auth_f() {return df_o(IAuth::class);}

/**
 * 2017-11-17
 * @used-by \Df\Payment\W\Action::execute()
 * @return bool
 */
function df_is_redirect() {return df_response()->isRedirect();}

/**
 * 2019-11-21
 * @used-by \RWCandy\Captcha\Observer\CustomerAccountCreatePost::execute()
 */
function df_no_dispatch() {
	$af = df_o(AF::class); /** @var AF $af */
	$af->set('', Controller::FLAG_NO_DISPATCH, true);
}

/**
 * 2017-11-16
 * I implemented it by analogy with @see \Magento\Framework\App\Action\Action::_redirect():
 *		protected function _redirect($path, $arguments = []) {
 *			$this->_redirect->redirect($this->getResponse(), $path, $arguments);
 *			return $this->getResponse();
 *		}
 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/App/Action/Action.php#L159-L170
 * @used-by df_redirect_to_checkout()
 * @used-by df_redirect_to_home()
 * @used-by df_redirect_to_payment()
 * @used-by df_redirect_to_success()
 * @param string $path
 * @param array(string => mixed) $p [optional]
 * @return IResponseRedirect|ResponseRedirect
 */
function df_redirect($path, $p = []) {
	$r = df_response_redirect(); /** @var IResponseRedirect|ResponseRedirect $r */
	/**
	 * 2017-11-17
	 * @uses \Magento\Framework\App\Response\Http::setRedirect():
	 *		public function setRedirect($url, $code = 302) {
	 *			$this
	 *				->setHeader('Location', $url, true)
	 *				->setHttpResponseCode($code)
	 *			;
	 *			return $this;
	 *		}
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/HTTP/PhpEnvironment/Response.php#L113-L122
	 *
	 * We can then check whether a redirect is set using
	 * @see \Magento\Framework\HTTP\PhpEnvironment\Response::isRedirect():
	 *		public function isRedirect() {
	 *			return $this->isRedirect;
	 *		}
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/HTTP/PhpEnvironment/Response.php#L162-L170
	 *
	 * It does work because of the
	 * @see \Magento\Framework\HTTP\PhpEnvironment\Response::setHttpResponseCode() implementation:
	 * 		 $this->isRedirect = (300 <= $code && 307 >= $code) ? true : false;
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/HTTP/PhpEnvironment/Response.php#L124-L137
	 */
	$r->redirect(df_response(), $path, $p);
	return $r;
}

/**
 * 2019-11-21
 * @used-by \RWCandy\Captcha\Observer\CustomerAccountCreatePost::execute()
 */
function df_redirect_back() {df_response()->setRedirect(df_response_redirect()->getRefererUrl());}

/**
 * 2020-05-27
 * @used-by \BlushMe\Checkout\Observer\ControllerActionPredispatch\CheckoutCartIndex::execute()
 */
function df_redirect_to_checkout() {df_redirect('checkout');}

/**
 * 2020-10-20
 * @used-by \BlushMe\Checkout\Observer\ControllerActionPredispatch\CheckoutCartIndex::execute()
 */
function df_redirect_to_home() {df_redirect('/');}

/**
 * 2017-11-17
 * 2018-12-17
 * I have added the @uses df_order_last() condition
 * because otherwise the Yandex.Kassa payment module does not return a proper response to PSP.
 * 2019-07-04
 * The previous (2018-12-17) strange commit: https://github.com/mage2pro/core/commit/3eb6c1d2
 * Currtently, I do not understand it.
 * And it brokes the proper handling of PSP errors:
 * https://mail.google.com/mail/u/0/#inbox/FMfcgxwChSGRJmWZrRBLWpcnXKbQZvjw
 * So I have reverted the code for `df_redirect_to_payment`,
 * but preserved it for @see df_redirect_to_success()
 * @used-by \Df\Payment\CustomerReturn::execute()
 * @used-by \Df\Payment\W\Strategy\ConfirmPending::_handle()
 */
function df_redirect_to_payment() {df_redirect('checkout', ['_fragment' => 'payment']);}

/**
 * 2017-11-17
 * 2018-12-17
 * I have added the @uses df_order_last() condition
 * because otherwise the Yandex.Kassa payment module does not return a proper response to PSP.
 * @used-by \Df\Payment\CustomerReturn::execute()
 * @used-by \Df\Payment\W\Strategy\ConfirmPending::_handle()
 */
function df_redirect_to_success() {df_order_last(false) ? df_redirect('checkout/onepage/success') : null;}

/**
 * 2017-02-01
 * Добавил параметр $r.
 * IResult и wResult не родственны IResponse и HttpResponse.
 * 2017-11-17
 * You can read here more about the IResult/wResult and IResponse/HttpResponse difference:
 * 1) @see \Magento\Framework\App\Http::launch():
 *		# TODO: Temporary solution until all controllers return ResultInterface (MAGETWO-28359)
 *		if ($result instanceof ResultInterface) {
 *			$this->registry->register('use_page_cache_plugin', true, true);
 *			$result->renderResult($this->_response);
 *		} elseif ($result instanceof HttpInterface) {
 *			$this->_response = $result;
 *		} else {
 *			throw new \InvalidArgumentException('Invalid return type');
 *		}
 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/App/Http.php#L122-L149
 * 2) "[Question] To ResultInterface or not ResultInterface": https://github.com/magento/magento2/issues/1355
 * https://github.com/magento/magento2/issues/1355
 * @used-by df_is_redirect()
 * @used-by df_redirect()
 * @used-by df_redirect_back()
 * @used-by df_response_ar()
 * @used-by df_response_code()
 * @used-by df_response_content_type()
 * @used-by \Df\Framework\App\Action\Image::execute()
 * @param IResult|wResult|IResponse|HttpResponse|null $r [optional]
 * @return IResponse|IHttpResponse|HttpResponse|IResult|wResult
 */
function df_response($r = null) {return $r ?: df_o(IResponse::class);}

/**
 * 2017-02-01
 * @used-by df_response_headers()
 * @used-by df_response_sign()
 * @param IResult|wResult|IHttpResponse|HttpResponse|null|array(string => string) $a1 [optional]
 * @param IResult|wResult|IHttpResponse|HttpResponse|null|array(string => string) $a2 [optional]
 * @return array(array(string => string), IResult|wResult|IHttpResponse|HttpResponse)
 */
function df_response_ar($a1 = null, $a2 = null) {return
	is_array($a1) ? [$a1, df_response($a2)] : (
		is_array($a2) ? [$a2, df_response($a1)] : (
			is_object($a1) ? [[], $a1] : (
				is_object($a2) ? [[], $a2] :
					[[], df_response()]
			)
		)
	)
;}

/**
 * 2015-12-09
 * @used-by \Df\Framework\App\Action\Image::execute()
 * @used-by \Df\GoogleFont\Controller\Index\Index::execute()
 */
function df_response_cache_max() {df_response_headers([
	'Cache-Control' => 'max-age=315360000'
	,'Expires' => 'Thu, 31 Dec 2037 23:55:55 GMT'
	# 2015-12-09
	# Если не указывать заголовок Pragma, то будет добавлено Pragma: no-cache.
	# Так и не разобрался, кто его добавляет. Может, PHP или веб-сервер.
	# Простое df_response()->clearHeader('pragma') не позволяет от него избавиться.
	# http://stackoverflow.com/questions/11992946
	,'Pragma' => 'cache'
]);}

/**
 * 2015-11-29
 * @used-by df_500()
 * @param int $v
 */
function df_response_code($v) {df_response()->setHttpResponseCode($v);}

/**
 * I pass the 3rd argument ($replace = true) to @uses \Magento\Framework\HTTP\PhpEnvironment\Response::setHeader()
 * because the `Content-Type` headed can be already set.
 * @used-by \Df\Framework\App\Action\Image::execute()
 * @used-by \Df\Framework\W\Result\Text::render()
 * @used-by \Dfe\Qiwi\Result::render()
 * @used-by \Dfe\YandexKassa\Result::render() 
 * @used-by \Justuno\M2\W\Result\Js::render()
 * @param string $contentType
 * @param IResult|wResult|IHttpResponse|HttpResponse|null $r [optional]
 */
function df_response_content_type($contentType, $r = null) {df_response($r)->setHeader('Content-Type', $contentType, true);}

/**
 * 2015-11-29
 * 2017-02-01
 * @used-by df_response_cache_max()
 * @used-by df_response_sign()
 * @used-by \Df\Framework\App\Action\Image::execute()
 * @param IResult|wResult|IHttpResponse|HttpResponse|null|array(string => string) $a1 [optional]
 * @param IResult|wResult|IHttpResponse|HttpResponse|null|array(string => string) $a2 [optional]
 * @return IResult|wResult|IHttpResponse|HttpResponse
 */
function df_response_headers($a1 = null, $a2 = null) {
	/** @var array(string => string) $a */ /** @var IResult|wResult|IHttpResponse|HttpResponse $r */
	# 2020-03-02
	# The square bracket syntax for array destructuring assignment (`[…] = […]`) requires PHP ≥ 7.1:
	# https://github.com/mage2pro/core/issues/96#issuecomment-593392100
	# We should support PHP 7.0.
	list($a, $r) = df_response_ar($a1, $a2);
	array_walk($a, function($v, $k) use($r) {$r->setHeader($k, $v, true);});
	return $r;
}

/**
 * 2019-11-21
 * @used-by df_redirect()
 * @used-by df_redirect_back()
 * @return IResponseRedirect|ResponseRedirect
 */
function df_response_redirect() {return df_o(IResponseRedirect::class);}

/**
 * 2017-02-01
 * @used-by \Df\Core\Controller\Index\Index::execute()
 * @used-by \Df\Payment\W\Action::execute()
 * @param IResult|wResult|IHttpResponse|HttpResponse|null|array(string => string) $a1 [optional]
 * @param IResult|wResult|IHttpResponse|HttpResponse|null|array(string => string) $a2 [optional]
 * @return IResult|wResult|IHttpResponse|HttpResponse
 */
function df_response_sign($a1 = null, $a2 = null) {
	/** @var array(string => string) $a */ /** @var IResult|wResult|IHttpResponse|HttpResponse $r */
	# 2020-03-02
	# The square bracket syntax for array destructuring assignment (`[…] = […]`) requires PHP ≥ 7.1:
	# https://github.com/mage2pro/core/issues/96#issuecomment-593392100
	# We should support PHP 7.0.
	list($a, $r) = df_response_ar($a1, $a2);
	return df_response_headers($r, df_headers($a));
}