<?php
/**
* Modulo XPay
*
* @author    Kijam
* @copyright 2020 Kijam
* @license   MIT
*/

class XPay extends PaymentModule
{
    public $id_carrier;
    public $gateway = null;

    public function __construct()
    {
        $this->name = 'xpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.2';
        $this->author = 'Kijam';
        $this->module_key = '';
        if (version_compare(_PS_VERSION_, '1.7.1.0') >= 0) {
            $this->controllers = array('redirect');
            $this->currencies = true;
            $this->currencies_mode = 'checkbox';
        }

        if (version_compare(_PS_VERSION_, '1.6.0.0') >= 0) {
            $this->bootstrap = true;
        }

        parent::__construct();

        $this->displayName = $this->l('XPay');
        $this->description = $this->l('XPay es un método de pago basado en criptomonedas.');
        if (version_compare(_PS_VERSION_, '1.7.1.0') >= 0) {
            if (!count(Currency::checkPaymentCurrencies($this->id))) {
                $w = $this->trans('No currency has been set for this module.', array(), 'Modules.Checkpayment.Admin');
                $this->warning = $w;
            }
        }

        if (!defined('_PS_VERSION_')) {
            exit;
        }

        if ($this->active) {
            if (!class_exists('XPayGateway')) {
                include_once(dirname(__FILE__).'/xpay_gateway.php');
            }
            $this->gateway = XPayGateway::getInstance($this->name, $this);
            if ($this->gateway) {
                $this->warning .= $this->gateway->warning;
                $settings = $this->gateway->getSettings();
            }
        } else {
            $data = include(dirname(__FILE__).'/data-mp-countries.php');
            $settings = $data['CO'];
        }
    }
    
    public function install()
    {
        $incompatible_found = false;
        if (!function_exists('curl_version')) {
            $this->_errors[] = $this->l('Curl not installed');
            $incompatible_found = true;
        }
        if ($incompatible_found) {
            return false;
        }

        if (!class_exists('XPayGateway')) {
            include_once(dirname(__FILE__).'/xpay_gateway.php');
        }

        $this->gateway = XPayGateway::getInstance($this->name, $this);
        $db_created = $this->gateway->installDb();

        if (!$db_created) {
            $this->_errors[] = $this->l('Failed to create the table in the Database');
        }

        $is_17 = version_compare(_PS_VERSION_, '1.7.0.0') >= 0;
        $is_171 = version_compare(_PS_VERSION_, '1.7.1.0') >= 0;
        $result = $db_created && parent::install()
            && $this->registerHook('orderConfirmation')
            && $this->registerHook('payment')
            && $this->registerHook('updateOrderStatus')
            && $this->registerHook('displayAdminOrder')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayPDFInvoice')
            && ($is_17?$this->registerHook('paymentOptions'):true)
            && ($is_171?$this->registerHook('paymentReturn'):true);
        if (!$result && $db_created) {
            $this->gateway->uninstall();
        }
        if ($result) {
            @chmod(dirname(__FILE__).'/', 0755);
            $cache_id = 'Module::getModuleIdByName_'.pSQL($this->name);
            Cache::clean($cache_id);
        }

        return $result;
    }
    public function uninstall()
    {
        if ($this->gateway) {
            $this->gateway->uninstall();
        }
        return parent::uninstall();
    }
    public function hookDisplayPDFInvoice($params)
    {
        $order_invoice = $params['object'];
        if (!Validate::isLoadedObject($order_invoice) || !isset($order_invoice->id_order)) {
            return;
        }
        return $this->gateway?$this->gateway->hookDisplayPDFInvoice($params):'';
    }
    public function hookPDFInvoice($params)
    {
        return $this->hookDisplayPDFInvoice($params);
    }
    public function hookDisplayProductButtons($params)
    {
        return;
    }
    public function hookDisplayHeader($params)
    {
        return $this->hookHeader($params);
    }
    public function hookHeader($params)
    {
        if (!$this->gateway) {
            return '';
        }
        $result = $this->gateway->cronjob($this->smarty, __FILE__);
        return $result;
    }
    public function hookDisplayBackOfficeHeader($params)
    {
        return $this->hookBackOfficeHeader($params);
    }
    public function hookBackOfficeHeader($params)
    {
        if ($this->gateway && ($result = $this->gateway->hookBackOfficeHeader($params, $this->smarty, __FILE__))) {
            return $result;
        }
        return '';
    }
    public function hookDisplayAdminOrder($params)
    {
        if (!isset($params['id_order']) || !$this->gateway) {
            return '';
        }
        if ($result = $this->gateway->hookDisplayAdminOrder($params, __FILE__)) {
            $this->context->smarty->assign($result);
            return $this->display(__FILE__, 'views/templates/hook/displayAdminOrder.tpl');
        }
        return '';
    }
    public function hookAdminOrder($params)
    {
        return $this->hookDisplayAdminOrder($params);
    }
    public function hookOrderConfirmation($params)
    {
        if (!$this->active || !$this->gateway) {
            return;
        }
        $order = null;
        if (isset($params['objOrder'])) {
            $order = $params['objOrder'];
        } elseif (isset($params['order'])) {
            $order = $params['order'];
        } else {
            return;
        }
        if ($order->module != $this->name) {
            return;
        }
        $this->context->smarty->assign($this->gateway->hookOrderConfirmation($order));
        return $this->display(__FILE__, 'views/templates/hook/hookorderconfirmation.tpl');
    }
    public function getContent()
    {
        if (!$this->gateway) {
            return;
        }
        return $this->gateway->adminPage($this->smarty, __FILE__);
    }
    public function getPathTemplate()
    {
        return $this->_path;
    }
    public function hookPayment($params)
    {
        $config = $this->gateway->getConfig();
        if ($this->gateway && ($result = $this->gateway->paymentButton16($params))) {
            $this->context->smarty->assign($result);
            return $this->display(__FILE__, 'views/templates/hook/mpCart.tpl');
        }
        return '';
    }
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if ($this->gateway && ($result = $this->gateway->paymentButton17($params, $this->context))) {
            return $result;
        }
        return array();
    }
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return '';
        }
        return '';
    }
    public function hookUpdateOrderStatus($params)
    {
        if ($this->gateway && ($result = $this->gateway->hookUpdateOrderStatus($params))) {
            return $result;
        }
        return '';
    }
    public function lang($str, $specific = false)
    {
        return $this->l($str, $specific);
    }
    public function waitingPayment()
    {
        return $this->l('Waiting payment on XPay');
    }
}
