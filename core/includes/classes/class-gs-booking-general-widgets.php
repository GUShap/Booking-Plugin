<?php class GS_Booking_Calendar_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'gs_booking_calendar_widget';
    }

    public function get_title()
    {
        return esc_html__('GS Booking Calendar', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-calendar";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['calendar', 'retreat', 'booking'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
        	'before-after',
        	[
        		'label' => esc_html__( 'Text Content', 'elementor-addon' ),
        		'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        	]
        );

        // $this->end_controls_section();

        $this->add_control(
        	'title',
        	[
        		'label' => esc_html__( 'Title', 'elementor-addon' ),
        		'type' => \Elementor\Controls_Manager::TEXT,
                // 'default' => esc_html__( 'All Retreas', 'elementor-addon' ),
        	]
        );
        // $this->add_control(
        // 	'suffix',
        // 	[
        // 		'label' => esc_html__( 'Suffix', 'elementor-addon' ),
        // 		'type' => \Elementor\Controls_Manager::TEXT,
        // 	]
        // );
        $this->end_controls_section();

        // // Content Tab End

        // // Style Tab Start

        // $this->start_controls_section(
        //     'section_title_style',
        //     [
        //         'label' => esc_html__('Text', 'elementor-addon'),
        //         'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        //     ]
        // );

        // $this->add_control(
        // 	'text_color',
        // 	[
        // 		'label' => esc_html__( 'Text Color', 'elementor-addon' ),
        // 		'type' => \Elementor\Controls_Manager::COLOR,
        //         'default' => '#000000',
        // 		'selectors' => [
        // 			'{{WRAPPER}} .upcoming-retreat' => 'color: {{VALUE}}',
        // 		],
        // 	]
        // );

        // $this->add_group_control(
        // 	\Elementor\Group_Control_Typography::get_type(),
        // 	[
        // 		'name' => 'content_typography',
        // 		'selector' => '{{WRAPPER}} .upcoming-retreat',
        // 	]
        // );
        // $this->add_control(
        // 	'text_align',
        // 	[
        // 		'label' => esc_html__( 'Alignment', 'elementor-addon' ),
        // 		'type' => \Elementor\Controls_Manager::CHOOSE,
        // 		'options' => [
        // 			'left' => [
        // 				'title' => esc_html__( 'Left', 'elementor-addon' ),
        // 				'icon' => 'eicon-text-align-left',
        // 			],
        // 			'center' => [
        // 				'title' => esc_html__( 'Center', 'elementor-addon' ),
        // 				'icon' => 'eicon-text-align-center',
        // 			],
        // 			'right' => [
        // 				'title' => esc_html__( 'Right', 'elementor-addon' ),
        // 				'icon' => 'eicon-text-align-right',
        // 			],
        // 		],
        // 		'default' => 'center',
        // 		'toggle' => true,
        // 		'selectors' => [
        // 			'{{WRAPPER}} .upcoming-retreat' => 'text-align: {{VALUE}};',
        // 		],
        // 	]
        // );

        // $this->end_controls_section();

        // Style Tab End

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreats_data = $this->get_retreats_data();
        ?>
        <div class="retreats-calendar-heading">
        <h1 class="retreats-calendar-title"><?php echo $settings['title'] ?></h1>
    </div>
    <?php 
        set_retreats_calendar($retreats_data);
    }


    private function get_retreats_data()
    {
        $retreats_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => 'booking_retreat',
                ),
            ),
        ];
        $all_retreats_id = get_posts($retreats_args);

        $retreats_data = [];

        foreach ($all_retreats_id as $retreat_id) {
            $retreats_data[$retreat_id] = get_post_meta($retreat_id, 'retreat_product_data', true);
            $retreats_data[$retreat_id]['name'] = get_the_title($retreat_id);
        }
        return $retreats_data;
    }
}