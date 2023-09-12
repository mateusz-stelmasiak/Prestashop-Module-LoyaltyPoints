{*
* PrestaShop module created by VEKIA, a guy from official PrestaShop community ;-)
*
* @author    VEKIA https://www.prestashop.com/forums/user/132608-vekia/
* @copyright 2010-2020 VEKIA
* @license   This program is not free software and you can't resell and redistribute it
*
* CONTACT WITH DEVELOPER
* support@mypresta.eu
*}

{if Tools::getValue('ajax')!=1}<div id="loylalty_cart_parent">{/if}
    <p id="loyalty_cart">

        <span class="material-icons">redeem</span><br/>
        {if $points > 0}
            {l s='By checking out this shopping cart you can collect up to' mod='myprestaloyalty'}
            <b>
                {if $points > 1}
                    {$points} {l s='loyalty points' mod='myprestaloyalty'}
                {else}
                    {$points} {l s='loyalty point' mod='myprestaloyalty'}
                {/if}
            </b>
            {l s='that can be converted into a voucher of' mod='myprestaloyalty'} {Tools::displayPrice($voucher)}{if isset($guest_checkout) && $guest_checkout}<sup>*</sup>{/if}.
            <br/>
        {else}
            {l s='Add some products to your shopping cart to collect some loyalty points.' mod='myprestaloyalty'}
        {/if}
    </p>
{if Tools::getValue('ajax')!=1}</div>{/if}