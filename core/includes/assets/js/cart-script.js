const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const nonce = customVars.completing_product_nonce;


$(document).ready(function () {
    setCompletingProductsRemove();
});

function setCompletingProductsRemove() {
    const $removeButton = $('form.woocommerce-cart-form button.remove-upsell');
    $removeButton.on('click', async function (e) {
        e.preventDefault();
        const $this = $(this);
        const upsellID = $this.data('product');
        const cartItemKey = $this.data('key');
        $this.addClass('loading');

        const cartUpdated = await removeCompletingProduct(upsellID, cartItemKey);
        if(cartUpdated){
            window.location.reload();
        }
    });
}

async function removeCompletingProduct(product_id, cart_item_key) {
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'add_product_to_cart_item',
            cart_item_key,
            quantity: 0,
            product_id,
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
