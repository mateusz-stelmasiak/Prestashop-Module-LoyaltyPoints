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

{extends file="helpers/form/form.tpl"}
{block name="field"}
    {if $input.type == 'desc'}
        <div class="alert alert-info">{$input.text}</div>
    {/if}
    {$smarty.block.parent}
{/block}

{block name="input"}

    {if $input.type == 'switch' && $smarty.const._PS_VERSION_|@addcslashes:'\'' < '1.6'}
        {foreach $input.values as $value}
            <input type="radio" name="{$input.name}" id="{$value.id}" value="{$value.value|escape:'html':'UTF-8'}"
                   {if $fields_value[$input.name] == $value.value}checked="checked"{/if}
                    {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if} />
            <label class="t" for="{$value.id}">
                {if isset($input.is_bool) && $input.is_bool == true}
                    {if $value.value == 1}
                        <img src="../img/admin/enabled.gif" alt="{$value.label}" title="{$value.label}"/>
                    {else}
                        <img src="../img/admin/disabled.gif" alt="{$value.label}" title="{$value.label}"/>
                    {/if}
                {else}
                    {$value.label}
                {/if}
            </label>
            {if isset($input.br) && $input.br}<br/>{/if}
            {if isset($value.p) && $value.p}<p>{$value.p}</p>{/if}
        {/foreach}
    {else}
        {$smarty.block.parent}
    {/if}

{/block}