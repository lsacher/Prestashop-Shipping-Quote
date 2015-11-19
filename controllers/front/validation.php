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

class ShippingquoteValidationModuleFrontController extends ModuleFrontController
{
	public $ssl = true;

	public function postProcess()
	{
		if ($this->context->cart->id_customer == 0 
     || $this->context->cart->id_address_delivery == 0 
     || $this->context->cart->id_address_invoice == 0 
     || !$this->module->active)
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		require_once _PS_ROOT_DIR_.'/config/config.inc.php';
		require_once _PS_ROOT_DIR_.'/init.php';
		require_once _PS_ROOT_DIR_.'/classes/Message.php';

		$customer = new Customer($this->context->cart->id_customer);    
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

    // Reset shipping cost and cart_changed for this customer and cart
    $sql = "
       UPDATE `"._DB_PREFIX_."cart` c
          SET c.`sq_shipping_quote` = 0.00,
              c.`sq_cart_changed` = 0,
              c.`date_upd` = NOW()
        WHERE c.`id_cart` = ".(int)$this->context->cart->id."                           
        	AND c.`id_customer` = ".(int)$this->context->cart->id_customer."
        LIMIT 1";
    if (!Db::getInstance()->execute($sql))
        return false;

    // Set the variables for the template:
    $id_lang = (int)$this->context->cart->id_lang;
		$iso     = Language::getIsoById($id_lang);  
    $id_cart = (int)$this->context->cart->id;    
    $configuration = Configuration::getMultiple(array(
			'PS_SHOP_EMAIL',
			'PS_SHOP_NAME'
		));
    $admin_template    = 'quote_admin';
    $customer_template = 'quote_customer';
    $errorMsg = 'Shipping Quote Error: Mailing template was not found.';
    $admin_dir = __PS_BASE_URI__ . Configuration::get('SHIPPING_QUOTE_ADMIN_DIR');
    
    // Get customer's message / comments
    $message_array = Message::getMessagesByCartId((int)$this->context->cart->id);

    // Uses only the first active employee in profile
    $id_profile = (int)Configuration::get('SHIPPING_QUOTE_EMPLOYEE_PROFILE');
    $employee_array = Employee::getEmployeesByProfile($id_profile, true);  // true = $active_only
    list($id_employee, , , $lastname, $firstname, $employee_email) = array_values($employee_array[0]);
    $employee_name = $firstname.' '.$lastname;

    $cart_token = Tools::getAdminToken('AdminCarts'.intval(Tab::getIdFromClassName('AdminCarts')).intval($id_employee));
    $cart_url = Tools::getProtocol(true).htmlentities($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').$admin_dir.'index.php?controller=AdminCarts&id_cart='.$id_cart.'&viewcart&token='.$cart_token;

    // Check to see if HTML templates exist.
		if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$admin_template.'.html') &&
		  	file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$customer_template.'.html'))
    {
      /*
      **  Sent to the administrator
      */ 
      Mail::Send(
				(int)$this->context->cart->id_lang, 
        $admin_template, 
				Mail::l('Shipping Quote Needed!', $id_lang),
				array (
     			'{shop_name}' => Configuration::get('PS_SHOP_NAME'),
     			'{firstname}' => $this->context->customer->firstname,
     			'{lastname}'  => $this->context->customer->lastname,
     			'{email}'     => $this->context->customer->email,
     			'{cart_id}'   => (int)$this->context->cart->id,
				  '{cart_url}'  => $cart_url,
				  '{message}'   => (isset($message_array[0]['message']) ? $message_array[0]['message'] : NULL)
        ),
        $employee_email,
        $employee_name,
				$this->context->customer->email,
				$this->context->customer->firstname.' '.$this->context->customer->lastname,
				null,
				null,
				dirname(__FILE__).'/mails/'
        );
     
      /*
      **  Sent to the customer
      */      
      Mail::Send(
				(int)$this->context->cart->id_lang, 
        $customer_template, 
				Mail::l('Shipping Quote Auto Reply', $id_lang),
				array (
     			'{shop_name}' => Configuration::get('PS_SHOP_NAME'),
     			'{firstname}' => $this->context->customer->firstname,
     			'{lastname}'  => $this->context->customer->lastname,
     			'{email}'     => $this->context->customer->email,
     			'{cart_id}'   => (int)$this->context->cart->id,
				  '{message}'   => (isset($message_array[0]['message']) ? $message_array[0]['message'] : NULL)          
        ),
				$this->context->customer->email,
				$this->context->customer->firstname.' '.$this->context->customer->lastname,
   			$configuration['PS_SHOP_EMAIL'],
   			$configuration['PS_SHOP_NAME'],
				null,
				null,
				dirname(__FILE__).'/mails/'
        );
    } else 
      Logger::addLog($errorMsg, 3);
              
		$customer = new Customer($this->context->cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');

		if (Tools::getValue('confirm'))
		{
			$customer = new Customer((int)$this->context->cart->id_customer);
			$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
			$this->module->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);
			Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder);
		}
	} 

	/*
	** @see FrontController::initContent()
	*/
	public function initContent()
	{
		$this->display_column_left = false;
		parent::initContent();

		$this->context->smarty->assign(array(
			'total' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('validation.tpl');
	}
}
