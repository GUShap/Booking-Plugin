const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const nonce = customVars.completing_product_nonce;

$(window).on('load', () => {
    setCheckoutLoader();
    setCheckoutInfoTable();
    setParticipantsDetails();
    setCompletingProductsRemove();
    thankyouPageItems();
});
function setCheckoutLoader() {
    const $loader = $('.blockOverlay');

    const interval = setInterval(() => {
        if (!$.active) {
            clearInterval(interval);
            $loader.remove();
        }
    }, 50);
}
function setCheckoutInfoTable() {
    const $table = $('.shop_table');
    const $rows = $table.find('tr');
    const $productText = $rows.find('td.product-name');

    $productText.each(function () {
        $keys = $(this).find('dt');
        $keys.each(function () {
            $(this).next().andSelf().wrapAll('<div class="item-wrapper"></div>');
        });
    });
}
function setParticipantsDetails() {
    const $secondParticipantConatiner = $('.participants-details-container'),
        $sameDetailsCheckbox = $secondParticipantConatiner.find('input[name="same_details"]');
    $sameDetailsCheckbox.on('change', function () {
        const $emailInput = $(this).closest('.room-participants-wrapper').find('.participant-details-content').not(':first').find('input.participant_email'),
            $phoneInput = $(this).closest('.room-participants-wrapper').find('.participant-details-content').not(':first').find('input.participant_phone');
        if ($(this).is(':checked')) {
            $emailInput.val('').prop({
                'required': false,
                'disabled': true,
                'placeholder':''
            });
            $phoneInput.val('').prop({
                'required': false,
                'disabled': true,
                'placeholder':''
            });
        } else {
            $emailInput.prop({
                'required': true,
                'disabled': false,
                'placeholder':'Email'
            });
            $phoneInput.prop({
                'required': true,
                'disabled': false,
                'placeholder':'Phone Number'
            });

        }
    })
}
function setCompletingProductsRemove() {
    const $removeButton = $('table.shop_table button.remove-upsell');
    $removeButton.on('click', async function (e) {
        e.preventDefault();
        const $this = $(this);
        const upsellID = $this.data('product');
        const cartItemKey = $this.data('key');
        $this.addClass('loading');

        const cartUpdated = await removeCompletingProduct(upsellID, cartItemKey);
        if (cartUpdated) {
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

function thankyouPageItems(){
    $('.woocommerce-order-details table.order_details ul.wc-item-meta li:contains("room_id")').remove();
}