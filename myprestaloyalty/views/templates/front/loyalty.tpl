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


{extends file='customer/page.tpl'}
{block name='page_title'}
    {l s='My loyalty points' mod='myprestaloyalty'}
{/block}

{block name="page_content"}
    {if $orders}
        <div class="block-center">
            {if $orders && count($orders)}
                <table class="table table-bordered table-active">
                    <thead class="thead-inverse">
                    <tr>
                        <th class="first_item">{l s='Order' mod='myprestaloyalty'}</th>
                        <th class="item">{l s='Date' mod='myprestaloyalty'}</th>
                        <th class="item">{l s='Points' mod='myprestaloyalty'}</th>
                        <th class="last_item">{l s='Points Status' mod='myprestaloyalty'}</th>
                    </tr>
                    </thead>
                    <tfoot>
                    <tr class="alternate_item">
                        <td colspan="2" class="history_method bold" style="text-align:center;">{l s='Total points available:' mod='myprestaloyalty'}</td>
                        <td class="history_method" style="text-align:left;">{$totalPoints|intval}</td>
                        <td class="history_method">&nbsp;</td>
                    </tr>
                    </tfoot>
                    <tbody>
                    {foreach from=$displayorders item='order'}
                        <tr class="alternate_item">
                            <td class="history_link bold">{l s='#' mod='myprestaloyalty'}{$order.reference}</td>
                            <td class="history_date">{dateFormat date=$order.date full=1}</td>
                            <td class="history_method">{$order.points|intval}</td>
                            <td class="history_method">{$order.state|escape:'html':'UTF-8'}</td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                <div id="block-order-detail" class="hidden">&nbsp;</div>
            {else}
                <p class="warning">{l s='You have not placed any orders.' mod='myprestaloyalty'}</p>
            {/if}
        </div>
        <div class="bottom-pagination-content clearfix">
            <div id="pagination" class="pagination clearfix">
                {if $orders|@count > $nbpagination}
                    <form class="showall" action="{$pagination_link}" method="get">
                        <div>
                            <button type="submit" class="btn btn-default button exclusive-medium">
                                <span>{l s='Show all' mod='myprestaloyalty'}</span>
                            </button>
                            <input name="n" id="nb_item" class="hidden" value="{$orders|@count}"/>
                            <input name="process" class="hidden" value="summary"/>
                        </div>
                    </form>
                {/if}
            </div>
            <div class="product-count">
                {assign var='itemShowing' value=99999}
                {assign var='itemShowingStart' value=1}
            </div>
        </div>
        <br/>
        <h2>{l s='My vouchers from loyalty points' mod='myprestaloyalty'}</h2>
        {l s='Vouchers generated here are usable in the following categories : ' mod='myprestaloyalty'}
        {if $categories}{$categories}{else}{l s='All' mod='myprestaloyalty'}{/if}
        <div>
            {if $transformation_allowed}
                <p style="text-align:center; margin-top:20px">
                    <a href="{Context::getContext()->link->getModuleLink('myprestaloyalty', 'default', ['process' => 'transformpoints'])|escape:'html'}" onclick="return confirm('{l s='Are you sure you want to transform your points into vouchers?' mod='myprestaloyalty' js=1}');">{l s='Transform my points into a voucher of' mod='myprestaloyalty'} <span class="price">{Tools::displayPrice($voucher)}</span>.</a>
                </p>
            {/if}
            {if $nbDiscounts}
                <div class="block-center" id="block-history">
                    <table class="table table-success table-bordered">
                        <thead class="thead-inverse">
                        <tr>
                            <th class="first_item">{l s='Created' mod='myprestaloyalty'}</th>
                            <th class="item">{l s='Value' mod='myprestaloyalty'}</th>
                            <th class="item">{l s='Code' mod='myprestaloyalty'}</th>
                            <th class="item">{l s='Valid from' mod='myprestaloyalty'}</th>
                            <th class="item">{l s='Valid until' mod='myprestaloyalty'}</th>
                            <th class="item">{l s='Status' mod='myprestaloyalty'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$discounts item=discount name=myLoop}
                            <tr class="alternate_item">
                                <td class="history_date">{dateFormat date=$discount->date_add}</td>
                                <td class="history_price"><span class="price">{if $discount->reduction_percent > 0}
						{$discount->reduction_percent}%
					{elseif $discount->reduction_amount}
						{Tools::displayPrice($discount->reduction_amount)}
					{else}
						{l s='Free shipping' mod='myprestaloyalty'}
					{/if}</span></td>
                                <td class="history_method bold">{$discount->code}</td>
                                <td class="history_date">{dateFormat date=$discount->date_from}</td>
                                <td class="history_date">{dateFormat date=$discount->date_to}</td>
                                <td class="history_method bold">{if $discount->quantity > 0}{l s='Ready to use' mod='myprestaloyalty'}{else}{l s='Already used' mod='myprestaloyalty'}{/if}</td>

                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info">
                    {if $minimalLoyalty > 0}<p>{l s='The minimum order amount in order to use these vouchers is:' mod='myprestaloyalty'} {Tools::displayPrice($minimalLoyalty)}</p>{/if}
                </div>
            {else}
                <p class="alert alert-warning">{l s='No vouchers yet.' mod='myprestaloyalty'}</p>
            {/if}
        </div>
    {else}
        <p class="alert alert-warning">{l s='No reward points yet.' mod='myprestaloyalty'}</p>
    {/if}
{/block}
