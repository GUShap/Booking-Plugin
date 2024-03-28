<?php

// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

/**
 * Class Gs_Booking_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		GSBOOKING
 * @subpackage	Classes/Gs_Booking_Run
 * @author		Guy Shapira
 * @since		1.0.0
 */
class Gs_Booking_Run
{

    /**
     * Our Gs_Booking_Run constructor 
     * to run the plugin logic.
     *
     * @since 1.0.0
     */
    function __construct()
    {
        $this->add_hooks();
        $this->set_woocommerce_class();
    }

    /**
     * ######################
     * ###
     * #### WORDPRESS HOOKS
     * ###
     * ######################
     */

    /**
     * Registers all WordPress and plugin related hooks
     *
     * @access	private
     * @since	1.0.0
     * @return	void
     */
    private function add_hooks()
    {

        add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_scripts_and_styles'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts_and_styles'), 20);
        add_action('elementor/widgets/register', array($this, 'activate_gs_booking_widgets'));
        add_action('elementor/elements/categories_registered', array($this, 'add_elementor_widget_categories'));

    }

    /**
     * ######################
     * ###
     * #### WORDPRESS HOOK CALLBACKS
     * ###
     * ######################
     */

    /**
     * Enqueue the backend related scripts and styles for this plugin.
     * All of the added scripts andstyles will be available on every page within the backend.
     *
     * @access	public
     * @since	1.0.0
     *
     * @return	void
     */
    public function enqueue_backend_scripts_and_styles()
    {
        $is_product_edit_page = isset ($_GET['post']) && get_post_type($_GET['post']) === 'product';
        if (!$is_product_edit_page) {
            $post_type = get_post_type(get_the_ID());
            wp_enqueue_style('gsbooking-backend-styles', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), time(), 'all');
            wp_enqueue_script('gsbooking-backend-scripts', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array(), time(), false);
            wp_localize_script(
                'gsbooking-backend-scripts',
                'customVars',
                array(
                    'plugin_name' => __(GSBOOKING_NAME, 'gs-booking'),
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'post_id' => get_the_ID(),  // Adjust this based on how you retrieve the post ID
                    'nonce' => wp_create_nonce('gallery_meta_nonce'),
                    'selected_room_images' => get_post_meta(get_the_ID(), 'room_gallery', true) ? array_values(get_post_meta(get_the_ID(), 'room_gallery', true)) : array(),
                )
            );
        }
    }
    /**
     * Enqueue the frontend related scripts and styles for this plugin.
     * All of the added scripts andstyles will be available on every page within the frontend.
     *
     * @access	public
     * @since	1.0.0
     *
     * @return	void
     */
    public function enqueue_frontend_scripts_and_styles()
    {
        wp_enqueue_style('gsbooking-calendar-widget-style', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/calendar-widget.css', array(), time(), 'all');
        if (is_single()) {
            global $post;
            $product = wc_get_product($post->ID);
            $is_retreat = !empty ($product) && $product->get_type() == 'booking_retreat';
            $is_completing_product = !empty ($product) && has_term('completing-products', 'product_cat', $post->ID);

            if ($is_retreat) {
                $retreat_in_cart = WC()->session->get('is_retreat');
                $retreat_id = WC()->session->get('retreat_id');
                $departure_date = WC()->session->get('departure_date');
                wp_enqueue_style('single-retreat-style', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/single-retreat-style.css', array(), time(), 'all');
                wp_enqueue_script('single-retreat-script', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/single-retreat-script.js', array('jquery'), time(), false);
                wp_localize_script(
                    'single-retreat-script',
                    'customVars',
                    array(
                        'is_mobile' => wp_is_mobile(),
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'retreat_id' => $post->ID,
                        // 'retreat_data' => get_post_meta($post->ID, 'retreat_product_data', true),
                        'rooms_nonce' => wp_create_nonce('rooms_nonce'),
                        'add_to_cart_nonce' => wp_create_nonce('add_to_cart_nonce'),
                        'has_retreat_in_cart' => $retreat_in_cart,
                        'departure_date' => $departure_date,
                        'retreat_item_data' => $retreat_in_cart ? get_available_rooms_for_date($retreat_id, $departure_date) : null
                    )
                );
            }
            if ($is_completing_product) {
                wp_enqueue_style('completing-product-style', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/completing-product-style.css', array(), time(), 'all');
                wp_enqueue_script('completing-product-script', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/completing-product-script.js', array('jquery'), time(), false);
                wp_localize_script(
                    'completing-product-script',
                    'customVars',
                    array(
                        'is_mobile' => wp_is_mobile(),
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'product_id' => $post->ID,
                        'completing_product_nonce' => wp_create_nonce('completing_product_nonce'),
                        'has_retreat_in_cart' => is_product_type_in_cart('booking_retreat'),
                    )
                );
            }
        } else {
            if (!is_checkout() && !is_cart()) {
                wp_enqueue_script('gsbooking-calendar-widget-scripts', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/calendar-widget-script.js', array('jquery'), time(), false);
                wp_localize_script(
                    'gsbooking-calendar-widget-scripts',
                    'customVars',
                    array(
                        'is_mobile' => wp_is_mobile(),
                        'is_single' => is_single(),
                        'ajax_url' => admin_url('admin-ajax.php'),
                    )
                );
            }
        }

        if (is_checkout()) {
            wp_enqueue_style('gsbooking-checkout-style', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/checkout-style.css', array(), time(), 'all');
            wp_enqueue_script('gsbooking-checkout-script', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/checkout-script.js', array('jquery'), time(), false);
            wp_localize_script(
                'gsbooking-checkout-script',
                'customVars',
                array(
                    'is_mobile' => wp_is_mobile(),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'completing_product_nonce' => wp_create_nonce('completing_product_nonce'),
                )
            );
        }

        if (is_cart()) {
            wp_enqueue_style('gsbooking-cart-style', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/cart-style.css', array(), time(), 'all');
            wp_enqueue_script('gsbooking-cart-script', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/cart-script.js', array('jquery'), time(), false);
            wp_localize_script(
                'gsbooking-cart-script',
                'customVars',
                array(
                    'is_mobile' => wp_is_mobile(),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'completing_product_nonce' => wp_create_nonce('completing_product_nonce'),
                )
            );
        }
    }
    private function set_woocommerce_class()
    {
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-woocommerce.php';

            new GS_Booking_Woocommerce;
        }

    }
    public function activate_gs_booking_widgets($widgets_manager)
    {
        if (is_plugin_active('elementor/elementor.php')) {
            require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-general-widgets.php';
            require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-single-retreat-widgets.php';
            require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-single-product-widgets.php';

            // $widgets_manager->register( new \GS_Booking_Upcoming_Retreat_Widget() );
            $widgets_manager->register(new \GS_Booking_Retreat_Duration_Widget());
            $widgets_manager->register(new \GS_Booking_Group_Size_Widget());
            $widgets_manager->register(new \GS_Booking_Retreat_Location_Widget());
            $widgets_manager->register(new \GS_Booking_Single_Retreat_Calendar());
            $widgets_manager->register(new \GS_Booking_Completing_Product_Add_To_Cart());
            // $widgets_manager->register( new \GS_Booking_Add_Retreat_To_Cart());
            if (basename(get_permalink()) == 'reservation-confirmation') {
                $widgets_manager->register(new \GS_Booking_Reservation_Confirmed());
            }

            $widgets_manager->register(new \GS_Booking_Calendar_Widget());
        }
    }
    public function add_elementor_widget_categories($elements_manager)
    {

        $elements_manager->add_category(
            'eleusinia',
            [
                'title' => esc_html__('Eleusinia', 'elementor-addon'),
                'icon' => 'fa fa-plug',
            ]
        );

    }
}