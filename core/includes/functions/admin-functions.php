<?php
function is_booking_retreat_product_page()
{
    // Check if we are on a single product page
    if (is_product()) {
        // Get the current product ID
        $product_id = get_the_ID();
        // Check if the product is of type 'booking_retreat'
        $product = wc_get_product($product_id);
        if ($product && $product->get_type() === 'booking_retreat') {
            return true;
        }
    }

    return false;
}
function get_rooms_data($departure_rooms_data = [], $prev_departure_rooms_data, $general_rooms_data, $departure_date)
{
    $data = [];
    if (!empty ($departure_rooms_data)) {
        foreach ($departure_rooms_data as $room_id => $room_data) {
            if (array_key_exists($room_id, $prev_departure_rooms_data)) {
                $data[$room_id] = $prev_departure_rooms_data[$room_id];
                if (!isset ($data[$room_id]['price'])) {
                    $data[$room_id]['price'] = $general_rooms_data[$room_id];
                }
            } else {
                $room_metadata = get_post_meta($room_id, 'package_product_data', true);
                $data[$room_id] = $room_data;
                $data[$room_id]['is_booked'] = false;
                $data[$room_id]['guests'] = [];
                $data[$room_id]['price'] = $general_rooms_data[$room_id];
                $data[$room_id]['expired_orderes_ids'] = [];
                $data[$room_id]['status'] = 'available';
                $data[$room_id]['payments_collected'] = 0;
                $data[$room_id]['room_capacity'] = $room_metadata['max_room_capacity'];
            }
        }
    }
    return $data;
}

function format_date_range($departure_date, $duration)
{
    // Create DateTime object from the departure date
    $start_date = new DateTime($departure_date);

    // Calculate end date based on duration
    $end_date = clone $start_date;
    $end_date->modify('+' . ($duration - 1) . ' days');

    // Format dates
    $start_month = $start_date->format('F');
    $start_day = $start_date->format('j');
    $end_month = $end_date->format('F');
    $end_day = $end_date->format('j');

    // Check if the end date is in a different month
    $date_range = ($start_month === $end_month)
        ? "$start_month $start_day - $end_day, " . $end_date->format('Y')
        : "$start_month $start_day - $end_month $end_day, " . $end_date->format('Y');

    return $date_range;
}

// Function to calculate remaining days
function calculate_remaining_days($departure_date)
{
    // Create DateTime objects for today and the departure date
    $today = new DateTime();
    $departure = new DateTime($departure_date);

    // Calculate the difference in days
    $interval = $today->diff($departure);

    // Get the remaining days as an integer
    $remaining_days = intval($interval->format('%r%a'));

    return $remaining_days;
}

function has_date_passed($date)
{
    // Get the current date
    $current_date = date('Y-m-d');

    // Compare the dates
    return ($current_date > $date);
}

function calculate_time_left_with_html($start_time, $end_time)
{
    // Convert the time strings to DateTime objects
    $startDateTime = strtotime($start_time);
    $endDateTime = strtotime($end_time);

    // Calculate the interval between the two dates
    $timeDifference = $endDateTime - $startDateTime;
    // dd(strtotime($start_time));
    // Format the result
    $hours = $timeDifference > 0 ? floor($timeDifference / 3600) : '00';
    $minutes = $timeDifference > 0 ? floor(($timeDifference % 3600) / 60) : '00';
    $seconds = $timeDifference > 0 ? $timeDifference % 60 : '00';

    // Return the formatted result with HTML
    return "<span class='hours'>$hours</span>:<span class='minutes'>$minutes</span>:<span class='seconds'>$seconds</span>";
}

function get_reservations_by_status($status)
{
    // Your code to retrieve reservations by status
    return wc_get_orders(
        array(
            'status' => $status, // Leave empty to get orders regardless of status
            'limit' => -1,  // Set to -1 to retrieve all orders
        )
    ); // Placeholder, replace with your logic
}

function array_every(array $array, callable $callback)
{
    foreach ($array as $element) {
        if (!$callback($element)) {
            return false;
        }
    }
    return true;
}

function is_room_booked($room)
{
    return isset ($room['is_booked']) && $room['is_booked'] == true;
}

