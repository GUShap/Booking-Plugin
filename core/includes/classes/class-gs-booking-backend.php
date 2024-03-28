<?php

class GS_Booking_Backend
{
    // Constructor to initialize the class
    public function __construct()
    {
        // Hook the function to register the custom post type to the init action
        add_action('init', array($this, 'register_retreat_rooms_post_type'), 10, 0);
        add_action('init', array($this, 'register_retreat_messages_post_type'), 11, 0);

        // Hook the main menu page function to the admin_menu action
        add_action('admin_menu', array($this, 'gs_bookings_menu_page'));

        add_action('add_meta_boxes', array($this, 'add_retreat_rooms_options_metabox'));
        add_action('add_meta_boxes', array($this, 'add_retreat_rooms_gallery_metabox'));

        add_action('add_meta_boxes', array($this, 'add_retreat_messages_metabox'));

        // Hook the method to save custom data when a "Retreat Room" post is saved
        add_action('save_post', array($this, 'set_cpt_data'));
    }

    // Add the main menu page
    function gs_bookings_menu_page()
    {
        add_menu_page(
            'GS Bookings',              // Page title
            'GS Bookings',              // Menu title
            'manage_options',           // Capability required to access the menu item
            'gs_bookings_page',         // Menu slug (unique identifier)
            array($this, 'display_admin_page'),  // Callback function to display the page
            'dashicons-calendar',       // Icon URL or Dashicon class
            30                          // Position in the menu
        );

        // Add a submenu page for the custom post type "Retreats Archive"
        add_submenu_page(
            'gs_bookings_page',         // Parent menu slug
            'Retreats Management',                  // Page title
            'Retreats Management',         // Menu title
            'manage_options',           // Capability required to access the submenu item
            'retreats-manage',
            array($this, 'display_retreats_manage'),  // Callback function to display the page
        );

        add_submenu_page(
            'gs_bookings_page',         // Parent menu slug
            'Rooms Dashboard',                  // Page title
            'Rooms Dashboard',         // Menu title
            'manage_options',           // Capability required to access the submenu item
            'rooms-stats',
            array($this, 'display_rooms_stats'),  // Callback function to display the page
        );

        add_submenu_page(
            'gs_bookings_page',         // Parent menu slug
            'Options',                  // Page title
            'Options',         // Menu title
            'manage_options',           // Capability required to access the submenu item
            'options',
            array($this, 'display_options'),  // Callback function to display the page
        );

    }

    // Callback function to display the main menu page
    function display_admin_page()
    {
        // Your main menu page content goes here
        echo '<div class="wrap">';
        echo '<h1>GS Bookings Main Page</h1>';
        echo '<p>Main menu page content goes here.</p>';
        echo '</div>';
    }

