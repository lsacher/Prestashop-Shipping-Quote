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

/*
*  SHIPPING QUOTE
*  Allows customer requested shipping quote to be manually set in the back office.
*  Designed for PrestaShop™ 1.6
*  Created by Larry Sacherich 2014-02-02
*  Based on variableshipping by Gennady Kovshenin for Prestashop 1.5.6.2 
*  
*  SEE: README.md for details.
*
*  FEATURES:
*  - Assign a default Payment Module, Payment Status, and Employee Profile
*  - Works with standard 5-page checkout or one-page checkout
*  - Emails both customer and designated employee with quote request.
*  - Includes any customer comments/messages in the emails.
*  - Employee creates order with quote and order is sent to customer 
*  - Checks for Terms of Service (if required) before allowing customer to request a quote.
*  - Hides Payment options from customer until needed. (Stops customer from paying without quote)
*  - Follows closely to Manual Order.
*/

// Avoid direct access to the file
if (!defined('_PS_VERSION_'))
	exit;

class shippingquote extends CarrierModule 
{
	public  $id_carrier;   
	private $_html = '';
	private $_postErrors = array();
	private $_moduleName = 'shippingquote';

	/*
	** Construct Method
	*/
	public function __construct()
	{
		$this->name = 'shippingquote';
		$this->tab = 'shipping_logistics';
		$this->version = '1.2';
		$this->author = 'Larry Sacherich';
		parent::__construct();
		$this->displayName = $this->l('Shipping Quote');
		$this->description = $this->l('Allows customer requested shipping quote to be manually set in the back office.');
	}

