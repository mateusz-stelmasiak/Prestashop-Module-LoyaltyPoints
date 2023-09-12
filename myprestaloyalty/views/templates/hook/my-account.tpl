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


<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="discounts-link" href="{Context::getContext()->link->getModuleLink('myprestaloyalty', 'default', ['process' => 'summary'], true)|escape:'html'}" title="{l s='My loyalty points' mod='myprestaloyalty'}" rel="nofollow">
    <span class="link-item"> <i class="material-icons">attach_money</i>{l s='My loyalty points' mod='myprestaloyalty'}</span>
</a>