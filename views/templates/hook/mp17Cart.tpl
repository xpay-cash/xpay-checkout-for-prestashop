{*
* Hook - Despliegue de Boton de Pago
*
* @author    Kijam.com <info@kijam.com>
* @copyright 2014 Kijam.com
* @license   MIT
*}
<div class="row" id="is_xpay">
{if !empty($error)}
    <div class="col-xs-12">
        {$error|escape:'htmlall':'UTF-8'}
    </div>
{/if}
    <div class="col-xs-12">
        <div class="payment_module row" style="padding: 10px;margin: 0px;margin-top: 15px;background-color: #fbfbfb;border: 1px solid #dddddd;">
            <form action="{$create_order|escape:'htmlall':'UTF-8'}" method="post">
                <b>{l s='Seleccione una moneda:' mod='xpay'}</b><br />
                <ul>
                {foreach from=$currencies item=currency name=it_currency}
                    <li>
                        <input name="xpay-currency" type="radio" {if $smarty.foreach.it_currency.index == 0}checked{/if}
                                class="xpay-currency"
                                value="{$currency.currency.code|escape:'htmlall':'UTF-8'}-{$currency.exchange|escape:'htmlall':'UTF-8'}"
                        /> - <b>{$currency.currency.name|escape:'htmlall':'UTF-8'}</b>: {$currency.amount|escape:'htmlall':'UTF-8'} {$currency.currency.symbol|escape:'htmlall':'UTF-8'}
                    </li>
                {/foreach}
                </ul>
                <input name="iso_crypto" type="hidden" id="xpay_iso_crypto" />
                <input name="exchange" type="hidden" id="xpay_exchange" />
            </form>
            <script>
                var xpay_jquery = setInterval(function(){
                    if (typeof jQuery == 'undefined') return;
                    clearInterval(xpay_jquery);
                    var $ = jQuery;
                    $('.xpay-currency').change(function(){
                        var v = $('.xpay-currency:checked').val().split('-');
                        $('#xpay_iso_crypto').val(v[0]);
                        $('#xpay_exchange').val(v[1]);
                    }).first().trigger('change');
                    var v = $('.xpay-currency:checked').val().split('-');
                    $('#xpay_iso_crypto').val(v[0]);
                    $('#xpay_exchange').val(v[1]);
                }, 500);
            </script>
        </div>
    </div>
</div>