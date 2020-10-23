<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

if (!Module::getInstanceByName('feedercustom')->active)
	exit;

$list_divisor = ',';

// Utilities
function getFeatureValue($product_id, $feature_id)
{
	$value = null;

	$features = Product::getFeaturesStatic($product_id);
	
	$target_feature = array_pop(
		array_filter(
			$features, 
			function($item) use ($feature_id) { 
				return $item["id_feature"] === $feature_id;
			}
		)
	);

	if ($target_feature) 
	{
		$target_feature_value = $target_feature["id_feature_value"];
		$target_value_lang = array_pop(FeatureValue::getFeatureValueLang($target_feature_value));

		if ($target_value_lang) 
		{
			$value = $target_value_lang["value"];
		}
	}

	return $value;
}

// Get data
$orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
$orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));
$products = Product::getProducts((int)$context->language->id, 0, 0, $orderBy, $orderWay, false, true);
$currency = new Currency((int)$context->currency->id);
$metas = Meta::getMetaByPage('index', (int)$context->language->id);
$shop_uri = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__;

// Send feed
header("Content-Type:text/xml; charset=utf-8");
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rss version="2.0">
	<channel>
		<title><![CDATA[<?php echo Configuration::get('PS_SHOP_NAME') ?>]]></title>
		<description><![CDATA[<?php echo $metas['description'] ?>]]></description>
		<link><?php echo $shop_uri ?></link>
		<generator>PrestaShop</generator>
		<webMaster><?php echo Configuration::get('PS_SHOP_EMAIL') ?></webMaster>
		<language><?php echo $context->language->iso_code; ?></language>
		<image>
			<title><![CDATA[<?php echo Configuration::get('PS_SHOP_NAME') ?>]]></title>
			<url><?php echo $link->getMediaLink(_PS_IMG_.Configuration::get('PS_LOGO')) ?></url>
			<link><?php echo $shop_uri ?></link>
		</image>
<?php
	foreach ($products AS $product)
	{
		$pid = $product['id_product'];
		$name = $product['name'];
		$price = Product::getPriceStatic($pid, true, null, 2);
		$description = $product['description'];
		$stripped_short_description = strip_tags($product['description_short']);
		$product_link = str_replace('&amp;', '&', htmlspecialchars($link->getproductLink($pid, $product['link_rewrite'], Category::getLinkRewrite((int)($product['id_category_default']), $cookie->id_lang))));
		$is_available = $product['available_now'];
		$language = $context->language->iso_code;
		$images = Image::getImages((int)($cookie->id_lang), $pid);

		$categories_ids = Product::getProductCategories($pid);
		$categories = Category::getCategoryInformations($categories_ids);

		$quantity = (int)StockAvailable::getQuantityAvailableByProduct($pid);
		$condition = $product['condition'];
		$tax_name = $product['tax_name'];
		
		$brand = getFeatureValue($pid, '16') ?: "-";

		// Get a list of all the images
		$all_images = [];

		foreach ($images as $i)
		{
			$il = $link->getImageLink($product['link_rewrite'], $i['id_image']);

			if ($il) array_push($all_images, $il);
		}
		
		// Get categories
		$all_categories_ids = [];

		foreach ($categories as $c)
		{
			$cid = $c['id_category'];

			if ($cid != null) 
			{
				array_push($all_categories_ids, $cid);
			}
		}


		echo "\t\t<item>\n";
		echo "\t\t\t<guid><![CDATA[".$pid."]]></guid>\n";
		echo "\t\t\t<title><![CDATA[".$name."]]></title>\n";
		echo "\t\t\t<description><![CDATA[".$description."]]></description>\n";
		echo "\t\t\t<shortdescription><![CDATA[".$stripped_short_description."]]></shortdescription>\n";
		echo "\t\t\t<link><![CDATA[".$product_link."]]></link>\n";

		echo "\t\t\t<language><![CDATA[".$language."]]></language>\n";
		echo "\t\t\t<price><![CDATA[".$price."]]></price>\n";
		echo "\t\t\t<availability><![CDATA[".$is_available."]]></availability>\n";
		echo "\t\t\t<image><![CDATA[".$all_images[0]."]]></image>\n";
		echo "\t\t\t<images><![CDATA[".implode($list_divisor, $all_images)."]]></images>\n";
		echo "\t\t\t<quantity><![CDATA[".$quantity."]]></quantity>\n";
		echo "\t\t\t<condition><![CDATA[".$condition."]]></condition>\n";
		echo "\t\t\t<brand><![CDATA[".$brand."]]></brand>\n";
		echo "\t\t\t<categoriesids><![CDATA[".implode($list_divisor, $all_categories_ids)."]]></categoriesids>\n";
		echo "\t\t\t<taxname><![CDATA[".$tax_name."]]></taxname>\n";
		echo "\t\t</item>\n";
	}
?>
	</channel>
</rss>