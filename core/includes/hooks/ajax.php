<?php

function update_retreat_product_data_callback()
{
    // Get data from AJAX request
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
    $prev_departure_date = isset($_POST['prev_departure_date']) ? $_POST['prev_departure_date'] : '';
    // Your logic to update product datas
    if ($retreat_id && $departure_date) {
        $retreat_product_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true)) ? get_post_meta($retreat_id, 'retreat_product_data', true) : [];
        // Add/update data for the selected date
        if (!empty($prev_departure_date)) {
            if ($prev_departure_date != $departure_date) {
                $retreat_product_data['departure_dates'][$departure_date] = $retreat_product_data['departure_dates'][$prev_departure_date];
                unset($retreat_product_data['departure_dates'][$prev_departure_date]);
            }
            foreach ($_POST as $key => $value) {
                if (isset($retreat_product_data['departure_dates'][$departure_date][$key])) {
                    if ($key == 'rooms_list') {
                        $prev_rooms_data = $retreat_product_data['departure_dates'][$departure_date][$key];
                        $rooms_data = get_rooms_data($value, $prev_rooms_data, $retreat_product_data['rooms'], $departure_date);
                        $booked_rooms = array_filter($rooms_data, function ($room) {
                            return isset ($room['is_booked']) && $room['is_booked'] === true;
                        });
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $rooms_data;
                        $retreat_product_data['departure_dates'][$departure_date]['rooms_availability'] = count($rooms_data) - count($booked_rooms);
                    } else if ($key == 'max_participants') {
                        $number_of_guests = count(get_departure_date_guests_list($retreat_id, $departure_date));
                        $retreat_product_data['departure_dates'][$departure_date]['guests_availability'] = $value - $number_of_guests;
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $value;
                    } else if ($key == 'registration_active') {
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $value == 'true';
                        $retreat_product_data['departure_dates'][$departure_date]['status_tags'][] = 'active';
                    } else {
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $value;
                        $key = array_search('active', $retreat_product_data['departure_dates'][$departure_date]['status_tags']);
                        unset($retreat_product_data['departure_dates'][$departure_date]['status_tags'][$key]);
                    }
                }
            }
        } else {
            $rooms_data = get_rooms_data($_POST['rooms_list'], [], $retreat_product_data['rooms'], $departure_date);
            $retreat_product_data['departure_dates'][$departure_date] = array(
                'is_available' => true,
                'is_full_booked' => '',
                'registration_active' => true,
                'max_participants' => $_POST['max_participants'],
                'rooms_availability' => count($rooms_data),
                'rooms_list' => $rooms_data,
                'guests_availability' => $_POST['max_participants'],
                'waitlist' => array(),
                'expired_reservations' => array(),
                'status_tags' => ['running'],
                'retreat_id' => $retreat_id
            );
        }

        // sort by date
        ksort($retreat_product_data['departure_dates']);
        // Update product metadata
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_product_data);
    }

    // Send a response back to the AJAX request
    wp_send_json_success($retreat_product_data);
    wp_die();
}
add_action('wp_ajax_update_retreat_product_data', 'update_retreat_product_data_callback');

function remove_retreat_departure_date_callback()
{
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';

    if ($retreat_id && $departure_date) {
        $retreat_product_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true)) ? get_post_meta($retreat_id, 'retreat_product_data', true) : [];
        if (isset($retreat_product_data['departure_dates'][$departure_date])) {
            unset($retreat_product_data['departure_dates'][$departure_date]);
        }
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_product_data);
    }
    wp_send_json_success($retreat_product_data);
    wp_die();
}
add_action('wp_ajax_remove_retreat_departure_date', 'remove_retreat_departure_date_callback');

function get_available_rooms_callback()
{
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'rooms_nonce')) {

        $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
        $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $rooms_data = get_available_rooms_for_date($retreat_id, $departure_date);

        wp_send_json_success($rooms_data);
    } else {
        wp_send_json_error();
    }
    wp_die();
}
add_action('wp_ajax_get_available_rooms', 'get_available_rooms_callback');
add_action('wp_ajax_nopriv_get_available_rooms', 'get_available_rooms_callback');

function add_retreat_to_cart_callback()
{
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'add_to_cart_nonce')) {
        $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
        $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

        if (empty($retreat_id) || empty($departure_date) || empty($room_id)) {
            wp_send_json_error();
        }

        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $is_booked = !empty($retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['is_booked']);

        if ($is_booked) {
            wp_send_json_error();
        }

        $cart_item_data = [];
        foreach ($_POST as $key => $value) {
            if ($key !== 'nonce' && $key !== 'action') {
                $cart_item_data[$key] = $value;
            }
            if ($key == 'additional') {
                foreach ($value as $upsell_id => $quantity) {
                    $upsell_product = wc_get_product($upsell_id);
                    $categories_id = $upsell_product->get_category_ids();
                    foreach ($categories_id as $category_id) {
                        $slug = get_term_by('id', $category_id, 'product_cat')->slug;
                        if ($slug == 'second-participant') {
                            $cart_item_data['is_second_participant'] = true;
                            break;
                        }
                    }
                }
            }
        }
        WC()->cart->add_to_cart($retreat_id, 1, 0, array(), $cart_item_data);
        $redirect_after_atc_id = get_option('redirect_after_atc', get_the_id());
        $redirect_url = get_permalink($redirect_after_atc_id);
        $res = [
            'redirect_url' => $redirect_url,
            'room_id' => $room_id,
            'room_name' => get_the_title($room_id),
            'departure_date' => $departure_date
        ];

        WC()->session->set('retreat_id', $retreat_id);
        WC()->session->set('departure_date', $departure_date);
        WC()->session->set('is_retreat', true);

        wp_send_json_success($res);
    } else {
        wp_send_json_error();
    }
    wp_die();
}
add_action('wp_ajax_add_retreat_to_cart', 'add_retreat_to_cart_callback');
add_action('wp_ajax_nopriv_add_retreat_to_cart', 'add_retreat_to_cart_callback');