	/*
	** Install / Uninstall Methods
	*/
	public function install()
	{
    // Set carrier defaults
    // shipping_method ( Default=0, Weight=1, Price=2, Free=3 )     
		$carrierConfig = array(
			0 => array('name' => 'Shipping Quote',
				'id_tax_rules_group' => 0,
				'active' => true,
				'deleted' => 0,
				'is_free' => false,
				'shipping_handling' => false,
		    'range_behavior' => 0,
				'delay' => array(
            'en' => 'Varies by destination', 
            'fr' => 'Varie selon la destination', 
            Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')) => 'Varies by destination'),
				'id_zone' => 1,
				'is_module' => true,
				'shipping_external' => true,
        'shipping_method' => 1,
				'external_module_name' => 'shippingquote',
				'need_range' => true
			),
		);
    
    $id_carrier1 = $this->installExternalCarrier($carrierConfig[0]);
    if ($id_carrier1 === false) 
      return false;
      
    $admin_dir = str_replace(_PS_ROOT_DIR_ . '/', '', _PS_ADMIN_DIR_) . '/';
     
		if (!Configuration::updateValue('SHIPPING_QUOTE_CARRIER_ID', (int)$id_carrier1)
     || !Configuration::updateValue('SHIPPING_QUOTE_ADMIN_DIR', $admin_dir) 
     || !Configuration::updateValue('SHIPPING_QUOTE_PAYMENT', '')
     || !Configuration::updateValue('SHIPPING_QUOTE_STATUS', '')          
     || !Configuration::updateValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE', _PS_ADMIN_PROFILE_)    
     || !Configuration::updateValue('SHIPPING_QUOTE_MESSAGE_1', $this->l('The customer has made changes to his cart. Please verify all items, quantities, and shipping addresses.'))
     || !Configuration::updateValue('SHIPPING_QUOTE_MESSAGE_2', $this->l('When ready, click this checkbox and the Update button to override.'))
    ) return false;

		if (!parent::install() 
        || !$this->registerHook('actionCartSave')            
        || !$this->registerHook('displayBackOfficeHeader')
        || !$this->registerHook('actionCarrierUpdate')
        || !$this->registerHook('displayCarrierList')
      ) return false; 
       
    // Add SQL cart columns                               
    $sql = 'ALTER TABLE `'._DB_PREFIX_.'cart`
      ADD `sq_shipping_quote` DECIMAL(17, 2) NOT NULL DEFAULT \'0.00\',
      ADD `sq_cart_changed` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\';';
      
    if (!Db::getInstance()->execute($sql))
      return false;

		return true;
	} 

	public function uninstall()
	{
		if (!parent::uninstall() 
        || !$this->unregisterHook('actionCartSave')
        || !$this->unregisterHook('displayBackOfficeHeader')
        || !$this->unregisterHook('actionCarrierUpdate')
        || !$this->unregisterHook('displayCarrierList')
       ) return false;

		// Delete external carrier
		$Carrier1 = new Carrier((int)(Configuration::get('SHIPPING_QUOTE_CARRIER_ID')));

		// If external carrier is default set another one as default
		if (Configuration::get('PS_CARRIER_DEFAULT') == (int)($Carrier1->id))
		{
			global $cookie;
			$carriersD = Carrier::getCarriers($cookie->id_lang, true, false, false, NULL, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
			foreach($carriersD as $carrierD)
				if ($carrierD['active'] AND !$carrierD['deleted'] AND ($carrierD['name'] != $this->_config['name']))
					Configuration::updateValue('PS_CARRIER_DEFAULT', $carrierD['id_carrier']);
		}
    
		// Then delete carrier
		$Carrier1->deleted = 1;
		if (!$Carrier1->update())
      return false;

    // Delete configuration variables
    Configuration::deleteByName('SHIPPING_QUOTE_CARRIER_ID');       
    Configuration::deleteByName('SHIPPING_QUOTE_ADMIN_DIR');   
    Configuration::deleteByName('SHIPPING_QUOTE_PAYMENT');
    Configuration::deleteByName('SHIPPING_QUOTE_STATUS');           
    Configuration::deleteByName('SHIPPING_QUOTE_EMPLOYEE_PROFILE');    
    Configuration::deleteByName('SHIPPING_QUOTE_MESSAGE_1');
    Configuration::deleteByName('SHIPPING_QUOTE_MESSAGE_2');

    // Drop SQL cart columns
    $sql = 'ALTER TABLE `'._DB_PREFIX_.'cart` 
      DROP COLUMN `sq_shipping_quote`,
      DROP COLUMN `sq_cart_changed`;';
      
    if (!Db::getInstance()->execute($sql))
      return false;
               
		return true;
	}

	/*
	** Install External Carrier Function
	*/
	public static function installExternalCarrier($config)
	{ 
		$carrier = new Carrier();
		$carrier->name = $config['name'];
		$carrier->id_tax_rules_group = $config['id_tax_rules_group'];
		$carrier->active = $config['active'];
		$carrier->deleted = $config['deleted'];
		$carrier->delay = $config['delay'];
		$carrier->shipping_handling = $config['shipping_handling'];
		$carrier->range_behavior = $config['range_behavior'];
		$carrier->is_module = $config['is_module'];
		$carrier->shipping_external = $config['shipping_external'];
		$carrier->external_module_name = $config['external_module_name'];
		$carrier->need_range = $config['need_range'];
		$carrier->id_zone = $config['id_zone'];
		$carrier->shipping_method = $config['shipping_method'];    
    $carrier->is_free = $config['is_free'];           

		$languages = Language::getLanguages(true);
		foreach ($languages as $language)
		{
			if ($language['iso_code'] == 'fr')
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == 'en')
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
			if ($language['iso_code'] == Language::getIsoById(Configuration::get('PS_LANG_DEFAULT')))
				$carrier->delay[(int)$language['id_lang']] = $config['delay'][$language['iso_code']];
		}

		if ($carrier->add())
		{
			$groups = Group::getGroups(true);
			foreach ($groups as $group)
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_group', array('id_carrier' => (int)($carrier->id), 'id_group' => (int)($group['id_group'])), 'INSERT');

			$rangePrice = new RangePrice();
			$rangePrice->id_carrier = $carrier->id;
			$rangePrice->delimiter1 = '0';
			$rangePrice->delimiter2 = '10000';
			$rangePrice->add();

			$rangeWeight = new RangeWeight();
			$rangeWeight->id_carrier = $carrier->id;
			$rangeWeight->delimiter1 = '0';
			$rangeWeight->delimiter2 = '10000'; 
			$rangeWeight->add();

			$zones = Zone::getZones(true);
			foreach ($zones as $zone)
			{
				Db::getInstance()->autoExecute(_DB_PREFIX_.'carrier_zone', array('id_carrier' => (int)($carrier->id), 'id_zone' => (int)($zone['id_zone'])), 'INSERT');
				Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => (int)($rangePrice->id), 'id_range_weight' => NULL, 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
				Db::getInstance()->autoExecuteWithNullValues(_DB_PREFIX_.'delivery', array('id_carrier' => (int)($carrier->id), 'id_range_price' => NULL, 'id_range_weight' => (int)($rangeWeight->id), 'id_zone' => (int)($zone['id_zone']), 'price' => '0'), 'INSERT');
			}

			// Copy logo
			if (!copy(dirname(__FILE__).'/shippingquote.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'))
				return false;

			// Return ID carrier
			return (int)($carrier->id);
		}
		return false;
	}

	/*
	** Hook Functions
	*/
  public function hookactionCartSave($params)
	{
    // Skip if employee is updating quote
		$context = Context::getContext();
		if (isset($context->employee->id))
			return true; 
      
    // Cart creation and update hook
    // Reset shipping costs for this customer and cart
    $sql = '
       UPDATE `'._DB_PREFIX_.'cart` c
          SET c.`sq_shipping_quote` = 0.00,
              c.`sq_cart_changed` = 1,
              c.`date_upd` = NOW()
        WHERE c.`id_cart` = '.(int)$context->cart->id.'
        LIMIT 1';
        
    if (!Db::getInstance()->execute($sql))
        return false;
        
    return true;
	}
  
	public function hookActionCarrierUpdate($params) 
  {
    // Update carrier ID
		Configuration::updateValue('SHIPPING_QUOTE_CARRIER_ID', (int)$params['carrier']->id);
  }

	public function hookDisplayCarrierList($params)
	{                                      
		if ($this->id_carrier != (int)(Configuration::get('SHIPPING_QUOTE_CARRIER_ID')))
		  return false; 

    // Shipping quote request button               
    include_once(_PS_ROOT_DIR_ . '/config/settings.inc.php'); 
		$sql = 'SELECT *
  		 FROM `'._DB_PREFIX_.'cart` c 
  		WHERE c.`id_cart` = '.(int)$params['cart']->id.'
        AND c.`id_customer` = '.(int)$params['address']->id_customer.'
        AND c.`id_cart` NOT IN (SELECT `id_cart` FROM '._DB_PREFIX_.'orders o)
			ORDER BY `date_add` DESC';
    $result = Db::getInstance()->getRow($sql);

    // Check for previous quote
		if (isset($result) && $result['sq_shipping_quote'] == 0)
      return $this->display(__FILE__, 'views/templates/hook/request.tpl');
	}

	public function hookDisplayBackOfficeHeader() 
  {
    // Set previous values
  	if (Configuration::get('SHIPPING_QUOTE_PAYMENT') != '' )
      $_POST['payment_module_name'] = Configuration::get('SHIPPING_QUOTE_PAYMENT');
    
  	if (Configuration::get('SHIPPING_QUOTE_STATUS') != '' )
      $_POST['id_order_state'] = Configuration::get('SHIPPING_QUOTE_STATUS');
    
    // Pass variables and language items
		$out  = '<script type="text/javascript" src="'.$this->_path.'script.js'.'"></script>'; 
		$out .= '<script>var shippingquote_carrier_id = '.Configuration::get('SHIPPING_QUOTE_CARRIER_ID').';</script>';
		$out .= '<script>var shippingquote_token = "'.sha1(_COOKIE_KEY_.'shippingquote').'";</script>';
		$out .= '<script>var shippingquote_ajax_url = "'.$this->_path.'ajax.php'.'";</script>'; 
		$out .= '<script>var shipping_price = "'.$this->l('Shipping Price ').'";</script>';         
		$out .= '<script>var update_shipping_price = "'.$this->l(' Update Shipping Price ').'";</script>';  // Update Shipping Button       
		return $out;
  }
  
  /*
  ** Other Functions
  */
	public function getOrderShippingCost($params, $shipping_cost)
	{
		return $this->getOrderShippingCostExternal($params);
  }

	public function getOrderShippingCostExternal($params)
	{
		if ($this->id_carrier != (int)(Configuration::get('SHIPPING_QUOTE_CARRIER_ID')))
		  return false;

    include_once(_PS_ROOT_DIR_ . '/config/settings.inc.php');
		$sql = 'SELECT *
  		 FROM `'._DB_PREFIX_.'cart` c 
      WHERE c.`id_cart` = '.(int)$params->id.'
        AND c.`id_customer` = '.(int)$params->id_customer.'
        AND c.`id_cart` NOT IN (SELECT `id_cart` FROM '._DB_PREFIX_.'orders o)
      ORDER BY `date_add` DESC';
    $result = Db::getInstance()->getRow($sql);
    return isset($result) ? round(floatval($result['sq_shipping_quote']), 2) : 0.00;
	}
  
  // Configure shipping payment module and payment status 
	public function getContent()
	{
		$this->_html .= '<h2>' . $this->l('Shipping Quote').'</h2>';
		if (!empty($_POST) AND Tools::isSubmit('submitSave'))
		{
			$this->_postValidation();
		}
		$this->_displayForm();
		return $this->_html;
	}

  // Display the configuration form
  private function _displayForm()
	{ 
		$this->_html .= '<fieldset>
		<legend><img src="'.$this->_path.'logo.gif" alt="" /> '.$this->l('Module Status').'</legend>';

    foreach ($this->_postErrors AS $err)
    	$this->_html .= '<div class="alert error"><img src="'._PS_IMG_.'admin/forbbiden.gif" alt="forbbiden" />&nbsp;'.$err.'</div>';

  	if (!Configuration::get('SHIPPING_QUOTE_PAYMENT') 
     || !Configuration::get('SHIPPING_QUOTE_STATUS')
     || !empty($this->_postErrors))    
			$this->_html .= '<img src="'._PS_IMG_.'admin/warning.gif" /><strong>'.$this->l('Shipping Quote is Not Configured!').'</strong>';      
		else
			$this->_html .= '<img src="'._PS_IMG_.'admin/module_install.png" /><strong>'.$this->l('Shipping Quote is configured and online.').'</strong>';

		$this->_html .= '</fieldset><div class="clear">&nbsp;</div>
			<style>
				#tabList { clear: left; }
				.tabItem { display: block; background: #FFFFF0; border: 1px solid #CCCCCC; padding: 10px; padding-top: 20px; }
			</style>
			<div id="tabList">
				<div class="tabItem">
					<form action="index.php?tab='.Tools::getValue('tab').'&configure='.Tools::getValue('configure').'&token='.Tools::getValue('token').'&tab_module='.Tools::getValue('tab_module').'&module_name='.Tools::getValue('module_name').'&id_tab=1&section=general" method="post" class="form" id="configForm">

					<fieldset style="border: 0px;">
						<h4>'.$this->l('General configuration').' :</h4>
            <p>'.$this->l('Here you may set the default payment method and status. You can always override these defaults in the Back Office when assigning the shipping cost.').'</p>
						<label>'.$this->l('Payment Module').' : </label>
						<div class="margin-form">
            <select style="width:180px;" name="SHIPPING_QUOTE_PAYMENT"  id="SHIPPING_QUOTE_PAYMENT">
            <option value="">- No default -</option>';
            
    // Get list of payment modules with display names
		$payment_modules = array();
		foreach (PaymentModule::getInstalledPaymentModules() as $p_module)
			$payment_modules[] = Module::getInstanceById((int)$p_module['id_module']);
      
    foreach ($payment_modules as $module)
      $this->_html .= '<option value="'.$module->name.'"'.(Configuration::get('SHIPPING_QUOTE_PAYMENT') == $module->name ? ' selected="selected"' : '').'> '.$module->displayName.' </option>';

    $this->_html .= '</select>
            </div>
						<label>'.$this->l('Payment Status').' : </label>
						<div class="margin-form">
            <select style="width:220px;" name="SHIPPING_QUOTE_STATUS"  id="SHIPPING_QUOTE_STATUS">
            <option value="">- No default -</option>';
            
    // Get list of payment statuses
    $statuses = array();
    $statuses = OrderState::getOrderStates((int)$this->context->language->id);
    foreach ($statuses as $status)
      $this->_html .= '<option value="'.$status['id_order_state'].'"'.(Configuration::get('SHIPPING_QUOTE_STATUS') == $status['id_order_state'] ? ' selected="selected"' : '').'> '.$status['name'].' </option>';

    $this->_html .= '</select>
            </div>
						<label>'.$this->l('Employee Profile').' : </label>
						<div class="margin-form">
            <select style="width:220px;" name="SHIPPING_QUOTE_EMPLOYEE_PROFILE"  id="SHIPPING_QUOTE_EMPLOYEE_PROFILE">';
            
    // Get list of employee profiles
    $profiles = array();
    $profiles = Profile::getProfiles((int)$this->context->language->id);
    foreach ($profiles as $profile)
      $this->_html .= '<option value="'.$profile['id_profile'].'"'.(Configuration::get('SHIPPING_QUOTE_EMPLOYEE_PROFILE') == $profile['id_profile'] ? ' selected="selected"' : '').'> '.$profile['name'].' </option>';

    $this->_html .= '</select>
            </div>
					</div>
					<br /><br />
				</fieldset>				
				<div class="margin-form"><input class="button" name="submitSave" type="submit"></div>
			</form>
		</div></div>';
	}

	private function _postValidation() 
	{ 
		if (!Configuration::updateValue('SHIPPING_QUOTE_PAYMENT', Tools::getValue('SHIPPING_QUOTE_PAYMENT'))
     || !Configuration::updateValue('SHIPPING_QUOTE_STATUS', Tools::getValue('SHIPPING_QUOTE_STATUS')) 
     || !Configuration::updateValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE', (int)Tools::getValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE'))) 
      $this->_postErrors[] = $this->displayError($this->l('Settings did not update!'));
  
		// Check configuration values
		if (Tools::getValue('SHIPPING_QUOTE_PAYMENT') == '')
    {
//       Configuration::updateValue('SHIPPING_QUOTE_PAYMENT', '');
 			$this->_postErrors[] = $this->l('Payment Module was not selected.');
    }
      
		if (Tools::getValue('SHIPPING_QUOTE_STATUS') == '')
    {
//       Configuration::updateValue('SHIPPING_QUOTE_STATUS', '');
 			$this->_postErrors[] = $this->l('Payment Status was not selected.');
    }
    
		if (Tools::getValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE') == '')
  		$this->_postErrors[] = $this->l('Employee Profile was not selected.');
      
    else if (!Employee::getEmployeesByProfile(Tools::getValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE'), true))
       $this->_postErrors[] = $this->l('No employees were found for the specified Employee Profile.');

    if (empty($this->_postErrors))
			$this->_html .= $this->displayConfirmation($this->l('Settings were updated.')); 
		else
      $this->_html .= $this->displayError($this->l('Settings Failed!'));  
	}

//   private function _postProcess()    
// 	{
// 		// Saving new configurations
// 		if (!Configuration::updateValue('SHIPPING_QUOTE_PAYMENT', Tools::getValue('SHIPPING_QUOTE_PAYMENT'))
//      || !Configuration::updateValue('SHIPPING_QUOTE_STATUS', Tools::getValue('SHIPPING_QUOTE_STATUS')) 
//      || !Configuration::updateValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE', Tools::getValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE')) 
//      || !Employee::getEmployeesByProfile(Tools::getValue('SHIPPING_QUOTE_EMPLOYEE_PROFILE'), true))
// 			$this->_html .= $this->displayErrors($this->l('Settings failed'));
// 		else
// 			$this->_html .= $this->displayConfirmation($this->l('Settings updated')); 
// 	}
  
}
