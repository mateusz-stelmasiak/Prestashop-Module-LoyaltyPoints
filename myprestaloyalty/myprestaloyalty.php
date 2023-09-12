<?php
/*
* PrestaShop module created by VEKIA, a guy from official PrestaShop community ;-)
*
* @author    VEKIA https://www.prestashop.com/forums/user/132608-vekia/
* @copyright 2010-2020 VEKIA
* @license   This program is not free software and you can't resell and redistribute it
*
* CONTACT WITH DEVELOPER
* support@mypresta.eu
*/

if (!defined('_PS_VERSION_'))
    exit;


class myprestaloyalty extends Module
{
    protected $html = '';

    public function __construct()
    {
        $this->name = 'myprestaloyalty';
        $this->tab = 'pricing_promotion';
        $this->version = '3.0.7';
        $this->author = 'mypresta.eu';
        $this->need_instance = 0;
        $this->mypresta_link = 'https://mypresta.eu/modules/advertising-and-marketing/loyalty-points.html';

        $this->controllers = array('default');

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Customer loyalty and rewards');
        $this->description = $this->l('Provide a loyalty program to your customers.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete all loyalty points and customer history?');
        $this->checkforupdates(0, 0);

    }

    public function inconsistency($ret = false)
    {
        return;
    }

    public function psversion($part = 1)
    {
        $version = _PS_VERSION_;
        $exp = $explode = explode(".", $version);
        if ($part == 1) {
            return $exp[1];
        }
        if ($part == 2) {
            return $exp[2];
        }
        if ($part == 3) {
            return $exp[3];
        }
    }

    public function displayAdvert()
    {
        return $this->display(__file__, 'views/templates/admin/advert.tpl');
    }

    private function instanceDefaultStates()
    {
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');

        /* Recover default loyalty status save at module installation */
        $this->loyaltyStateDefault = new LoyaltyStateModule(LoyaltyStateModule::getDefaultId());
        $this->loyaltyStateValidation = new LoyaltyStateModule(LoyaltyStateModule::getValidationId());
        $this->loyaltyStateCancel = new LoyaltyStateModule(LoyaltyStateModule::getCancelId());
        $this->loyaltyStateConvert = new LoyaltyStateModule(LoyaltyStateModule::getConvertId());
        $this->loyaltyStateNoneAward = new LoyaltyStateModule(LoyaltyStateModule::getNoneAwardId());
    }

    public function install()
    {
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');

        if (!parent::install() ||
            !$this->installDB() ||
            !$this->registerHook('header') ||
            !$this->registerHook('displayProductAdditionalInfo') ||
            !$this->registerHook('displayShoppingCartFooter') ||
            !$this->registerHook('displayNav2') ||
            !$this->registerHook('updateOrderStatus') ||
            !$this->registerHook('newOrder') ||
            !$this->registerHook('adminCustomers') ||
            !$this->registerHook('shoppingCart') ||
            !$this->registerHook('orderReturn') ||
            !$this->registerHook('cancelProduct') ||
            !$this->registerHook('customerAccount') ||
            !Configuration::updateValue('PS_LOYALTY_POINT_VALUE', '0.05') ||
            !Configuration::updateValue('PS_LOYALTY_MINIMAL', 0) ||
            !Configuration::updateValue('PS_LOYALTY_POINT_RATE', '1') ||
            !Configuration::updateValue('PS_LOYALTY_NONE_AWARD', '1') ||
            !Configuration::updateValue('PS_LOYALTY_TAX', '0') ||
            !Configuration::updateValue('PS_LOYALTY_VALIDITY_PERIOD', '0'))
        {
            return false;
        }


        $defaultTranslations = array('en' => 'Loyalty reward', 'fr' => 'Récompense fidélité');
        $conf = array((int)Configuration::get('PS_LANG_DEFAULT') => $this->l('Loyalty reward'));
        foreach (Language::getLanguages() as $language) {
            if (isset($defaultTranslations[$language['iso_code']])) {
                $conf[(int)$language['id_lang']] = $defaultTranslations[$language['iso_code']];
            }
        }
        Configuration::updateValue('PS_LOYALTY_VOUCHER_DETAILS', $conf);

        $category_config = '';
        $categories = Category::getSimpleCategories((int)Configuration::get('PS_LANG_DEFAULT'));
        foreach ($categories as $category) {
            $category_config .= (int)$category['id_category'] . ',';
        }
        $category_config = rtrim($category_config, ',');
        Configuration::updateValue('PS_LOYALTY_VOUCHER_CATEGORY', $category_config);
        if (!LoyaltyStateModule::insertDefaultData())
            return false;
        return true;
    }

