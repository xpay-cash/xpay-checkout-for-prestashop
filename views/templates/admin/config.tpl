{*
* Pagina administrativa
*
* @author    Kijam
* @copyright 2020 Kijam
* @license   Comercial
*}
<form action="" method="post" id="formKMP" enctype="multipart/form-data" class="form-horizontal">
    <ps-panel icon="icon-cogs" header="{$display_name|escape:'htmlall':'UTF-8'}">
    
        <ps-input-text name="client_secret" 
            label="{l s='Token de XPay' mod='xpay'}"
            help="{l s='Token de XPay' mod='xpay'}" size="10" 
            value="{if isset($client_secret)}{$client_secret|escape:'htmlall':'UTF-8'}{/if}"
            required-input="true" 
            hint="{l s='Ingresar Token de XPay' mod='xpay'}"></ps-input-text>
        <ps-select name="xpay_country" label="{l s='País donde opera tu cuenta de XPay' mod='xpay'}">
            {foreach from=$countries item=country}
                <option value="{$country.code|escape:'htmlall':'UTF-8'}" {if isset($xpay_country) and  $country.code == $xpay_country}data-selected="true"{/if}>{$country.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </ps-select>
        <ps-select name="currency_rate" label="{l s='Conversion de Tasas' mod='xpay'}">
            <option value="PS">{l s='Manejada por Prestashop' mod='xpay'}</option>
            <option value="LIVE">{l s='Usar la tasa de conversion de live-rates.com (No aplica a Bolivares)' mod='xpay'}</option>
            <option value="DICOM">{l s='Usar la tasa de conversion oficial (Solo aplica a USD/EUR <-> Bolivares)' mod='xpay'}</option>
            <option value="PROMEDIO">{l s='Usar la tasa de conversion promedio (Solo aplica a USD/EUR <-> Bolivares)' mod='xpay'}</option>
        </ps-select>
        <ps-switch name="currency_rate_default" 
            label="{l s='Aplicar tasa a Prestashop' mod='xpay'}" 
            yes="{l s='Yes' mod='xpay'}" no="{l s='No' mod='xpay'}"
            {if isset($currency_rate_default) && $currency_rate_default}active="true"{/if}></ps-switch>
        
            
        <ps-select name="os_authorization" label="{l s='Accepted payment status' mod='xpay'}">
            {foreach from=$order_states item=val}
                <option value="{$val.id_order_state|escape:'htmlall':'UTF-8'}" {if isset($os_authorization) and $val.id_order_state == $os_authorization}data-selected="true"{/if}>{$val.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </ps-select>
        <ps-select name="os_refused" label="{l s='Error or Invalid payment status' mod='xpay'}">
            {foreach from=$order_states item=val}
                <option value="{$val.id_order_state|escape:'htmlall':'UTF-8'}" {if isset($os_refused) and  $val.id_order_state == $os_refused}data-selected="true"{/if}>{$val.name|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </ps-select>
        <ps-switch name="debug" 
            label="{l s='Actvar Log:' mod='xpay'}"
            yes="{l s='Yes' mod='xpay'}" no="{l s='No' mod='xpay'}"
            {if isset($debug) && $debug}active="true"{/if}></ps-switch>
            
        <ps-label-information label="">
            {l s='Ver log en:' mod='xpay'} <a href='{$log_path_url|escape:'htmlall':'UTF-8'}' target='_blank'>{$log_path|escape:'htmlall':'UTF-8'}</a>
        </ps-label-information>
  
        <ps-panel-footer>
            <ps-panel-footer-submit title="{l s='Save changes' mod='xpay'}" icon="process-icon-save" direction="right" name="submitPanel"></ps-panel-footer-submit>
        </ps-panel-footer>

    </ps-panel>
</form>
