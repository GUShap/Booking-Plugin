<?php

function set_retreats_calendar($retreats_data, $is_single = false, $enable_info_element = true)
{
    $margins_departure_date = get_margins_departure_date($retreats_data);
    if (empty($margins_departure_date))
        return;
    $calendar_range_months = get_months_in_range($margins_departure_date['first'], $margins_departure_date['last']);
    ?>
    <div class="retreats-calendar-container<?php echo $is_single ? ' single-retreat' : '' ?>">
        <div class="retreats-calendar-content">
            <?php
            $difference = null;
            $retreat_color = '';
            $bg_color = '#29323f';
            foreach ($calendar_range_months as $month_label => $month_dates) {
                $month_name = date('F', strtotime('01-' . $month_label));
                $month_number = date('m', strtotime('01-' . $month_label));
                $year = date('Y', strtotime('01-' . $month_label));
                $counter = 0;
                $retreat_ids = '';
                $keys = array_keys($calendar_range_months);
                $location = array_search($month_label, $keys);
                $order_class = '';
                if ($location == 0)
                    $order_class = ' first';
                else if ($location == count($calendar_range_months) - 1)
                    $order_class = ' last';
                ?>
                <div class="month-wrapper<?php echo $order_class ?>" data-month="<?php echo $month_name ?>"
                    data-year="<?php echo $year ?>">
                    <div class="month-heading">
                        <h4 class="month-title">
                            <?php echo $month_name ?>
                        </h4>
                    </div>
                    <div class="month-content">
                        <div class="days-of-week-wrapper">
                            <div class="day-wrapper sunday" data-dow="sunday">
                                <p>Su</p>
                            </div>
                            <div class="day-wrapper monday" data-dow="monday">
                                <p>Mo</p>
                            </div>
                            <div class="day-wrapper tuesday" data-dow="tuesday">
                                <p>Tu</p>
                            </div>
                            <div class="day-wrapper wednesday" data-dow="wednesday">
                                <p>We</p>
                            </div>
                            <div class="day-wrapper thursday" data-dow="thursday">
                                <p>Th</p>
                            </div>
                            <div class="day-wrapper friday" data-dow="friday">
                                <p>Fr</p>
                            </div>
                            <div class="day-wrapper saturday" data-dow="saturday">
                                <p>Sa</p>
                            </div>
                        </div>
                        <div class="dates-wrapper">
                            <?php
                            $day_counter = 1;
                            foreach ($month_dates as $date => $day) {
                                $date_value = $year . '-' . $month_number . '-' . sprintf('%02d', $date);
                                $matching_retreats = get_matching_retreats($retreats_data, $date_value);
                                $is_departure_date = !empty($matching_retreats) ? true : false;
                                $is_last_day_of_month = $day_counter == count($month_dates) ? true : false;
                                $is_first_day_of_month = $date == 1 ? true : false;
                                $retreat_ids = '';
                                $is_full_booked = false;
                                $day_args = [
                                    'date' => $date,
                                    'day' => $day,
                                    'date_value' => $date_value,
                                    'is_departure' => false,
                                    'is_trip' => false,
                                    'retreat_id' => '',
                                    'color' => '',
                                    'cell_style' => '',
                                    'has_overlay' => !$is_single,
                                    'wrapper_class' => '',
                                    'wrapper_style' => ''
                                ];

                                if ($is_departure_date) {
                                    $retreat_data = $matching_retreats[0];
                                    $retreat_ids = $retreat_data['id'];
                                    $retreat_duration = $retreat_data['general_info']['retreat_duration'];
                                    $retreat_color = $retreat_data['general_info']['calendar_color'];
                                    $counter = $retreat_duration;
                                    $cell_style = '';
                                    $is_multiple_retreats_on_date = count($matching_retreats) > 1 ? true : false;
                                    if ($is_multiple_retreats_on_date) {
                                        //     $temp_arr = [];
                                        //     foreach ($matching_retreats as $retreat) {
                                        //         $temp_arr[] = $retreat['id'];
                                        //     }
                                        //     $retreat_ids = implode(', ', $temp_arr);
                                    } else {
                                        $is_full_booked = array_every($retreat_data['departure_dates'][$date_value]['rooms_list'], 'is_room_booked');
                                    }

                                    if (!$is_single) {
                                        $cell_style = 'border:2px solid ' . $retreat_color . ';background-color:' . $bg_color . ';';
                                    } else {
                                        $cell_style = 'border:2px solid ' . $retreat_color . ';border-right:none;border-radius:50% 0 0 50%;';
                                    }
                                    $day_args['is_departure'] = true;
                                    $day_args['is_trip'] = true;
                                    $day_args['retreat_id'] = $retreat_ids;
                                    $day_args['color'] = $retreat_color;
                                    $day_args['cell_style'] = $cell_style;
                                    $day_args['has_overlay'] = !$is_single;
                                    if ($is_full_booked) {
                                        $day_args['wrapper_class'] = 'full-booked';

                                    }
                                    set_single_day($day_args);
                                    $day_args['wrapper_class'] = '';
                                    if ($is_last_day_of_month) {
                                        for ($i = 1; $i < $counter; $i++) {
                                            $date_value = date('Y-m-d', strtotime($date_value . ' +1 day'));
                                            $day = strtolower(date('l', strtotime($date_value)));
                                            $is_last = $i == $counter - 1 ? true : false;

                                            $cell_style = $is_last
                                                ? 'border:2px solid ' . $retreat_color . ';border-left:none;border-radius:0 50% 50% 0;'
                                                : 'border-top:2px solid ' . $retreat_color . ';border-bottom:2px solid ' . $retreat_color . ';';

                                            $day_args['is_departure'] = false;
                                            $day_args['is_trip'] = true;
                                            $day_args['date_value'] = $date_value;
                                            $day_args['date'] = date('n', strtotime($date_value)) . ' / ' . $i;
                                            $day_args['day'] = $day;
                                            $day_args['cell_style'] = $cell_style;
                                            $day_args['wrapper_class'] = 'different-month';
                                            $day_args['wrapper_class'] .= $is_last ? ' last' : '';
                                            $day_args['retreat_id'] = $retreat_ids;
                                            set_single_day($day_args);
                                        }
                                        $difference = 0;

                                    }

                                } else {
                                    $is_in_retreat = false;
                                    $is_return_day = false;
                                    if ($difference !== null && $is_first_day_of_month) {
                                        $loop_length = $difference + 1;
                                        for ($i = $loop_length; $i >= 1; $i--) {
                                            $current_is_departue = $i == $loop_length;
                                            $subtract_from_date = $i == 1
                                                ? ' -1 day'
                                                : '-' . $i . ' days';
                                            $current_day = strtolower(date('l', strtotime($date_value . $subtract_from_date)));
                                            $current_cell_style = 'border-top:2px solid ' . $retreat_color . ';border-bottom:2px solid ' . $retreat_color . ';';
                                            if ($current_is_departue) {
                                                $current_cell_style = 'border:2px solid ' . $retreat_color . ';border-right:none;border-radius:50% 0 0 50%;';
                                            }
                                            if ($is_return_day) {
                                                $current_cell_style = 'border:2px solid ' . $retreat_color . ';border-right:none;border-radius:50% 0 0 50%;';
                                            }
                                            $day_args['date_value'] = date('Y-m-d', strtotime($date_value . $subtract_from_date));
                                            $day_args['date'] = date('j', strtotime($day_args['date_value'])) . ' / ' . date('n', strtotime($day_args['date_value']));
                                            $day_args['day'] = $current_day;
                                            $day_args['retreat_id'] = $retreat_ids;
                                            $day_args['is_departure'] = $current_is_departue;
                                            $day_args['is_trip'] = true;
                                            $day_args['wrapper_class'] = ' different-month';
                                            $day_args['cell_style'] = $current_cell_style;
                                            set_single_day($day_args);
                                        }
                                        $counter = $retreat_duration - $loop_length;
                                        $difference = null;
                                    }
                                    $is_in_retreat = $counter > 0 ? true : false;
                                    $is_return_day = $counter == 1 ? true : false;
                                    $wrapper_style = '';
                                    $paragraph_style = '';

                                    if ($is_in_retreat) {                                        
                                        if ($is_return_day && $is_single)
                                            $paragraph_style .= 'border:2px solid ' . $retreat_color . ';border-left:none;border-radius:0 50% 50% 0;';
                                        if (!$is_return_day && !$is_single)
                                            $wrapper_style .= 'background-color:' . $retreat_color . ';';

                                        $paragraph_style .= $is_single
                                            ? 'border-top:2px solid ' . $retreat_color . ';border-bottom:2px solid ' . $retreat_color . '; '
                                            : 'background-color:' . $retreat_color . ';';
                                    }

                                    $day_args['date_value'] = $date_value;
                                    $day_args['date'] = $date;
                                    $day_args['day'] = $day;
                                    $day_args['is_departure'] = false;
                                    $day_args['retreat_id'] = $retreat_ids;
                                    $day_args['is_trip'] = $is_in_retreat;
                                    $day_args['cell_style'] = $paragraph_style;
                                    $day_args['wrapper_class'] .= $is_return_day ? ' last' : '';
                                    $day_args['wrapper_style'] = $wrapper_style;
                                    $day_args['has_overlay'] = $counter == 1 && !$is_single;
                                    set_single_day($day_args);
                                    $day_args['wrapper_style'] = '';

                                    if ($is_in_retreat && $is_last_day_of_month) {
                                        for ($i = 1; $i < $counter; $i++) {
                                            $date_value = date('Y-m-d', strtotime($date_value . ' +1 day'));
                                            $day = strtolower(date('l', strtotime($date_value)));
                                            $date = date('j', strtotime($date_value));
                                            $is_last = $i == $counter - 1 ? true : false;

                                            $cell_style = $is_last
                                                ? 'border:2px solid ' . $retreat_color . ';border-left:none;border-radius:0 50% 50% 0;'
                                                : 'border-top:2px solid ' . $retreat_color . ';border-bottom:2px solid ' . $retreat_color . ';';

                                            $day_args['date_value'] = $date_value;
                                            $day_args['date'] = date('n', strtotime($date_value)) . ' / ' . $i;
                                            $day_args['day'] = $day;
                                            $day_args['cell_style'] = $cell_style;
                                            $day_args['retreat_id'] = $retreat_ids;
                                            $day_args['wrapper_class'] = ' different-month';
                                            set_single_day($day_args);

                                        }
                                        $difference = $retreat_duration - $counter;

                                    }
                                }
                                $day_counter++;
                                $counter--;
                            } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="arrows-container">
            <div class="arrow-right-container arrow-container">
                <button type="button" class="calendar-arrow-button right">&#5125;</button>
            </div>
            <div class="arrow-left-container arrow-container">
                <button type="button" class="calendar-arrow-button left">&#5130;</button>
            </div>
        </div>
        <?php if ($enable_info_element) {
            get_retreat_info_element($retreats_data);
        } ?>
    </div>
    <style>
        :root {
            --months-count:
                <?php echo count($calendar_range_months) ?>
            ;
            --retreat-color:
                <?php echo $retreat_color ?>
            ;
        }
    </style>
    <?php
}

function get_margins_departure_date($retreats_data)
{
    $all_departure_dates = [];
    foreach ($retreats_data as $retreat_id => $retreat) {
        foreach ($retreat['departure_dates'] as $departure_date => $departure_data) {
            if (!empty($departure_data['registration_active']))
                $all_departure_dates[] = $departure_date;
        }
    }
    usort($all_departure_dates, function ($a, $b) {
        return strtotime($a) - strtotime($b);
    });

    if (!empty($all_departure_dates)) {
        return [
            'first' => $all_departure_dates[0],
            'last' => $all_departure_dates[count($all_departure_dates) - 1]
        ];
    } else
        return [];
}

function get_months_in_range($first_departure, $last_departure)
{
    $months = array();
    $current_date = new DateTime($first_departure);

    while ($current_date <= new DateTime($last_departure)) {
        $month_key = $current_date->format('m-Y');
        $months[$month_key] = get_month_dates($current_date->format('Y-m-d'));

        $current_date->modify('first day of next month');
    }
    return $months;
}
function get_month_dates($selected_date)
{
    $dates = array();
    $firstDayOfMonth = new DateTime($selected_date);
    $firstDayOfMonth->modify('first day of this month');

    $lastDayOfMonth = new DateTime($firstDayOfMonth->format('Y-m-t'));

    while ($firstDayOfMonth <= $lastDayOfMonth) {
        $dateNumber = $firstDayOfMonth->format('j'); // Day of the month without leading zeros
        $dayOfWeek = strtolower($firstDayOfMonth->format('l')); // Day of the week in lowercase
        $dates[$dateNumber] = $dayOfWeek;
        $firstDayOfMonth->modify('+1 day');
    }

    return $dates;
}

function set_single_day($args)
{
    $date = $args['date'];
    $day = $args['day'];
    $date_value = $args['date_value'];
    $is_departure = $args['is_departure'] ? "true" : "false";
    $is_trip = $args['is_trip'] ? "true" : "false";
    $retreat_id = $args['retreat_id'];
    $wrapper_class = $args['wrapper_class'];
    $color = $args['color'];
    $cell_style = $args['cell_style'];
    $has_overlay = $args['has_overlay'];
    $wrapper_style = isset($args['wrapper_style']) ? $args['wrapper_style'] : '';
    ?>
    <div class="day-wrapper <?php echo $wrapper_class ?>" data-date="<?php echo $date_value ?>"
        data-day="<?php echo $day ?>" data-departure="<?php echo $is_departure ?>" data-trip="<?php echo $is_trip ?>"
        data-retreat-id="<?php echo $retreat_id ?>" style="<?php echo $wrapper_style ?>">
        <p style="<?php echo $cell_style ?>">
            <span>
                <?php echo $date ?>
            </span>
        </p>
        <?php if ($has_overlay) { ?>
            <div class="overlay" style="background-color:<?php echo $color ?>;"></div>
        <?php } ?>
    </div>

<?php }

function get_matching_retreats($data, $valueToCheck)
{
    $matchingArrays = array();
    $counter = 0;
    foreach ($data as $label => $entry) {
        if (isset($entry['departure_dates']) && array_key_exists($valueToCheck, $entry['departure_dates'])) {
            $matchingArrays[$counter] = $entry;
            $matchingArrays[$counter]['id'] = $label;
            $counter++;
        }
    }

    return $matchingArrays;
}

function get_retreat_info_element($retreats_data)
{
    ?>
    <div class="retreats-info-wrapper">
        <?php foreach ($retreats_data as $retreat_id => $retreat) {
            $product = wc_get_product($retreat_id);
            $short_description = $product->get_short_description();
            $main_image = $product->get_image_id();
            // $gallery_ids = $product->get_gallery_image_ids();
            $retreat_duration = $retreat['general_info']['retreat_duration'];
            $retreat_color = $retreat['general_info']['calendar_color'];
            $retreat_url = get_permalink($retreat_id);
            ?>
            <div class="retreat-info-wrapper" data-id="<?php echo $retreat_id ?>" data-name="<?php echo $retreat['name'] ?>"
                data-selected="false" style="border:0px solid <?php echo $retreat_color ?>;">
                <div class="content-container">
                    <div class="retreat-image">
                        <div class="image-overlay" style="background-color:<?php echo $retreat_color ?>;"></div>
                        <?php echo wp_get_attachment_image($main_image, 'full') ?>
                    </div>
                    <div class="retreat-info-content">
                        <div class="info-heading">
                            <h6 class="retreat-name">
                                <?php echo $retreat['name'] ?>
                            </h6>
                            <p class="retreat-duration">Duration: <span>
                                    <?php echo $retreat_duration; ?>
                                </span> Days</p>
                        </div>
                        <div class="retreat-description">
                            <?php echo $short_description ?>
                        </div>
                        <a href="<?php echo $retreat_url ?>" class="retreat-link"
                            style="background-color:<?php echo $retreat_color ?>;">Book Now</a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php
}

function get_retreat_rooms_info($retreat_data)
{
    $retreat_rooms = $retreat_data['rooms'];
    $retreat_rooms_info = [];
    foreach ($retreat_rooms as $room_id => $room_data) {
        // $room_name = $room_data['name'];
        // $room_price = $room_data['price'];
        // $room_price = $room_price == 0 ? 'Free' : '$'.$room_price;
        // $room_capacity = $room_data['capacity'];
        // $room_type = $room_data['type'];
        // $retreat_rooms_info[] = [
        //     'name' => $room_name,
        //     'price' => $room_price,
        //     'capacity' => $room_capacity,
        //     'type' => $room_type,
        // ];
    }
    return $retreat_rooms_info;
}

function get_retreat_duration()
{
    $retreat_id = get_the_id();
    $retreat_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true))
        ? get_post_meta($retreat_id, 'retreat_product_data', true)
        : [];

    return $retreat_data['general_info']['retreat_duration'];
}

function is_product_type_in_cart($product_type)
{
    // Get the cart
    $cart = WC()->cart;

    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];

        // Check if the product type matches
        if ($product->get_type() === $product_type) {
            return true; // Found the product type in the cart
        }
    }

    return false; // Product type not found in the cart
}

function get_retreat_data()
{
    $retreats_data = [
        get_the_id() => get_post_meta(get_the_id(), 'retreat_product_data', true)
    ];
    return $retreats_data;
}