    public function installDB()
    {
        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'loyalty` (
			`id_loyalty` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_loyalty_state` INT UNSIGNED NOT NULL DEFAULT 1,
			`id_customer` INT UNSIGNED NOT NULL,
			`id_order` INT UNSIGNED DEFAULT NULL,
			`id_cart_rule` INT UNSIGNED DEFAULT NULL,
			`points` INT NOT NULL DEFAULT 0,
			`date_add` DATETIME NOT NULL,
			`date_upd` DATETIME NOT NULL,
			PRIMARY KEY (`id_loyalty`),
			INDEX index_loyalty_loyalty_state (`id_loyalty_state`),
			INDEX index_loyalty_order (`id_order`),
			INDEX index_loyalty_discount (`id_cart_rule`),
			INDEX index_loyalty_customer (`id_customer`)
		) DEFAULT CHARSET=utf8 ;');

        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'loyalty_history` (
			`id_loyalty_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_loyalty` INT UNSIGNED DEFAULT NULL,
			`id_loyalty_state` INT UNSIGNED NOT NULL DEFAULT 1,
			`points` INT NOT NULL DEFAULT 0,
			`date_add` DATETIME NOT NULL,
			PRIMARY KEY (`id_loyalty_history`),
			INDEX `index_loyalty_history_loyalty` (`id_loyalty`),
			INDEX `index_loyalty_history_loyalty_state` (`id_loyalty_state`)
		) DEFAULT CHARSET=utf8 ;');

        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'loyalty_state` (
			`id_loyalty_state` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_order_state` INT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (`id_loyalty_state`),
			INDEX index_loyalty_state_order_state (`id_order_state`)
		) DEFAULT CHARSET=utf8 ;');

        Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'loyalty_state_lang` (
			`id_loyalty_state` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_lang` INT UNSIGNED NOT NULL,
			`name` varchar(64) NOT NULL,
			UNIQUE KEY `index_unique_loyalty_state_lang` (`id_loyalty_state`,`id_lang`)
		) DEFAULT CHARSET=utf8 ;');

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('submitLoyalty')) {
            $id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
            $languages = Language::getLanguages();

            $this->_errors = array();
            if (!is_array(Tools::getValue('categoryBox')) || !count(Tools::getValue('categoryBox')))
                $this->_errors[] = $this->l('You must choose at least one category for voucher\'s action');
            if (!count($this->_errors)) {
                Configuration::updateValue('PS_LOYALTY_VOUCHER_CATEGORY', $this->voucherCategories(Tools::getValue('categoryBox')));
                Configuration::updateValue('PS_LOYALTY_POINT_VALUE', (float)(Tools::getValue('point_value')));
                Configuration::updateValue('PS_LOYALTY_POINT_RATE', (float)(Tools::getValue('point_rate')));
                Configuration::updateValue('PS_LOYALTY_NONE_AWARD', (int)(Tools::getValue('PS_LOYALTY_NONE_AWARD')));
                Configuration::updateValue('PS_LOYALTY_MINIMAL', (float)(Tools::getValue('minimal')));
                Configuration::updateValue('PS_LOYALTY_TAX', (int)(Tools::getValue('PS_LOYALTY_TAX')));
                Configuration::updateValue('PS_LOYALTY_VALIDITY_PERIOD', (int)(Tools::getValue('validity_period')));
                Configuration::updateValue('PS_LOYALTY_UNCOMBINABLE', (int)(Tools::getValue('PS_LOYALTY_UNCOMBINABLE')));

                $this->loyaltyStateValidation->id_order_state = (int)(Tools::getValue('id_order_state_validation'));
                $this->loyaltyStateCancel->id_order_state = (int)(Tools::getValue('id_order_state_cancel'));

                $arrayVoucherDetails = array();
                foreach ($languages as $language) {
                    $arrayVoucherDetails[(int)($language['id_lang'])] = Tools::getValue('voucher_details_' . (int)($language['id_lang']));
                    $this->loyaltyStateDefault->name[(int)($language['id_lang'])] = Tools::getValue('default_loyalty_state_' . (int)($language['id_lang']));
                    $this->loyaltyStateValidation->name[(int)($language['id_lang'])] = Tools::getValue('validation_loyalty_state_' . (int)($language['id_lang']));
                    $this->loyaltyStateCancel->name[(int)($language['id_lang'])] = Tools::getValue('cancel_loyalty_state_' . (int)($language['id_lang']));
                    $this->loyaltyStateConvert->name[(int)($language['id_lang'])] = Tools::getValue('convert_loyalty_state_' . (int)($language['id_lang']));
                    $this->loyaltyStateNoneAward->name[(int)($language['id_lang'])] = Tools::getValue('none_award_loyalty_state_' . (int)($language['id_lang']));
                }
                if (empty($arrayVoucherDetails[$id_lang_default]))
                    $arrayVoucherDetails[$id_lang_default] = ' ';
                Configuration::updateValue('PS_LOYALTY_VOUCHER_DETAILS', $arrayVoucherDetails);

                if (empty($this->loyaltyStateDefault->name[$id_lang_default]))
                    $this->loyaltyStateDefault->name[$id_lang_default] = ' ';
                $this->loyaltyStateDefault->save();

                if (empty($this->loyaltyStateValidation->name[$id_lang_default]))
                    $this->loyaltyStateValidation->name[$id_lang_default] = ' ';
                $this->loyaltyStateValidation->save();

                if (empty($this->loyaltyStateCancel->name[$id_lang_default]))
                    $this->loyaltyStateCancel->name[$id_lang_default] = ' ';
                $this->loyaltyStateCancel->save();

                if (empty($this->loyaltyStateConvert->name[$id_lang_default]))
                    $this->loyaltyStateConvert->name[$id_lang_default] = ' ';
                $this->loyaltyStateConvert->save();

                if (empty($this->loyaltyStateNoneAward->name[$id_lang_default]))
                    $this->loyaltyStateNoneAward->name[$id_lang_default] = ' ';
                $this->loyaltyStateNoneAward->save();

                $this->html .= $this->displayConfirmation($this->l('Settings updated.'));
            } else {
                $errors = '';
                foreach ($this->_errors as $error)
                    $errors .= $error . '<br />';
                $this->html .= $this->displayError($errors);
            }
        }
    }

    public static function afterAdd($currentObject, $prefix, $rule_products = false)
    {
        // CART RULE RESTRICTIONS
        // If the new rule has no cart rule restriction, then it must be added to the white list of the other cart rules that have restrictions
        if (!$currentObject->cart_rule_restriction) {
            Db::getInstance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
				SELECT id_cart_rule, ' . (int)$currentObject->id . ' FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE cart_rule_restriction = 1
			)');
        } // And if the new cart rule has restrictions, previously unrestricted cart rules may now be restricted (a mug of coffee is strongly advised to understand this sentence)
        else {
            $ruleCombinations = Db::getInstance()->executeS('
			SELECT cr.id_cart_rule
			FROM ' . _DB_PREFIX_ . 'cart_rule cr
			WHERE cr.id_cart_rule != ' . (int)$currentObject->id . '
			AND cr.cart_rule_restriction = 0
			AND NOT EXISTS (
				SELECT 1
				FROM ' . _DB_PREFIX_ . 'cart_rule_combination
				WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_2 AND ' . (int)$currentObject->id . ' = id_cart_rule_1
			)
			AND NOT EXISTS (
				SELECT 1
				FROM ' . _DB_PREFIX_ . 'cart_rule_combination
				WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_1 AND ' . (int)$currentObject->id . ' = id_cart_rule_2
			)
			');
            foreach ($ruleCombinations as $incompatibleRule) {
                Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'cart_rule` SET cart_rule_restriction = 1 WHERE id_cart_rule = ' . (int)$incompatibleRule['id_cart_rule'] . ' LIMIT 1');
                Db::getInstance()->execute('
				INSERT IGNORE INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
					SELECT id_cart_rule, ' . (int)$incompatibleRule['id_cart_rule'] . ' FROM `' . _DB_PREFIX_ . 'cart_rule`
					WHERE active = 1
					AND id_cart_rule != ' . (int)$currentObject->id . '
					AND id_cart_rule != ' . (int)$incompatibleRule['id_cart_rule'] . '
				)');
            }
        }
    }

    private function voucherCategories($categories)
    {
        $cat = '';
        if ($categories && is_array($categories))
            foreach ($categories as $category)
                $cat .= $category . ',';
        return rtrim($cat, ',');
    }

    public function getContent()
    {
        $this->instanceDefaultStates();
        $this->_postProcess();

        $this->html .= $this->renderForm();

        return $this->displayAdvert() . $this->html . $this->checkforupdates(0, 1);
    }

    public function hookHeader($params)
    {
        if (Tools::getValue('controller') == 'cart' && Tools::getValue('myprestaloyaltyurl') == 1 && Tools::getValue('ajax') == true) {
            $myprestaloyalty = $this->returnCartTable();
            echo $myprestaloyalty;
            die();
        }

        $this->context->controller->addCSS($this->_path . 'views/css/myprestaloyalty.css');
        $this->context->controller->addJs($this->_path . 'views/js/myprestaloyalty.js');
        Media::addJsDef(array('myprestaloyaltyurl' => $this->context->link->getPageLink('cart', true, $this->context->language->id, array('ajax' => 1, 'myprestaloyaltyurl' => 1))));
    }

    /* Hook display on product detail */
    public function hookdisplayProductAdditionalInfo($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        $product = new Product((int)Tools::getValue('id_product'));
        if (Validate::isLoadedObject($product)) {
            if (Validate::isLoadedObject($params['cart'])) {
                $pointsBefore = (int)LoyaltyModule::getCartNbPoints($params['cart']);
                $pointsAfter = (int)LoyaltyModule::getCartNbPoints($params['cart'], $product);
                $points = (int)($pointsAfter - $pointsBefore);
            } else {

                if (!(int)Configuration::get('PS_LOYALTY_NONE_AWARD') && Product::isDiscounted((int)$product->id)) {
                    $points = 0;
                    $this->smarty->assign('no_pts_discounted', 1);
                } else {
                    $id_product_attribute = (Tools::getValue('id_product_attribute', 'false') != 'false' ? Tools::getValue('id_product_attribute') : 0);
                    if (Tools::getValue('group', 'false') != 'false') {
                        $id_product_attribute = Product::getIdProductAttributeByIdAttributes((int)$product->id, Tools::getValue('group'));
                    }
                    $points = (int)LoyaltyModule::getNbPointsByPrice($product->getPrice(Product::getTaxCalculationMethod() == PS_TAX_EXC ? false : true, $id_product_attribute));
                }

                $pointsAfter = $points;
                $pointsBefore = 0;
            }
            $this->smarty->assign(array(
                'points' => (int)$points,
                'total_points' => (int)$pointsAfter,
                'point_rate' => Configuration::get('PS_LOYALTY_POINT_RATE'),
                'point_value' => Configuration::get('PS_LOYALTY_POINT_VALUE'),
                'points_in_cart' => (int)$pointsBefore,
                'voucher' => LoyaltyModule::getVoucherValue((int)$pointsAfter),
                'none_award' => Configuration::get('PS_LOYALTY_NONE_AWARD')
            ));

            return $this->display(__FILE__, 'views/templates/hook/product.tpl');
        }

        return false;
    }

    public function hookCustomerAccount($params)
    {
        return $this->display(__FILE__, 'my-account.tpl');
    }

    //public function hookDisplayMyAccountBlock($params)
    //{
    //return $this->hookCustomerAccount($params);
    //}

    /* Catch product returns and substract loyalty points */
    public function hookOrderReturn($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        $total_price = 0;
        $taxesEnabled = Product::getTaxCalculationMethod();
        $details = OrderReturn::getOrdersReturnDetail((int)$params['orderReturn']->id);
        foreach ($details as $detail) {
            if ($taxesEnabled == PS_TAX_EXC)
                $total_price += Db::getInstance()->getValue('
				SELECT ROUND(total_price_tax_excl, 2)
				FROM ' . _DB_PREFIX_ . 'order_detail od
				WHERE id_order_detail = ' . (int)$detail['id_order_detail']);
            else
                $total_price += Db::getInstance()->getValue('
				SELECT ROUND(total_price_tax_incl, 2)
				FROM ' . _DB_PREFIX_ . 'order_detail od
				WHERE id_order_detail = ' . (int)$detail['id_order_detail']);
        }

        $loyalty_new = new LoyaltyModule();
        $loyalty_new->points = (int)(-1 * LoyaltyModule::getNbPointsByPrice($total_price));
        $loyalty_new->id_loyalty_state = (int)LoyaltyStateModule::getCancelId();
        $loyalty_new->id_order = (int)$params['orderReturn']->id_order;
        $loyalty_new->id_customer = (int)$params['orderReturn']->id_customer;
        $loyalty_new->save();
    }

    public function returnCartTable()
    {
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');
        $cart = Context::getContext()->cart;
        if (Validate::isLoadedObject($cart)) {
            $points = LoyaltyModule::getCartNbPoints($cart);
            $this->smarty->assign(array(
                'points' => (int)$points,
                'voucher' => LoyaltyModule::getVoucherValue((int)$points),
                'guest_checkout' => (int)Configuration::get('PS_GUEST_CHECKOUT_ENABLED')
            ));
        } else {
            $this->smarty->assign(array('points' => 0));
        }

        return $this->display(__FILE__, 'shopping-cart.tpl');
    }

    /* Hook display on shopping cart summary */
    public function hookShoppingCart($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        if (Validate::isLoadedObject($params['cart'])) {
            $points = LoyaltyModule::getCartNbPoints($params['cart']);
            $this->smarty->assign(array(
                'points' => (int)$points,
                'voucher' => LoyaltyModule::getVoucherValue((int)$points),
                'guest_checkout' => (int)Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            ));
        } else {
            $this->smarty->assign(array('points' => 0));
        }

        return $this->display(__FILE__, 'shopping-cart.tpl');
    }

    /* Hook prompt to convert points on order placed */
    public function hookDisplayShoppingCartFooter()
    {
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        $customerId = $this->context->customer->id;
        $customerPoints = LoyaltyModule::getAvailablePointsByIdCustomer((int)$customerId);
        $exchangeRate = (float)(Configuration::get('PS_LOYALTY_POINT_VALUE'));
        $currency = $this->context->currency->iso_code;


        $this->smarty->assign(array(
            'points' => (int)$customerPoints,
            'voucher' => LoyaltyModule::getVoucherValue((int) $customerPoints),
            'transformation_allowed' => $customerPoints > 0,
            'exchangeRate' =>  $exchangeRate,
            'currency'=>  $currency
        ));

        return $this->display(__FILE__, 'shopping-cart-summary.tpl');
    }

    /* Show points in header */
    public function hookDisplayNav2($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        //check if loged in
        if($this->context->customer->isLogged()){
            $customerId = $this->context->customer->id;
            $customerPoints = LoyaltyModule::getAvailablePointsByIdCustomer((int)$customerId);


            $this->smarty->assign(array(
                'points' => $customerPoints,
                'logedIn' => true
            ));
        }
        else{
            $this->smarty->assign(array(
                'logedIn' => false
            ));
        }

        return $this->display(__FILE__, 'navbar.tpl');
    }



    /* Hook called when a new order is created */
    public function hookNewOrder($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        if (!Validate::isLoadedObject($params['customer']) || !Validate::isLoadedObject($params['order']))
            die($this->l('Missing parameters'));
        $loyalty = new LoyaltyModule();
        $loyalty->id_customer = (int)$params['customer']->id;
        $loyalty->id_order = (int)$params['order']->id;
        $loyalty->points = LoyaltyModule::getOrderNbPoints($params['order']);
        if (!Configuration::get('PS_LOYALTY_NONE_AWARD') && (int)$loyalty->points == 0)
            $loyalty->id_loyalty_state = LoyaltyStateModule::getNoneAwardId();
        else
            $loyalty->id_loyalty_state = LoyaltyStateModule::getDefaultId();
        return $loyalty->save();
    }

    /* Hook called when an order change its status */
    public function hookUpdateOrderStatus($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        if (!Validate::isLoadedObject($params['newOrderStatus']))
            die($this->l('Missing parameters'));
        $new_order = $params['newOrderStatus'];
        $order = new Order((int)$params['id_order']);
        if ($order && !Validate::isLoadedObject($order))
            die($this->l('Incorrect Order object.'));
        $this->instanceDefaultStates();

        if ($new_order->id == $this->loyaltyStateValidation->id_order_state || $new_order->id == $this->loyaltyStateCancel->id_order_state) {
            if (!Validate::isLoadedObject($loyalty = new LoyaltyModule(LoyaltyModule::getByOrderId($order->id))))
                return false;
            if ((int)Configuration::get('PS_LOYALTY_NONE_AWARD') && $loyalty->id_loyalty_state == LoyaltyStateModule::getNoneAwardId())
                return true;

            if ($new_order->id == $this->loyaltyStateValidation->id_order_state) {
                $loyalty->id_loyalty_state = LoyaltyStateModule::getValidationId();
                if ((int)$loyalty->points < 0)
                    $loyalty->points = abs((int)$loyalty->points);
            } elseif ($new_order->id == $this->loyaltyStateCancel->id_order_state) {
                $loyalty->id_loyalty_state = LoyaltyStateModule::getCancelId();
                $loyalty->points = 0;
            }
            return $loyalty->save();
        }
        return true;
    }

    /* Hook display in tab AdminCustomers on BO */
    public function hookAdminCustomers($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');

        $customer = new Customer((int)$params['id_customer']);
        if ($customer && !Validate::isLoadedObject($customer))
            die($this->l('Incorrect Customer object.'));

        $details = LoyaltyModule::getAllByIdCustomer((int)$params['id_customer'], (int)$params['cookie']->id_lang);
        $points = (int)LoyaltyModule::getPointsByCustomer((int)$params['id_customer']);

        $html = '<div class="col-lg-12"><div class="card">
			<h3 class="card-header"><i class="material-icons">attach_money</i>' . sprintf($this->l('Loyalty points (%d points)'), $points) . '</h3>';

        if (!isset($points) || count($details) == 0)
            return $html . '<div class="card-body"> ' . $this->l('This customer has no points') . '</div></div></div>';

        $html .= '
		<div class="panel-body">
		<table cellspacing="0" cellpadding="0" class="table">
			<tr style="background-color:#F5E9CF; padding: 0.3em 0.1em;">
				<th>' . $this->l('Order') . '</th>
				<th>' . $this->l('Date') . '</th>
				<th>' . $this->l('Total (without shipping)') . '</th>
				<th>' . $this->l('Points') . '</th>
				<th>' . $this->l('Points Status') . '</th>
			</tr>';
        foreach ($details as $key => $loyalty) {
            $url = 'index.php?tab=AdminOrders&id_order=' . $loyalty['id'] . '&vieworder&token=' . Tools::getAdminToken('AdminOrders' . (int)Tab::getIdFromClassName('AdminOrders') . (int)$params['cookie']->id_employee);
            $html .= '
			<tr style="background-color: ' . ($key % 2 != 0 ? '#FFF6CF' : '#FFFFFF') . ';">
				<td>' . ((int)$loyalty['id'] > 0 ? '<a style="color: #268CCD; font-weight: bold; text-decoration: underline;" href="' . $url . '">' . sprintf($this->l('#%d'), $loyalty['id']) . '</a>' : '--') . '</td>
				<td>' . Tools::displayDate($loyalty['date']) . '</td>
				<td>' . ((int)$loyalty['id'] > 0 ? $loyalty['total_without_shipping'] : '--') . '</td>
				<td>' . (int)$loyalty['points'] . '</td>
				<td>' . $loyalty['state'] . '</td>
			</tr>';
        }
        $html .= '
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" class="bold" style="text-align: right;">' . $this->l('Total points available:') . '</td>
				<td>' . $points . '</td>
				<td>' . $this->l('Voucher value:') . ' ' . Tools::displayPrice(
                LoyaltyModule::getVoucherValue((int)$points, (int)Configuration::get('PS_CURRENCY_DEFAULT')),
                new Currency((int)Configuration::get('PS_CURRENCY_DEFAULT'))
            ) . '</td>
			</tr>
		</table>
		</div>
		</div></div>';

        return $html;
    }

    public function hookCancelProduct($params)
    {
        include_once(dirname(__FILE__) . '/LoyaltyStateModule.php');
        include_once(dirname(__FILE__) . '/LoyaltyModule.php');

        if (!Validate::isLoadedObject($params['order']) || !Validate::isLoadedObject($order_detail = new OrderDetail((int)$params['id_order_detail']))
            || !Validate::isLoadedObject($loyalty = new LoyaltyModule((int)LoyaltyModule::getByOrderId((int)$params['order']->id))))
            return false;

        $taxesEnabled = Product::getTaxCalculationMethod();
        $loyalty_new = new LoyaltyModule();
        if ($taxesEnabled == PS_TAX_EXC)
            $loyalty_new->points = -1 * LoyaltyModule::getNbPointsByPrice(number_format($order_detail->total_price_tax_excl, 2, '.', ''));
        else
            $loyalty_new->points = -1 * LoyaltyModule::getNbPointsByPrice(number_format($order_detail->total_price_tax_incl, 2, '.', ''));
        $loyalty_new->id_loyalty_state = (int)LoyaltyStateModule::getCancelId();
        $loyalty_new->id_order = (int)$params['order']->id;
        $loyalty_new->id_customer = (int)$loyalty->id_customer;
        $loyalty_new->add();

        return;
    }

    public function getL($key)
    {
        $translations = array(
            'Awaiting validation' => $this->l('Awaiting validation'),
            'Available' => $this->l('Available'),
            'Cancelled' => $this->l('Cancelled'),
            'Already converted' => $this->l('Already converted'),
            'Unavailable on discounts' => $this->l('Unavailable on discounts'),
            'Not available on discounts.' => $this->l('Not available on discounts.'));

        return (array_key_exists($key, $translations)) ? $translations[$key] : $key;
    }

    public function renderForm()
    {
        $order_states = OrderState::getOrderStates($this->context->language->id);
        $currency = new Currency((int)(Configuration::get('PS_CURRENCY_DEFAULT')));

        $root_category = Category::getRootCategory();
        $root_category = array('id_category' => $root_category->id, 'name' => $root_category->name);

        if (Tools::getValue('categoryBox'))
            $selected_categories = Tools::getValue('categoryBox');
        else
            $selected_categories = explode(',', Configuration::get('PS_LOYALTY_VOUCHER_CATEGORY'));


        $fields_form_1 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Ratio'),
                        'name' => 'point_rate',
                        'prefix' => $currency->sign,
                        'suffix' => $this->l('= 1 reward point.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('1 point ='),
                        'name' => 'point_value',
                        'prefix' => $currency->sign,
                        'suffix' => $this->l('for the discount.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Validity period of a point'),
                        'name' => 'validity_period',
                        'suffix' => $this->l('days'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Voucher details'),
                        'name' => 'voucher_details',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Minimum amount in which the voucher can be used'),
                        'name' => 'minimal',
                        'prefix' => $currency->sign,
                        'class' => 'fixed-width-sm',
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true, //retro-compat
                        'label' => $this->l('Apply taxes on the voucher'),
                        'name' => 'PS_LOYALTY_TAX',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Points are awarded when the order is'),
                        'name' => 'id_order_state_validation',
                        'options' => array(
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Points are cancelled when the order is'),
                        'name' => 'id_order_state_cancel',
                        'options' => array(
                            'query' => $order_states,
                            'id' => 'id_order_state',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true, //retro-compat
                        'label' => $this->l('Give points on discounted products'),
                        'name' => 'PS_LOYALTY_NONE_AWARD',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'is_bool' => true, //retro-compat
                        'label' => $this->l('Make voucher uncombinable with other vouchers'),
                        'desc' => $this->l('Option when active will make voucher generated with loyalty module uncombinable with other voucher codes available in shop'),
                        'name' => 'PS_LOYALTY_UNCOMBINABLE',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        )
                    ),
                    array(
                        'type' => 'categories',
                        'label' => $this->l('Vouchers created by the loyalty system can be used in the following categories:'),
                        'name' => 'categoryBox',
                        'desc' => $this->l('Mark the boxes of categories in which loyalty vouchers can be used.'),
                        'tree' => array(
                            'use_search' => false,
                            'id' => 'categoryBox',
                            'use_checkbox' => true,
                            'selected_categories' => $selected_categories,
                        ),
                        //retro compat 1.5 for category tree
                        'values' => array(
                            'trads' => array(
                                'Root' => $root_category,
                                'selected' => $this->l('Selected'),
                                'Collapse All' => $this->l('Collapse All'),
                                'Expand All' => $this->l('Expand All'),
                                'Check All' => $this->l('Check All'),
                                'Uncheck All' => $this->l('Uncheck All')
                            ),
                            'selected_cat' => $selected_categories,
                            'input_name' => 'categoryBox[]',
                            'use_radio' => false,
                            'use_search' => false,
                            'disabled_categories' => array(),
                            'top_category' => Category::getTopCategory(),
                            'use_context' => true,
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );


        $fields_form_2 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Loyalty points progression'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Initial'),
                        'name' => 'default_loyalty_state',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Unavailable'),
                        'name' => 'none_award_loyalty_state',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Converted'),
                        'name' => 'convert_loyalty_state',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Validation'),
                        'name' => 'validation_loyalty_state',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Cancelled'),
                        'name' => 'cancel_loyalty_state',
                        'lang' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLoyalty';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm(array($fields_form_1, $fields_form_2));
    }

    public function getConfigFieldsValues()
    {
        $fields_values = array(
            'point_rate' => Tools::getValue('PS_LOYALTY_POINT_RATE', Configuration::get('PS_LOYALTY_POINT_RATE')),
            'point_value' => Tools::getValue('PS_LOYALTY_POINT_VALUE', Configuration::get('PS_LOYALTY_POINT_VALUE')),
            'PS_LOYALTY_NONE_AWARD' => Tools::getValue('PS_LOYALTY_NONE_AWARD', Configuration::get('PS_LOYALTY_NONE_AWARD')),
            'minimal' => Tools::getValue('PS_LOYALTY_MINIMAL', Configuration::get('PS_LOYALTY_MINIMAL')),
            'validity_period' => Tools::getValue('PS_LOYALTY_VALIDITY_PERIOD', Configuration::get('PS_LOYALTY_VALIDITY_PERIOD')),
            'id_order_state_validation' => Tools::getValue('id_order_state_validation', $this->loyaltyStateValidation->id_order_state),
            'id_order_state_cancel' => Tools::getValue('id_order_state_cancel', $this->loyaltyStateCancel->id_order_state),
            'PS_LOYALTY_TAX' => Tools::getValue('PS_LOYALTY_TAX', Configuration::get('PS_LOYALTY_TAX')),
            'PS_LOYALTY_UNCOMBINABLE' => Tools::getValue('PS_LOYALTY_UNCOMBINABLE', Configuration::get('PS_LOYALTY_UNCOMBINABLE')),
        );

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $fields_values['voucher_details'][$lang['id_lang']] = Tools::getValue('voucher_details_' . (int)$lang['id_lang'], Configuration::get('PS_LOYALTY_VOUCHER_DETAILS', (int)$lang['id_lang']));
            $fields_values['default_loyalty_state'][$lang['id_lang']] = Tools::getValue('default_loyalty_state_' . (int)$lang['id_lang'], $this->loyaltyStateDefault->name[(int)($lang['id_lang'])]);
            $fields_values['validation_loyalty_state'][$lang['id_lang']] = Tools::getValue('validation_loyalty_state_' . (int)$lang['id_lang'], $this->loyaltyStateValidation->name[(int)($lang['id_lang'])]);
            $fields_values['cancel_loyalty_state'][$lang['id_lang']] = Tools::getValue('cancel_loyalty_state_' . (int)$lang['id_lang'], $this->loyaltyStateCancel->name[(int)($lang['id_lang'])]);
            $fields_values['convert_loyalty_state'][$lang['id_lang']] = Tools::getValue('convert_loyalty_state_' . (int)$lang['id_lang'], $this->loyaltyStateConvert->name[(int)($lang['id_lang'])]);
            $fields_values['none_award_loyalty_state'][$lang['id_lang']] = Tools::getValue('none_award_loyalty_state_' . (int)$lang['id_lang'], $this->loyaltyStateNoneAward->name[(int)($lang['id_lang'])]);
        }
        return $fields_values;
    }

    public function checkforupdates($display_msg = 0, $form = 0)
    {
        // ---------- //
        // ---------- //
        // VERSION 16 //
        // ---------- //
        // ---------- //
        $this->mkey = "nlc";
        if (@file_exists('../modules/' . $this->name . '/key.php')) {
            @require_once('../modules/' . $this->name . '/key.php');
        } else {
            if (@file_exists(dirname(__FILE__) . $this->name . '/key.php')) {
                @require_once(dirname(__FILE__) . $this->name . '/key.php');
            } else {
                if (@file_exists('modules/' . $this->name . '/key.php')) {
                    @require_once('modules/' . $this->name . '/key.php');
                }
            }
        }
        if ($form == 1) {
            return '
            <div class="panel" id="fieldset_myprestaupdates" style="margin-top:20px;">
            ' . ($this->psversion() == 6 || $this->psversion() == 7 ? '<div class="panel-heading"><i class="icon-wrench"></i> ' . $this->l('MyPresta updates') . '</div>' : '') . '
			<div class="form-wrapper" style="padding:0px!important;">
            <div id="module_block_settings">
                    <fieldset id="fieldset_module_block_settings">
                         ' . ($this->psversion() == 5 ? '<legend style="">' . $this->l('MyPresta updates') . '</legend>' : '') . '
                        <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                            <label>' . $this->l('Check updates') . '</label>
                            <div class="margin-form">' . (Tools::isSubmit('submit_settings_updates_now') ? ($this->inconsistency(0) ? '' : '') . $this->checkforupdates(1) : '') . '
                                <button style="margin: 0px; top: -3px; position: relative;" type="submit" name="submit_settings_updates_now" class="button btn btn-default" />
                                <i class="process-icon-update"></i>
                                ' . $this->l('Check now') . '
                                </button>
                            </div>
                            <label>' . $this->l('Updates notifications') . '</label>
                            <div class="margin-form">
                                <select name="mypresta_updates">
                                    <option value="-">' . $this->l('-- select --') . '</option>
                                    <option value="1" ' . ((int)(Configuration::get('mypresta_updates') == 1) ? 'selected="selected"' : '') . '>' . $this->l('Enable') . '</option>
                                    <option value="0" ' . ((int)(Configuration::get('mypresta_updates') == 0) ? 'selected="selected"' : '') . '>' . $this->l('Disable') . '</option>
                                </select>
                                <p class="clear">' . $this->l('Turn this option on if you want to check MyPresta.eu for module updates automatically. This option will display notification about new versions of this addon.') . '</p>
                            </div>
                            <label>' . $this->l('Module page') . '</label>
                            <div class="margin-form">
                                <a style="font-size:14px;" href="' . $this->mypresta_link . '" target="_blank">' . $this->displayName . '</a>
                                <p class="clear">' . $this->l('This is direct link to official addon page, where you can read about changes in the module (changelog)') . '</p>
                            </div>
                            <div class="panel-footer">
                                <button type="submit" name="submit_settings_updates"class="button btn btn-default pull-right" />
                                <i class="process-icon-save"></i>
                                ' . $this->l('Save') . '
                                </button>
                            </div>
                        </form>
                    </fieldset>
                    <style>
                    #fieldset_myprestaupdates {
                        display:block;clear:both;
                        float:inherit!important;
                    }
                    </style>
                </div>
            </div>
            </div>';
        } else {
            if (defined('_PS_ADMIN_DIR_')) {
                if (Tools::isSubmit('submit_settings_updates')) {
                    Configuration::updateValue('mypresta_updates', Tools::getValue('mypresta_updates'));
                }
                if (Configuration::get('mypresta_updates') != 0 || (bool)Configuration::get('mypresta_updates') != false) {
                    if (Configuration::get('update_' . $this->name) < (date("U") - 259200)) {
                        $actual_version = myprestaloyaltyUpdate::verify($this->name, (isset($this->mkey) ? $this->mkey : 'nokey'), $this->version);
                    }
                    if (myprestaloyaltyUpdate::version($this->version) < myprestaloyaltyUpdate::version(Configuration::get('updatev_' . $this->name)) && Tools::getValue('ajax', 'false') == 'false') {
                        $this->context->controller->warnings[] = '<strong>' . $this->displayName . '</strong>: ' . $this->l('New version available, check http://MyPresta.eu for more informations') . ' <a href="' . $this->mypresta_link . '">' . $this->l('More details in changelog') . '</a>';
                        $this->warning = $this->context->controller->warnings[0];
                    }
                } else {
                    if (Configuration::get('update_' . $this->name) < (date("U") - 259200)) {
                        $actual_version = myprestaloyaltyUpdate::verify($this->name, (isset($this->mkey) ? $this->mkey : 'nokey'), $this->version);
                    }
                }
                if ($display_msg == 1) {
                    if (myprestaloyaltyUpdate::version($this->version) < myprestaloyaltyUpdate::version(myprestaloyaltyUpdate::verify($this->name, (isset($this->mkey) ? $this->mkey : 'nokey'), $this->version))) {
                        return "<span style='color:red; font-weight:bold; font-size:16px; margin-right:10px;'>" . $this->l('New version available!') . "</span>";
                    } else {
                        return "<span style='color:green; font-weight:bold; font-size:16px; margin-right:10px;'>" . $this->l('Module is up to date!') . "</span>";
                    }
                }
            }
        }
    }
}

class myprestaloyaltyUpdate extends myprestaloyalty
{
    public static function version($version)
    {
        $version = (int)str_replace(".", "", $version);
        if (strlen($version) == 3) {
            $version = (int)$version . "0";
        }
        if (strlen($version) == 2) {
            $version = (int)$version . "00";
        }
        if (strlen($version) == 1) {
            $version = (int)$version . "000";
        }
        if (strlen($version) == 0) {
            $version = (int)$version . "0000";
        }
        return (int)$version;
    }

    public static function encrypt($string)
    {
        return base64_encode($string);
    }

    public static function verify($module, $key, $version)
    {
        if (ini_get("allow_url_fopen")) {
            if (function_exists("file_get_contents")) {
                $actual_version = @file_get_contents('http://dev.mypresta.eu/update/get.php?module=' . $module . "&version=" . self::encrypt($version) . "&lic=$key&u=" . self::encrypt(_PS_BASE_URL_ . __PS_BASE_URI__));
            }
        }
        Configuration::updateValue("update_" . $module, date("U"));
        Configuration::updateValue("updatev_" . $module, $actual_version);
        return $actual_version;
    }
}
