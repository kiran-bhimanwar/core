<?php
# 2016-08-31  Портировал из Российской сборки Magento
namespace Df\Xml;
final class G extends \Df\Core\O {
	/**
	 * 2016-08-31  
	 * @used-by p()
	 * @return string
	 */
	private function _p() {
		/** @var string $result */
		/**
		 * Метод ядра Magento CE @see \Magento\Framework\Simplexml\Element::asNiceXml()
		 * не сохраняет в документе XML блоки CDATA,
		 * а вместо этого заменяет недопустимые для XML символы их допустимыми кодами,
		 * например: & => &amp;
		 *
		 * Также @see \Magento\Framework\Simplexml\Element::asNiceXml()
		 * не добавляет к документу заголовок XML: его надо добавить вручную.
		 *
		 * 2015-02-27
		 * Для конвертации объекта класса @see SimpleXMLElement в строку
		 * надо использовать именно метод @uses SimpleXMLElement::asXML(),
		 * а не @see SimpleXMLElement::__toString() или оператор (string)$this.
		 *
		 * @see SimpleXMLElement::__toString() и (string)$this
		 * возвращают непустую строку только для концевых узлов (листьев дерева XML).
		 * Пример:
		 *	<?xml version='1.0' encoding='utf-8'?>
		 *		<menu>
		 *			<product>
		 *				<cms>
		 *					<class>aaa</class>
		 *					<weight>1</weight>
		 *				</cms>
		 *				<test>
		 *					<class>bbb</class>
		 *					<weight>2</weight>
		 *				</test>
		 *			</product>
		 *		</menu>
		 * Здесь для $e1 = $xml->{'product'}->{'cms'}->{'class'}
		 * мы можем использовать $e1->__toString() и (string)$e1.
		 * http://3v4l.org/rAq3F
		 * Однако для $e2 = $xml->{'product'}->{'cms'}
		 * мы не можем использовать $e2->__toString() и (string)$e2,
		 * потому что узел «cms» не является концевым узлом (листом дерева XML).
		 * http://3v4l.org/Pkj37
		 * Более того, метод @see SimpleXMLElement::__toString()
		 * отсутствует в PHP версий 5.2.17 и ниже:
		 * http://3v4l.org/Wiia2#v500
		 */
		/** @var string $header */
		$header = $this[self::P__SKIP_HEADER] ? '' : df_xml_header($this[self::P__1251] ? 'Windows-1251' : 'UTF-8');
		/** @var X $x */
		$x = df_xml_parse(df_cc_n($header, $this[self::P__DOC_TYPE], sprintf('<%s/>', $this[self::$P__TAG])));
		$x->addAttributes($this[self::P__ATTRIBUTES]);
		$x->importArray($this[self::$P__CONTENTS], $this[self::P__WRAP_IN_CDATA]);
		# Убеждаемся, что asXML вернуло строку, а не false.
		$result = df_assert_nef($this[self::P__SKIP_HEADER] ? $x->asXMLPart() : df_cc_n($header, $x->asNiceXml()));
		# Символ 0xB (вертикальная табуляция) допустим в UTF-8, но недопустим в XML:
		# http://stackoverflow.com/a/10095901
		$result = str_replace("\x0B", "&#x0B;", $result);
		if ($this[self::P__1251]) {
			$result = df_1251_to($result);
		}
		if ($this[self::P__REMOVE_LINE_BREAKS]) {
			$result = df_t()->removeLineBreaks($result);
		}
		if ($this[self::P__DECODE_ENTITIES]) {
			$result = html_entity_decode($result, ENT_NOQUOTES, 'UTF-8');
		}
		return $result;
	}

	/** @var string */
	private static $P__CONTENTS = 'contents';
	/** @var string */
	private static $P__TAG = 'tag';

	/**
	 * 2016-08-31
	 * @used-by df_xml_g()
	 * @param string $tag
	 * @param array(string => mixed) $contents
	 * @param array(string => mixed) $p [optional]
	 * @return string
	 */
	static function p($tag, array $contents, array $p = []) {return
		(new static([self::$P__CONTENTS => $contents, self::$P__TAG => $tag] + $p))->_p();
	}

	const P__1251 = '1251';
	/**
	 * @used-by \Dfe\Vantiv\API\Client::_construct()
	 * @used-by \Dfe\Vantiv\Test\CaseT\Charge::t04()
	 * @used-by \Dfe\YandexKassa\Result::__toString()
	 */
	const P__ATTRIBUTES = 'attributes';
	const P__DECODE_ENTITIES = 'need_decode_entities';
	const P__DOC_TYPE = 'doc_type';
	const P__REMOVE_LINE_BREAKS = 'need_remove_line_breaks';
	const P__SKIP_HEADER = 'skip_header';
	const P__WRAP_IN_CDATA = 'wrap_in_cdata';
}