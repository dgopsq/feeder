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

function render_categories($cats, $l) {
	foreach ($cats AS $cat)
	{
			$cat_id = $cat["id_category"];
			$cat_name = $cat["name"];
			$cat_link = $l->getCategoryLink($cat_id);
			$cat_children = $cat["children"];

			echo "\t\t<item>\n";
			echo "\t\t\t<id><![CDATA[".$cat_id."]]></id>\n";
			echo "\t\t\t<name><![CDATA[".$cat_name."]]></name>\n";
			echo "\t\t\t<link><![CDATA[".$cat_link."]]></link>\n";
			echo "\t\t</item>\n";

			if ($cat_children)
				render_categories($cat_children, $l);
	}
}

// Get data
$id_lang = (int)$context->language->id;
$categories = Category::getNestedCategories(null, $id_lang, true);
$currency = new Currency((int)$context->currency->id);
$metas = Meta::getMetaByPage('index', $id_lang);
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
render_categories($categories, $link); 
?>
	</channel>
</rss>