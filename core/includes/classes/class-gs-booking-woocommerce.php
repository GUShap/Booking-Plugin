<?php

class GS_Booking_Woocommerce
{
    // Constructor to initialize the class
    public function __construct()
    {
        $this->initialize_woocommerce_hooks();
        $this->register_custom_order_statuses();
    }

    // Method to initialize WooCommerce hooks
    public function initialize_woocommerce_hooks()
    {
        add_action('admin_enqueue_scripts', array($this, 'gs_bookings_enqueue_woocommerce_scripts'));

        add_action('init', 'set_retreat_product_type');

        add_filter('product_type_selector', array($this, 'retreat_add_custom_product_type'));
        add_filter('woocommerce_product_class', array($this, 'add_booking_custom_product_class'), 10, 2);


        add_action('admin_footer', array($this, 'enable_stock_tab'));

        add_action('woocommerce_product_options_general_product_data', array($this, 'custom_product_type_show_price'));
        add_action('woocommerce_product_data_panels', array($this, 'custom_product_types_tabs_content'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_product_data'));

        add_filter('woocommerce_product_data_tabs', array($this, 'set_custom_product_types_tabs'));

        // Add 'Privacy Statement' to the list of order meta data
        add_action('add_meta_boxes_product', array($this, 'add_privacy_statement_metabox'));
        add_action('save_post', array($this, 'save_privacy_statement_metabox'));

        add_action('woocommerce_cart_item_removed', array($this, 'update_wc_session'), 10, 1);

        add_action('woocommerce_review_order_before_payment', array($this, 'set_participants_details'), 10);
        add_action('woocommerce_review_order_before_payment', array($this, 'set_deposit_payment_options'), 11);

        add_filter('woocommerce_get_item_data', array($this, 'set_cart_item_custom_data'), 10, 2);
        add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_item_price'), 10, 1);

        add_filter('wc_order_statuses', array($this, 'add_custom_order_status'));
        add_action('woocommerce_order_status_changed', array($this, 'check_deposit_payment_status'), 10, 4);
        add_filter('wc_order_is_editable', array($this, 'custom_order_status_editable'), 9999, 2);

        add_filter('woocommerce_cart_item_quantity', array($this, 'customize_cart_quantity_display'), 10, 3);

        add_action('woocommerce_checkout_create_order', array($this, 'custom_process_payment'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_custom_order_columns'));
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'custom_retreat_data_order_line_item'), 10, 4);
        add_action('woocommerce_admin_order_item_headers', array($this, 'add_deposit_status_item_header'), 10, 1);
        add_action( 'woocommerce_admin_order_item_values', array($this, 'add_deposit_status_item_values'), 10, 3 );


        add_action('woocommerce_thankyou', array($this, 'set_custom_emails'), 10, 1);
        add_action('woocommerce_thankyou', array($this, 'reset_custom_session_items'), 11, 1);

        add_action('woocommerce_calculate_totals', array($this, 'set_cart_subtotal'), 10, 1);
        add_action('woocommerce_review_order_before_order_total', array($this, 'set_deposit_payment_discount'), 10);

        add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_fee_for_stripe'));
        add_action('woocommerce_review_order_before_payment', array($this, 'set_payment_method_update'));

        add_action('woocommerce_payment_complete', array($this, 'create_complete_reservation_link'), 10, 4);

        // Hook the function to the woocommerce_order_status_cancelled and woocommerce_order_status_refunded actions
        add_action('woocommerce_order_status_cancelled', array($this, 'update_retreat_data'), 10, 1);
        add_action('woocommerce_order_status_refunded', array($this, 'update_retreat_data'), 10, 1);
        // add_action('woocommerce_before_cart', array($this,'limit_booking_retreat_product_in_cart'));
        // add_action('woocommerce_before_checkout', array($this,'limit_booking_retreat_product_in_cart'));

        // add_action('wp', array($this, 'send_test_mail'));
        add_action('template_redirect', array($this, 'set_complete_payment_page'));

        // add_action('init', array($this, 'send_test_mail'));

    }
    function send_test_mail()
    {

        $order = wc_get_order(12216);
        order_custom_email($order, 'deposit_payment_recieved');
    }
    function gs_bookings_enqueue_woocommerce_scripts($hook)
    {
        global $post_type;

        // Check if the current page is the WooCommerce product edit page
        if (($hook == 'post.php' || $hook == 'post-new.php') && $post_type == 'product') {
            $product = wc_get_product(get_the_id());
            $retreat_data = get_post_meta($product->get_id(), 'retreat_product_data', true);
            $rooms_data = [];
            if (!empty($retreat_data['rooms'])) {
                foreach ($retreat_data['rooms'] as $room_id => $price) {
                    $rooms_data[] = [
                        'name' => get_the_title($room_id),
                        'data' => get_post_meta($room_id, 'package_product_data', true),
                        'id' => $room_id,
                        'price' => $price,
                    ];
                }
            }
            wp_enqueue_style('gsbooking-product-type-styles', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/custom-products-style.css', array(), time(), 'all');
            wp_enqueue_script('gsbooking-product-type-scripts', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/custom-product-script.js', array('jquery'), time(), false);
            wp_localize_script(
                'gsbooking-product-type-scripts',
                'customVars',
                array(
                    'plugin_name' => __(GSBOOKING_NAME, 'gs-booking'),
                    'rooms_data' => $rooms_data,
                    'product_id' => $product->get_id(),
                    'product_type' => $product->get_type(),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'retreat_data' => $retreat_data,
                    'is_published' => $product->get_status() === 'publish',
                    'product_url' => get_permalink($product->get_id()),
                    'checkout_url' => wc_get_checkout_url(),
                    'cart_url' => wc_get_cart_url(),
                    'add_to_cart_url' => $product->add_to_cart_url(),
                )
            );

            wp_enqueue_script('qr_generator', 'https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js', array('jquery'), '1.5.0', false);
        }
    }

    function retreat_add_custom_product_type($types)
    {
        $types['booking_retreat'] = __('Booking Retreat');
        return $types;
    }
    // Loads Type on product
    function add_booking_custom_product_class($classname, $product_type)
    {
        if ($product_type == 'booking_retreat') {
            $classname = 'WC_Product_Booking_Retreat';
        }
        return $classname;
    }

    // Show Product Data General Tab Prices 
    function custom_product_type_show_price()
    {
        global $product_object;
        if ($product_object) {
            if ('booking_retreat' === $product_object->get_type()) {
                wc_enqueue_js("
                $('.product_data_tabs .general_tab').addClass('show_if_package').show();
                $('.pricing').addClass('show_if_package').show();
                ");
            }
        }
    }
    function set_custom_product_types_tabs($tabs)
    {
        global $product_object;
        $product_id = $product_object->get_id();
        $product_type = $product_object->get_type();
        $is_completing_product = has_term('completing-products', 'product_cat', $product_id);

        if (empty($product_object))
            return;
        if ('booking_retreat' === $product_type) {
            unset($tabs['shipping']);
            unset($tabs['attribute']);
            unset($tabs['variations']);
            unset($tabs['advanced']);

            $tabs['general_info'] = array(
                'label' => __('General Info', 'woocommerce'),
                'target' => 'general_info',
                'priority' => 20, // Adjust the priority to control the tab order
            );
            $tabs['dates'] = array(
                'label' => __('Retreat Dates', 'woocommerce'),
                'target' => 'dates',
                'priority' => 21, // Adjust the priority to control the tab order
            );
            $tabs['rooms'] = array(
                'label' => __('Available Rooms', 'woocommerce'),
                'target' => 'rooms',
                'priority' => 22, // Adjust the priority to control the tab order
            );
        }

        if ($is_completing_product) {
            unset($tabs['shipping']);
            unset($tabs['attribute']);
            unset($tabs['variations']);

            $tabs['completing_product'] = array(
                'label' => __('Completing Product Options', 'woocommerce'),
                'target' => 'completing_product',
                'priority' => 21, // Adjust the priority to control the tab order
            );
        }

        $tabs['procut_qr'] = array(
            'label' => __('QR Code', 'woocommerce'),
            'target' => 'procut_qr',
            'priority' => 25, // Adjust the priority to control the tab order
        );
        return $tabs;
    }

    function custom_product_types_tabs_content()
    {
        global $post;
        $product_id = $post->ID;

        $product = wc_get_product($product_id);
        $product_type = $product->get_type();
        $is_completing_product = has_term('completing-products', 'product_cat', $product_id);

        if ($product_type == 'booking_retreat') {
            $this->set_retreat_product_type_tab_content($product_id);
        }
        if ($is_completing_product) {
            $this->set_completing_product_tab_content($product_id);
        }
        $this->set_qr_code_tab_content($product_id);
    }

    private function set_qr_code_tab_content($product_id)
    {
        $product_page_qr_image_url = get_post_meta($product_id, 'product_page_qr_image_url', true);
        $atc_qr_image_url = get_post_meta($product_id, 'atc_qr_image_url', true);
        ?>
        <div id="procut_qr" class="panel woocommerce_options_panel custom-tab-content">
            <div class="product-page-wrapper qr-code-wrapper"
                data-active="<?php echo !empty($product_page_qr_image_url) ? 'true' : 'false' ?>">
                <h4>Product Page QR Code</h4>
                <div class="button-wrapepr">
                    <button class="create-qr-code-btn" id="create-product-page-qr-btn" type="button">Create QR Code for Product
                        Page</button>
                </div>
                <div class="qr-image-wrapper product">
                    <?php if (!empty($product_page_qr_image_url)) { ?>
                        <img src="<?php echo $product_page_qr_image_url ?>" alt="Product Page QR Code">
                    <?php } ?>
                </div>
                <div class="action-buttons-wrapper">
                    <button class="download-btn" id="download-product-page-qr-code" type="button">Download QR Code</button>
                    <button class="save-btn" id="save-product-page-qr-code" type="button">Save QR Code</button>
                </div>
            </div>
            <div class="atc-wrapper qr-code-wrapper" data-active="<?php echo !empty($atc_qr_image_url) ? 'true' : 'false' ?>">
                <h4>Add To Cart QR Code</h4>
                <div class="button-wrapepr">
                    <select name="atc_redirect" id="atc-redirect-select">
                        <option value="checkout">Checkout Page</option>
                        <option value="cart">Cart Page</option>
                    </select>
                    <button class="create-qr-code-btn" id="create-atc-qr-btn" type="button">Create Add To Cart QR Code</button>
                </div>
                <div class="qr-image-wrapper atc">
                    <?php if (!empty($atc_qr_image_url)) { ?>
                        <img src="<?php echo $atc_qr_image_url ?>" alt="Add To Cart QR Code">
                    <?php } ?>
                </div>
                <div class="action-buttons-wrapper">
                    <button class="download-btn" id="download-atc-qr-code" type="button">Download QR Code</button>
                    <button class="save-btn" id="save-atc-qr-code" type="button">Save QR Code</button>
                </div>
            </div>
        </div>
    <?php }
    private function set_retreat_product_type_tab_content($product_id)
    {
        $retreat_data = get_post_meta($product_id, 'retreat_product_data', true);
        $general_data = !empty($retreat_data['general_info']) ? $retreat_data['general_info'] : '';
        $rooms_data = !empty($retreat_data['rooms']) ? $retreat_data['rooms'] : [];
        $dates_data = !empty($retreat_data['departure_dates']) ? $retreat_data['departure_dates'] : '';

        $this->set_retreat_general_tab_content($general_data, $rooms_data);
        $this->set_retreat_rooms_tab_content($rooms_data);
        $this->set_retreat_dates_tab_contents($dates_data, $rooms_data);
    }

    private function set_retreat_general_tab_content($general_data, $rooms_ids)
    {
        $max_group_size = 0;
        foreach ($rooms_ids as $room_id => $room_price) {
            $room_metadata = get_post_meta($room_id, 'package_product_data', true);
            $max_group_size += $room_metadata['max_room_capacity'];
        }
        ?>
        <div id="general_info" class="panel woocommerce_options_panel custom-tab-content">
            <div class="general-info-heading">
                <h4 class="general-info-title custom-tab-title">General Info</h4>
            </div>
            <div class="general-info-content custom-tab-data">
                <div class="retreat-duration-wrapper general-info-wrapper">
                    <label for="retreat-duration-input">Retreat Duration</label>
                    <input type="number" name="general_info[retreat_duration]" id="retreat-duration-input"
                        value="<?php echo !empty($general_data['retreat_duration']) ? $general_data['retreat_duration'] : '' ?>">
                </div>
                <div class="group-size-wrapper general-info-wrapper">
                    <p>Group Size Range:</p>
                    <input type="number" name="general_info[min_group_size]" id="min-group-size-input" placeholder="min"
                        value="<?php echo !empty($general_data['min_group_size']) ? $general_data['min_group_size'] : '' ?>">
                    <span>to</span>
                    <input type="number" name="" id="max-group-size-input" disabled placeholder="max"
                        value="<?php echo $max_group_size ?>">
                    <input type="hidden" name="general_info[max_group_size]" value="<?php echo $max_group_size ?>">
                </div>
                <div class="retreat-location-wrapper general-info-wrapper">
                    <p>Retreat Location</p>
                    <input type="text" name="general_info[retreat_address]" id="retreat-address-input"
                        value="<?php echo !empty($general_data['retreat_address']) ? $general_data['retreat_address'] : '' ?>"
                        placeholder="Retrear Address">
                    <input type="text" name="general_info[retreat_location_url]" id="retreat-location-input"
                        value="<?php echo !empty($general_data['retreat_location_url']) ? $general_data['retreat_location_url'] : '' ?>"
                        placeholder="Maps Url">
                </div>
                <div class="calendar-color-wrapper general-info-wrapper">
                    <label for="calendar-color">Color For Calendar</label>
                    <input type="color" name="general_info[calendar_color]" id="calendar-color"
                        value="<?php echo !empty($general_data['calendar_color']) ? $general_data['calendar_color'] : '' ?>">
                </div>
            </div>
        </div>
    <?php }

    private function set_retreat_rooms_tab_content($available_rooms = [])
    {
        $all_rooms_ids = get_all_rooms_ids();
        ?>
        <div id="rooms" class="panel woocommerce_options_panel custom-tab-content">
            <div class="rooms-heading">
                <h4 class="rooms-title custom-tab-title">Available Rooms</h4>
            </div>
            <div class="rooms-content custom-tab-data">
                <div class="rooms-checkboxes-wrapper">
                    <?php foreach ($all_rooms_ids as $room_id) {
                        $room_name = get_the_title($room_id);
                        $room_metadata = get_post_meta($room_id, 'package_product_data', true);
                        $is_available_room = array_key_exists($room_id, $available_rooms);
                        $room_price = $is_available_room ? $available_rooms[$room_id] : '';
                        ?>
                        <div class="room-wrapper">
                            <div class="room-checkbox-wrapper">
                                <label for="<?php echo $room_id ?>">
                                    <?php echo $room_name ?>
                                </label>
                                <input type="checkbox" class="available-room-checkbox"
                                    name="rooms[<?php echo $room_id ?>][is_available]" id="<?php echo $room_id ?>" <?php echo $is_available_room ? 'checked' : '' ?>>
                            </div>
                            <div class="room-info">
                                <p class="capacity">
                                    Room Capacity: <span class="number">
                                        <?php echo $room_metadata['max_room_capacity'] ?>
                                    </span> Guests
                                </p>
                            </div>
                            <div class="room-price-wrapper" edit-mode="false">
                                <label for="room-price-<?php echo $room_id ?>">Room Price: </label>
                                <input type="number" class="room-price-input" name="rooms[<?php echo $room_id ?>][price]"
                                    id="room-price-<?php echo $room_id ?>" value="<?php echo $room_price ?>" <?php echo $is_available_room ? 'required' : '' ?>>
                                <p><span class="currency">
                                        <?php echo get_woocommerce_currency_symbol(); ?>
                                    </span><span class="room-price">
                                        <?php echo $room_price ?>
                                    </span></p>
                                <div class="buttons-wrapper">
                                    <button type="button" class="set-room-price-button">Set</button>
                                    <button type="button" class="edit-room-price-button">Edit</button>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php }

    private function set_retreat_dates_tab_contents($dates_data, $available_rooms_details)
    { ?>
        <div id="dates" class="panel woocommerce_options_panel custom-tab-content">
            <div class="dates-heading">
                <h4 class="dates-title custom-tab-title">Departure Dates</h4>
            </div>
            <div class="dates-content custom-tab-data">
                <div class="date-repeater-container">
                    <div class="dates-wrapper">
                        <?php
                        if (!empty($dates_data)) {
                            $count = 0;
                            foreach ($dates_data as $departure_label => $departure_data) {
                                $dt = new DateTime($departure_label);
                                $tab_date_formatted = $dt->format('F j, Y');
                                $selected_rooms = $departure_data['rooms_list'];
                                ?>
                                <div class="departure-date-wrapper" data-date="<?php echo $departure_label ?>"
                                    data-selected="<?php echo $count == 0 ? 'true' : 'false' ?>" edit-mode="false">
                                    <div class="date-tab">
                                        <input type="date" name="departure_date[<?php echo $departure_label ?>]"
                                            value="<?php echo $departure_label ?>" />
                                        <p>
                                            <?php echo $tab_date_formatted; ?>
                                        </p>
                                    </div>
                                    <div class="date-content-wrapper">
                                        <div class="availability-wrapper">
                                            <div class="max-participants-wrapper">
                                                <p>Maximum Participants: <span>
                                                        <?php echo $departure_data['max_participants'] ?>
                                                    </span></p>
                                                <div class="set-max-participants-wrapper edit-info-wrapper">
                                                    <input type="number" name="max_participants" min="1"
                                                        class="max-participants-input edit-input"
                                                        value="<?php echo $departure_data['max_participants'] ?>">
                                                </div>
                                            </div>
                                            <div class="guests-availability-wrapper">
                                                <p>Guests Availability: <span>
                                                        <?php echo $departure_data['guests_availability'] ?>
                                                    </span></p>
                                            </div>
                                            <div class="rooms-availability-wrapper">
                                                <p>Rooms Availability: <span>
                                                        <?php echo $departure_data['rooms_availability'] ?>
                                                    </span></p>
                                            </div>
                                        </div>
                                        <div class="info-wrapper">
                                            <?php if (!empty($departure_data['guests_info'])) { ?>
                                                <div class="guests-list-wrapper">
                                                    <ul class="guests-list">
                                                        <?php foreach ($departure_data['guests_info'] as $guest) { ?>
                                                            <li class="guest-item">
                                                                <?php echo $guest['name'] ?>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                            <div class="rooms-list-wrapper">
                                                <p>Available Rooms:</p>
                                                <?php if (!empty($selected_rooms)) { ?>
                                                    <ul class="rooms-list">
                                                        <?php foreach ($selected_rooms as $room_id => $room_data) {
                                                            $room_name = get_the_title($room_id);
                                                            $is_booked = !empty($room_data['is_booked']);
                                                            $guests = $room_data['guests'];
                                                            $guests_count = isset($guests) ? count($guests) : 0;
                                                            $room_capacity = $room_data['room_capacity'];
                                                            ?>
                                                            <li class="room-item" data-booked="<?php echo $is_booked ? "true" : "false" ?>">
                                                                <p class="room-name">
                                                                    <?php echo $room_name ?>
                                                                </p>
                                                                <p class="max-capacity">Room Capacity:
                                                                    <span>
                                                                        <?php echo $room_capacity ?>
                                                                    </span>
                                                                </p>
                                                                <p class="number-of-guests">Guests Count:
                                                                    <span>
                                                                        <?php echo $guests_count; ?>
                                                                    </span>
                                                                </p>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php } ?>
                                                <ul class="select-room-list">
                                                    <?php
                                                    foreach ($available_rooms_details as $room_id => $room_price) {
                                                        $room_name = get_the_title($room_id);
                                                        $room_general_data = get_post_meta($room_id, 'package_product_data', true);
                                                        $is_selected = in_array($room_id, array_keys($selected_rooms));
                                                        $is_checked_attr = $is_selected ? 'checked' : '';
                                                        $room_el_id = str_replace(' ', '_', strtolower($room_name));
                                                        $room_price_str = 'price: ' . get_woocommerce_currency_symbol() . number_format($room_price);
                                                        $room_capacity_str = 'room capacity:' . $room_general_data['max_room_capacity'];
                                                        ?>
                                                        <li class="select-room-item">
                                                            <div class="checkbox-wrapper">
                                                                <input type="checkbox" id="<?php echo $room_el_id ?>"
                                                                    data-product="<?php echo $room_id ?>" <?php echo $is_checked_attr; ?>>
                                                                <label for="<?php echo $room_el_id; ?>">
                                                                    <?php echo $room_name ?>
                                                                </label>
                                                            </div>
                                                            <p class="room-capacity">
                                                                <?php $room_capacity_str ?>
                                                            </p>
                                                            <p class="room-price">
                                                                <?php echo $room_price_str ?>
                                                            </p>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="footing-wrapper">
                                            <div class="registration-activation-wrapper">
                                                <span>Activate Registration:</span>
                                                <div class="switch-button-wrapper">
                                                    <input type="checkbox" class="activate-registration-switch"
                                                        id="switch-<?php echo $departure_label ?>" <?php echo !empty($departure_data['registration_active']) ? 'checked' : '' ?> />
                                                    <label for="switch-<?php echo $departure_label ?>"></label>
                                                </div>
                                            </div>
                                            <div class="buttons-wrapper">
                                                <button type="button" class="edit-info-button">Change</button>
                                                <button type="button" class="save-info-button">Save</button>
                                            </div>
                                            <div class="delete-date-wrapper">
                                                <button type="button" class="remove-date-button"
                                                    id="remove_<?php echo $departure_label ?>">Remove Date</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $count++;
                            }
                        } ?>
                    </div>
                    <button type="button" class="add-date-btn">Add Date</button>
                </div>
            </div>
        </div>
    <?php }

    private function set_completing_product_tab_content($product_id)
    {
        $enable_multiple_items = get_post_meta($product_id, '_enable_multiple_items', true);
        $limit_quantity = get_post_meta($product_id, '_limit_quantity', true);
        $max_items_limit = get_post_meta($product_id, '_max_items_limit', true);
        ?>
        <div id="completing_product" class="panel woocommerce_options_panel custom-tab-content">
            <div class="completing-product-heading">
                <h4 class="completing-product-title custom-tab-title">Completing Product Options</h4>
            </div>
            <div class="completing-product-content custom-tab-data">
                <div class="enable-multiple-items-wrapper completing-product-wrapper">
                    <input type="checkbox" id="enable_multiple_items" name="enable_multiple_items" value="1" <?php echo checked(1, $enable_multiple_items, false) ?>>
                    <label for="enable_multiple_items">Enable Multiple Items</label>
                </div>
                <div class="limit-quantity-wrapper completing-product-wrapper">
                    <input type="checkbox" id="limit_quantity" name="limit_quantity" value="1" <?php echo checked(1, $limit_quantity, false) ?>>
                    <label for="limit_quantity">Limit Quantity</label>
                </div>
                <div class="max-items-limit-wrapper completing-product-wrapper">
                    <input type="number" id="max_items_limit" name="max_items_limit" min="1"
                        value="<?php echo esc_attr($max_items_limit) ?>">
                    <label for="max_items_limit">Max Items Limit</label>
                </div>
            </div>
        </div>
    <?php }

    function save_custom_product_data($post_id)
    {
        $product = wc_get_product($post_id);
        $product_type = $product->get_type();
        $is_completing_product = has_term('completing-products', 'product_cat', $post_id);

        if ($product_type == 'booking_retreat') {
            $this->save_retreat_custom_data($post_id);
        }

        if ($is_completing_product) {
            $this->save_completing_product_data($post_id);
        }
    }

    private function save_retreat_custom_data($product_id)
    {
        $retreat_data = !empty(get_post_meta($product_id, 'retreat_product_data', true))
            ? get_post_meta($product_id, 'retreat_product_data', true)
            : [];

        if (!empty($_POST['general_info'])) {
            $retreat_data['general_info'] = $_POST['general_info'];
        }
        if (!empty($_POST['rooms'])) {
            $retreat_data['rooms'] = [];
            foreach ($_POST['rooms'] as $room_id => $room_val) {
                if (!empty($room_val['is_available']))
                    $retreat_data['rooms'][$room_id] = $room_val['price'];
            }

        }

        if (!empty($_POST['_participants_info'])) {
            $retreat_data['participants_info'] = wp_kses_post($_POST['_participants_info']);
        }
        update_post_meta($product_id, 'retreat_product_data', $retreat_data);
    }

    function add_privacy_statement_metabox()
    {
        global $post;

        // Check if the product type is 'booking_retreat'
        $product = wc_get_product($post->ID);

        if ($product && $product->get_type() === 'booking_retreat') {
            add_meta_box(
                'privacy_statement_metabox_id',     // Unique ID for the meta box
                'Privacy Statement',                 // Title of the meta box
                'display_privacy_statement_metabox', // Callback function to display the meta box content
                'product',                           // Post type where the meta box will be added
                'normal',                            // Context (e.g., 'normal', 'advanced', 'side')
                'high'                               // Priority (e.g., 'high', 'core', 'default', 'low')
            );
        }
    }

    function save_privacy_statement_metabox($post_id)
    {
        // Check if the nonce is set
        if (!isset($_POST['privacy_statement_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['privacy_statement_nonce'], 'privacy_statement_nonce')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['_privacy_statement'])) {
            $privacy_statement = wp_kses_post($_POST['_privacy_statement']);
            update_post_meta($post_id, '_privacy_statement', $privacy_statement);
        }
    }

    function save_completing_product_data($post_id)
    {
        $enable_multiple_items = isset($_POST['enable_multiple_items']) ? 1 : 0;
        $limit_quantity = isset($_POST['limit_quantity']) ? 1 : 0;
        $max_items_limit = isset($_POST['max_items_limit']) ? absint($_POST['max_items_limit']) : 0;

        update_post_meta($post_id, '_enable_multiple_items', $enable_multiple_items);
        update_post_meta($post_id, '_limit_quantity', $limit_quantity);
        update_post_meta($post_id, '_max_items_limit', $max_items_limit);
    }

    function enable_stock_tab()
    {

        if ('product' != get_post_type()):
            return;
        endif;

        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                //for Inventory tab
                jQuery('.inventory_options').addClass('show_if_simple_rental').show();

                jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_simple_rental').show();
                jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_simple_rental').show();
            });
        </script>
        <?php

    }
    function register_custom_order_statuses()
    {

        register_post_status(
            'wc-deposit-paid',
            array(
                'label' => 'Deposit Paid',
                'public' => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list' => true,
                'exclude_from_search' => false,
                'label_count' => _n_noop('Deposit Paid (%s)', 'Deposit Paid (%s)')
            )
        );

        register_post_status(
            'wc-deposit-expired',
            array(
                'label' => _x('Deposit Expired', 'Order status', 'text_domain'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Deposit Expired <span class="count">(%s)</span>', 'Deposit Expired <span class="count">(%s)</span>', 'text_domain')
            )
        );
    }

    function add_custom_order_status($statuses)
    {
        $new_statuses = array();

        foreach ($statuses as $key => $status) {
            $new_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_statuses['wc-deposit-paid'] = 'Deposit Paid';
                $new_statuses['wc-deposit-expired'] = 'Deposit Expired';
                // $new_statuses['wc-deposit-underbooked'] = 'Deposit Underbooked';
            }
        }
        return $new_statuses;
    }

    function update_wc_session($cart_item_key)
    {
        $cart = WC()->cart->get_cart();
        $is_retreat = false;
        $retreat_id = 0;
        foreach ($cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            if ($product->get_type() === 'booking_retreat') {
                $is_retreat = true;
                $retreat_id = $product->get_id();
                break;
            }
        }
        WC()->session->set('is_retreat', $is_retreat);
        WC()->session->set('retreat_id', $retreat_id);
    }
    /*********/

    /****CART****/

    function set_cart_item_custom_data($item_data, $cart_item_data)
    {
        $cart_item_key = $cart_item_data['key'];
        if (!empty($cart_item_data['departure_date'])) {
            $date = new DateTime($cart_item_data['departure_date']);
            $formattedDate = $date->format('F j, Y');
            $item_data[] = array(
                'key' => 'Departure Date',
                'value' => $formattedDate
            );
        }
        if (!empty($cart_item_data['room_id'])) {
            $item_data[] = array(
                'key' => 'Room',
                'value' => get_the_title($cart_item_data['room_id'])
            );
        }
        if (!empty($cart_item_data['room_price'])) {
            $item_data[] = array(
                'key' => 'Price for Room',
                'value' => get_woocommerce_currency_symbol() . number_format($cart_item_data['room_price'])
            );
        }
        if (!empty($cart_item_data['additional'])) {
            foreach ($cart_item_data['additional'] as $upsell_id => $quantity) {
                $product = wc_get_product($upsell_id);
                $product_name = get_the_title($upsell_id);
                $product_price = $product->get_price();
                $final_price = $product_price * $quantity;
                $final_price_str = $quantity > 1
                    ? get_woocommerce_currency_symbol() . number_format($final_price) . ' x ' . $quantity
                    : get_woocommerce_currency_symbol() . number_format($final_price);

                if (strpos($product_name, '2nd Participant') !== false) {
                    $product_name = str_replace($product_name, $product_name, '2nd Participant');
                }

                $item_data[] = array(
                    'key' => $quantity > 1 ? $product_name . ' x ' . $quantity : $product_name,
                    'value' => get_woocommerce_currency_symbol() . number_format($final_price) . ' <button type="button" class="remove-upsell" data-key="' . $cart_item_key . '" data-product="' . $upsell_id . '">&#215;</button>'
                );
            }
        }
        if (!empty($cart_item_data['order_id'])) {
            $item_data[] = array(
                'key' => 'Added to order',
                'value' => '#' . $cart_item_data['order_id']
            );
        }
        return $item_data;
    }

    function update_cart_item_price($cart)
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $init_action = isset($_GET['init_action']) ? sanitize_text_field($_GET['init_action']) : '';
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['room_price'])) {
                $price = $cart_item['room_price'];
                if (!empty($cart_item['additional'])) {
                    foreach ($cart_item['additional'] as $upsell_id => $quantity) {
                        $product = wc_get_product($upsell_id);
                        $product_price = $product->get_price();
                        $price += $product_price * $quantity;
                    }
                }
                if (!empty($order_id) && !empty($init_action) && $init_action === 'deposit') {
                    $order = wc_get_order($order_id);
                    $deposit_paid = $order->get_meta('deposit_payment');
                    $price -= $deposit_paid;
                }
                $cart_item['data']->set_price($price);
            }
        }
    }

    function set_cart_subtotal($cart_object)
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $init_action = isset($_GET['init_action']) ? sanitize_text_field($_GET['init_action']) : '';
        if (empty($order_id) || empty($init_action) || $init_action !== 'deposit')
            return;

        $order = wc_get_order($order_id);
        $deposit_paid = $order->get_meta('deposit_payment');

        $cart_object->subtotal = $cart_object->get_cart_contents_total() + $deposit_paid;

    }

    function limit_booking_retreat_product_in_cart($cart)
    {
        // Check if the 'booking_retreat' product type is already in the cart
        $is_booking_retreat_in_cart = false;

        foreach ($cart->get_cart() as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            if ($product && $product->get_type() === 'booking_retreat') {
                $is_booking_retreat_in_cart = true;
                break;
            }
        }

        // If 'booking_retreat' is already in the cart, prevent adding another one
        if ($is_booking_retreat_in_cart) {
            wc_add_notice(__('Only one Retreat at a time.', 'eleusinia'), 'error');
        }
    }

    /****CHECKOUT*****/
    function set_deposit_payment_discount()
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $init_action = isset($_GET['init_action']) ? sanitize_text_field($_GET['init_action']) : '';

        if (empty($order_id) || empty($init_action) || $init_action !== 'deposit')
            return;
        $order = wc_get_order($order_id);
        $deposit_paid = $order->get_meta('deposit_payment');
        ?>
        <tr>
            <th>Deposit Paid</th>
            <td>
                <?php echo get_woocommerce_currency_symbol() . number_format($deposit_paid, 2) ?>
            </td>
        </tr>
        <?php
    }

    function set_deposit_payment_options()
    {
        $init_action = isset($_GET['init_action']) ? sanitize_text_field($_GET['init_action']) : '';

        if (!empty($init_action) && $init_action == 'deposit')
            return;

        $cart = WC()->cart;
        $is_deposit_enabled = false;
        $deposit_percent = get_option('deposit_per_cent', 15) / 100;
        $deposit_amount = 0;
        $days_before_deposit_disabled = get_option('days_before_deposit_disabled', 0);
        $info_message = '';
        $deposit_payment_info_url = get_permalink(get_option('deposit_info_page', ''));
        foreach ($cart->get_cart() as $key => $item) {
            $product = wc_get_product($item['product_id']);
            if ($product->get_type() === 'booking_retreat') {
                $departure_date = $item['departure_date'];
                $departure_date_ts = new DateTime($departure_date);
                $today = new DateTime();
                $diff = $today->diff($departure_date_ts)->days;
                $is_deposit_enabled = $diff > $days_before_deposit_disabled;

                $total_item_price = $item['line_total'];
                $room_price = $item['room_price'];
                $item_deposit_price = $room_price * $deposit_percent + ($total_item_price - $room_price);
                $room_id = $item['room_id'];
                $retreat_data = get_post_meta($item['product_id'], 'retreat_product_data', true);
                $has_expired_orderes_ids = !empty($retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids']);
                $deposit_amount += $item_deposit_price;
                if ($diff > 60) {
                    $info_message = 'Deposit will reserve the room for you until' . date('F j, Y', strtotime('-58 days', strtotime($item['departure_date'])));
                } else if ($diff > 30) {
                    $info_message = 'Deposit will reserve the room for you for the next 48 hours';
                } else {
                    $info_message = 'Deposit will reserve the room for you for the next 12 hours';
                }
                if ($has_expired_orderes_ids) {
                    $info_message = 'Deposit will reserve the room for you for the next 2 hours';
                }
            } else {
                $deposit_amount += $item['line_total'];
            }
        }
        $fee = 0.029 * $deposit_amount + 0.3;
        if (!$is_deposit_enabled)
            return;
        // start new session
        if (!session_id()) {
            session_start();
        }
        WC()->session->set('deposit_amount', $deposit_amount);
        WC()->session->set('deposit_fee', $fee);
        ?>
        <div class="deposit-payment-options-container">
            <div class="deposit-payment-options-wrapper">
                <div class="deposit-payment-option">
                    <input type="radio" name="deposit_payment_option" id="deposit_payment_option" value="deposit" checked
                        required>
                    <label for="deposit_payment_option">Pay Deposit of <span class="deposit-amount">
                            <?php echo get_woocommerce_currency_symbol() . number_format($deposit_amount, 2) ?>
                        </span> + <i>fee of
                            <?php echo get_woocommerce_currency_symbol() . number_format($fee, 2) ?>
                        </i></label>
                </div>
                <div class="deposit-payment-option">
                    <input type="radio" name="deposit_payment_option" id="full_payment_option" value="full">
                    <label for="full_payment_option">Pay Full Amount</label>
                </div>
            </div>
            <p class="deposit-payment-info">
                Deposit payment applies only for retreat booking, payment for the rest of the products is full.<br>
                <?php echo $info_message ?>
            </p>
            <p>after that time the room will be avalable again from other guests.</p>
            <p>You can complete you reservation at any time, by clicking <em>"Complete Your Reservation"</em> Button in the
                confimation email.</p>
            <a href="<?php echo $deposit_payment_info_url ?>" target="_blank" class="deposit-payment-info-link">Read about
                deposit terms & conditions</a>
        </div>
    <?php }

    function set_participants_details()
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $items = WC()->cart->get_cart();
        $prticipants = [];
        $completing_product_participants = [];

        foreach ($items as $item_key => $item) {
            $product = wc_get_product($item['product_id']);
            $is_completing_product = has_term('completing-products', 'product_cat', $item['product_id']);
            $is_completing_product_second_participant = has_term('second-participant', 'product_cat', $item['product_id']);
            if ($product->get_type() == 'booking_retreat') {
                $room_id = $item['room_id'];
                $prticipants[$room_id] = [
                    'quantity' => 1,
                    'cart_item_key' => $item_key,
                ];

                if (!empty($item['is_second_participant'])) {
                    $prticipants[$room_id]['quantity']++;
                }
            }
            
            if ($is_completing_product && $is_completing_product_second_participant) {
                if(!empty($item['order_id'])){
                    $completing_product_participants = [
                        'order_id' => $item['order_id']
                    ];
                }
            }
        }

        ?>
        <div class="participants-details-container">
            <h5 class="participants-heading">Set Participants Details</h5>
            <div class="participants-content">
                <?php foreach ($prticipants as $room_id => $details) {
                    $room_name = get_the_title($room_id);
                    $checkbox_id = 'checkbox_' . $room_id;
                    $quantity = $details['quantity'];
                    $cart_item_key = $details['cart_item_key'];
                    ?>
                    <div class="room-participants-wrapper">
                        <div class="room-participants-heading">
                            <p class="room-participants-title">
                                <strong>
                                    <?php echo $room_name ?>
                                </strong> Room Geuests
                            </p>
                            <?php if ($quantity > 1) { ?>
                                <div class="input-wrapper type-checkbox">
                                    <input type="checkbox" name="same_details" id="<?php echo $checkbox_id ?>">
                                    <label for="<?php echo $checkbox_id ?>">Same Email & Phone</label>
                                </div>
                            <?php } ?>
                        </div>
                        <?php for ($i = 0; $i < $quantity; $i++) { ?>
                            <div class="participant-details-content">
                                <p>details for guest
                                    <?php echo $i + 1 ?>
                                </p>
                                <div class="input-group-wrapper">
                                    <div class="input-wrapper type-text participant-name-wrapper">
                                        <input type="text" class="participant_name"
                                            name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][name]"
                                            placeholder="Full Name" required data-room="<?php echo $room_id ?>"
                                            data-idx="<?php echo $i ?>">
                                    </div>
                                    <div class="input-wrapper type-text participant-email-wrapper">
                                        <input type="email" class="participant_email"
                                            name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][email]"
                                            placeholder="Email" required data-room="<?php echo $room_id ?>" data-idx="<?php echo $i ?>">
                                    </div>
                                    <div class="input-wrapper type-text participant-phone-wrapper">
                                        <input type="text" class="participant_phone"
                                            name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][phone]"
                                            placeholder="Phone Number" required data-room="<?php echo $room_id ?>"
                                            data-idx="<?php echo $i ?>">
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>
        <?php
    }

    /*****ORDER*****/
    // Hook to check order status change
    function check_deposit_payment_status($order_id, $old_status, $new_status, $order)
    {
        $is_deposit_payment = $order->get_meta('is_deposit_payment');
        $is_paid_in_full = $order->get_meta('is_paid_in_full');
        $expiration_time = $order->get_meta('expiration_time');
        $has_reservation_expired = strtotime($expiration_time) - strtotime('now') <= 0;
        // Check if the order status changed to 'processing' or 'completed'
        if (($new_status === 'processing') && $is_deposit_payment && !$is_paid_in_full) {
            // Update the order status to 'wc-deposit-paid'
            $has_reservation_expired
                ? $order->update_status('wc-deposit-expired')
                : $order->update_status('wc-deposit-paid');
        }
    }

    function custom_order_status_editable($allow_edit, $order)
    {
        if ($order->get_status() === 'deposit-paid') {
            $allow_edit = true;
            $expiration_time = $order->get_meta('expiration_time');
            $has_reservation_expired = strtotime($expiration_time) - strtotime('now') <= 0;
            if ($has_reservation_expired) {
                // $order->update_status('processing');
                // $allow_edit = false;
            }
        }
        return $allow_edit;
    }

    function customize_cart_quantity_display($product_quantity, $cart_item_key, $cart_item)
    {
        // Check if the product type is 'booking_retreat'
        if ($cart_item['data']->get_type() === 'booking_retreat') {
            // Return an empty string to hide the quantity column
            return '';
        }

        // For other product types, return the default quantity display
        return $product_quantity;
    }
    function custom_process_payment($order)
    {
        $session = WC()->session;
        $is_retreat = $session->get('is_retreat');
        // Check if the deposit payment option checkbox is checked
        if ($is_retreat) {
            $retreat_id = $session->get('retreat_id');
            $is_deposit_payment = isset($_POST['deposit_payment_option']) && $_POST['deposit_payment_option'] === 'deposit';
            $emails_sent = ['full_payment_recieved' => false];
            if ($is_deposit_payment) {
                $order_total = $order->get_total();
                $departure_date = $session->get('departure_date');
                $random_str_for_nonce = wp_generate_password(12, false);
                $days_until_retreat = calculate_remaining_days($departure_date);

                $deposit_amount = WC()->session->get('deposit_amount');
                $deposit_fee = WC()->session->get('deposit_fee');
                $remaining_payment = $order_total - $deposit_amount;

                // Modify the order total to be the partial payment
                $order->set_total($deposit_amount);

                // Create an order note about the partial payment
                $order->add_order_note('Deposit payment of ' . number_format($deposit_amount) . ' processed.');

                foreach ($order->get_fees() as $fee) {
                    $fee->set_total($deposit_fee);
                    $fee->save();
                }
                $emails_sent = [
                    'full_payment_recieved' => false,
                    'deposit_payment_recieved' => false,
                    'remaining_payment_reminder' => false,
                    'expired_reservation_notice' => false
                ];
                $remaining_payment = $order_total - $deposit_amount;
                $is_limited_reservation_time = $days_until_retreat <= 60;
                $time_to_limit_reservation = date('Y-m-d H:i:s');
                $hours_before_unresereved = 48;
                if ($is_limited_reservation_time) {
                    $hours_before_unresereved = $days_until_retreat <= 30 ? 12 : 48;
                    if (!empty($prev_order_id))
                        $hours_before_unresereved = 2;
                } else {
                    $time_to_limit_reservation = date('Y-m-d H:i:s', strtotime('-60 days', strtotime($departure_date)));
                }

                $expiration_time = date('Y-m-d H:i:s', strtotime('+' . $hours_before_unresereved . ' hours', strtotime($time_to_limit_reservation)));

                $order->update_meta_data('time_to_limit_reservation', $time_to_limit_reservation);
                $order->update_meta_data('hours_before_unresereved', $hours_before_unresereved);
                $order->update_meta_data('expiration_time', $expiration_time);
                $order->update_meta_data('has_expired', false);

                $order->update_meta_data('is_deposit_payment', true);
                $order->update_meta_data('is_paid_in_full', false);
                $order->update_meta_data('deposit_payment', number_format($deposit_amount, 2, '.', ''));
                $order->update_meta_data('deposit_nonce', wp_create_nonce('deposit_' . $random_str_for_nonce));
                $order->update_meta_data('remaining_payment', number_format($remaining_payment, 2, '.', ''));

                $order->add_order_note('Deposit payment of ' . number_format($deposit_amount, 2, '.', '') . ' processed.');

            }
            $order->update_meta_data('days_before_retreat', $days_until_retreat);
            $order->update_meta_data('emails_sent', $emails_sent);
            $order->update_meta_data('retreat_id', $retreat_id);
            $order->update_meta_data('departure_date', $departure_date);
            $order->save();
        }
    }

    function after_order_created($order)
    {
        $order_id = $order->get_id();
        $is_deposit_payment = $order->get_meta('is_deposit_payment');

    }

    function custom_retreat_data_order_line_item($item, $cart_item_key, $values, $order)
    {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);
        $product_type = $product->get_type();
        $item_subtotal = $item->get_subtotal();
        $item_total = $item->get_total();
        $order_rooms = !empty($order->get_meta('rooms')) ? $order->get_meta('rooms') : [];
        $order_guests = !empty($order->get_meta('guests')) ? $order->get_meta('guests') : [];
        $order_id = $order->get_id();

        if ($product_type == 'booking_retreat') {
            $billing_email = $order->get_billing_email();
            $room_id = $values['room_id'];
            $room_price = $values['room_price'];
            $departure_date = $values['departure_date'];
            $participants_data = $_POST['participants'][$cart_item_key];
            $number_of_guests = count($participants_data);
            $additional_products = $values['additional'];

            foreach ($participants_data as $idx => $participant_data) {
                $participants_data[$idx]['room_id'] = $room_id;
                $participants_data[$idx]['order_id'] = $order_id;
                $participants_data[$idx]['main_participant'] = $participant_data['email'] === $billing_email;
                $participants_data[$idx]['emails_recieved'] = [];
            }

            $retreat_data = get_post_meta($product_id, 'retreat_product_data', true);
            $guests_availability = $retreat_data['departure_dates'][$departure_date]['guests_availability'];
            $rooms_availability = $retreat_data['departure_dates'][$departure_date]['rooms_availability'];
            $duration = $retreat_data['general_info']['retreat_duration'];
            $retreat_dates = format_date_range($departure_date, $duration);
            $guests_availability -= $number_of_guests;
            $rooms_availability -= 1;

            $prev_order_id = !empty($retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id'])
                ? $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id']
                : '';

            $is_deposit_payment = isset($_POST['deposit_payment_option']) && $_POST['deposit_payment_option'] === 'deposit';
            $deposit_percent = get_option('deposit_per_cent') / 100;
            $payment_denominator = $is_deposit_payment ? $deposit_percent : 1;
            $room_status = $is_deposit_payment ? 'deposit' : 'booked';

            $item->add_meta_data('Room', get_the_title($room_id));
            $item->add_meta_data('Retreat Dates', $retreat_dates);

            if (!$is_deposit_payment) {
                $expired_orders_ids = !empty($prev_order_id)
                    ? $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids']
                    : [];
                $expired_orders_ids[] = $prev_order_id;
                if (!empty($expired_orders_ids)) {
                    foreach ($expired_orders_ids as $expired_order_id) {
                        $expired_order = wc_get_order($expired_order_id);
                        if (empty($expired_order))
                            continue;
                        $complete_reservation_link_id = $expired_order->get_meta('complete_reservation_link_id');
                        deactivate_payment_link($complete_reservation_link_id);
                    }
                }
            }
            $order_rooms[] = [
                'room_id' => $room_id,
                'room_price' => $room_price,
                'additional' => $additional_products,
            ];
            foreach ($participants_data as $participant_idx => $participant_data) {
                $order_guests[] = $participant_data;
                $item->add_meta_data('Guest ' . ($participant_idx + 1), $participant_data['name']);
            }

            if (!empty($additional_products)) {
                $added_items = [];
                $added_items_str = '';
                foreach ($additional_products as $additional_product_id => $additional_product_quantity) {
                    $additional_product = wc_get_product($additional_product_id);
                    $added_items[] = $additional_product_id;
                    $product_name = $additional_product->get_name();
                    // $is_extra_days = strpos($product_name, 'Extra Days') !== false;
                    empty($added_items_str)
                        ? $added_items_str .= $product_name
                        : $added_items_str .= ', ' . $product_name;
                    // if ($is_extra_days) {
                    $item->add_meta_data($product_name, $additional_product_quantity);
                    // }
                }
                $item->add_meta_data('Addons', $added_items_str);
                $item->add_meta_data('_added_items', $added_items);
            }
            // $payment_collected_from_room = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['payments_collected']
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'] = $participants_data;
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['payments_collected'] += $room_price * $payment_denominator;
            $retreat_data['departure_dates'][$departure_date]['guests_availability'] = $guests_availability;
            $retreat_data['departure_dates'][$departure_date]['rooms_availability'] = $rooms_availability;
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['is_booked'] = true;
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id'] = $order_id;
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['status'] = $room_status;
            $retreat_data['departure_dates'][$departure_date]['is_full_booked'] = !$guests_availability || !$rooms_availability;

            update_post_meta($product_id, 'retreat_product_data', $retreat_data);

            $new_subtotal = $item_subtotal - $room_price + ($room_price * $payment_denominator);
            $new_total = $item_total - $room_price + ($room_price * $payment_denominator);

            // $item->set_subtotal($new_subtotal);
            $item->set_total($new_total);

            $item->update_meta_data('room_id', $room_id);
            $order->update_meta_data('rooms', $order_rooms);
            $order->update_meta_data('guests', $order_guests);
        } else {
            $is_completing_product = has_term('completing-products', 'product_cat', $product_id);
            $is_second_participant = has_term('second-participant', 'product_cat', $product_id);
            if ($is_completing_product) {
                $existing_order_id = $values['order_id'];
                $existing_order_room_id = $values['room_id'];
                $existing_order = wc_get_order($existing_order_id);
                $existing_order_items = $existing_order->get_items();
                $item->add_meta_data('For Order', $existing_order_id);
                $item->add_meta_data('Room', get_the_title($existing_order_room_id));
                if ($is_second_participant) {
                    $second_participant_existing_order = $_POST['completing_product_participant'];
                    $second_participant_existing_order['order_id'] = $order_id;
                    $existing_order_retreat_id = $existing_order->get_meta('retreat_id');
                    $existing_order_departure_date = $existing_order->get_meta('departure_date');
                    $existing_order_guests = $existing_order->get_meta('guests');

                    $retreat_data = get_post_meta($existing_order_retreat_id, 'retreat_product_data', true);
                    $retreat_data['departure_dates'][$existing_order_departure_date]['rooms_list'][$existing_order_room_id]['guests'][] = $second_participant_existing_order;
                    $existing_order_guests[] = $second_participant_existing_order;

                    $existing_order->update_meta_data('guests', $existing_order_guests);
                    update_post_meta($existing_order_retreat_id, 'retreat_product_data', $retreat_data);
                }
                foreach($existing_order_items as $item_key =>$order_item){
                    $item_room_id = wc_get_order_item_meta($order_item,'room_data');
                    if($item_room_id === $existing_order_room_id){
                        $cart = WC()->cart;
                        $cart_item = $cart->get_cart_item( $cart_item_key );
                        $quantity = $cart_item['quantity'];
                        $order_item->add_meta_data($second_participant_existing_order['name'], $quantity);
                    }
                }
            }
        }
        $order->save();
    }
    function create_complete_reservation_link($order_id)
    {
        $order = wc_get_order($order_id);
        $is_deposit_payment = $order->get_meta('is_deposit_payment');
        if ($is_deposit_payment) {
            $complete_reservation_link = create_full_payment_link_object($order_id);
            $order->update_meta_data('complete_reservation_url', $complete_reservation_link->url);
            $order->update_meta_data('complete_reservation_link_id', $complete_reservation_link->id);
            $order->save();
        }
    }
    function add_custom_order_columns($order)
    {
        $is_deposit_payment = $order->get_meta('is_deposit_payment');
        $remaining_payment = $order->get_meta('remaining_payment');

        $items = $order->get_items();
        $keys = array_keys($items);

        $retreat_id = $items[$keys[0]]->get_product_id();
        ?>
        </div>
        <?php if (!empty($is_deposit_payment)) {
            $time_to_limit_reservation = $order->get_meta('time_to_limit_reservation');
            $expiration_time = $order->get_meta('expiration_time');
            $has_reservation_countdown_started = strtotime($time_to_limit_reservation) - strtotime('now') <= 0;
            $has_reservation_expired = strtotime($expiration_time) - strtotime('now') <= 0;
            ?>
            <div class="order_data_columm deposit-data-container">
                <h3>Deposit Payment</h3>
                <div class="payments-wrapper">
                    <p class="amount-paid"><strong>Amount Paid:</strong>
                        <?php echo get_woocommerce_currency_symbol() . $order->get_total() ?>
                    </p>
                    <p><strong>Remaining Amount:</strong>
                        <?php echo get_woocommerce_currency_symbol() . $remaining_payment ?>
                    </p>
                    <p><strong>Payment Link:</strong> <a href="<?php echo $order->get_meta('complete_reservation_url') ?>"
                            target="_blank">
                            <?php echo $order->get_meta('complete_reservation_url') ?>
                        </a>
                </div>
                <div class="reservation-wrapper">
                    <p><strong>Reservation Date:</strong>
                        <?php echo $order->get_date_created()->format('F j, Y') ?>
                    </p>
                    <p><strong>Limited Until:</strong>
                        <?php echo date('F j, Y H:i', strtotime($time_to_limit_reservation)) ?>
                    </p>
                    <?php if (!$has_reservation_expired) { ?>
                        <p><strong>Expires At:</strong>
                            <?php echo date('F j, Y H:i', strtotime($expiration_time)) ?>
                        </p>
                    <?php } ?>
                    <?php if ($has_reservation_countdown_started && !$has_reservation_expired) { ?>
                        <p><strong>Time Until Expired:</strong>
                        <div id="expiration-countdown">
                            <?php echo calculate_time_left_with_html(date('Y-m-d H:i:s'), $expiration_time) ?>
                        </div>
                        </p>
                    <?php } else { ?>
                        <p><strong>Reservation Has Expired On:</strong>
                            <?php echo date('F j, Y H:i', strtotime($expiration_time)) ?>
                        </p>
                    <?php } ?>
                </div>
            </div>
        <?php }
        foreach ($items as $item_id => $item) {
            $product = wc_get_product($item->get_product_id());
            $product_type = $product->get_type();
        }
    }

    function add_deposit_status_item_header($order)
    {

    }

    function add_deposit_status_item_values($product = null, $item, $item_id)
    {
        // dd($item);
        // $order = wc_get_order($item->get_order_id());
        // $item_type = $item->get_type();
        // $product_type = $item_type == 'line_item' ? $product->get_type() : '';

    }

    function set_custom_emails($order_id)
    {
        $order = wc_get_order($order_id);
        $retreat_id = $order->get_meta('retreat_id');
        $departure_date = $order->get_meta('departure_date');
        $emails_sent = $order->get_meta('emails_sent');
        $messgase_scheduled = empty($order->get_meta('messages_scheduled'));
        if (!$retreat_id || !$departure_date)
            return;
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $is_deposit_payment = $order->get_meta('is_deposit_payment');
        $action = $is_deposit_payment ? 'deposit_payment_recieved' : 'full_payment_recieved';
        $guests = $order->get_meta('guests');
        $rooms = $order->get_meta('rooms');
        $all_retreat_messages_ids = get_all_retreat_messages_ids();
        $messages_for_guests = [];

        foreach ($rooms as $room_details) {
            $room_id = $room_details['room_id'];
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id'] = $order_id;
        }
        foreach ($all_retreat_messages_ids as $message_id) {
            $message_data = get_post_meta($message_id, 'retreat_message_data', true);
            $schedule = $message_data['schedule'];
            $is_message_for_guests = in_array($retreat_id, $message_data['retreats']) && in_array('guests', $message_data['recipients']);
            if (!$is_message_for_guests)
                continue;
            $messages_for_guests[$message_id] = [];
            foreach ($schedule as $event => $time) {

                $duration = $retreat_data['general_info']['retreat_duration'] - 1;
                $relating_time = date('Y-m-d H:i:s');
                $calc = '+';
                $days = $time['days'];
                $hour = $time['time'];

                switch ($event) {
                    case 'booking':
                        $relating_time = strtotime($order->get_date_created());
                        break;
                    case 'before':
                        $calc = '-';
                        $relating_time = strtotime($departure_date);
                        break;
                    case 'during':
                        $relating_time = strtotime($departure_date);
                        break;
                    case 'after':
                        $relating_time = strtotime('+' . $duration . ' days', strtotime($departure_date));
                        break;
                }

                $scheduled_time = strtotime($calc . $days . ' days ' . $hour, $relating_time);

                if ($scheduled_time < time()) {
                    $scheduled_time = strtotime('+10 minutes', time());
                }
                $messages_for_guests[$message_id][$event] = $scheduled_time;
            }
        }
        foreach ($guests as $idx => $guest) {
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'][$idx]['emails_recieved'] = $messages_for_guests;
        }
        if (empty($emails_sent[$action])) {
            order_custom_email($order, $action);
        }
        if (!$messgase_scheduled) {
            schedule_retreat_email($order_id);
            $order->update_meta_data('messages_scheduled', true);
        }
        if ($is_deposit_payment) {
            schedule_remaining_payment_reminder_email($order_id);
            schedule_expired_reservation_events($order_id);
        }
        $order->save();
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    }

    function reset_custom_session_items($order_id)
    {
        // check if cart is empty
        if (WC()->cart->is_empty()) {
            WC()->session->__unset('is_retreat');
            WC()->session->__unset('retreat_id');
            WC()->session->__unset('departure_date');
        }
    }

    function calculate_fee_for_stripe()
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $chosen_payment_method = WC()->session->get('chosen_payment_method');

        if ($chosen_payment_method == 'stripe_cc') {
            $total_amount = WC()->cart->subtotal;
            $fee = $total_amount * 0.029 + 0.3;

            WC()->cart->add_fee(__('Service Fee', 'eleusinia'), $fee);
        }
        if ($chosen_payment_method == 'stripe_googlepay') {
            $total_amount = WC()->cart->subtotal;
            $fee = $total_amount * 0.029 + 0.3;

            WC()->cart->add_fee(__('Service  Fee', 'eleusinia'), $fee);
        }
    }

    function set_payment_method_update()
    { ?>
        <script type="text/javascript">
            (function ($) {
                $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
                    $('body').trigger('update_checkout');
                });
            })(jQuery);
        </script>
        <?php
    }

