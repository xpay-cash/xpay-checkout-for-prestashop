<?php
/**
* Modulo XPay
*
* @author    Kijam
* @copyright 2016Kijam
* @license   Comercial
*/

//Libreria requerida para Prestashop 1.7
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class PaymentOptionXPay
{
    public static function getInstance()
    {
        return new PaymentOption();
    }
}