function process_update_booking_options()
{
    // Check if the user has the necessary capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Permission Denied');
    }

    $days_before_deposit_disabled = isset($_POST['days_before_deposit_disabled']) ? $_POST['days_before_deposit_disabled'] : '';
    $days_after_departure_to_archive_date = isset($_POST['days_after_departure_to_archive_date']) ? $_POST['days_after_departure_to_archive_date'] : '';
    $redirect_after_atc_id = isset($_POST['redirect_after_atc_id']) ? sanitize_text_field($_POST['redirect_after_atc_id']) : '';
    $deposit_info_page_id = isset($_POST['deposit_info_page_id']) ? sanitize_text_field($_POST['deposit_info_page_id']) : '';
    $deposit_per_cent = isset($_POST['deposit_per_cent']) ? sanitize_text_field($_POST['deposit_per_cent']) : '';
    // Get the posted stripe_secret_key and stripe_test_publishable_key from $_POST
    $stripe_test_secret_key = isset($_POST['stripe_test_secret_key']) ? sanitize_text_field($_POST['stripe_test_secret_key']) : '';
    $stripe_test_publishable_key = isset($_POST['stripe_test_publishable_key']) ? sanitize_text_field($_POST['stripe_test_publishable_key']) : '';

    $stripe_live_secret_key = isset($_POST['stripe_live_secret_key']) ? sanitize_text_field($_POST['stripe_live_secret_key']) : '';
    $stripe_live_publishable_key = isset($_POST['stripe_live_publishable_key']) ? sanitize_text_field($_POST['stripe_live_publishable_key']) : '';

    $is_live_mode = isset($_POST['is_live_mode']) && !empty($_POST['is_live_mode']);

    // Hash keys before saving (if needed)
    $encrypted_stripe_test_secret_key = encrypt_data($stripe_test_secret_key);
    $encrypted_stripe_live_secret_key = encrypt_data($stripe_live_secret_key);
    $response = [
        'status' => 'failed',
        'message' => 'Failed to save stripe keys'
    ];
    update_option('deposit_per_cent', $deposit_per_cent);
    update_option('days_before_deposit_disabled', $days_before_deposit_disabled);
    update_option('days_after_departure_to_archive_date', $days_after_departure_to_archive_date);
    update_option('redirect_after_atc', $redirect_after_atc_id);
    update_option('deposit_info_page', $deposit_info_page_id);
    // Save the hashed value as an option
    update_option('hashed_stripe_test_secret_key', $encrypted_stripe_test_secret_key);
    update_option('hashed_stripe_live_secret_key', $encrypted_stripe_live_secret_key);
    // Save the unhashed value as an option
    update_option('stripe_test_publishable_key', $stripe_test_publishable_key);
    update_option('stripe_live_publishable_key', $stripe_live_publishable_key);

    update_option('is_stripe_live_mode', $is_live_mode);
    // Return a response (optional)
    $response = [
        'status' => 'success',
        'message' => 'data successfully'
    ];
    wp_send_json($response);
    // Always exit to prevent extra output
    wp_die();
}
// Hook for processing Ajax form submission
add_action('wp_ajax_update_booking_options', 'process_update_booking_options');

// In your theme's functions.php or a custom plugin file
function upload_product_qr_file_callback()
{
    // Get the SVG content, target, and product_id from the AJAX request
    $target = sanitize_text_field($_POST['target']);
    $product_id = intval($_POST['product_id']);
    $file = $_FILES['qr_file'];

    $upload_result = upload_qr_image_file_handle($file, $target, $product_id);

    if (!is_wp_error($upload_result)) {
        $image_url = $upload_result['url'];
        update_post_meta($product_id, $target . '_qr_image_url', $image_url);
        wp_send_json_success(array('file_url' => $image_url));
    }
}
add_action('wp_ajax_upload_product_qr_file', 'upload_product_qr_file_callback');

function send_order_emails_callback()
{
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order = wc_get_order($order_id);
    $action = isset($_POST['email_action']) ? sanitize_text_field($_POST['email_action']) : '';

    order_custom_email($order, $action);

    wp_send_json_success('success');
    wp_die();

}
add_action('wp_ajax_send_order_emails', 'send_order_emails_callback');

