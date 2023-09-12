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

<p id="loyalty_product" class="align_justify">
    <span class="material-icons">redeem</span><br/>
    {if $points}
        {l s='By buying this product you can collect up to' mod='myprestaloyalty'}
        <b><span id="loyalty_points">{$points}</span>
            {if $points > 1}{l s='loyalty points' mod='myprestaloyalty'}{else}{l s='loyalty point' mod='myprestaloyalty'}{/if}</b>
        .
        {l s='Your cart will contain total' mod='myprestaloyalty'}
        <b><span id="total_loyalty_points">{$total_points}</span>
            {if $total_points > 1}{l s='points' mod='myprestaloyalty'}{else}{l s='point' mod='myprestaloyalty'}{/if}</b>
        {l s='that can be converted into a voucher of' mod='myprestaloyalty'}
        <span id="loyalty_price">{Tools::displayPrice($voucher)}</span>
        .
    {else}
        {if isset($no_pts_discounted) && $no_pts_discounted == 1}
            {l s='No reward points for this product because there\'s already a discount.' mod='myprestaloyalty'}
        {else}
            {l s='No reward points for this product.' mod='myprestaloyalty'}
        {/if}
    {/if}
</p>
<br class="clear"/>