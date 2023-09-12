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
{if $logedIn}
<div id="loylalty_navbar_parent">

    <a id="loylalty_navbar_points_container" href="{Context::getContext()->link->getModuleLink('myprestaloyalty', 'default', ['process' => 'summary'], true)|escape:'html'}" style="display: inherit;">
        <span>{l s='Loyalty points' mod='myprestaloyalty'}:&nbsp;</span>
        {if $points}
            {$points}
        {else}
            0
        {/if}

    </a>

</div>
{/if}