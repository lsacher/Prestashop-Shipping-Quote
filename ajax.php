<?php
/*
*  NOTICE OF LICENSE
*
*  This source file is subject to the Academic Free License (AFL 3.0)
*  that is bundled with this package in the file LICENSE.txt.
*  It is also available through the world-wide-web at this URL:
*  http://opensource.org/licenses/afl-3.0.php
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of 
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.   
*/

	include_once('../../config/config.inc.php');
	include_once('../../init.php');
	include_once('../../modules/shippingquote/shippingquote.php');

	$context = Context::getContext();

	if (!Tools::getValue('ajax') || Tools::getValue('token') != sha1(_COOKIE_KEY_.'shippingquote'))
    die;

  if (Tools::getValue('override_cart') == 'yes') 
    $sq_cart_changed = 0; 
  else {
    // Check to see if cart has changed since the request for a quote.
    $sql = "SELECT `sq_cart_changed`
  		FROM `"._DB_PREFIX_."cart` c 
  		WHERE c.`id_cart` = ".(int)Tools::getValue('id_cart')."
        AND c.`id_customer` = ".(int)Tools::getValue('id_customer')."
        AND c.`id_cart` NOT IN (SELECT `id_cart` FROM "._DB_PREFIX_."orders o)
  	ORDER BY `date_add` DESC";
    $sq_cart_changed = (int)Db::getInstance()->getValue($sql);
  }

  if ($sq_cart_changed == 1)
    echo json_encode(array('sq_cart_changed' => true, 
                           'message1' => Configuration::get('SHIPPING_QUOTE_MESSAGE_1'),
                           'message2' => Configuration::get('SHIPPING_QUOTE_MESSAGE_2')
                           ));
  else
    echo json_encode(array('sq_cart_changed' => $sq_cart_changed));

  // Set shipping costs for this customer and cart
  $sql = "
     UPDATE `"._DB_PREFIX_."cart` c
        SET c.`sq_shipping_quote` = ".($sq_cart_changed ? 0.00 : round(floatval(Tools::getValue('value')), 2)).",
            c.`sq_cart_changed` = $sq_cart_changed,
            c.`date_upd` = NOW()
      WHERE c.`id_cart` = ".(int)Tools::getValue('id_cart')."
      	AND c.`id_customer` = ".(int)Tools::getValue('id_customer')."
      LIMIT 1"; 
  if (!Db::getInstance()->execute($sql))
    return false;
?>
