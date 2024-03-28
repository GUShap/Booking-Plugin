<?php

function scheduled_reservation_expired_callback($order_id)
{
    $order = wc_get_order($order_id);
    $retreat_id = $order->get_meta('retreat_id');
    $room_id = $order->get_meta('room_id');
    $departure_date = $order->get_meta('departure_date');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $current_guests_info = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'];
    $number_of_guests = count($current_guests_info);

    $status = $order->get_status();
    if ($status !== 'wc-deposit-paid' && $status !== 'deposit-paid') {
        return;
    }
    $retreat_data['departure_dates'][$departure_date]['expired_reservations'][$order->get_id()] = $current_guests_info;
    $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'] = [];
    $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['is_booked'] = false;
    $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['status'] = 'available';
    $retreat_data['departure_dates'][$departure_date]['guests_availability'] += $number_of_guests;
    $retreat_data['departure_dates'][$departure_date]['rooms_availability'] += 1;
    $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids'][] = $order->get_id();

    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    $order->update_status('wc-deposit-expired');
    $order->save();
}

add_action('scheduled_reservation_expired', 'scheduled_reservation_expired_callback', 10, 1);


function archive_passed_retreats(){
    $all_retreats_id = get_all_retreats_ids();
    if(empty($all_retreats_id)){
        return;
    }

    foreach($all_retreats_id as $retreat_id){
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $departure_dates = $retreat_data['departure_dates'];
        $duration = get_option('days_after_departure_to_archive_date','');
        $current_date = new DateTime();

        foreach($departure_dates as $date => $date_data){
            $rooms_list = $date_data['rooms_list'];
            $givenDateTime = new DateTime($date);

           $interval = $givenDateTime->diff($current_date);
           $daysDifference = $interval->days;

            if($givenDateTime < $current_date && $daysDifference >= $duration){
                $date_data['status_tag'][] = 'archived';
                $date_data['is_available'] = false;
                $date_data['registration_active'] = false;
                $retreat_data['archived_departure_dates'][$date] = $date_data;
                foreach($rooms_list as $room_id=> $room_data){
                    $room_product_data = get_post_meta($room_id, 'package_product_data', true);
                    $payments_collected = !empty($room_data['payments_collected']) ? $room_data['payments_collected'] : 0;
                     
                    isset($room_product_data['departure_dates'])
                        ? $room_product_data['departure_dates'][] = $date
                        :[];

                    $room_product_data['payments_collected'][$date] = [
                        'retreat_id'=> $retreat_id,
                        'amount' => $payments_collected];

                    update_post_meta( $room_id, 'package_product_data', $room_product_data );
                }

                unset($retreat_data['departure_dates'][$date]);
            }
        }
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    }
}
add_action('init', 'archive_passed_retreats');