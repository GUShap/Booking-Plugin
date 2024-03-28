<?php

class GS_Booking_Retreat_Duration_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'retreat_duration_widget';
    }

    public function get_title()
    {
        return esc_html__('Retreat Duration', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-clock-o";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'duration'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'before-after',
            [
                'label' => esc_html__('Prefix & Sufix', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        $this->add_control(
            'prefix',
            [
                'label' => esc_html__('Prefix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Duration:', 'elementor-addon'),
            ]
        );
        $this->add_control(
            'suffix',
            [
                'label' => esc_html__('Suffix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('days', 'elementor-addon'),
            ]
        );
        $this->end_controls_section();

        // Content Tab End

        // Style Tab Start

        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Text', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .retreat-duration-wrapper' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .retreat-duration-wrapper',
            ]
        );

        $this->end_controls_section();

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreat_duration = get_retreat_duration();
        ?>
        <div class="retreat-duration-wrapper">
            <p class="retreat-duration" style="margin:0;">
                <span class="prefix">
                    <?php echo $settings['prefix'] ?>
                </span>
                <?php echo $retreat_duration ?>
                <span class="suffix">
                    <?php echo $settings['suffix'] ?>
                </span>
            </p>
        </div>

        <?php
    }
}

class GS_Booking_Group_Size_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'group_size_widget';
    }

    public function get_title()
    {
        return esc_html__('Group Size', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-shape";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['group', 'size'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'before-after',
            [
                'label' => esc_html__('Prefix & Sufix', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        $this->add_control(
            'prefix',
            [
                'label' => esc_html__('Prefix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Group Size:', 'elementor-addon'),
            ]
        );
        $this->add_control(
            'suffix',
            [
                'label' => esc_html__('Suffix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('participants', 'elementor-addon'),
            ]
        );
        $this->end_controls_section();

        // Content Tab End

        // Style Tab Start

        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Text', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .group-size-wrapper' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .group-size-wrapper',
            ]
        );
        $this->end_controls_section();

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreat_id = get_the_id();
        $retreat_data = !empty (get_post_meta($retreat_id, 'retreat_product_data', true))
            ? get_post_meta($retreat_id, 'retreat_product_data', true)
            : [];
        $general_info = $retreat_data['general_info'];
        ?>
        <div class="group-size-wrapper">
            <p class="group-size" style="margin:0;">
                <span class="prefix">
                    <?php echo $settings['prefix'] ?>
                </span>
                <?php echo $general_info['min_group_size'] . '-' . $general_info['max_group_size']; ?>
                <span class="suffix">
                    <?php echo $settings['suffix'] ?>
                </span>
            </p>
        </div>

        <?php
    }
}

class GS_Booking_Retreat_Location_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'retreat_location_widget';
    }

    public function get_title()
    {
        return esc_html__('Retreat Location', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-map-pin";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'location'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Text', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .retreat-location' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .retreat-location',
            ]
        );

        $this->end_controls_section();

    }

    protected function render()
    {
        $retreat_id = get_the_id();
        $retreat_data = !empty (get_post_meta($retreat_id, 'retreat_product_data', true))
            ? get_post_meta($retreat_id, 'retreat_product_data', true)
            : [];
        $retreat_loaction_url = !empty ($retreat_data) ? $retreat_data['general_info']['retreat_location_url'] : '#';
        $retreat_address = !empty ($retreat_data) ? $retreat_data['general_info']['retreat_address'] : '';
        ?>
        <div class="retreat-location-wrapper">
            <a href="<?php echo $retreat_loaction_url ?>" target="_blank" class="retreat-location">
                <?php echo $retreat_address; ?>
            </a>
        </div>

        <?php
    }
}

