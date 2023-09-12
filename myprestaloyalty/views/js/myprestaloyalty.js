/**
 * PrestaShop module created by VEKIA, a guy from official PrestaShop community ;-)
 *
 * @author    VEKIA https://www.prestashop.com/forums/user/132608-vekia/
 * @copyright 2010-2020 VEKIA
 * @license   This program is not free software and you can't resell and redistribute it
 *
 * CONTACT WITH DEVELOPER
 * support@mypresta.eu
 */

$(document).ready(function () {
    if (prestashop.page.page_name == 'cart') {
        prestashop.on('updatedCart', function (event) {
            $.ajax({
                type: "POST",
                url: myprestaloyaltyurl,
                success: function (data) {
                    $('#loylalty_cart_parent').html(data);
                }
            });
        });
        prestashop.emit('updatedCart');
    }
});