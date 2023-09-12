{*
*custom page added by Mati
*}


<div id="loyality_cart_summary_parent">
    {if $transformation_allowed > 0}
        <div id="loyality_cart_summary_header">
            <i class="material-icons">attach_money</i>
            <span class="link-item">{l s='Loyality points' mod='myprestaloyalty'}</span>
        </div>

        <div  id="loyality_cart_summary_countup">
            <div class="loyality_cart_summary_countup_row">
                <span><b>{l s='Available points: ' mod='myprestaloyalty'}</b></span>
                <span>{$points}</span>
            </div>

            <div class="loyality_cart_summary_countup_row">
                <span><b>{l s='Point convertion: ' mod='myprestaloyalty'}</b></span>
                <span>{$exchangeRate} {$currency}/{l s='pt' mod='myprestaloyalty'}</span>
            </div>

            <div id="loyality_cart_summary_countup_divdier"></div>

            <div class="loyality_cart_summary_countup_row">
                <span><b>{l s='Voucher of: ' mod='myprestaloyalty'}</b></span>
                <span><b>{Tools::displayPrice($voucher)}</b></span>
            </div>

        </div>


        <a id="loyality_cart_summary_countup_button" href="{Context::getContext()->link->getModuleLink('myprestaloyalty', 'default', ['process' => 'transformpoints'])|escape:'html'}" onclick="return confirm('{l s='Are you sure you want to transform your points into vouchers?' mod='myprestaloyalty' js=1}');">
            {l s='Create voucher' mod='myprestaloyalty'}
        </a>

    {/if}
</div>