    function set_complete_payment_page()
    {
        $is_reservation_confirmed_page = basename(get_permalink()) == 'reservation-confirmation';
        if (!$is_reservation_confirmed_page)
            return;
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $init_action = isset($_GET['init_action']) ? sanitize_text_field($_GET['init_action']) : '';
        $nonce = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';

        if (empty($order_id) || empty($init_action) || empty($nonce))
            return;

        $order = wc_get_order($order_id);
        if (!$order || $init_action !== 'full_payment')
            return;

        $current_status = $order->get_status();
        if ($current_status === 'completed')
            return;

        $deposit_nonce = $order->get_meta('deposit_nonce');
        if ($nonce !== $deposit_nonce)
            return;

        $deposit_payment = $order->get_meta('deposit_payment');
        $remaining_payment = $order->get_meta('remaining_payment');

        $retreat_id = $order->get_meta('retreat_id');
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $departure_date = $order->get_meta('departure_date');
        $rooms = $order->get_meta('rooms');
        $rooms_str = '';
        $overrided_orders_ids = [];

        $order->update_status('wc-completed');
        $order->update_meta_data('is_paid_in_full', true);
        $order->set_total(number_format($deposit_payment + $remaining_payment, 2, '.', ''));

        foreach ($rooms as $idx => $room_details) {
            $room_id = $room_details['room_id'];
            $last_order_id = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id'];
            $expired_orderes_ids = isset($retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids'])
                ? $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids']
                : [];
            if ($order_id == $last_order_id) {
                $overrided_orders_ids = $expired_orderes_ids;
            } else if (in_array($order_id, $expired_orderes_ids)) {
                $overrided_orders_ids = array_diff($expired_orderes_ids, [$order_id]);
                $overrided_orders_ids[] = $last_order_id;
                $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'] = $retreat_data['departure_dates'][$departure_date]['expired_reservations'][$order_id];
            }
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['status'] = 'booked';
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['payments_collected'] += $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['price'];
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id'] = $order_id;

            $rooms_str .= count($rooms) > 1 && $idx !== (count($rooms) - 1)
                ? get_the_title($room_id) . ', '
                : get_the_title($room_id);
        }

        // do foreach that cancells payment links for expired orders
        if (!empty($overrided_orders_ids)) {
            foreach ($overrided_orders_ids as $overrided_order_id) {
                $overrided_order = wc_get_order($overrided_order_id);
                $complete_reservation_link_id = $overrided_order->get_meta('complete_reservation_link_id');
                deactivate_payment_link($complete_reservation_link_id);
                $overrided_order->update_status('wc-on-hold');
                $overrided_order->add_order_note('Reservation has been under booked');
                $overrided_order->update_meta_data('expiration_time', date('Y-m-d H:i:s'));
                $overrided_order->save();
            }
        }

        $order->add_order_note('Payment For Room ' . $rooms_str . ' on ' . $departure_date . ' has been completed');
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
        order_custom_email($order, 'full_payment_recieved');
        $order->save();
    }
    function update_retreat_data($order_id)
    {
        $order = wc_get_order($order_id);
        $order_status = $order->get_status();
        $retreat_id = $order->get_meta('retreat_id');
        $rooms = $order->get_meta('rooms');
        $room_id = $order->get_meta('room_id');
        $departure_date = $order->get_meta('departure_date');
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $current_guests_info = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'];
        $number_of_guests = count($current_guests_info);

        // $retreat_data['departure_dates'][$departure_date]['expired_reservations'][$order->get_id()] = $current_guests_info;
        $retreat_data['departure_dates'][$departure_date]['guests_availability'] += $number_of_guests;
        $retreat_data['departure_dates'][$departure_date]['rooms_availability'] += 1;

        foreach ($rooms as $room_details) {
            $room_id = $room_details['room_id'];
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['order_id'] = '';
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'] = [];
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['is_booked'] = false;
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['status'] = 'available';
            if ($order_status == 'refund') {
                $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['payments_collected'] = 0;
            }
        }

        // $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids'][] = $order->get_id();
        $complete_reservation_link_id = $order->get_meta('complete_reservation_link_id');
        deactivate_payment_link($complete_reservation_link_id);
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    }
}
function display_privacy_statement_metabox($post)
{
    // Retrieve and display your privacy statement content or custom fields here
    $privacy_statement = get_post_meta($post->ID, '_privacy_statement', true);

    // Output the WYSIWYG editor
    wp_editor(
        $privacy_statement,          // Current content of the editor
        '_privacy_statement',        // Name of the textarea and the associated meta key
        array(
            'textarea_name' => '_privacy_statement', // Important for saving the data
            'media_buttons' => true,                  // Display media upload buttons
            'teeny' => false,                         // Use the full editor
            'textarea_rows' => 10,                    // Number of rows for the textarea
        )
    );

    // Add nonce field for security
    wp_nonce_field('privacy_statement_nonce', 'privacy_statement_nonce');
}

// Register Retreat Product Types
function set_retreat_product_type()
{
    class WC_Product_Booking_Retreat extends WC_Product
    {
        public function __construct($product)
        {
            parent::__construct($product);
            $this->is_virtual = 'yes';
            $this->set_sold_individually = 'yes';
            $this->supports[] = 'ajax_add_to_cart';
            // Set the product type
            $this->product_type = 'booking_retreat';
        }

        public function get_type()
        {
            return 'booking_retreat';
        }

        // Add any custom methods or properties for the new product type here
    }

}

function dd($val)
{
    echo '<pre>';
    print_r($val);
    echo '</pre>';
}