function get_pages_ids()
{
    $args = [
        'post_type' => 'page',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function get_all_rooms_ids()
{
    $args = [
        'post_type' => 'retreat_rooms',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function get_all_retreats_ids()
{
    $args = array(
        'status' => 'publish', // You can change this to 'draft' or 'pending' if needed
        'type' => 'booking_retreat',
        'limit' => -1, // Set to -1 to get all products
        'return' => 'ids', // Return only ids
        'order' => 'ASC',

    );
    return wc_get_products($args);

}

function get_all_retreat_messages_ids()
{
    $args = [
        'post_type' => 'retreat_messages',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function create_full_payment_link_object($order_id)
{
    $order = wc_get_order($order_id);
    $is_live_mode = get_option('is_stripe_live_mode', '');
    $hssk = $is_live_mode ? get_option('hashed_stripe_live_secret_key', '') : get_option('hashed_stripe_test_secret_key', '');
    $ssk = decrypt_data($hssk);
    $stripe = new \Stripe\StripeClient($ssk);
    $room_id = $order->get_meta('room_id');
    $price_object = create_price_object($stripe, $order);
    $order_nonce = $order->get_meta('deposit_nonce');
    return $stripe->paymentLinks->create([
        'line_items' => [
            [
                'price' => $price_object->id,
                'quantity' => 1,
            ],
        ],
        'custom_text' => [
            'submit' => [
                'message' => 'Complete Reservation payment for ' . get_the_title($room_id) . ' Room'
            ],
        ],
        'restrictions' => [
            'completed_sessions' => ['limit' => 1]
        ],
        'submit_type' => 'book',
        'after_completion' => [
            'type' => 'redirect',
            'redirect' => ['url' => home_url('reservation-confirmation/?order_id=' . $order_id . '&key=' . $order_nonce . '&init_action=full_payment')],
        ],
    ]);
}

function deactivate_payment_link($payment_link_id)
{
    $stripe = new \Stripe\StripeClient('sk_test_51KPwm8CeIWmGIaSJOCL08FC3hzpnHKJKkx7VJvFapbn0nhPUmahVQNfn2YUDc6NTHsWUWEQKrqx9cdVFKmyM0kWh00qvWNWDHx');
    $stripe->paymentLinks->update($payment_link_id, [
        'active' => false,
    ]);
}
function create_price_object($stripe, $order)
{
    $retreat_id = $order->get_meta('retreat_id');
    $remaining_payment = $order->get_meta('remaining_payment');
    return $stripe->prices->create([
        'currency' => 'usd',
        'unit_amount' => $remaining_payment * 100,
        'product_data' => ['name' => get_the_title($retreat_id)],
    ]);
}

function get_departure_date_guests_list($retreat_id, $departure_date)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $guests = [];
    foreach ($retreat_data['departure_dates'][$departure_date]['rooms_list'] as $room_id => $room_data) {
        // dd($room_data);
        if (isset ($room_data['guests'])) {
            foreach ($room_data['guests'] as $idx => $guest) {
                // $room_data['guests'][$idx]['order_id'] = $room_data['order_id'];
                if (empty ($room_data['guests'][$idx]['room_id'])) {
                    $room_data['guests'][$idx]['room_id'] = $room_id;
                }
            }
            $guests = array_merge($guests, $room_data['guests']);
        }
    }
    return $guests;
}

// Function to encrypt data
function encrypt_data($data)
{
    $method = 'aes-256-cbc';

    $iv_length = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($iv_length);

    $encrypted_data = openssl_encrypt($data, $method, SK_EK, 0, $iv);

    return base64_encode($iv . $encrypted_data);
}

// Function to decrypt data
function decrypt_data($data)
{
    $method = 'aes-256-cbc';

    $data = base64_decode($data);
    $iv_length = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $iv_length);
    $encrypted_data = substr($data, $iv_length);

    return openssl_decrypt($encrypted_data, $method, SK_EK, 0, $iv);
}

function get_decrypted_stripe_secret_key()
{
    // Get the encrypted stripe_secret_key from options
    $encrypted_stripe_secret_key = get_option('encrypted_stripe_secret_key', '');

    // Return the decrypted value if it exists
    return $encrypted_stripe_secret_key ? wp_decrypt($encrypted_stripe_secret_key, SK_EK) : '';
}

function schedule_retreat_email($order_id)
{
    $order = wc_get_order($order_id);
    $retreat_id = $order->get_meta('retreat_id');
    $room_id = $order->get_meta('room_id');
    $departure_date = $order->get_meta('departure_date');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $guests = $order->get_meta('guests');

    foreach ($guests as $idx => $guest) {
        $is_main_guest = !empty ($guest['main_participant']);
        $is_same_email = !empty ($guest['same_as_first_participant']);
        $retreat_messages = $guest['emails_recieved'];
        $email = $guest['email'];
        $name = $guest['name'];
        if (!$is_main_guest && $is_same_email)
            continue;

        foreach ($retreat_messages as $message_id => $message_data) {
            foreach ($message_data as $event => $ts) {
                if ($ts < time())
                    $ts = time();
                wp_schedule_single_event($ts, 'scheduled_retreat_message_template', array($order_id, $message_id, $event, $email, $name));
            }
        }
    }

}

function schedule_remaining_payment_reminder_email($order_id)
{
    $order = wc_get_order($order_id);
    $ts = strtotime($order->get_meta('time_to_limit_reservation')) > time() ? strtotime($order->get_meta('time_to_limit_reservation')) : time();
    $action = 'remaining_payment_reminder';
    wp_schedule_single_event($ts, 'scheduled_deposit_order_email', array($order_id, $action));
}
function schedule_expired_reservation_events($order_id)
{
    $order = wc_get_order($order_id);
    $ts = strtotime($order->get_meta('expiration_time'));
    $action = 'expired_reservation_notice';
    wp_schedule_single_event($ts, 'scheduled_deposit_order_email', array($order_id, $action));
    wp_schedule_single_event($ts, 'scheduled_reservation_expired', array($order_id));
}
function check_order_editable($order_id, $product_id)
{
    $order = wc_get_order($order_id);
    if (empty ($order))
        return false;
    $departure_date = !empty ($order->get_meta('departure_date')) ? $order->get_meta('departure_date') : '';
    $departure_date_time = DateTime::createFromFormat('Y-m-d', $departure_date);
    $now = new DateTime();

    if (!empty ($departure_date) && $departure_date_time < $now)
        return false;


    return true;
}
function get_available_rooms_for_date($retreat_id, $departure_date)
{
    global $woocommerce;
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $is_full_booked = !empty ($retreat_data['departure_dates'][$departure_date]['is_full_booked']);
    $rooms_list = $retreat_data['departure_dates'][$departure_date]['rooms_list'];
    $rooms_data = [
        'rooms_list' => [],
        'status' => 'available',
        'second_participant_available' => true
    ];

    $cart_items = $woocommerce->session->get('cart', array());
    $selected_rooms_in_dates = [];
    if (!empty ($cart_items)) {
        foreach ($cart_items as $cart_item) {
            $is_same_retreat = $cart_item['product_id'] == $retreat_id;
            if ($is_same_retreat) {
                $selected_rooms_in_dates[] = [
                    'departure_date' => $cart_item['departure_date'],
                    'room_id' => $cart_item['room_id'],
                ];
            }
        }
    }
    if ($is_full_booked) {
        $rooms_data['status'] = 'full';
    }
    if ($retreat_data['departure_dates'][$departure_date]['guests_availability'] < 2) {
        $rooms_data['second_participant_available'] = false;
    }
    foreach ($rooms_list as $room_id => $room) {
        $room_gallery = get_post_meta($room_id, 'room_gallery', true);
        $rooms_data['rooms_list'][$room_id] = $room;
        $rooms_data['rooms_list'][$room_id]['details'] = get_post_meta($room_id, 'package_product_data', true);
        $rooms_data['rooms_list'][$room_id]['image_src'] = wp_get_attachment_url(get_post_thumbnail_id($room_id));
        $rooms_data['rooms_list'][$room_id]['gallery'] = $room_gallery ? array_values(array_map(function ($image_id) {
            return wp_get_attachment_url($image_id);
        }, $room_gallery)) : [];

        $availability = $retreat_data['departure_dates'][$departure_date]['guests_availability'];
        $number_of_guests = count(get_departure_date_guests_list($retreat_id, $departure_date));
        $rooms_data['rooms_list'][$room_id]['can_multiple_guests'] = $availability - $number_of_guests > 1 && $room['room_capacity'] > 1;
        unset($rooms_data['rooms_list'][$room_id]['guests']);
        $rooms_data['rooms_list'][$room_id]['is_selected'] = false;
        foreach ($selected_rooms_in_dates as $selected_room) {
            if ($selected_room['departure_date'] == $departure_date && $selected_room['room_id'] == $room_id) {
                $rooms_data['rooms_list'][$room_id]['is_selected'] = true;
                break;
            }
        }
    }

     return $rooms_data;
}

function upload_qr_image_file_handle($file, $target, $product_id)
{
    // Set the uploads directory
    $upload_dir = wp_upload_dir();
    $user_images_dir = trailingslashit($upload_dir['basedir']) . 'products_qr_codes/';

    // Create the directory if it doesn't exist
    if (!file_exists($user_images_dir)) {
        wp_mkdir_p($user_images_dir);
    }

    $file_name = wp_unique_filename($user_images_dir, $target . '_qr_code_' . $product_id . '.png');
    $file_path = $user_images_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $url = home_url('/wp-content/uploads/products_qr_codes/') . $file_name;
        $upload_result = array(
            'file' => $file_path,
            'url' => $url
        );

        return $upload_result;
    } else {
        return new WP_Error('upload_error', 'Error moving uploaded file');
    }
}