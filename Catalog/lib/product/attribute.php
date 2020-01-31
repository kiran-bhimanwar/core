<?php
use Closure as F;
use Magento\Catalog\Model\Product as P;
use Magento\Catalog\Model\Product\Attribute\Repository as R;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as A;
use Magento\Framework\Exception\NoSuchEntityException as NSE;

/**
 * 2019-08-21
 * @used-by df_product_att()
 * @return R
 */
function df_product_atts_r() {return df_o(R::class);}

/**
 * 2019-08-21                   
 * @used-by df_product_att_options()
 * @param string $c 
 * @param F|bool|mixed $onE [optional]
 * @return A|null
 * @throws NSE
 */
function df_product_att($c, $onE = true) {return df_try(
	function() use($c) {return df_product_atts_r()->get($c);}, $onE
);}

/**      
 * 2019-10-22
 * @used-by df_product_att_options_m()
 * @used-by \Dfe\Color\Image::opts()
 * @param string $c
 * @return array(array(string => int|string))
 */
function df_product_att_options($c) {return dfcf(function($c) {return
	df_product_att($c)->getSource()->getAllOptions(false)
;}, [$c]);}

/**
 * 2019-10-22
 * @used-by \Dfe\Color\Image::opts()
 * @used-by \PPCs\Core\Plugin\Iksanika\Stockmanage\Block\Adminhtml\Product\Grid::aroundAddColumn()
 * @param string $c
 * @return array(array(string => int|string))
 */
function df_product_att_options_m($c) {return df_options_to_map(df_product_att_options($c));}

/**              
 * 2019-09-22
 * @param string $sku
 * @return int
 */
function df_product_sku2id($sku) {return (int)df_product_res()->getIdBySku($sku);}

/**
 * 2020-01-31
 * @see \Magento\Catalog\Model\Product::getAttributeText() 
 * @used-by \Df\Catalog\Test\product\attribute::df_product_att_val_s()
 * @param P $p
 * @param string $c
 * @param F|bool|mixed $onE [optional]
 * @return string|null
 * @throws NSE
 */
function df_product_att_val_s(P $p, $c, $onE = true) {return df_try(
	function() use($p, $c) {
		$a = df_product_att($c); /** @var A $a */
		return $a->getSource()->getOptionText($p[$c]);
	}, $onE
);}