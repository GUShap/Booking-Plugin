const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const nonce = customVars.completing_product_nonce;
const productID = customVars.product_id;

$(document).ready(function () {
    setAddProductToExistingOrder();
    setAddProductToCartItems();
});

function setAddProductToExistingOrder() {
    const $exsitingOrderIdInput = $('#order-number'),
        $checkOrderButton = $('button.check-order-button'),
        $buttonWrapper = $checkOrderButton.parent(),
        $quantityInput = $('input#quantity-existing-order'),
        $addToCartButton = $('button#add-to-existing-order-btn');

    $exsitingOrderIdInput.on('focusout', async function () {
        const orderID = $(this).val();
        const $loader = $('<span class="custom-loader"></span>');
        const $resultMessage = $('<div class="result-message"></div>');
        const $optionWrapper = $(this).closest('.option-wrapper');

        $checkOrderButton.hide();
        $buttonWrapper.append($loader);
        $buttonWrapper.find('.result-message').remove();

        const editableOrder = await checkOrderEditable(orderID);

        $loader.remove();
        $buttonWrapper.append($resultMessage);

        if (editableOrder && editableOrder.is_editable) {
            $resultMessage.html('<span>&#10003;</span>').addClass('success');
            // $addToCartButton.attr('data-order', orderID).prop('disabled', true);
            setItemsCheckboxes($optionWrapper, editableOrder);
            $(this).attr('data-active', 'true');
        } else {
            $resultMessage.html('<span>&#215;</span>').addClass('error');
            $addToCartButton.attr('data-order', '');
            $(this).attr('data-active', 'false');
        }

    });
    $quantityInput.on('input', function () {
        const quantity = $(this).val();
        $addToCartButton.attr('data-quantity', quantity);
    });

    // $addToCartButton.on('click', async function () {
    //     const orderID = $(this).data('order');
    //     const quantity = $(this).data('quantity');
    //     const $loader = $('<span class="custom-loader"></span>');
    //     const $resultMessage = $('<div class="result-message"></div>');

    //     $(this).hide();
    //     $(this).after($loader);

    //     const cartUpdated = await addProductToExistingOrder(orderID, quantity);
    //     $quantityInput.prop('disabled', true);
    //     if (cartUpdated) {
    //         $resultMessage.html('<span>&#10003;</span>').addClass('success');
    //     } else {
    //         $resultMessage.html('<span>&#215;</span>').addClass('error');
    //     }

    //     $loader.remove();
    //     $(this).after($resultMessage);
    // });
}

function checkOrderEditable(orderId) {
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'check_order_editable',
            order_id: orderId,
            product_id: productID,
            security: nonce
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response.data;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}

function setItemsCheckboxes($optionWrapper, orderData) {
    const $roomsListWrapper = $optionWrapper.find('.rooms-list-wrapper'),
        $list = $roomsListWrapper.find('.input-group-wrapper');

    itemsHtml = orderData.items_html;
    itemsHtml.forEach(itemHtml => {
        const $item = $(itemHtml);
        const $quantityInput = $item.find('input.quantity');
        const $addToCartBtn = $item.find('button.add-to-cart');

        $list.append($item);
        $quantityInput.on('change', function () {
            const quantity = $(this).val();
            $addToCartBtn.attr('data-quantity', quantity);
        });
        $addToCartBtn.on('click', async function () {
            const orderID = $(this).data('order');
            const roomID = $(this).data('room');
            const quantity = $(this).attr('data-quantity');

            const $loader = $('<span class="custom-loader"></span>');
            const $resultMessage = $('<div class="result-message"></div>');

            $(this).hide();
            $(this).after($loader);

            const cartUpdated = await addProductToExistingOrder(orderID, roomID, quantity);
            $quantityInput.prop('disabled', true);
            if (cartUpdated) {
                $resultMessage.html('<span>&#10003;</span>').addClass('success');
            } else {
                $resultMessage.html('<span>&#215;</span>').addClass('error');
            }

            $loader.remove();
            $(this).after($resultMessage);
        })

    });
    $roomsListWrapper.attr('data-active', 'true');
}

function addProductToExistingOrder(order_id, room_id, quantity) {
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'add_product_to_existing_order',
            order_id,
            room_id,
            quantity,
            product_id: productID,
            security: nonce
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response.data;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}

function setAddProductToCartItems() {
    const $conatinaer = $('.option-wrapper.cart-option'),
        $optionWrapper = $conatinaer.find('.item-option-wrapper'),
        $quantityInput = $optionWrapper.find('input.quantity'),
        $addToCartButton = $optionWrapper.find('button.add-to-cart');

    $quantityInput.on('input', function () {
        const $siblingAddToCartButton = $(this).siblings('button.add-to-cart');
        const quantity = $(this).val();
        $siblingAddToCartButton.attr('data-quantity', quantity);
    });
    $addToCartButton.on('click', async function () {
        const $siblingQuantityInput = $(this).siblings('input.quantity');
        const cart_item_key = $(this).data('key');
        const quantity = $(this).attr('data-quantity');

        const btnWidth = $(this).width();
        const $loader = $(`<div class="loader-wrapper" style="width:${btnWidth}px"><span class="custom-loader"></span></div>`);
        const $resultMessage = $('<div class="result-message"></div>');

        $(this).hide();
        $(this).after($loader);

        const cartUpdated = await updateCart(cart_item_key, quantity);
        $siblingQuantityInput.prop('disabled', true);
        if (cartUpdated) {
            $resultMessage.html('<span>&#10003;</span>').addClass('success');
        } else {
            $resultMessage.html('<span>&#215;</span>').addClass('error');
        }

        $loader.remove();
        $(this).after($resultMessage);
    });
}

async function updateCart(cart_item_key, quantity) {
    // write ajax request for action 'update_cart', the code passes productID to the server
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'add_product_to_cart_item',
            cart_item_key,
            quantity,
            product_id: productID,
            security: nonce
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response.data;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}