    function display_rooms_stats()
    {
        $all_rooms_ids = get_all_rooms_ids();
        $all_retreats_ids = get_all_retreats_ids();

        $archived_rooms_data = [];
        foreach ($all_retreats_ids as $retreat_id) {
            $retreat_name = get_the_title($retreat_id);
            $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
            $archived_departure_dates = !empty($retreat_data['archived_departure_dates']) ? $retreat_data['archived_departure_dates'] : [];

            foreach ($archived_departure_dates as $date => $date_data) {
                $retreat_rooms_list = $date_data['rooms_list'];

                foreach ($retreat_rooms_list as $room_id => $room_data) {
                    $payments_collected_for_date = !empty($room_data['payments_collected']) ? $room_data['payments_collected'] : 0;
                    if (empty($archived_rooms_data[$room_id])) {
                        $room_color = !empty(get_post_meta($room_id, 'package_product_data', true)['room_color']) ? get_post_meta($room_id, 'package_product_data', true)['room_color'] : '#f62355';
                        $archived_rooms_data[$room_id] = [
                            'name' => get_the_title($room_id),
                            'color' => $room_color,
                            'total_revenue' => 0,
                            'retreats' => []
                        ];
                    }
                    if ($payments_collected_for_date) {
                        $archived_rooms_data[$room_id]['retreats'][$retreat_id][$date] = $room_data;
                        $archived_rooms_data[$room_id]['total_revenue'] += $payments_collected_for_date;
                    }
                }
            }
        }
        ?>
        <div id="rooms-stats" class="rooms-stats-container">
            <h1>Rooms Stats</h1>
            <div class="rooms-wrapper">
                <ul class="rooms-list">
                    <?php
                    /* INACTIVE CODE */
                    if (empty($archived_rooms_data)) {
                        foreach ($archived_rooms_data as $room_id => $room) {
                            $room_name = $room['name'];
                            $room_data = get_post_meta($room_id, 'package_product_data', true);
                            $total_revenue = $room['total_revenue'];
                            $room_color = !empty($room_data['room_color']) ? $room_data['room_color'] : '#f62355';
                            $item_style = "border:2px solid $room_color;";

                            $is_booked = !empty($room['is_booked']);
                            $guests = !empty($room['guests']) ? $room['guests'] : [];
                            $status = !empty($room['status']) ? $room['status'] : 'available';
                            $expired_orderes_ids = !empty($room['expired_orderes_ids']) ? $room['expired_orderes_ids'] : [];
                            $payments_collected = !empty($room['payments_collected']) ? $room['payments_collected'] : 0;
                            $order_id = !empty($room['order_id']) ? $room['order_id'] : '';

                            $has_content = $is_booked || $payments_collected || $status == 'booked' || $status == 'deposit' || $order_id;
                            ?>
                            <li data-selected="false" data-color="<?php echo $room_color ?>">
                                <div class="room-heading" style="<?php echo $item_style ?>">
                                    <p class="room-name">
                                        <?php echo $room_name ?>
                                    </p>
                                    <p class="status">status: <strong>
                                            <?php echo $status ?>
                                        </strong></p>
                                </div>
                                <div class="room-content" style="<?php echo $item_style ?>">
                                    <?php if (!empty($guests)) { ?>
                                        <div class="room-guests">
                                            <p><strong>Guests</strong></p>
                                            <ul class="room-guests-list">
                                                <?php foreach ($guests as $guest) { ?>
                                                    <li>
                                                        <?php echo $guest['name'] ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                    <?php if (!empty($payments_collected)) { ?>
                                        <div class="payments-collected">
                                            <p><strong>Payments Collected</strong></p>
                                            <p>
                                                <?php echo get_woocommerce_currency_symbol() . number_format($payments_collected) ?>
                                            </p>
                                        </div>
                                    <?php } ?>
                                    <?php if ($status == 'available') { ?>
                                        <div class="room-actions">
                                            <div class="manual-order">
                                                <button type="button" class="manual-booking-btn">
                                                    Book Room Manually
                                                </button>
                                                <form action="post" class="book-room-form" data-active="false">
                                                    <input type="hidden" name="room_id" value="<?php echo $room_id ?>">
                                                    <input type="hidden" name="retreat_id" value="<?php echo $retreat_id ?>">
                                                    <input type="hidden" name="departure_date" value="<?php echo $date ?>">
                                                    <div class="first-guest">
                                                        <p>1st Guest</p>
                                                        <div class="input-wrapper type-text">
                                                            <label for="<?php echo $room_name ?>-fp-name">Name</label>
                                                            <input type="text" name="guests[first][name]"
                                                                id="<?php echo $room_name ?>-fp-name" class="first-guest-name" required>
                                                        </div>
                                                        <div class="input-wrapper type-text">
                                                            <label for="<?php echo $room_name ?>-fp-email">Email</label>
                                                            <input type="email" name="guests[first][email]"
                                                                id="<?php echo $room_name ?>-fp-email" class="first-guest-email" required>
                                                        </div>
                                                        <div class="input-wrapper type-text">
                                                            <label for="<?php echo $room_name ?>-fp-phone">Phone</label>
                                                            <input type="tel" name="guests[first][phone]"
                                                                id="<?php echo $room_name ?>-fp-phone" class="first-guest-phone" required>
                                                        </div>
                                                    </div>
                                                    <div class="second-guest">
                                                        <div class="input-wrapper type-checkbox add-sp">
                                                            <input type="checkbox" name="guests[second][add]"
                                                                id="<?php echo $room_name ?>-add-sp">
                                                            <label for="<?php echo $room_name ?>-add-sp">Add
                                                                2nd Guest?</label>
                                                        </div>
                                                        <div class="second-guest-content">
                                                            <p>2nd Guest</p>
                                                            <div class="input-wrapper type-text">
                                                                <label for="<?php echo $room_name ?>-sp-name">Name</label>
                                                                <input type="text" name="guests[second][name]"
                                                                    id="<?php echo $room_name ?>-sp-name" class="second-guest-name">
                                                            </div>
                                                            <div class="input-wrapper type-text">
                                                                <label for="<?php echo $room_name ?>-sp-email">Email</label>
                                                                <input type="email" name="guests[second][email]"
                                                                    id="<?php echo $room_name ?>-sp-email" class="second-guest-email">
                                                            </div>
                                                            <div class="input-wrapper type-text">
                                                                <label for="<?php echo $room_name ?>-sp-phone">Phone</label>
                                                                <input type="tel" name="guests[second][phone]"
                                                                    id="<?php echo $room_name ?>-sp-phone" class="second-guest-phone">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="order-wrapper">
                                                        <div class="input-wrapper type-checkbox create-order">
                                                            <input type="checkbox" name="order[create_order]"
                                                                id="<?php echo $room_name ?>-create-order">
                                                            <label for="<?php echo $room_name ?>-create-order">Create
                                                                an order?</label>
                                                        </div>
                                                        <div class="order-content">
                                                            <div class="payment-type">
                                                                <select name="order[payment_type]"
                                                                    id="<?php echo $room_name ?>-payment-type">
                                                                    <option value="" selected disabled>Choos
                                                                        Payment Amount</option>
                                                                    <option value="full">Full Payment
                                                                    </option>
                                                                    <option value="deposit">Deposit</option>
                                                                </select>
                                                            </div>
                                                            <div class="payment-method">
                                                                <select name="order[payment_method]"
                                                                    id="<?php echo $room_name ?>-payment-method">
                                                                    <option value="" selected disabled>Choos
                                                                        Payment Method</option>
                                                                    <option value="cash">Cash</option>
                                                                    <option value="credit_card">Credit Card
                                                                    </option>
                                                                    <option value="bank_transfer">Bank
                                                                        Transfer</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </li>
                        <?php }
                    }
                    /*****************/
                    ?>
                    <?php foreach ($archived_rooms_data as $room_id => $room) {
                        $room_data = get_post_meta($room_id, 'package_product_data', true);
                        $payments_collected = $room_data['payments_collected'];
                        $total_revenue = $room['total_revenue'];
                        $room_name = $room['name'];
                        $room_color = !empty($room['color']) ? $room['color'] : '#f62355';
                        $item_style = "border:2px solid $room_color;";
                        $retreats = $room['retreats'];
                        ?>
                        <li data-selected="false" data-color="<?php echo $room_color ?>">
                            <div class="room-heading" style="<?php echo $item_style ?>">
                                <p class="room-name">
                                    <?php echo $room_name ?>
                                </p>
                            </div>
                            <div class="room-content" style="<?php echo $item_style ?>">
                                <div class="retreats-revenue-wrapper">
                                    <h4><strong>Retreats:</strong></h4>
                                    <div class="retreats-content">
                                        <?php
                                        // Retreats
                                        $retreats_count = 0;
                                        foreach ($retreats as $retreat_id => $dates) {
                                            $retreat_title = get_the_title($retreat_id);
                                            ?>
                                            <div class="retreat-wrapper<?php echo count($retreats) > 1 && $retreats_count == 0 ? ' first' : '' ?><?php echo count($retreats) > 1 && $retreats_count == count($retreats) - 1 ? ' last' : '' ?>"
                                                data-current="<?php echo $retreats_count == 0 ? 'true' : 'false' ?>">
                                                <h5>
                                                    <?php echo $retreat_title ?>
                                                </h5>
                                                <div class="retreat-dates">
                                                    <?php foreach ($dates as $date => $date_data) { // Departure Dates
                                                                            $payments_collected_for_date = !empty($date_data['payments_collected']) ? $date_data['payments_collected'] : 0;
                                                                            ?>
                                                        <div class="date-wrapper">
                                                            <div class="tab">
                                                                <p>
                                                                    <?php echo date('F j, Y', strtotime($date)) ?>
                                                                </p>
                                                            </div>
                                                            <div class="content">
                                                                <p>
                                                                    <?php echo get_woocommerce_currency_symbol() . number_format($payments_collected_for_date) ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <?php
                                            $retreats_count++;
                                        } ?>
                                    </div>
                                    <?php if (count($retreats) > 1) { ?>
                                        <div class="arrows-container">
                                            <button type="button" class="prev-arrow">&#10094;</button>
                                            <button type="button" class="next-arrow">&#10095;</button>
                                        </div>
                                    <?php } ?>
                                    <p class="total-room-revenew"><strong>Total Revenue: </strong>
                                        <?php echo get_woocommerce_currency_symbol() . number_format($total_revenue) ?>
                                    </p>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    <?php }

    /* Rooms CPT */
    // Register the custom post type "Retreat Rooms"
    function register_retreat_rooms_post_type()
    {
        $labels = array(
            'name' => 'Rooms',
            'singular_name' => 'Retreat Room',
            'menu_name' => 'Rooms',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Retreat Room',
            'edit_item' => 'Edit Retreat Room',
            'new_item' => 'New Retreat Room',
            'view_item' => 'View Retreat Room',
            'search_items' => 'Search Retreat Rooms',
            'not_found' => 'No Retreat Rooms found',
            'not_found_in_trash' => 'No Retreat Rooms found in Trash',
            'parent_item_colon' => 'Parent Retreat Room:',
            'all_items' => 'Rooms',
            'archives' => 'Retreat Room Archives',
            'insert_into_item' => 'Insert into Retreat Room',
            'uploaded_to_this_item' => 'Uploaded to this Retreat Room',
            'featured_image' => 'Featured Image',
            'set_featured_image' => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image' => 'Use as featured image',
            'public' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'menu_position' => 10,  // Adjust the position as needed
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category', 'post_tag'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'query_var' => true,
            'rewrite' => array('slug' => 'retreat_rooms'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-hammer',  // Customize the icon as needed
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('retreat_rooms', $args);
    }
    public function add_retreat_rooms_gallery_metabox()
    {
        add_meta_box(
            'retreat_rooms_gallery_metabox',
            'Gallery',
            array($this, 'retreat_rooms_gallery_metabox_content'),
            'retreat_rooms',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }

    public function retreat_rooms_gallery_metabox_content($post)
    {
        // Get existing gallery images
        $gallery_images = get_post_meta($post->ID, 'room_gallery', true);

        // Output the HTML for the gallery metabox
        ?>
        <p>
            <label for="gallery_images">Gallery Images:</label>
            <input type="button" class="button button-secondary" value="Select Images" onclick="uploadGalleryImages();">
            <input type="hidden" name="gallery_images" id="gallery_images" value="<?php echo esc_attr($gallery_images); ?>">
        <ul id="gallery-preview">
            <?php
            if (!empty($gallery_images)) {
                foreach ($gallery_images as $image_label => $image_id) { ?>
                    <li class="gallery-item">
                        <button type="button" class="remove-image-button">&#215;</button>
                        <?php echo wp_get_attachment_image($image_label, 'thumbnail') ?>
                        <input type="hidden" id="image-input-<?php echo $image_label ?>" name="gallery[<?php echo $image_label ?>]"
                            value="<?php echo $image_label ?>">
                    </li>
                <?php }
            } ?>
        </ul>
        </p>
    <?php }

    // Function to add the custom metabox
    public function add_retreat_rooms_options_metabox()
    {
        add_meta_box(
            'retreat_rooms_metabox',
            'Retreat Room Options',
            array($this, 'retreat_rooms_metabox_content'),
            'retreat_rooms',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }

    public function retreat_rooms_metabox_content($post)
    {
        // Use the post ID to get the product data
        $room_id = $post->ID;
        $this->set_package_product_type_tab_content($room_id);
    }
    public function set_cpt_data($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'retreat_rooms') {
            $this->save_package_custom_data($post_id);
        }
        if (isset($_POST['post_type']) && $_POST['post_type'] == 'retreat_messages') {
            $this->save_retreat_message_data($post_id);
        }
    }

    private function set_package_product_type_tab_content($room_id)
    {
        $product_custom_data = get_post_meta($room_id, 'package_product_data', true);
        $max_room_capacity = !empty($product_custom_data['max_room_capacity']) ? $product_custom_data['max_room_capacity'] : '';
        $room_color = !empty($product_custom_data['room_color']) ? $product_custom_data['room_color'] : '';
        $beds = !empty($product_custom_data['beds']) ? $product_custom_data['beds'] : '';
        $amenities = !empty($product_custom_data['amenities']) ? $product_custom_data['amenities'] : '';

        ?>
        <div id="package_attr" class="panel woocommerce_options_panel custom-tab-content">
            <div class="package-attr-headline">
                <h4 class="attr-title custom-tab-title">Package Attributes</h4>
            </div>
            <div class="package-attr-content custom-tab-data">
                <section class="general-info-wrapper">
                    <?php
                    $max_guests_select_args = [
                        'id' => 'max_room_capacity',
                        'label' => 'Max Guests Capacity',
                        'wrapper_class' => 'room-capacity-wrapper',
                        'selected_val' => $max_room_capacity
                    ];
                    $this->set_numbers_select($max_guests_select_args, 4);
                    ?>
                    <div class="room-color-wrapper">
                        <label for="room_color">Room Color</label>
                        <input type="color" name="room_color" id="room_color" value="<?php echo $room_color ?>">
                    </div>
                </section>
                <section class="beds-wrapper">
                    <h5 class="bed-title section-title">Beds Options</h5>
                    <div class="options-wrapper">
                        <div class="king-bed-wrapper bed-type-wrapper">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" name="beds[king][has_beds]" id="king_bed_checkbox" <?php echo !empty($beds['king']['has_beds']) ? 'checked' : '' ?>>
                                <label for="king_bed_checkbox">King Beds</label>
                            </div>
                            <?php
                            $king_bed_select_args = [
                                'id' => 'beds[king][number]',
                                'wrapper_class' => 'number-of-king-bed-wrapper number-of-beds-wrapper',
                                'selected_val' => !empty($beds['king']['number']) ? $beds['king']['number'] : ''
                            ];
                            $this->set_numbers_select($king_bed_select_args, 4);
                            ?>
                        </div>
                        <div class="queen-bed-wrapper bed-type-wrapper">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" name="beds[queen][has_beds]" id="queen_bed_checkbox" <?php echo !empty($beds['queen']['has_beds']) ? 'checked' : '' ?>>
                                <label for="queen_bed_checkbox">Queen Beds</label>
                            </div>
                            <?php
                            $queen_bed_select_args = [
                                'id' => 'beds[queen][number]',
                                'wrapper_class' => 'number-of-queen-bed-wrapper number-of-beds-wrapper',
                                'selected_val' => !empty($beds['queen']['number']) ? $beds['queen']['number'] : ''

                            ];
                            $this->set_numbers_select($queen_bed_select_args, 4);
                            ?>
                        </div>
                        <div class="double-bed-wrapper bed-type-wrapper">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" name="beds[double][has_beds]" id="double_bed_checkbox" <?php echo !empty($beds['double']['has_beds']) ? 'checked' : '' ?>>
                                <label for="double_bed_checkbox">Double Beds</label>
                            </div>
                            <?php
                            $double_bed_select_args = [
                                'id' => 'beds[double][number]',
                                'wrapper_class' => 'number-of-double-bed-wrapper number-of-beds-wrapper',
                                'selected_val' => !empty($beds['double']['number']) ? $beds['double']['number'] : ''
                            ];
                            $this->set_numbers_select($double_bed_select_args, 4);
                            ?>
                        </div>
                        <div class="single-bed-wrapper bed-type-wrapper">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" name="beds[single][has_beds]" id="single_bed_checkbox" <?php echo !empty($beds['single']['has_beds']) ? 'checked' : '' ?>>
                                <label for="single_bed_checkbox">Single Beds</label>
                            </div>
                            <?php
                            $single_bed_select_args = [
                                'id' => 'beds[single][number]',
                                'wrapper_class' => 'number-of-single-bed-wrapper number-of-beds-wrapper',
                                'selected_val' => !empty($beds['single']['number']) ? $beds['single']['number'] : ''
                            ];
                            $this->set_numbers_select($single_bed_select_args, 4);
                            ?>
                        </div>
                    </div>
                </section>
                <section class="amenities-wrapper">
                    <h5 class="amenities-title section-title">Amenities</h5>
                    <div class="options-wrapper">
                        <div class="fireplace-checkbox-wrapper checkbox-wrapper">
                            <input type="checkbox" name="amenities[fireplace_checkbox]" id="amenities[fireplace_checkbox]" <?php echo !empty($amenities['fireplace']) ? 'checked' : '' ?>>
                            <label for="amenities[fireplace_checkbox]">Fireplace</label>
                        </div>
                        <div class="bathroom-checkbox-wrapper checkbox-wrapper">
                            <input type="checkbox" name="amenities[bathroom_checkbox]" id="amenities[bathroom_checkbox]" <?php echo !empty($amenities['bathroom']) ? 'checked' : '' ?>>
                            <label for="amenities[bathroom_checkbox]">Private Bathroom</label>
                        </div>
                        <div class="meals-checkbox-wrapper checkbox-wrapper">
                            <input type="checkbox" name="amenities[meals_checkbox]" id="amenities[meals_checkbox]" <?php echo !empty($amenities['meals']) ? 'checked' : '' ?>>
                            <label for="amenities[meals_checkbox]">Meals</label>
                        </div>
                        <div class="activities-checkbox-wrapper checkbox-wrapper">
                            <input type="checkbox" name="amenities[activities_checkbox]" id="amenities[activities_checkbox]"
                                <?php echo !empty($amenities['activities']) ? 'checked' : '' ?>>
                            <label for="amenities[activities_checkbox]">Activities</label>
                        </div>
                        <div class="pickup-checkbox-wrapper checkbox-wrapper">
                            <input type="checkbox" name="amenities[pickup_checkbox]" id="amenities[pickup_checkbox]" <?php echo !empty($amenities['pickup']) ? 'checked' : '' ?>>
                            <label for="amenities[pickup_checkbox]">Airport Pickup</label>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    <?php }

    private function save_package_custom_data($room_id)
    {
        $package_data = !empty(get_post_meta($room_id, 'package_product_data', true))
            ? get_post_meta($room_id, 'package_product_data', true)
            : [];

        $max_room_capacity = $_POST['max_room_capacity'];
        $room_color = $_POST['room_color'];
        $amenities = $_POST['amenities'];
        $beds = $_POST['beds'];

        if (!empty($max_room_capacity)) {
            $package_data['max_room_capacity'] = $max_room_capacity;
        }
        if (!empty($room_color)) {
            $package_data['room_color'] = $room_color;
        }
        if (!empty($beds)) {
            $package_data['beds'] = $beds;
        }
        if (!empty($amenities)) {
            $package_data['amenities'] = [
                'fireplace' => !empty($amenities['fireplace_checkbox']) ? $amenities['fireplace_checkbox'] : '',
                'bathroom' => !empty($amenities['bathroom_checkbox']) ? $amenities['bathroom_checkbox'] : '',
                'meals' => !empty($amenities['meals_checkbox']) ? $amenities['meals_checkbox'] : '',
                'activities' => !empty($amenities['activities_checkbox']) ? $amenities['activities_checkbox'] : '',
                'pickup' => !empty($amenities['pickup_checkbox']) ? $amenities['pickup_checkbox'] : '',
            ];
        }
        if (!empty($_POST['gallery'])) {
            update_post_meta($room_id, 'room_gallery', $_POST['gallery']);
        } else {
            delete_post_meta($room_id, 'room_gallery');
        }

        update_post_meta($room_id, 'package_product_data', $package_data);
    }

    private function set_numbers_select($args, $number_of_options)
    {
        $id = $args['id'];
        $label = !empty($args['label']) ? $args['label'] : '';
        $wrapper_class = !empty($args['wrapper_class']) ? $args['wrapper_class'] : '';
        $selected_val = !empty($args['selected_val']) ? $args['selected_val'] : 0;
        ?>
        <div class="select-wrapper <?php echo $wrapper_class ?>">
            <label for="<?php echo $id ?>">
                <?php echo $label ?>
            </label>
            <select name="<?php echo $id ?>" id="<?php echo $id ?>">
                <?php for ($i = 1; $i <= $number_of_options; $i++) { ?>
                    <option value="<?php echo $i ?>" <?php echo $selected_val == $i ? 'selected' : '' ?>>
                        <?php echo $i ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    <?php }

    /* Retreats Messages CPT */
    function register_retreat_messages_post_type()
    {
        $labels = array(
            'name' => 'Retreats Messages',
            'singular_name' => 'Retreat Message',
            'menu_name' => 'Retreats Messages',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Retreat Message',
            'edit_item' => 'Edit Retreat Message',
            'new_item' => 'New Retreat Message',
            'view_item' => 'View Retreat Message',
            'search_items' => 'Search Retreat Messages',
            'not_found' => 'No Retreat Messages found',
            'not_found_in_trash' => 'No Retreat Messages found in Trash',
            'parent_item_colon' => 'Parent Retreat Message:',
            'all_items' => 'Retreats Messages',
            'archives' => 'Retreat Messages Archives',
            'insert_into_item' => 'Insert into Retreat Message',
            'uploaded_to_this_item' => 'Uploaded to this Retreat Message',
            'featured_image' => 'Featured Image',
            'set_featured_image' => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image' => 'Use as featured image',
            'public' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'menu_position' => 10,  // Adjust the position as needed
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category', 'post_tag'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'query_var' => true,
            'rewrite' => array('slug' => 'retreat_messages'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-email-alt',  // Customize the icon as needed
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('retreat_messages', $args);
    }

    function add_retreat_messages_metabox()
    {
        add_meta_box(
            'retreat_messages_metabox',
            'Message Options',
            array($this, 'retreat_messages_metabox_content'),
            'retreat_messages',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }

    function retreat_messages_metabox_content($post)
    {
        // Use the post ID to get the product data
        $message_id = $post->ID;

        $message_data = get_post_meta($message_id, 'retreat_message_data', true);
        $message_subject = !empty($message_data['subject']) ? $message_data['subject'] : '';
        $retreats_ids = !empty($message_data['retreats']) ? $message_data['retreats'] : [];
        $recipients = !empty($message_data['recipients']) ? $message_data['recipients'] : [];

        $schedule = !empty($message_data['schedule']) ? $message_data['schedule'] : [];
        $schedule_booking = !empty($schedule['booking']) ? $schedule['booking'] : [];
        $schedule_before = !empty($schedule['before']) ? $schedule['before'] : [];
        $schedule_during = !empty($schedule['during']) ? $schedule['during'] : [];
        $schedule_after = !empty($schedule['after']) ? $schedule['after'] : [];

        $attachment = !empty($message_data['attachment']) ? $message_data['attachment'] : [];
        $attachment_id = !empty($attachment['id']) ? $attachment['id'] : '';
        $attachment_url = !empty($attachment['url']) ? $attachment['url'] : '';

        $all_retreats_ids = get_all_retreats_ids();
        ?>
        <div id="retreat-message-options" class="">
            <div class="message-options-content">
                <div class="input-wrapper subject">
                    <label for="message_subject">Message Subject</label>
                    <input type="text" name="subject" id="message_subject" placeholder="<?php echo $post->post_title ?>"
                        value="<?php echo $message_subject ?>">
                </div>
                <div class="group-wrapper checkbox choose-retreats">
                    <h4>Choose Retreats</h4>
                    <div class="content">
                        <?php foreach ($all_retreats_ids as $retreat_id) {
                            $checked_prop = in_array($retreat_id, $retreats_ids) ? ' checked' : '';
                            ?>
                            <div class="input-wrapper type-checkbox">
                                <input type="checkbox" name="retreats[<?php echo $retreat_id ?>]"
                                    id="retreat_<?php echo $retreat_id ?>" value="<?php echo $retreat_id ?>" <?php echo $checked_prop ?>>
                                <label for="retreat_<?php echo $retreat_id ?>">
                                    <?php echo get_the_title($retreat_id); ?>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="group-wrapper checkbox choose-recipients-list">
                    <h4>Choose Recipients</h4>
                    <div class="content">
                        <div class="input-wrapper type-checkbox">
                            <input type="checkbox" name="recipients[]" id="guests_rec" value="guests" <?php echo in_array('guests', $recipients) ? 'checked' : '' ?>>
                            <label for="guests_rec">Guests</label>
                        </div>
                        <div class="input-wrapper type-checkbox">
                            <input type="checkbox" name="recipients[]" id="waitlist_rec" value="waitlist" <?php echo in_array('waitlist', $recipients) ? 'checked' : '' ?>>
                            <label for="waitlist_rec">Waitlist</label>
                        </div>
                    </div>
                </div>
                <div class="group-wrapper checkbox schedule-message">
                    <h4>When To Schedule Message?</h4>
                    <div class="content">
                        <div class="after-booking timing-wrapper">
                            <div class="input-wrapper type-checkbox">
                                <input type="checkbox" role="activate" name="schedule[booking][is_scheduled]"
                                    id="schedule_booking" value="true" <?php echo !empty($schedule_booking) ? 'checked' : '' ?>>
                                <label for="schedule_booking">After Booking</label>
                            </div>
                            <div class="input-wrapper type-text">
                                <input type="number" role="days" name="schedule[booking][days]" id="days_booking" min="0"
                                    value="<?php echo $schedule_booking['days'] ?>">
                                <label for="days_booking">Days</label>
                            </div>
                            <div class="input-wrapper type-time">
                                <label for="time_booking">At: </label>
                                <input type="time" role="time" name="schedule[booking][time]" id="time_booking"
                                    value="<?php echo !empty($schedule_booking['time']) ? $schedule_booking['time'] : '12:00' ?>">
                            </div>
                        </div>
                        <div class="before-wrapper timing-wrapper">
                            <div class="input-wrapper type-checkbox">
                                <input type="checkbox" role="activate" name="schedule[before][is_scheduled]"
                                    id="schedule_before" value="true" <?php echo !empty($schedule_before) ? 'checked' : '' ?>>
                                <label for="schedule_before">Before Retreat</label>
                            </div>
                            <div class="input-wrapper type-text">
                                <input type="number" role="days" name="schedule[before][days]" id="days_before" min="1"
                                    value="<?php echo $schedule_before['days'] ?>">
                                <label for="days_before">Days</label>
                            </div>
                            <div class="input-wrapper type-time">
                                <label for="time_before">At: </label>
                                <input type="time" role="time" name="schedule[before][time]" id="time_before"
                                    value="<?php echo !empty($schedule_before['time']) ? $schedule_before['time'] : '12:00' ?>">
                            </div>
                        </div>
                        <div class="during-wrapper timing-wrapper">
                            <div class="input-wrapper type-checkbox">
                                <input type="checkbox" role="activate" name="schedule[during][is_scheduled]"
                                    id="schedule_during" value="true" <?php echo !empty($schedule_during) ? 'checked' : '' ?>>
                                <label for="schedule_during">During Retreat</label>
                            </div>
                            <div class="input-wrapper type-text">
                                <input type="number" role="days" name="schedule[during][days]" id="days_departure" min="0"
                                    value="<?php echo $schedule_during['days'] ?>">
                                <label for="days_departure">Days after departure</label>
                            </div>
                            <div class="input-wrapper type-time">
                                <label for="time_departure">At: </label>
                                <input type="time" role="time" name="schedule[during][time]" id="time_departure"
                                    value="<?php echo !empty($schedule_during['time']) ? $schedule_during['time'] : '12:00' ?>">
                            </div>
                        </div>
                        <div class="after-wrapper timing-wrapper">
                            <div class="input-wrapper type-checkbox">
                                <input type="checkbox" role="activate" name="schedule[after][is_scheduled]" id="schedule_after"
                                    value="true" <?php echo !empty($schedule_after) ? 'checked' : '' ?>>
                                <label for="schedule_after">After Retreat</label>
                            </div>
                            <div class="input-wrapper type-text">
                                <input type="number" role="days" name="schedule[after][days]" id="days_after" min="0"
                                    value="<?php echo $schedule_after['days'] ?>">
                                <label for="days_after">Days</label>
                            </div>
                            <div class="input-wrapper type-time">
                                <label for="time_after">At: </label>
                                <input type="time" role="time" name="schedule[after][time]" id="time_after"
                                    value="<?php echo !empty($schedule_after['time']) ? $schedule_after['time'] : '12:00' ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="input-wrapper type-file attachment"
                    data-selected="<?php echo !empty($attachment_url) ? 'true' : 'false' ?>">
                    <p class="choose-attachment">Attachment: </p>
                    <button id="upload-file-button" type="button" onclick="uploadFileForMail()">
                        <?php echo !empty($attachment_url) ? 'Change' : 'Attache' ?> File
                    </button>
                    <p id="attachment-name">
                        <?php echo !empty($attachment_url) ? basename($attachment_url) : '' ?>
                    </p>
                    <input type="hidden" name="attachment[id]" id="attachment-id" value="<?php echo $attachment_id ?>">
                    <input type="hidden" name="attachment[url]" id="attachment-url" value="<?php echo $attachment_url ?>">
                </div>
            </div>
        </div>
    <?php }

    function save_retreat_message_data($message_id)
    {
        $message_data = !empty(get_post_meta($message_id, 'message_data', true))
            ? get_post_meta($message_id, 'message_data', true)
            : [];
        $message_subject = $_POST['subject'];
        $retreats = $_POST['retreats'];
        $recipients = $_POST['recipients'];
        $schedule = $_POST['schedule'];
        $attachment = $_POST['attachment'];

        $message_data['subject'] = $message_subject;
        $message_data['retreats'] = $retreats;
        $message_data['recipients'] = $recipients;
        $message_data['attachment'] = $attachment;

        if (!empty($schedule)) {
            foreach ($schedule as $key => $value) {
                if ($value['is_scheduled'] == 'true') {
                    unset($value['is_scheduled']);
                    $message_data['schedule'][$key] = $value;
                } else {
                    unset($message_data['schedule'][$key]);
                }
            }
        }
        update_post_meta($message_id, 'retreat_message_data', $message_data);
    }

    /* Retreats Management */
    function display_retreats_manage()
    {
        $all_retreats_ids = get_all_retreats_ids();
        ?>
        <div id="retreats-management" class="retreats-management-container">
            <h1>Manage Retreats</h1>
            <div class="retreats-content-wrapper">
                <div class="retreats-wrapper">
                    <?php
                    foreach ($all_retreats_ids as $idx => $retreat_id) {
                        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
                        $retreat_duration = $retreat_data['general_info']['retreat_duration'];
                        $departure_dates = $retreat_data['departure_dates'];
                        $all_retreat_messages_ids = get_all_retreat_messages_ids();
                        $messages_for_guests = [];
                        foreach ($all_retreat_messages_ids as $message_id) {
                            $message_data = get_post_meta($message_id, 'retreat_message_data', true);
                            $enabled_message = in_array($retreat_id, $message_data['retreats']) && in_array('guests', $message_data['recipients']);
                            if (!$enabled_message)
                                continue;
                            $messages_for_guests[] = $message_id;
                        }
                        ?>
                        <div class="single-retreat" data-selected="<?php echo $idx == 0 ? 'true' : 'false' ?>">
                            <div class="retreat-heading">
                                <h3 class="retreat-title">
                                    <?php echo get_the_title($retreat_id) ?>
                                </h3>
                            </div>
                            <div class="retreat-content">
                                <p class="title-echo">
                                    <?php echo get_the_title($retreat_id) ?>
                                </p>
                                <?php if (!empty($departure_dates)) { ?>
                                    <div class="departure-dates">
                                        <div class="departure-dates-content">
                                            <?php
                                            foreach ($departure_dates as $date => $date_data) {
                                                $departure_date = date('F j, Y', strtotime($date));
                                                $rooms_list = $date_data['rooms_list'];
                                                $available_rooms = $date_data['rooms_availability'];
                                                $total_rooms = count($date_data['rooms_list']);
                                                // $guests_availability = $date_data['guests_availability'];
                                                $guests_list = get_departure_date_guests_list($retreat_id, $date);
                                                $expired_reservations = !empty($date_data['expired_reservations']) ? $date_data['expired_reservations'] : [];
                                                $expired_reservations_count = count($expired_reservations);
                                                $expired_reservations_ids = array_keys($expired_reservations);
                                                ?>
                                                <div class="departure-date" data-selected="false">
                                                    <div class="heading">
                                                        <h5>
                                                            <?php echo $departure_date ?>
                                                        </h5>
                                                    </div>
                                                    <div class="content">
                                                        <p class="available-rooms">
                                                            available rooms:
                                                            <?php echo $available_rooms ?>
                                                        </p>
                                                        <p class="expired-reservations-count">expired reservations:
                                                            <?php echo $expired_reservations_count ?>
                                                        </p>
                                                        <div class="rooms">
                                                            <h5>Rooms</h5>
                                                            <ul class="rooms-list">
                                                                <?php
                                                                if (!empty($rooms_list)) {
                                                                    foreach ($rooms_list as $room_id => $room) {
                                                                        $room_name = get_the_title($room_id);
                                                                        $room_data = get_post_meta($room_id, 'package_product_data', true);
                                                                        $room_color = !empty($room_data['room_color']) ? $room_data['room_color'] : '#f62355';
                                                                        $item_style = "border:2px solid $room_color;";

                                                                        $is_booked = !empty($room['is_booked']);
                                                                        $guests = !empty($room['guests']) ? $room['guests'] : [];
                                                                        $status = !empty($room['status']) ? $room['status'] : 'available';
                                                                        $expired_orderes_ids = !empty($room['expired_orderes_ids']) ? $room['expired_orderes_ids'] : [];
                                                                        $payments_collected = !empty($room['payments_collected']) ? $room['payments_collected'] : 0;
                                                                        $order_id = !empty($room['order_id']) ? $room['order_id'] : '';

                                                                        $has_content = $is_booked || $payments_collected || $status == 'booked' || $status == 'deposit' || $order_id;
                                                                        ?>
                                                                        <li data-selected="false" data-color="<?php echo $room_color ?>">
                                                                            <div class="room-heading" style="<?php echo $item_style ?>">
                                                                                <p class="room-name">
                                                                                    <?php echo $room_name ?>
                                                                                </p>
                                                                                <p class="status">status: <strong>
                                                                                        <?php echo $status ?>
                                                                                    </strong></p>
                                                                            </div>
                                                                            <div class="room-content" style="<?php echo $item_style ?>">
                                                                                <?php if (!empty($guests)) { ?>
                                                                                    <div class="room-guests">
                                                                                        <p><strong>Guests</strong></p>
                                                                                        <ul class="room-guests-list">
                                                                                            <?php foreach ($guests as $guest) {
                                                                                                if (empty($guest['name']))
                                                                                                    continue;
                                                                                                ?>
                                                                                                <li>
                                                                                                    <?php echo $guest['name'] ?>
                                                                                                </li>
                                                                                            <?php } ?>
                                                                                        </ul>
                                                                                    </div>
                                                                                <?php } ?>
                                                                                <?php if (!empty($payments_collected)) { ?>
                                                                                    <div class="payments-collected">
                                                                                        <p><strong>Payments Collected</strong></p>
                                                                                        <p>
                                                                                            <?php echo get_woocommerce_currency_symbol() . number_format($payments_collected) ?>
                                                                                        </p>
                                                                                    </div>
                                                                                <?php } ?>
                                                                                <?php if ($status == 'available') { ?>
                                                                                    <div class="room-actions">
                                                                                        <!-- <div class="manual-order">
                                                                                            <button type="button" class="manual-booking-btn">
                                                                                                Book Room Manually
                                                                                            </button>
                                                                                            <form action="post" class="book-room-form"
                                                                                                data-active="false">
                                                                                                <input type="hidden" name="room_id"
                                                                                                    value="<?php // echo $room_id  ?>">
                                                                                                <input type="hidden" name="retreat_id"
                                                                                                    value="<?php // echo $retreat_id  ?>">
                                                                                                <input type="hidden" name="departure_date"
                                                                                                    value="<?php // echo $date  ?>">
                                                                                                <div class="first-guest">
                                                                                                    <p>1st Guest</p>
                                                                                                    <div class="input-wrapper type-text">
                                                                                                        <label
                                                                                                            for="<?php // echo $room_name  ?>-fp-name">Name</label>
                                                                                                        <input type="text" name="guests[first][name]"
                                                                                                            id="<?php // echo $room_name  ?>-fp-name"
                                                                                                            class="first-guest-name" required>
                                                                                                    </div>
                                                                                                    <div class="input-wrapper type-text">
                                                                                                        <label
                                                                                                            for="<?php // echo $room_name  ?>-fp-email">Email</label>
                                                                                                        <input type="email" name="guests[first][email]"
                                                                                                            id="<?php // echo $room_name  ?>-fp-email"
                                                                                                            class="first-guest-email" required>
                                                                                                    </div>
                                                                                                    <div class="input-wrapper type-text">
                                                                                                        <label
                                                                                                            for="<?php // echo $room_name  ?>-fp-phone">Phone</label>
                                                                                                        <input type="tel" name="guests[first][phone]"
                                                                                                            id="<?php // echo $room_name  ?>-fp-phone"
                                                                                                            class="first-guest-phone" required>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="second-guest">
                                                                                                    <div class="input-wrapper type-checkbox add-sp">
                                                                                                        <input type="checkbox"
                                                                                                            name="guests[second][add]"
                                                                                                            id="<?php // echo $room_name  ?>-add-sp">
                                                                                                        <label for="<?php // echo $room_name  ?>-add-sp">Add
                                                                                                            2nd Guest?</label>
                                                                                                    </div>
                                                                                                    <div class="second-guest-content">
                                                                                                        <p>2nd Guest</p>
                                                                                                        <div class="input-wrapper type-text">
                                                                                                            <label
                                                                                                                for="<?php // echo $room_name  ?>-sp-name">Name</label>
                                                                                                            <input type="text"
                                                                                                                name="guests[second][name]"
                                                                                                                id="<?php // echo $room_name  ?>-sp-name"
                                                                                                                class="second-guest-name">
                                                                                                        </div>
                                                                                                        <div class="input-wrapper type-text">
                                                                                                            <label
                                                                                                                for="<?php // echo $room_name  ?>-sp-email">Email</label>
                                                                                                            <input type="email"
                                                                                                                name="guests[second][email]"
                                                                                                                id="<?php // echo $room_name  ?>-sp-email"
                                                                                                                class="second-guest-email">
                                                                                                        </div>
                                                                                                        <div class="input-wrapper type-text">
                                                                                                            <label
                                                                                                                for="<?php // echo $room_name  ?>-sp-phone">Phone</label>
                                                                                                            <input type="tel"
                                                                                                                name="guests[second][phone]"
                                                                                                                id="<?php // echo $room_name  ?>-sp-phone"
                                                                                                                class="second-guest-phone">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="order-wrapper">
                                                                                                    <div
                                                                                                        class="input-wrapper type-checkbox create-order">
                                                                                                        <input type="checkbox"
                                                                                                            name="order[create_order]"
                                                                                                            id="<?php // echo $room_name  ?>-create-order">
                                                                                                        <label
                                                                                                            for="<?php // echo $room_name  ?>-create-order">Create
                                                                                                            an order?</label>
                                                                                                    </div>
                                                                                                    <div class="order-content">
                                                                                                        <div class="payment-type">
                                                                                                            <select name="order[payment_type]"
                                                                                                                id="<?php // echo $room_name  ?>-payment-type">
                                                                                                                <option value="" selected disabled>Choos
                                                                                                                    Payment Amount</option>
                                                                                                                <option value="full">Full Payment
                                                                                                                </option>
                                                                                                                <option value="deposit">Deposit</option>
                                                                                                            </select>
                                                                                                        </div>
                                                                                                        <div class="payment-method">
                                                                                                            <select name="order[payment_method]"
                                                                                                                id="<?php // echo $room_name  ?>-payment-method">
                                                                                                                <option value="" selected disabled>Choos
                                                                                                                    Payment Method</option>
                                                                                                                <option value="cash">Cash</option>
                                                                                                                <option value="credit_card">Credit Card
                                                                                                                </option>
                                                                                                                <option value="bank_transfer">Bank
                                                                                                                    Transfer</option>
                                                                                                            </select>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </form>
                                                                                        </div> -->
                                                                                    </div>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </li>
                                                                    <?php }
                                                                }
                                                                ?>
                                                            </ul>
                                                        </div>
                                                        <div class="guests">
                                                            <h5>Guests</h5>
                                                            <ul class="guests-list">
                                                                <li class="guests-heading">
                                                                    <p>Details</p>
                                                                    <p>Actions</p>
                                                                </li>
                                                                <?php
                                                                if (!empty($guests_list)) {
                                                                    foreach ($guests_list as $guest) {
                                                                        if (empty($guest['name']))
                                                                            continue;
                                                                        $is_main_particiant = !empty($guest['main_participant']);
                                                                        $order_id = !empty($guest['order_id']) ? $guest['order_id'] : 0;
                                                                        ?>
                                                                        <li>
                                                                            <div class="details expanding-wrapper">
                                                                                <strong class="expanding-item-heading">
                                                                                    <?php echo $guest['name'] ?>
                                                                                </strong>
                                                                                <div class="expanding-details expanding-item-content">
                                                                                    <?php if (!empty($guest['email'])): ?>
                                                                                        <p>
                                                                                            <?php echo $guest['email'] ?>
                                                                                        </p>
                                                                                    <?php endif; ?>
                                                                                    <?php if (!empty($guest['phone'])): ?>
                                                                                        <p>
                                                                                            <?php echo $guest['phone'] ?>
                                                                                        </p>
                                                                                    <?php endif; ?>
                                                                                    <p>Room:
                                                                                        <?php echo get_the_title($guest['room_id']) ?>
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="actions expanding-wrapper">
                                                                                <div class="retreat-emails actions-wrapper">
                                                                                    <strong class="expanding-item-heading">retreat emails</strong>
                                                                                    <div class="expanding-actions expanding-item-content">
                                                                                        <!-- <button type="button">Send Deposit Paid Email</button>
                                                                                        <button type="button">Send Reservation Completed
                                                                                            Email</button>
                                                                                        <button type="button">Send reservation Completed
                                                                                            Email</button> -->
                                                                                    </div>
                                                                                </div>
                                                                                <?php if ($is_main_particiant) { ?>
                                                                                    <div class="order-emails actions-wrapper">
                                                                                        <strong class="expanding-item-heading">order emails</strong>
                                                                                        <div class="expanding-actions expanding-item-content">
                                                                                            <button type="button" data-order="<?php echo $order_id ?>"
                                                                                                data-action="deposit_payment_recieved">Send Deposit Paid
                                                                                                Email</button>
                                                                                            <button type="button" data-order="<?php echo $order_id ?>"
                                                                                                data-action="remaining_payment_reminder">Send Remaining
                                                                                                Payment Reminder Email</button>
                                                                                            <button type="button" data-order="<?php echo $order_id ?>"
                                                                                                data-action="full_payment_recieved">Send Reservation
                                                                                                Completed Email</button>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php } ?>
                                                                            </div>
                                                                        </li>
                                                                        <?php
                                                                    }
                                                                }
                                                                ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div class="retreat-actions">
                                    <a href="<?php echo get_edit_post_link($retreat_id) ?>">Edit</a>
                                    <a href="<?php echo get_permalink($retreat_id) ?>">View</a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
    }

    /* Configurations */
    function display_options()
    {
        $hstsk = get_option('hashed_stripe_test_secret_key', '');
        $stsk = decrypt_data($hstsk);
        $stpk = get_option('stripe_test_publishable_key', '');

        $hslsk = get_option('hashed_stripe_live_secret_key', '');
        $slsk = decrypt_data($hslsk);
        $slpk = get_option('stripe_live_publishable_key', '');

        $is_live_mode = get_option('is_stripe_live_mode', '');
        $redirected_page_id = get_option('redirect_after_atc', '');
        $deposit_payment_info_page_id = get_option('deposit_info_page', '');
        $deposit_per_cent = get_option('deposit_per_cent', 0);
        $days_before_deposit_disabled = get_option('days_before_deposit_disabled', '');
        $days_after_departure_to_archive_date = get_option('days_after_departure_to_archive_date', '');
        $all_pages_ids = get_pages_ids();
        ?>
        <div id="options" class="configs-container">
            <h1>Options</h1>
            <form method="post" class="retreat-options-form" id="retreat-options-form">
                <div class="retreat-options-wrapper form-section">
                    <h3>Retreat options</h3>
                    <div class="input-wrapper type-text">
                        <label for="deposit_per_cent">Deposit amount (percentage)</label>
                        <input type="text" name="deposit_per_cent" id="deposit_per_cent"
                            value="<?php echo $deposit_per_cent ?>">
                    </div>
                    <div class="input-wrapper type-text">
                        <label for="days_before_deposit_disabled">When to disable deposit option? (Days Before
                            Departure)</label>
                        <input type="number" min="0" name="days_before_deposit_disabled" id="days_before_deposit_disabled"
                            value="<?php echo $days_before_deposit_disabled ?>">
                    </div>
                    <div class="input-wrapper type-text">
                        <label for="days_after_departure_to_archive_date">When to archive date? (Days After Departure)</label>
                        <input type="number" min="0" name="days_after_departure_to_archive_date"
                            id="days_after_departure_to_archive_date"
                            value="<?php echo $days_after_departure_to_archive_date ?>">
                    </div>
                    <div class="input-wrapper type-select">
                        <label for="redirect-after-atc">Where To Redirect After Add To Cart?</label>
                        <select name="redirect_after_atc" id="redirect_after_atc">
                            <option value="" selected disabled>Choose Page</option>
                            <?php foreach ($all_pages_ids as $page_id) { ?>
                                <option value="<?php echo $page_id ?>" <?php echo $page_id == $redirected_page_id ? 'selected' : '' ?>>
                                    <?php echo get_the_title($page_id) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="input-wrapper type-select">
                        <label for="deposit_info_page">Deposit Payment Info Page</label>
                        <select name="deposit_info_page" id="deposit_info_page">
                            <option value="" selected disabled>Choose Page</option>
                            <?php foreach ($all_pages_ids as $page_id) { ?>
                                <option value="<?php echo $page_id ?>" <?php echo $page_id == $deposit_payment_info_page_id ? 'selected' : '' ?>>
                                    <?php echo get_the_title($page_id) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="stripe-configs-wrapper form-section">
                    <h3>Stripe Configurations</h3>
                    <div class="test mode-wrapper">
                        <h5>Test</h5>
                        <div class="input-wrapper">
                            <label for="stripe_test_publishable_key">Publishable Test Key</label>
                            <input type="text" name="stripe_test_publishable_key" id="stripe_test_publishable_key"
                                value="<?php echo $stpk ?>">
                        </div>
                        <div class="input-wrapper">
                            <label for="stripe_test_secret_key">Secret Test Key</label>
                            <input type="password" name="stripe_test_secret_key" id="stripe_test_secret_key"
                                value="<?php echo $stsk ?>">
                        </div>
                    </div>
                    <div class="live mode-wrapper">
                        <h5>Live</h5>
                        <div class="input-wrapper">
                            <label for="stripe_live_publishable_key">Publishable Live Key</label>
                            <input type="text" name="stripe_live_publishable_key" id="stripe_live_publishable_key"
                                value="<?php echo $slpk ?>">
                        </div>
                        <div class="input-wrapper">
                            <label for="stripe_live_secret_key">Secret Live Key</label>
                            <input type="password" name="stripe_live_secret_key" id="stripe_live_secret_key"
                                value="<?php echo $slsk ?>">
                        </div>
                    </div>
                    <div class="switch-button-wrapper input-wrapper">
                        <p>Turn On Live Mode</p>
                        <input type="checkbox" class="live-mode-switch" id="switch-stripe-live-mode" name="is_stripe_live_mode"
                            <?php echo !empty($is_live_mode) ? 'checked' : '' ?> />
                        <label for="switch-stripe-live-mode"></label>
                    </div>
                </div>
                <input type="submit" value="Save">
            </form>
        </div>
    <?php }

}