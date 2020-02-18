<?php
/**
* Modulo XPay
*
* @author    Kijam
* @copyright 2020 Kijam
* @license   MIT
*/

/**
 * the name of the class should be [ModuleName][ControllerName]ModuleFrontController
 */
class XPayRedirectModuleFrontController extends ModuleFrontController
{
    private $gateway;
    private $config;
    private $api;
    private $paymentDetails;
    private $items = array();
    private $itemTotalValue = 0;
    private $taxTotalValue = 0;
    private $itemDiscountValue = 0;
    private $isFreeShipping = false;
    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
        $this->gateway = $this->module->gateway;
        $this->api = $this->gateway->api;
        $this->config = $this->module->gateway->config;
        $this->settings = $this->gateway->getSettings();
    }
    public function setAttributesButton($cart)
    {
        $id_currency_xpay = XPayGateway::getIdByIso($this->settings['CURRENCY']);
        $module_currency = new Currency($id_currency_xpay);
        $m_currency = $module_currency->iso_code;
        $cart_currency = new Currency((int)$cart->id_currency);
        $c_currency = $cart_currency->iso_code;
        $rate = $this->gateway->getRate($c_currency, $this->settings['CURRENCY']);
        $total = $cart->getOrderTotal(true, Cart::BOTH) * $rate;
        try {
            if (!$this->gateway->verifyXPay()) {
                throw new \Exception('Servicio no disponible temporalmente');
            }
            $iso_crypto = Tools::getValue('iso_crypto');
            if (!$iso_crypto || empty($iso_crypto)) {
                throw new \Exception('Debe especificar una criptomoneda');
            }
            $exchange = Tools::getValue('exchange');
            if (!$exchange || empty($exchange)) {
                throw new \Exception('Debe especificar una exchange');
            }
            $url = Context::getContext()->link->getModuleLink('xpay', 'redirect', array(
                'ipn-xpay' => $cart->id,
            ));
            $payment = $this->api->createTransaction($total, $this->settings['CURRENCY'], $iso_crypto, $url, $exchange);
            if (!$payment) {
                throw new \Exception('No se pudo procesar el pago, intente mas tarde...');
            }
            $status_act = $this->gateway->os_wait_payment;
            $customer = new Customer((int)$cart->id_customer);
            $this->module->validateOrder(
                $cart->id,
                $status_act,
                $cart->getOrderTotal(true, Cart::BOTH),
                'XPay',
                false,
                array(),
                (int)$cart->id_currency,
                false,
                $customer->secure_key
            );
            $id_order = (int)Order::getOrderByCartId($cart->id);
            $order = new Order($id_order);
            $payment[ 'gen_transaction_time' ] = time();
            $payment[ 'id_cart' ] = $cart->id;
            $payment[ 'id_order' ] = $id_order;
            XPayGateway::setCache('payment_data_c'.$cart->id, $payment, 3600 * 24 * 365);
            $order_payments = $order->getOrderPayments();
            if (count($order_payments) > 1) {
                for ($i = 0, $s = count($order_payments); $i < $s - 2; ++$i) {
                    $order_payments[$i]->delete();
                }
                $order_payments = $order->getOrderPayments();
            }
            if (!count($order_payments)) {
                $order_payment = new OrderPayment();
                $order_payment->order_reference = $order->reference;
                $order_payment->id_currency = $order->id_currency;
                $order_payment->amount = $order->total_paid;
                $order_payment->payment_method = 'xpay';
                $order_payment->transaction_id = $payment['id'];
                $order_payment->save();
            } else {
                foreach ($order_payments as $order_payment) {
                    $order_payment->transaction_id = $payment['id'];
                    $order_payment->save();
                }
            }
            return $payment;
        } catch (Exception $e) {
            XPayGateway::log("Exception result: ".$e->getFile().'['.$e->getLine().']: '.$e->getMessage());
        }
        return false;
    }
    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        if (!isset($this->module->gateway) || !$this->module->gateway || !$this->module->active) {
            Tools::redirect('index');
            exit;
        }
        if (Tools::isSubmit('ipn-xpay')) {
            $id_cart = Tools::getValue('ipn-xpay');
            $input = XPayGateway::getCache('payment_data_c'.$id_cart);
            if ($input) {
                $new_input = $this->api->getTransaction($input['id']);
                if (!$new_input) {
                    throw new \Exception('No se encontro la transaccion...');
                }
                $new_input['gen_transaction_time'] = $input['gen_transaction_time'];
                $new_input['id_cart'] = $input['id_cart'];
                $new_input['id_order'] = $input['id_order'];
                XPayGateway::setCache('payment_data_c'.$id_cart, $new_input, 3600 * 24 * 365);
                $input = $new_input;
                $order = new Order($input['id_order']);
                if ($input['status'] == 'approved') {
                    $order->setCurrentState((int)$this->config['os_authorization']);
                    XPayGateway::log("Result: 'accept' > ".print_r($input, true));
                } elseif ($input[ 'status' ] == 'cancelled' || $input[ 'status' ] == 'rejected') {
                    $order->setCurrentState((int)$this->config['os_refused']);
                    XPayGateway::log("Result: 'reject' > ".print_r($input, true));
                } elseif ($input[ 'status' ] == 'refunded') {
                    $order->setCurrentState((int)Configuration::get('PS_OS_REFUND'));
                    XPayGateway::log("Result: 'refunded' > ".print_r($input, true));
                } else {
                    XPayGateway::log("Result: 'desconocido' > ".print_r($input, true));
                }
            }
            exit;
        }
        if (Tools::isSubmit('iso_crypto') && Tools::isSubmit('exchange')) {
            $cart = $this->context->cart;
            if (!$cart || $cart->getOrderTotal(true, Cart::BOTH) < 0.1) {
                Tools::redirect('index');
                exit;
            }
            if (!$cart || $cart->getOrderTotal(true, Cart::BOTH) < 0.1) {
                Tools::redirect('index');
                exit;
            }
            if ($payment = $this->setAttributesButton($cart)) {
                $customer = new Customer($cart->id_customer);
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.
                                '&id_module='.$this->module->id.
                                '&id_order='.$payment['id_order'].
                                '&key='.$customer->secure_key);
                exit;
            }
            Tools::redirect('index');
            exit;
        }
        echo 'OK';
        exit;
    }
}
