{*
* Hook - Despliegue descripcion del pago
*
* @author    Kijam
* @copyright 2020 Kijam
* @license   MIT
*}
{if $backwardcompatible}
    <fieldset id="xpay_status">
        <legend><img src="../img/admin/money.gif">{l s='XPay' mod='xpay'}</legend>
        <b>{l s='Order ID' mod='xpay'}: </b> {$order_id|escape:'htmlall':'UTF-8'}<br />
        <b>{l s='XPay ID' mod='xpay'}: </b> {$xpay_data.id|escape:'htmlall':'UTF-8'}<br />
        <b>{l s='XPay Status' mod='xpay'}: </b> {$xpay_data.status|escape:'htmlall':'UTF-8'}<br />
        <b>{l s='Monto Cobrado' mod='xpay'}: </b> {$xpay_data.string_amount_to_change|escape:'htmlall':'UTF-8'}<br />
        <b>{l s='Monto Pagado' mod='xpay'}: </b> {$xpay_data.string_amount_to_paid|escape:'htmlall':'UTF-8'}<br />
        <b>{l s='Monto recibido por el comercio en moneda Fiat' mod='xpay'}: </b> {$xpay_data.string_fiat_amount_to_commerce|escape:'htmlall':'UTF-8'}<br />
        <b>{l s='Monto recibido por el comercio en Criptomoneda' mod='xpay'}: </b> {$xpay_data.string_crypto_amount_to_commerce|escape:'htmlall':'UTF-8'}<br />
    </fieldset>
{else}
    <div class="row" id="xpay_status">
        <div class="col-lg-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-credit-card"></i>
                     {l s='XPay' mod='xpay'}
                </div>
                <div class="well">
                    <b>{l s='Order ID' mod='xpay'}: </b> {$order_id|escape:'htmlall':'UTF-8'}<br />
                    <b>{l s='XPay ID' mod='xpay'}: </b> {$xpay_data.id|escape:'htmlall':'UTF-8'}<br />
                    <b>{l s='XPay Status' mod='xpay'}: </b> {$xpay_data.status|escape:'htmlall':'UTF-8'}<br />
                    <b>{l s='Monto Cobrado' mod='xpay'}: </b> {$xpay_data.string_amount_to_change|escape:'htmlall':'UTF-8'}<br />
                    <b>{l s='Monto Pagado' mod='xpay'}: </b> {$xpay_data.string_amount_to_paid|escape:'htmlall':'UTF-8'}<br />
                    <b>{l s='Monto recibido por el comercio en moneda Fiat' mod='xpay'}: </b> {$xpay_data.string_fiat_amount_to_commerce|escape:'htmlall':'UTF-8'}<br />
                    <b>{l s='Monto recibido por el comercio en Criptomoneda' mod='xpay'}: </b> {$xpay_data.string_crypto_amount_to_commerce|escape:'htmlall':'UTF-8'}<br />
                </div>
            </div>
        </div>
    </div>
{/if}
<script>
    $('#xpay_status').prependTo($('#xpay_status').parent()); 
</script>