function check_order_editable_callback()
{
    // check nonce of $_POST['security']
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'completing_product_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    if (!isset($_POST['order_id'])) {
        wp_send_json_error('Invalid order id');
    }

    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : 0;
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : 0;
    // $is_second_participant = has_term('second-participant', 'product_cat', $product_id);
    $is_editable = check_order_editable($order_id, $product_id);
    $items_html = [];
    if ($is_editable) {
        $order = wc_get_order($order_id);
        $rooms = $order->get_meta('rooms');
        if (!empty($rooms)) {
            $enable_multiple_items = !empty(get_post_meta($product_id, '_enable_multiple_items', true));
            $limit_quantity = !empty(get_post_meta($product_id, '_limit_quantity', true)) || !$enable_multiple_items;
            $max_items_limit = get_post_meta($product_id, '_max_items_limit', true);
            foreach ($rooms as $room) {
                $allowed_quantity = $limit_quantity ? $max_items_limit : 9999;
                if (!$enable_multiple_items)
                    $allowed_quantity = 1;

                $room_id = $room['room_id'];
                $additional = $room['additional'];
                // $second_participant_available = true;

                foreach ($additional as $upsell_id => $quantity) {
                    if ($upsell_id == $product_id)
                        $allowed_quantity -= $quantity;
                }
                $is_disabled = $limit_quantity && $allowed_quantity <= 0;
                $disabled_str = $is_disabled ? 'disabled' : '';
                $html_str = '<div class="item-option-wrapper">';
                $html_str .= '<div class="input-wrapper type-checkbox room-item-wrapper">';
                $html_str .= '<input type="checkbox" name="room[' . $room_id . ']" id="order-' . $order_id . '-' . $room_id . '" data-order="' . $order_id . '" data-room="' . $room_id . '" value="1" ' . $disabled_str . ' >';
                $html_str .= '<label for="order-' . $order_id . '-' . $room_id . '"><strong>' . get_the_title($room_id) . '</strong> on order #' . $order_id . '</label>';
                $html_str .= '</div>';

                if (!$is_disabled) {
                    $html_str .= '<div class="quantity-atc-wrapper">';
                    if ($enable_multiple_items && $allowed_quantity > 0) {
                        $html_str .= '<input type="number" name="quantity" class="quantity" id="quantity-' . $room_id . '" value="1" min="1" max="' . $allowed_quantity . '">';
                    }
                    $html_str .= '<button class="add-to-cart" data-source="order" data-room="' . $room_id . '" data-order="' . $order_id . '" data-quantity="1">Add to Cart</button>';
                    $html_str .= '</div>';
                    $html_str .= '</div>';
                }
                $items_html[] = $html_str;
            }
        }
    }
    $res = [
        'is_editable' => $is_editable,
        'items_html' => $items_html
    ];
    wp_send_json_success($res);
    wp_die();
}
add_action('wp_ajax_check_order_editable', 'check_order_editable_callback');
add_action('wp_ajax_nopriv_check_order_editable', 'check_order_editable_callback');

function add_product_to_cart_item_callback()
{
    // add security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'completing_product_nonce')) {
        wp_send_json_error('Invalid security token');
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $cart_item_key = $_POST['cart_item_key'];
    $item = WC()->cart->get_cart_item($cart_item_key);
    $retreat_id = $item['retreat_id'];
    $is_second_participant_prod = has_term('second-participant', 'product_cat', $product_id);
    $additional = $item['additional'];
    if ($quantity) {
        $additional[$product_id] = $quantity;
        if ($is_second_participant_prod)
            $item['is_second_participant'] = true;
    } else {
        unset($additional[$product_id]);
        //  check if product has category second-participant
        if ($is_second_participant_prod)
            $item['is_second_participant'] = false;
    }

    $new_item_data = [
        'retreat_id' => $retreat_id,
        'room_id' => $item['room_id'],
        'departure_date' => $item['departure_date'],
        'room_price' => $item['room_price'],
        'additional' => $additional,
        'is_second_participant' => $item['is_second_participant']
    ];
    // create new cart item with identicle to current $item, and delete the old one
    WC()->cart->add_to_cart($retreat_id, 1, 0, array(), $new_item_data);
    WC()->cart->remove_cart_item($cart_item_key);

    wp_send_json_success('true');
    wp_die();

}
add_action('wp_ajax_add_product_to_cart_item', 'add_product_to_cart_item_callback');
add_action('wp_ajax_nopriv_add_product_to_cart_item', 'add_product_to_cart_item_callback');

function add_product_to_existing_order_callback()
{
    // add security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'completing_product_nonce')) {
        wp_send_json_error('Invalid security token');
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = intval($_POST['quantity']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $item_data = [
        'order_id' => $order_id,
        'room_id' => $room_id,
    ];

    if ($order_id) {
        WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $item_data);
        wp_send_json_success('true');
    } else {
        wp_send_json_error('Product not found in order');
    }
    wp_die();


}
add_action('wp_ajax_add_product_to_existing_order', 'add_product_to_existing_order_callback');
add_action('wp_ajax_nopriv_add_product_to_existing_order', 'add_product_to_existing_order_callback');