<?php
/**
* Modulo XPay
*
* @author    Kijam
* @copyright 2020 Kijam
* @license   MIT
*/

class XPayGateway
{
    //Constantes
    const DB_PREFIX = 'xpay';

    //Configuracion general del modulo
    private $settings = null;
    public $config = null;
    public $os_wait_payment = null;
    private $currency_convert = array();
    private $site_id = null;
    private $site_url = null;
    private $id_shop = null;
    private $id_shop_group = null;
    private $module_name = null;
    private $instance_module = null;
    public $api = null;
    public $api_me = null;
    private $context = null;
    public $warning = '';

    //Propiedades estaticas
    private static $instance = null;
    private static $instance_status = 'uninstance';
    private static $mp_cache = array();
    private static $ignore_update_status = false;

    private function __construct($load_module_name, $load_instance_module)
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }
        $this->context = Context::getContext();
        $this->site_url = Tools::htmlentitiesutf8(
            ((bool)Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
            .$_SERVER['HTTP_HOST'].__PS_BASE_URI__
        );
        self::$instance = $this;
        if (version_compare(_PS_VERSION_, '1.5.0.9') >= 0) {
            $this->id_shop = Shop::getContextShopID();
            $this->id_shop_group = Shop::getContextShopGroupID();
            if ((int)$this->id_shop > 0) {
                $shop = new Shop($this->id_shop);
                $this->site_url = (bool)Configuration::get('PS_SSL_ENABLED')?'https://'.$shop->domain_ssl:'http://'
                                    .$shop->domain;
                $this->site_url .= $shop->getBaseURI(true);
            }
            $str = Configuration::get(
                'xpay_config',
                null,
                $this->id_shop_group,
                $this->id_shop
            );
            $this->config = (array)Tools::jsonDecode($str, true);
            $this->currency_convert = (array)Tools::jsonDecode(Configuration::get(
                'xpay_currency_convert',
                null,
                $this->id_shop_group,
                $this->id_shop
            ), true);
            $this->os_wait_payment = (int)Configuration::get(
                'xpay_os_wait_payment',
                null,
                $this->id_shop_group,
                $this->id_shop
            );
        } else {
            $this->config = (array)Tools::jsonDecode(Configuration::get('xpay_config'), true);
            $this->os_wait_payment = (int)Configuration::get('xpay_os_wait_payment');
            $str = Configuration::get('xpay_currency_convert');
            $this->currency_convert = (array)Tools::jsonDecode($str, true);
        }
        if (!isset($this->config['xpay_country']) || empty($this->config['xpay_country'])) {
            $this->config = array();
            $this->config['xpay_country'] = 'CO';
            $this->config['currency_rate'] = 'PS';
            $this->config['debug'] = false;
            $this->config['os_authorization'] = (int)Configuration::get('PS_OS_PAYMENT');
            $this->config['os_refused'] = (int)Configuration::get('PS_OS_ERROR');
        }
        $this->module_name = $load_module_name;
        $this->instance_module = $load_instance_module;
        $this->site_id = $this->config['xpay_country'];
        $data = include(dirname(__FILE__).'/data-mp-countries.php');
        $this->settings = $data[Tools::strtoupper($this->site_id)];
        if (!class_exists('XPayLib')) {
            include(dirname(__file__).'/lib/xpay.php');
        }
        if (isset($this->config['client_secret'])) {
            $this->verifyOrderWaitStatus();
            $this->api = new XPayLib(
                $this->config['client_secret']
            );
            if (!$this->verifyXPay()) {
                $this->api_me = null;
                $this->api = null;
            }
        }
    }
    public static function getInstance($load_module_name, $load_instance_module)
    {
        if (is_null(self::$instance) && self::$instance_status == 'uninstance') {
            self::$instance_status = 'loading';
            self::$instance = new XPayGateway($load_module_name, $load_instance_module);
            self::$instance_status = 'loaded';
        }
        return self::$instance;
    }
    public function installDb()
    {
        try {
            return Db::getInstance()->Execute('
                CREATE TABLE IF NOT EXISTS `'.bqSQL(_DB_PREFIX_.self::DB_PREFIX).'_cache` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `cache_id` varchar(100) NOT NULL,
                    `data` LONGTEXT NOT NULL,
                    `ttl` INT(11) NOT NULL,
                    UNIQUE(`cache_id`),
                    INDEX(`ttl`)
                )
            ');
        } catch (PrestaShopDatabaseException $e) {
            //die(print_r($e, true));
            return false;
        }
    }
    public function uninstall()
    {
        if ((int)$this->os_wait_payment > 0) {
            $order_state = new OrderState((int)$this->os_wait_payment);
            @unlink(dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif');
            $order_state->delete();
        }
        
        Configuration::deleteByName('xpay_config');
        Configuration::deleteByName('xpay_os_wait_payment');
        Configuration::deleteByName('xpay_currency_convert');
        Db::getInstance()->Execute('DROP TABLE IF EXISTS `'.bqSQL(_DB_PREFIX_.self::DB_PREFIX).'_cache`');
    }
    public function getConfig()
    {
        return $this->config;
    }
    public function getSettings()
    {
        return $this->settings;
    }
    public function verifyOrderWaitStatus()
    {
        if ((int)$this->os_wait_payment > 0) {
            $order_state = new OrderState((int)$this->os_wait_payment);
            if ($order_state && (int)$order_state->id > 0 && !$order_state->deleted) {
                return;
            }
        }

        $order_state = new OrderState();
        $order_state->name = array();
        foreach (Language::getLanguages() as $language) {
            $order_state->name[$language['id_lang']] = 'Esperando Pago';
        }

        $order_state->send_email = false;
        $order_state->color = '#DDEEFF';
        $order_state->hidden = false;
        $order_state->delivery = false;
        $order_state->logable = false;
        if (version_compare(_PS_VERSION_, '1.5.0.1') > 0) {
            $order_state->paid = false;
        }
        $order_state->invoice = false;
        if ($order_state->add()) {
            $source = dirname(__FILE__).'/view/img/state_ms_2.gif';
            $destination = dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif';
            @copy($source, $destination);
        }
        $this->os_wait_payment = (int)$order_state->id;
        Configuration::updateValue('xpay_os_wait_payment', $this->os_wait_payment);
        if (version_compare(_PS_VERSION_, '1.5.0.9') >= 0) {
            $shops = Shop::getCompleteListOfShopsID();
            $shop_groups_list = array();

            /* Setup each shop */
            foreach ($shops as $shop_id) {
                $shop_group_id = (int)Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }
                /* Sets up configuration */
                Configuration::updateValue(
                    'xpay_os_wait_payment',
                    $this->os_wait_payment,
                    false,
                    $shop_group_id,
                    $shop_id
                );
            }

            /* Sets up Shop Group configuration */
            if (count($shop_groups_list)) {
                foreach ($shop_groups_list as $shop_group_id) {
                    Configuration::updateValue(
                        'xpay_os_wait_payment',
                        $this->os_wait_payment,
                        false,
                        $shop_group_id
                    );
                }
            }
        }
    }
    public function verifyXPay($force_reload = false)
    {
        if (!isset($this->config['client_secret']) || empty($this->config['client_secret'])) {
            return false;
        }
        if (!$force_reload && !is_null($this->api_me)) {
            return $this->api_me;
        }
        $cache_id = 'xpay_verify_'.md5($this->config['client_secret']);
        $me = false;
        if (!$force_reload && $me = self::getCache($cache_id)) {
            if (isset($me['key'])) {
                $this->api_me = $me;
                return true;
            }
        }
        $this->api = new XPayLib(
            $this->config['client_secret']
        );
        try {
            if (!is_null($this->api)) {
                $me = $this->api->isApiKeyValid();
                self::setCache($cache_id, $me);
                if (!$me || !isset($me['key'])) {
                    self::log('ERROR-verifyXPay: '.self::pL($this->api, true).self::pL($me, true));
                    return false;
                }
                self::log('verifyXPay: new cache '.$cache_id.' -> '.self::pL($me, true));
                $this->api_me = $me;
                return $me;
            }
        } catch (Exception $e) {
            self::log('ERROR-verifyXPay: '.$e->getFile()."[".$e->getLine()."] -> ".$e->getMessage());
            return false;
        }
        return false;
    }
    public function hookDisplayAdminOrder($params, $file_template)
    {
        $order_id = (int)$params['id_order'];
        $order = new Order($order_id);
        $valid = XPayGateway::getCache('payment_data_c'.$order->id_cart);
        self::log('hookDisplayAdminOrder-valid: '.$order->id_cart.'->'.self::pL($valid, true));
        if (!$valid || !isset($valid['id']) || empty($valid['id'])) {
            return false;
        }
        return array(
            'order_id' => $order_id,
            'xpay_data' => $valid,
            'backwardcompatible' => _PS_VERSION_ < '1.6'
        );
    }
    public function hookOrderConfirmation($order)
    {
        $status_act = $order->getCurrentState();
        switch ($status_act) {
            case $this->config['os_authorization']:
            case Configuration::get('PS_OS_PREPARATION'):
            case Configuration::get('PS_OS_SHIPPING'):
                $result = array('status' => 'ok', 'xpay' => false, 'show_modal' => false, 'id_order' => $order->id);
                break;
            case $this->os_wait_payment:
            case Configuration::get('PS_OS_OUTOFSTOCK'):
            case Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'):
                $input = XPayGateway::getCache('payment_data_c'.$order->id_cart);
                $input['waiting_time2'] = $input['waiting_time'] - (time() - $input['gen_transaction_time']);
                $input['qr_goo'] = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl='.urlencode($input['qr']);
                $result = array('status' => 'pending', 'xpay' => $input, 'id_order' => $order->id);
                break;
            default:
                $result = array('show_modal' => false, 'xpay' => false, 'status' => 'failed');
                break;
        }
        $result['shop_name'] = '';
        return $result;
    }
    public function hookDisplayPDFInvoice($params)
    {
        $order_invoice = $params['object'];
        $order = new Order((int)$order_invoice->id_order);
        $valid = XPayGateway::getCache('payment_data_c'.$order->id_cart);
        if (!$valid || !isset($valid['id'])) {
            return '';
        }
        return '<b>XPay ID</b> '.$valid['id'];
    }
    public function cronjob($smarty, $file_template)
    {
        if (is_null($this->api)) {
            return;
        }
        self::log('cronjob: init');
        self::log('cronjob: end');
        return $this->instance_module->display($file_template, 'views/templates/hook/cronjob.tpl');
    }
    public function hookUpdateOrderStatus($params)
    {
        if (!$this->api || self::$ignore_update_status) {
            return '';
        }
        return '';
    }
    public function paymentButton16($params, $context)
    {
        $create_order = Context::getContext()->link->getModuleLink($this->module_name, 'redirect');
        $cart = Context::getContext()->cart;
        $module_currency = new Currency(self::getIdByIso($this->settings['CURRENCY']));
        $m_currency = $module_currency->iso_code;
        $cart_currency = new Currency((int)$cart->id_currency);
        $c_currency = $cart_currency->iso_code;
        $rate = $this->getRate($c_currency, $m_currency);
        $total = $cart->getOrderTotal(true, Cart::BOTH) * $rate;
        if (!$this->gateway->verifyXPay()) {
            return false;
        }
        return array(
            'config' => $this->config,
            'create_order' => $create_order,
            'currencies' => $this->api->getCurrencies($total, $m_currency),
            'mini_logo' => $this->site_url.'modules/'.$this->module_name.'/views/img/xpay.png'
        );
    }
    protected function generateForm($result)
    {
        $this->context->smarty->assign($result);
        return $this->context->smarty->fetch('module:xpay/views/templates/hook/mp17Cart.tpl');
    }
    public function paymentButton17($params, $context)
    {
        if (!class_exists('PaymentOptionXPay')) {
            include_once(dirname(__FILE__).'/paymentoption.php');
        }
        $newOption = PaymentOptionXPay::getInstance();
        $cart = Context::getContext()->cart;
        try {
            $id_currency_xpay = self::getIdByIso($this->settings['CURRENCY']);
            $module_currency = new Currency($id_currency_xpay);
            $m_currency = $module_currency->iso_code;
            $cart_currency = new Currency((int)$cart->id_currency);
            $c_currency = $cart_currency->iso_code;
            $rate = $this->getRate($c_currency, $m_currency);
            $total = $cart->getOrderTotal(true, Cart::BOTH) * $rate;
            if (!$this->verifyXPay()) {
                throw new \Exception('Servicio no disponible temporalmente');
            }
            $create_order = Context::getContext()->link->getModuleLink($this->module_name, 'redirect');
            $result = array(
                'config' => $this->config,
                'create_order' => $create_order,
                'currencies' => $this->api->getCurrencies($this->settings['CURRENCY'], $total),
                'mini_logo' => $this->site_url.'modules/'.$this->module_name.'/views/img/xpay.png'
            );
            self::log('currencies c_currency: '.var_export($c_currency, true));
            self::log('currencies m_currency: '.var_export($m_currency, true));
            self::log('currencies rate: '.var_export($rate, true));
            self::log('currencies total: '.var_export($total, true));
            self::log('currencies result: '.var_export($result, true));
            if ($result['currencies']) {
                $form = $this->generateForm($result);
                $newOption->setCallToActionText($this->l('Pagar con Criptomonedas'))
                        ->setForm($form)
                        ->setLogo(Media::getMediaPath($this->site_url.'modules/'.$this->module_name.'/views/img/xpay.png'));
                $payment_options = array(
                    $newOption,
                );
                return $payment_options;
            }
            return array();
        } catch (Exception $e) {
            $newOption->setCallToActionText($this->l('XPay.cash Error'))
                        ->setAdditionalInformation(
                            $e->getFile().'['.$e->getLine().']: '.$e->getMessage()
                        );
            $payment_options = array(
                $newOption,
            );
            return $payment_options;
        }
    }
    /*******************************************************/
    /*******************************************************/
    /******** DESDE AQUI VA TODO LO RELACIONADO ************/
    /************* AL ADMIN DE PRESTASHOP ******************/
    /*******************************************************/
    /*******************************************************/
    public function hookBackOfficeHeader($params, $smarty, $file_template)
    {
        $ps_version = 1.6;
        if (version_compare(_PS_VERSION_, '1.6') < 0) {
            $ps_version = 1.5;
        }
        if (version_compare(_PS_VERSION_, '1.7.0.0') >= 0) {
            $ps_version = 1.7;
        }
        $smarty->assign('ps_version', $ps_version);
        $smarty->assign('new_ps_version', version_compare(_PS_VERSION_, '1.5.0.0') >= 0);
        return $this->instance_module->display($file_template, 'views/templates/admin/header.tpl');
    }
    private function updateConfig($file_template)
    {
        $errors = '';
        if (Tools::isSubmit('client_secret')) {
            $ui_error = $this->instance_module->display($file_template, 'views/templates/admin/errors.tpl');
            $reload_api = true;
            if (!isset($this->config['client_secret'])
                || $this->config['client_secret'] != trim(Tools::getValue('client_secret'))) {
            }
            $this->config['client_secret'] = trim(Tools::getValue('client_secret'));
            $this->config['currency_rate'] = trim(Tools::getValue('currency_rate'));
            $this->config['xpay_country'] = trim(Tools::getValue('xpay_country'));
            $this->config['currency_rate_default'] = (bool)Tools::getValue('currency_rate_default');
            $this->config['debug'] = (bool)Tools::getValue('debug');
            $this->config['os_authorization'] = (int)Tools::getValue('os_authorization');
            $this->config['os_refused'] = (int)Tools::getValue('os_refused');
            if (version_compare(_PS_VERSION_, '1.5.0.9') >= 0) {
                Configuration::updateValue(
                    'xpay_config',
                    Tools::jsonEncode($this->config),
                    false,
                    $this->id_shop_group,
                    $this->id_shop
                );
            } else {
                Configuration::updateValue('xpay_config', Tools::jsonEncode($this->config));
            }
            if ($reload_api) {
                if (version_compare(_PS_VERSION_, '1.5.1.0') >= 0) {
                    $this->site_id = $this->config['xpay_country'];
                    $data = include(dirname(__FILE__).'/data-mp-countries.php');
                    $this->settings = $data[Tools::strtoupper($this->site_id)];
                    $id_country = Country::getByIso($this->settings['ISO']);
                    if ($id_country) {
                        Country::addModuleRestrictions(
                            array(),
                            array(array('id_country'=>$id_country)),
                            array(array('id_module'=>$this->instance_module->id))
                        );
                    }
                }
                $r = $this->verifyXPay(true);
                self::log('R verifyXPay: '.var_export($r, true).var_export($this->config, true));
                if (!is_array($r)) {
                    $errors .= sprintf($ui_error, $this->l('Su token no es valido.'));
                }
            }
        }
        return $errors;
    }

    public function adminPage($smarty, $file_template)
    {
        if (defined('PHP_SESSION_NONE') && session_id() == PHP_SESSION_NONE || session_id() == '') {
            session_start();
        }
        $ps_version = 1.6;
        if (version_compare(_PS_VERSION_, '1.6') < 0) {
            $ps_version = 1.5;
        }
        if (version_compare(_PS_VERSION_, '1.7.0.0') >= 0) {
            $ps_version = 1.7;
        }
        $smarty->assign('ps_version', $ps_version);
        $smarty->assign('riot_compiler_url', $this->instance_module->getPathTemplate().'views/js/riot.compiler.min.js');
        $ui_alerts = $this->instance_module->display($file_template, 'views/templates/admin/prestui/ps-alert.tpl');
        $ui_riot   = $this->instance_module->display($file_template, 'views/templates/admin/libs.tpl');
        $b64_riot   = $this->instance_module->display($file_template, 'views/templates/admin/b64_riot.tpl');
        if (version_compare(_PS_VERSION_, '1.5.0.9') >= 0) {
            $str = $this->getWarningMultishopHtml($file_template);
            if ((bool)Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')
                && (Shop::getContext() == Shop::CONTEXT_GROUP
            || Shop::getContext() == Shop::CONTEXT_ALL)) {
                $html = htmlentities($str.$this->getShopContextError($file_template));
                return sprintf($b64_riot, $html.$ui_alerts).$ui_riot;
            }
        }
        $str = $this->updateConfig($file_template);
        $order_states = OrderState::getOrderStates($this->context->employee->id_lang);
        if (!isset($this->config['os_authorization'])) {
            $this->config['os_authorization'] = (int)Configuration::get('PS_OS_PAYMENT');
            $this->config['os_refused'] = (int)Configuration::get('PS_OS_ERROR');
            $this->config['currency_rate'] = 'PS';
            $this->config['currency_rate_default'] = false;
            $this->config['xpay_country'] = 'CO';
            $this->config['sandbox'] = false;
        }
        $countries = XpayLib::getCountries();
        $return_url = Context::getContext()->link->getModuleLink($this->module_name, 'redirect');
        $groups = Group::getGroups(Context::getContext()->language->id);
        $smarty->assign($this->config);
        $smarty->assign('settings', $this->settings);
        $smarty->assign('display_name', $this->instance_module->displayName);
        $smarty->assign('api_me', $this->verifyXPay(true));
        $smarty->assign('log_path', '/modules/'.$this->module_name.'/logs/');
        $smarty->assign('countries', $countries);
        $smarty->assign('order_states', $order_states);
        $smarty->assign('client_groups', $groups);
        $smarty->assign('return_url', $return_url);
        $smarty->assign('log_path_url', $this->site_url.'modules/'.$this->module_name.'/logs/index.php?token='.
            md5($_SERVER['HTTP_HOST'].gmdate('Ymd').session_id()));
        $html = $this->instance_module->display($file_template, 'views/templates/admin/config.tpl');
        $ui_form = $this->instance_module->display($file_template, 'views/templates/admin/prestui/ps-form.tpl');
        $ui_panel = $this->instance_module->display($file_template, 'views/templates/admin/prestui/ps-panel.tpl');

        return sprintf($b64_riot, htmlentities($str.$html).$ui_panel.$ui_form.$ui_alerts).$ui_riot;
    }

    /*******************************************************/
    /*******************************************************/
    /******** DESDE AQUI VA TODO LO RELACIONADO ************/
    /************ HERRAMIENTAS Y UTILIDADES ****************/
    /*******************************************************/
    /*******************************************************/
    public static function log($data)
    {
        if (!is_null(self::$instance)) {
            if (isset(self::$instance->config['debug']) && !self::$instance->config['debug']) {
                return;
            }
        } else {
            return;
        }
        if (!is_dir(dirname(__FILE__).'/logs')) {
            @mkdir(dirname(__FILE__).'/logs');
        }

        if (!is_dir(dirname(__FILE__).'/logs/'.date('Y-m'))) {
            @mkdir(dirname(__FILE__).'/logs/'.date('Y-m'));
        }

        $fp = fopen(dirname(__FILE__).'/logs/'.date('Y-m').'/log-'.date('Y-m-d').'.log', 'a');

        fwrite($fp, "\n----- ".date('Y-m-d H:i:s')." -----\n");
        fwrite($fp, $data);
        fclose($fp);
    }
    public static function pL(&$data, $return_log = true)
    {
        if (self::$instance == null) {
            return print_r($data, $return_log);
        }
        if (isset(self::$instance->config['debug']) && !self::$instance->config['debug']) {
            return '';
        }
        return print_r($data, $return_log);
    }
    public function l($key)
    {
        if ($this->instance_module) {
            return $this->instance_module->lang($key, 'xpay_gateway');
        }
        return $key;
    }
    public static function getIdByIso($iso)
    {
        $id = Currency::getIdByIsoCode($iso);
        if (!$id && in_array($iso, array('VES', 'VEF', 'VEB', 'BSF', 'BSS'))) {
            $id = Currency::getIdByIsoCode('VES');
            $id = !$id?Currency::getIdByIsoCode('BSS'):$id;
            $id = !$id?Currency::getIdByIsoCode('VEF'):$id;
            $id = !$id?Currency::getIdByIsoCode('BSF'):$id;
            $id = !$id?Currency::getIdByIsoCode('VEB'):$id;
        }
        return $id;
    }
    public static function getRate($from, $to)
    {
        $currency_rate = self::$instance->config['currency_rate'];
        $currency_rate_default = self::$instance->config['currency_rate_default'];
        $from = Tools::strtoupper($from);
        $to = Tools::strtoupper($to);
        $id_from = self::getIdByIso($from);
        $id_to = self::getIdByIso($to);
        if ($id_from == $id_to || $from == $to) {
            return 1.0;
        }
        if ($id_from * $id_to != 0 && $currency_rate == 'PS') {
            $currency_from = new Currency((int)$id_from);
            $currency_to = new Currency((int)$id_to);
            $result = $currency_to->conversion_rate / $currency_from->conversion_rate;
            if ($result > 0.0) {
                self::log(
                    "getRate($from -> $to) ==> from ps:
                    {$currency_to->conversion_rate} / {$currency_from->conversion_rate} = {$result}"
                );
                return (float)$result;
            }
        }
        if (in_array($from, array('VEF', 'VEB', 'BSF', 'BSS'))) {
            $from = 'VES';
        }
        if (in_array($to, array('VEF', 'VEB', 'BSF', 'BSS'))) {
            $to = 'VES';
        }
        if ($id_from * $id_to != 0 && ($to == 'VES' || $from == 'VES')) {
            if (isset(self::$instance->currency_convert[$from])
                    && isset(self::$instance->currency_convert[$from][$to])
                    && self::$instance->currency_convert[$from][$to]['time'] > time() - 60 * 60 * 2) {
                $result = false;
                if ($currency_rate == 'DICOM') {
                    $result = self::$instance->currency_convert[$from][$to]['rate_dicom'];
                } else {
                    $result = self::$instance->currency_convert[$from][$to]['rate_average'];
                }
                if ($result > 10000) {
                    $result = Tools::ps_round($result, 0);
                }
                if ($result > 0.0) {
                    self::log("getRate($from -> $to) ==> from cache ajax_get_factorization_rate: {$result}");
                    return (float)$result;
                }
            }
            if ($from == 'VES' && $to == 'USD' || $from == 'USD' && $to == 'VES' ||
                $from == 'VES' && $to == 'EUR' || $from == 'EUR' && $to == 'VES') {
                $data = Tools::jsonDecode(
                    @Tools::file_get_contents('https://s3.amazonaws.com/dolartoday/data.json'),
                    true
                );
                if (isset($data['USD']) && isset($data['USD']['promedio'])) {
                    self::$instance->currency_convert['USD']['VES']['rate_average'] = (float)$data['USD']['promedio'];
                    self::$instance->currency_convert['USD']['VES']['rate_dicom'] = (float)$data['USD']['sicad2'];
                    self::$instance->currency_convert['USD']['VES']['time'] = time();
                    self::$instance->currency_convert['VES']['USD']['rate_average'] = (float)1.0/$data['USD']['promedio'];
                    self::$instance->currency_convert['VES']['USD']['rate_dicom'] = (float)1.0/$data['USD']['sicad2'];
                    self::$instance->currency_convert['VES']['USD']['time'] = time();
                    $id_shop = Shop::getContextShopID();
                    $id_shop_group = Shop::getContextShopGroupID();
                    Configuration::updateValue(
                        'xpay_currency_convert',
                        Tools::jsonEncode(self::$instance->currency_convert),
                        false,
                        self::$instance->id_shop_group,
                        self::$instance->id_shop
                    );
                }
                if (isset($data['EUR']) && isset($data['EUR']['promedio'])) {
                    self::$instance->currency_convert['EUR']['VES']['rate_average'] = (float)$data['EUR']['promedio'];
                    self::$instance->currency_convert['EUR']['VES']['rate_dicom'] = (float)$data['EUR']['sicad2'];
                    self::$instance->currency_convert['EUR']['VES']['time'] = time();
                    self::$instance->currency_convert['VES']['EUR']['rate_average'] = (float)1.0/$data['EUR']['promedio'];
                    self::$instance->currency_convert['VES']['EUR']['rate_dicom'] = (float)1.0/$data['EUR']['sicad2'];
                    self::$instance->currency_convert['VES']['EUR']['time'] = time();
                    $id_shop = Shop::getContextShopID();
                    $id_shop_group = Shop::getContextShopGroupID();
                    Configuration::updateValue(
                        'xpay_currency_convert',
                        Tools::jsonEncode(self::$instance->currency_convert),
                        false,
                        $id_shop_group,
                        $id_shop
                    );
                }
                $result = false;
                if ($currency_rate == 'DICOM') {
                    $result = self::$instance->currency_convert[$from][$to]['rate_dicom'];
                } else {
                    $result = self::$instance->currency_convert[$from][$to]['rate_average'];
                }
                if ($result > 10000) {
                    $result = Tools::ps_round($result, 0);
                }
                if ($currency_rate_default) {
                    $currency_from = new Currency((int)$id_from);
                    $currency_to = new Currency((int)$id_to);
                    if ($currency_from->iso_code == 'USD' || $currency_from->iso_code == 'EUR') {
                        $currency_to->conversion_rate = $result;
                        $currency_to->save();
                    } elseif ($currency_to->iso_code == 'USD' || $currency_to->iso_code == 'EUR') {
                        $currency_from->conversion_rate = $result;
                        $currency_from->save();
                    }
                }
                return $result;
            }
        }
        if (isset(self::$instance->currency_convert[$from])
                && isset(self::$instance->currency_convert[$from][$to])
                && self::$instance->currency_convert[$from][$to]['time'] > time() - 60 * 60 * 12) {
            $result = self::$instance->currency_convert[$from][$to]['rate'];
            if ($result > 0.0) {
                self::log("getRate($from -> $to) ==> from cache live-rates.com: {$result}");
                return (float)$result;
            }
        }
        $headers = array(
                'Connection:keep-alive',
                'User-Agent:Mozilla/5.0 (Windows NT 6.3) AppleWebKit/53 (KHTML, like Gecko) Chrome/37 Safari/537.36');
        $ch = curl_init('https://www.live-rates.com/rates');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $api_json = curl_exec($ch);
        $api_arr = Tools::jsonDecode($api_json, true);
        foreach ($api_arr as $fields) {
            if (isset($fields['currency']) && Tools::strlen($fields['currency']) == 7 &&
                preg_match('/[A-Z0-9]{3}\/[A-Z0-9]{3}/', $fields['currency'])) {
                $cur = explode('/', $fields['currency']);
                self::$instance->currency_convert[$cur[0]][$cur[1]] = array();
                self::$instance->currency_convert[$cur[0]][$cur[1]]['rate'] = (float)$fields['rate'];
                self::$instance->currency_convert[$cur[0]][$cur[1]]['time'] = time();
                self::$instance->currency_convert[$cur[1]][$cur[0]] = array();
                self::$instance->currency_convert[$cur[1]][$cur[0]]['rate'] = 1.0 / (float)$fields['rate'];
                self::$instance->currency_convert[$cur[1]][$cur[0]]['time'] = time();
            }
        }
        Configuration::updateValue(
            'xpay_currency_convert',
            Tools::jsonEncode(self::$instance->currency_convert),
            false,
            self::$instance->id_shop_group,
            self::$instance->id_shop
        );
        $result = self::$instance->currency_convert[$from][$to]['rate'];
        self::log("getRate($from -> $to) ==> from live-rates.com: {$result}");
        return $result;
    }
    public static function getCache($cache_id)
    {
        $data = false;
        if (isset(self::$mp_cache[$cache_id]) && ($data = self::$mp_cache[$cache_id])) {
            return $data;
        }
        try {
            Db::getInstance()->Execute('DELETE FROM `'.bqSQL(_DB_PREFIX_.self::DB_PREFIX).'_cache`
                    WHERE ttl < '.(int)time());
            $d = Db::getInstance()->getValue('SELECT `data` FROM `'.bqSQL(_DB_PREFIX_.self::DB_PREFIX).'_cache`
                    WHERE ttl >= '.(int)time().' AND `cache_id` = \''.pSQL($cache_id).'\'');
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }
        if ($d) {
            $data = unserialize($d);
        }
        return $data;
    }
    public static function setCache($cache_id, $value, $ttl = 36000)
    {
        self::$mp_cache[$cache_id] = $value;
        try {
            Db::getInstance()->Execute('DELETE FROM `'.bqSQL(_DB_PREFIX_.self::DB_PREFIX).'_cache`
                    WHERE ttl < '.(int)time().' OR cache_id = \''.pSQL($cache_id).'\'');
            return Db::getInstance()->Execute('INSERT IGNORE INTO `'.bqSQL(_DB_PREFIX_.self::DB_PREFIX).'_cache`
                        (`cache_id`, `data`, `ttl`) VALUES
                        (\''.pSQL($cache_id).'\',
                         \''.pSQL(serialize($value)).'\',
                         '.(int)(time() + $ttl).')');
        } catch (PrestaShopDatabaseException $e) {
            return false;
        }
    }
    protected function getWarningMultishopHtml($file_template)
    {
        if ((bool)Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')
            && (Shop::getContext() == Shop::CONTEXT_GROUP
            || Shop::getContext() == Shop::CONTEXT_ALL)) {
            $ui_warning = $this->instance_module->display($file_template, 'views/templates/admin/alerts.tpl');
            return sprintf(
                $ui_warning,
                'warning',
                $this->l('You cannot change setting from a "All Shops" or a "Group Shop" context, '.
                         'select directly the shop you want to edit')
            );
        } else {
            return '';
        }
    }
    protected function getShopContextError($file_template)
    {
        $ui_warning = $this->instance_module->display($file_template, 'views/templates/admin/alerts.tpl');
        return sprintf(
            $ui_warning,
            'danger',
            sprintf($this->l('You cannot edit setting from a "All Shops" or a "Group Shop" context'))
        );
    }
}
