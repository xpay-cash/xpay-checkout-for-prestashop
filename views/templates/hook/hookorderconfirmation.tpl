{*
* Hook - Confirmacion de Orden
*
* @author    Kijam
* @copyright 2020 Kijam
* @license   MIT
*}
{if $xpay && $xpay.status == 'sending'}
    <center>
        {l s='Debes enviar un monto exacto de' mod='xpay'} <b>{$xpay.amount_to_paid|escape:'htmlall':'UTF-8'} {$xpay.currency_to_paid|escape:'htmlall':'UTF-8'}</b> {l s='a la billetera' mod='xpay'}:<br />
        <a href="{$xpay.url|escape:'htmlall':'UTF-8'}" target="_blank">
            <img src="{$xpay.qr_goo|escape:'htmlall':'UTF-8'}" /><br />
            <b>{$xpay.wallet|escape:'htmlall':'UTF-8'}</b>
        </a><br /><br />
        {l s='En menos de' mod='xpay'} <b id="xpay-timer" data-seconds="{$xpay.waiting_time2|escape:'htmlall':'UTF-8'}"></b> {l s='minutos, de lo contrario esta transaccion sera cancelada.' mod='xpay'}<br /><br />
    </center>
    <script>
        var xpaytoreload = 0;
        var xpaytimer = setInterval(function(){
            if (typeof jQuery == 'undefined') return;
            var $ = jQuery;
            var t = $('#xpay-timer');
            if (t.length > 0) {
                var s = t.attr('data-seconds');
                --s;
                t.attr('data-seconds', s);
                if (s < 0) {
                    t.html('EXPIRADO');
                    if (s < -10) {
                        location.reload(true);
                        clearInterval(xpaytimer);
                        return;
                    }
                } else {
                    ++xpaytoreload;
                    if (xpaytoreload > 120) {
                        location.reload(true);
                        clearInterval(xpaytimer);
                        return;
                    }
                    var mm = parseInt(s/60);
                    var ss = s%60;
                    if (mm < 10) mm = '0'+mm;
                    if (ss < 10) ss = '0'+ss;
                    t.html(mm+':'+ss);
                }
            }
        }, 950);
    </script>
{else}
    {if $status == 'ok'}
        <p>{l s='Your order' mod='xpay'} <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span> {l s='was processed successfully.' mod='xpay'}
            <br /><br /><span class="bold">{l s='If you pay for a home delivery order will be sent as soon as possible.' mod='xpay'}</span>
            <br /><br />{l s='For any questions or more information, please contact us.' mod='xpay'} <a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='Customer Service' mod='xpay'}</a>.
        </p>
    {else}
        {if $status == 'pending'}
            <p class="warning">
                {l s='Your payment was processed but is currently in state <b> Pending </b> means that your payment still not been released by your bank.' mod='xpay'}
            </p>
        {else}
            <p class="warning">
                {l s='Apparently occurred a problem with your payment. If you think this is a mistake on our' mod='xpay'} <a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='Customer Service' mod='xpay'}</a>.
            </p>
        {/if}
    {/if}
{/if}
<!-- Modulo desarrollado por Kijam -->