class GS_Booking_Single_Retreat_Calendar extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'single_retreat_calendar';
    }

    public function get_title()
    {
        return esc_html__('Single Retreat Calendar', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-preview-medium";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'calendar'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'before-after',
            [
                'label' => esc_html__('Text Content', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                // 'default' => esc_html__( 'All Retreas', 'elementor-addon' ),
            ]
        );
        $this->add_control(
            'instruction_text',
            [
                'label' => esc_html__('Instruction Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => esc_html__('Select a date to book your retreat', 'elementor-addon'),
            ]
        );
        $this->add_control(
            'retreat_in_cart_text',
            [
                'label' => esc_html__('Retreat In Cart Message', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => esc_html__('Please complete retreat purchase before adding another', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'room_title_text',
            [
                'label' => esc_html__('Rooms List Title', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Available Rooms for:', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'price_prefix',
            [
                'label' => esc_html__('Room Price Prefix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Price for Room:', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('BOOK NOW', 'elementor-addon'),
            ]
        );

        $this->end_controls_section();

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreat_data = get_retreat_data();
        if (empty ($retreat_data) || empty (get_the_id()))
            return;
        $retreat_duration = $retreat_data[get_the_id()]['general_info']['retreat_duration'];
        $is_cart_contains_retreat = is_product_type_in_cart('booking_retreat');
        $product = wc_get_product(get_the_id());
        $upsell_ids = !empty ($product) ? $product->get_upsell_ids() : [];
        ?>
        <div class="book-retreat-container">
            <div class="calendar-container">
                <div class="retreats-calendar-heading">
                    <h5 class="retreats-calendar-title">
                        <?php echo $settings['title'] ?>
                    </h5>
                </div>
                <?php set_retreats_calendar($retreat_data, true, false); ?>
            </div>
            <div class="add-retreat-to-cart-container" date-selected="false" room-selected="false">
                <div class="instruction-text-wrapper">
                    <p class="instruction-text">
                        <?php echo $settings['instruction_text'] ?>
                    </p>
                </div>
                <div class="retreat-dates-container">
                    <p class="dates-prefix">
                        <?php echo $settings['room_title_text'] ?>
                    </p>
                    <p class="retreat-dates-range"></p>
                </div>
                <div class="rooms-list-container" data-active="false" data-duration="<?php echo $retreat_duration ?>">
                    <div class="rooms-dropdown-wrapper">
                        <select class="rooms-options" id="rooms-options-select">
                        </select>
                        <div class="show-rooms-list-wrapper"><button type="button" class="open-list-popup-button">Show Rooms
                                List</button></div>
                        <div class="rooms-list-popup" data-active="false">
                            <div class="list-wrapper">
                                <div class="close-button-wrapper">
                                    <button type="button" class="close-button">&#215;</button>
                                </div>
                                <ul class="rooms-list"></ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="room-price-wrapper">
                    <p class="price-prefix">
                        <?php echo $settings['price_prefix'] ?>
                    </p>
                    <p class="price-text">
                        <span class="currency">
                            <?php echo get_woocommerce_currency_symbol() ?>
                        </span><span class="price"></span>
                    </p>
                    <?php if (!empty ($upsell_ids)) {
                        ?>
                        <div class="completing-products">
                            <?php
                            foreach ($upsell_ids as $upsell_id) {
                                $upsell_product = wc_get_product($upsell_id);
                                $upsell_product_name = $upsell_product->get_name();
                                $upsell_product_price = $upsell_product->get_price();
                                $el_id = str_replace(' ', '-', strtolower($upsell_product_name));
                                $enable_multiple_items = !empty (get_post_meta($upsell_id, '_enable_multiple_items', true));
                                $limit_quantity = !empty (get_post_meta($upsell_id, '_limit_quantity', true));
                                $max_items_limit = get_post_meta($upsell_id, '_max_items_limit', true);
                                $max_attr_txt = $limit_quantity ? 'max="' . $max_items_limit . '"' : '';
                                $is_second_participant = has_term('second-participant', 'product_cat', $upsell_id);
                                ?>
                                <div class="completing-product-wrapper <?php echo $el_id ?>-wrapper" data-second-participant="<?php echo $is_second_participant?'true':'false' ?>">
                                    <div class="<?php echo $el_id ?>-checkbox-wrapper checkbox-wrapper">
                                        <input type="checkbox" class="upsell-checkbox" name="additional[<?php echo $upsell_id ?>]"
                                            id="<?php echo $upsell_id ?>" value="1">
                                        <label for="<?php echo $el_id ?>">Add
                                            <?php echo $upsell_product_name ?>
                                        </label>
                                    </div>
                                    <p class="<?php echo $el_id ?>-price completing-product-price">for
                                        <span class="currency">
                                            <?php echo get_woocommerce_currency_symbol() ?>
                                        </span>
                                        <span class="upsell-price">
                                            <?php echo number_format($upsell_product_price) ?>
                                        </span>
                                    </p>

                                    <?php if ($enable_multiple_items) { ?>
                                        <div class="quantity-wrapper">
                                            <input type="number" class="quantity-input" name="quantity[<?php echo $upsell_id ?>]"
                                                id="<?php echo $upsell_id ?>" min="1" <?php echo $max_attr_txt ?> value="1">
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <div class="book-retreat-wrapper">
                    <button type="button" class="book-retreat-button" role="add-to-cart" disabled>
                        <?php echo $settings['button_text'] ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

class GS_Booking_Reservation_Confirmed extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'reservation_confirmed';
    }

    public function get_title()
    {
        return esc_html__('Reservation Confirmed', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-check";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'reservation', 'confirmed'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'Content',
            [
                'label' => esc_html__('Text Content', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'confirmation_text',
            [
                'label' => esc_html__('Confirmation Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => esc_html__('Your reservation has been confirmed', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'reservation_details_text',
            [
                'label' => esc_html__('Reservation Details Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => esc_html__('Your reservation details:', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('VIEW RESERVATION', 'elementor-addon'),
            ]
        );

        $this->end_controls_section();

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $order_id = $_GET['order_id'];
        $order = wc_get_order($order_id);
        $nonce = $_GET['key'];
        $action = $_GET['init_action'];
        ?>
        <div class="reservation-confirmed-container">
            <div class="confirmation-text-wrapper">
                <p class="confirmation-text">
                    <?php echo $settings['confirmation_text'] ?>
                </p>
            </div>
            <div class="reservation-details-wrapper">
                <p class="reservation-details-text">
                    <?php echo $settings['reservation_details_text'] ?>
                </p>
            </div>
            <div class="view-reservation-wrapper">
                <button type="button" class="view-reservation-button">
                    <?php echo $settings['button_text'] ?>
                </button>
            </div>
        </div>
    <?php }
}