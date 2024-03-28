<?php

function get_order_email_head_callback($order, $title_text)
{
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>
            <?php echo $title_text ?>
        </title>
        <?php do_action('get_order_email_body_style', $order) ?>
    </head>
    <?php

}
add_action('get_order_email_head', 'get_order_email_head_callback', 10, 2);

function open_email_body_callback()
{
    ?>

    <body>
        <div class="content-container">
            <div class="email-container">
                <?php
}
add_action('open_email_body', 'open_email_body_callback');

function close_email_body_callback()
{
    ?>
            </div>
        </div>
    </body>

    </html>
    <?php
}
add_action('close_email_body', 'close_email_body_callback');

function open_email_content_section_callback()
{
    ?>
    <section class="content">
        <?php
}
add_action('open_email_content_section', 'open_email_content_section_callback');

function close_email_content_section_callback()
{
    ?>
    </section>
    <?php
}
add_action('close_email_content_section', 'close_email_content_section_callback');

function get_order_email_body_style_callback($order)
{
    // $room_id = $order->get_meta('room_id');
    // $room_image_url = wp_get_attachment_url(get_post_thumbnail_id($room_id));
    ?>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
        }

        .content-container {
            background: linear-gradient(135deg, #f0e7dd 25%, transparent 25%), linear-gradient(315deg, #f0e7dd 25%, #757f9aad 25%);
        }

        @media only screen and (min-width: 768px) {
            .content-container {
                padding: 35px 0;
            }
        }

        .email-container {
            max-width: 500px;
            margin: auto;
            background-color: #f7f4f0;
            border-radius: 5px;
        }

        /* section.heading {
            position: relative;
            display: grid;
            background: url(<?php //echo $room_image_url ?>);
            background-position: center;
            background-size: cover;
            height: 300px;
        } */

        section.heading a {
            width: fit-content;
            height: fit-content;
            padding: 10px;
            background-color: #f0e7dd;
            border-radius: 0 0% 50% 0;
            margin: -1px;
        }

        section.heading img.icon-heading {
            height: 90px;
            width: 90px;
        }

        section.content .title {
            margin: 0;
            padding: 13px 20px;
            font-size: 22px;
            font-family: 'open sans';
            line-height: 1.2;
            border-bottom: 1px solid #757f9aad;
        }

        section.content .wrapper-heading {
            font-size: 20px;
        }

        section.content .float-wrapper {
            padding: 13px 20px 62px;
            line-height: 1.3;
            border-bottom: 1px solid #757f9aad;
        }

        section.content .left {
            float: left;
        }

        section.content .right {
            float: right;
            text-align: right;
        }

        section.content .dates,
        section.content .address {
            font-size: 20px;
        }

        section.content p {
            margin: 0;
            padding: 0;
        }

        section.content .retreat-information,
        section.content .added-items,
        section.content .expired-notice {
            padding: 13px 20px;
            border-bottom: 1px solid #757f9aad;
        }

        section.content a.block-link {
            display: block;
            font-size: 16px;
            padding: 8px 0;
            text-decoration: none;
            text-align: center;
            border-radius: 4px;

        }

        section.content a.retreat-information-btn,
        section.content a.complete-reservation-btn {
            margin: 0;
            color: #fff;
        }

        section.content a.retreat-information-btn {
            background-color: #a1a8ba;
        }

        section.content .address .address-content,
        section.content .address .right {
            font-size: 14px;
        }

        section.content .deposit {
            padding-bottom: 110px;
        }

        section.content .deposit .right {
            max-width: 170px;
            line-height: 1.2;
            text-align: left;
        }

        section.content .deposit .left {
            max-width: 200px;
        }

        section.content .order {
            padding-bottom: 38px;
        }

        section.content .complete-reservation {
            padding: 20px;
            border-bottom: 1px solid #757f9aad;
        }

        section.content a.complete-reservation-btn {
            background-color: #fe5a5c;
        }

        section.footing {
            padding: 15px 0;
            background-color: #f0e7dd;
            border: 1px solid #757f9aad;
            border-left: none;
        }

        section.footing .icons-wrapper {
            display: flex;
            margin: auto;
            width: fit-content;
        }

        section.footing .icons-wrapper a {
            text-decoration: none;
        }

        section.footing .footer-icon {
            height: 30px;
            width: 30px;
        }

        section.footing .copyright {
            text-align: center;
            margin: 10px 0 0;
        }
    </style>
    <?php
}

add_action('get_order_email_body_style', 'get_order_email_body_style_callback', 10, 1);
function get_order_email_body_heading_callback($order)
{
    $rooms = $order->get_meta('rooms');
    $room_id = $rooms[0]['room_id'];
    $room_image_url = wp_get_attachment_url(get_post_thumbnail_id($room_id));
    ?>
    <section class="heading"
        style="position: relative;display: grid;background: url(<?php echo $room_image_url ?>);background-position: center;background-size: cover;height: 300px;">
        <a href="<?php echo get_home_url(); ?>"
            style="width: fit-content;height: fit-content;padding: 10px;background-color: #f0e7dd;border-radius: 0 0% 50% 0;margin: -1px;">
            <img src="<?php echo get_home_url(); ?>/wp-content/uploads/2023/12/cropped-favicon.png" alt="eleusinia icon"
                class="icon-heading" style="height: 90px;width: 90px;">
        </a>
    </section>
    <?php
}
add_action('get_order_email_body_heading', 'get_order_email_body_heading_callback', 10, 1);

function get_email_body_footing_callback()
{
    ?>
    <style>
        section.footing {
            padding: 15px 0;
            background-color: #f0e7dd;
            border: 1px solid #757f9aad;
            border-left: none;
        }

        section.footing .icons-wrapper {
            display: flex;
            margin: auto;
            width: fit-content;
        }

        section.footing .icons-wrapper a {
            text-decoration: none;
        }

        section.footing .footer-icon {
            height: 30px;
            width: 30px;
        }

        section.footing .copyright {
            text-align: center;
            margin: 10px 0 0;
        }
    </style>
    <section class="footing">
        <div class="icons-wrapper">
            <a href="https://www.facebook.com/eleusiniaretreat" target="_blank">
                <img src="<?php echo home_url('wp-content/uploads/2024/01/facebook-icon.png'); ?>"
                    class="facebook-icon footer-icon" alt="facebook icon">
            </a>
            <a href="mailto:info@eleusiniaretreat.com" target="_blank">
                <img src="<?php echo home_url('wp-content/uploads/2024/01/email-envelope-mail-svgrepo-com.png'); ?>"
                    class="email-icon footer-icon" alt="email icon" style="margin:0 20px;">
            </a>
            <a href="https://www.youtube.com/@eleusiniaretreat1182/" target="_blank">
                <img src="<?php echo home_url('wp-content/uploads/2024/01/youtube-outline-svgrepo-com.png'); ?>"
                    class="youtube-icon footer-icon" alt="youtube icon">
            </a>
        </div>
        <p class="copyright">Copyright ©
            <?php echo date('Y') ?> Eleusinia Retreat. All rights reserved
        </p>
    </section>
    <?php
}
add_action('get_email_body_footing', 'get_email_body_footing_callback');

function get_order_email_body_title_callback($order, $prefix = 'Eleusinia', $suffix = '')
{
    $retreat_id = $order->get_meta('retreat_id');
    $rooms = $order->get_meta('rooms');
    $rooms_str = '';

    foreach ($rooms as $idx => $room) {
        $seperator = $idx + 1 === count($rooms) ? ' & ' : ', ';
        if (count($rooms) === 1) {
            $rooms_str = get_the_title($room['room_id']);
            break;
        }
        empty ($rooms_str)
            ? $rooms_str .= get_the_title($room['room_id'])
            : $rooms_str .= $seperator . get_the_title($room['room_id']);
    }

    $rooms_str .= count($rooms) > 1
        ? ' rooms'
        : ' room';

    ?>
    <h3 class="title">
        <?php echo $prefix ?>
        <?php echo get_the_title($retreat_id) ?> retreat,
        <br>
        <?php echo $rooms_str ?>
        <?php echo $suffix ?>
    </h3>
    <?php
}
add_action('get_order_email_body_title', 'get_order_email_body_title_callback', 10, 3);

function get_email_body_dates_callback($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $retreat_duration_calc = $retreat_data['general_info']['retreat_duration'] - 1;
    $departure_date = $order->get_meta('departure_date');
    ?>
    <div class="dates float-wrapper">
        <div class="departure-wrapper left">
            <p class="day wrapper-heading">
                <?php echo date('l', strtotime($departure_date)) ?>
            </p>
            <p class="date">
                <?php echo date('M d, Y', strtotime($departure_date)) ?>
            </p>
        </div>
        <div class="return-wrapper right">
            <p class="day">
                <?php echo date('l', strtotime('+' . $retreat_duration_calc . 'days', strtotime($departure_date))) ?>
            </p>
            <p class="date">
                <?php echo date('M d, Y', strtotime('+' . $retreat_duration_calc . 'days', strtotime($departure_date))) ?>
            </p>
        </div>
    </div>
    <?php
}
add_action('get_email_body_dates', 'get_email_body_dates_callback', 10, 1);

function get_email_body_information_button_callback($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    ?>
    <div class="retreat-information button-wrapper">
        <a href="<?php echo get_home_url(); ?>/retreat-info/?retreat_id=<?php echo $retreat_id ?>/"
            class="retreat-information-btn block-link">See Retreat Information</a>
    </div>
    <?php
}
add_action('get_email_body_information_button', 'get_email_body_information_button_callback', 10, 1);

function get_email_body_address_callback($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    ?>
    <div class="address float-wrapper">
        <div class="left">
            <p class="address-heading wrapper-heading">Address</p>
            <p class="address-content">
                <?php echo $retreat_data['general_info']['retreat_address'] ?>
            </p>
        </div>
        <div class="right">
            <p>&#10003; airport pickup</p>
            <a style="font-size:12px;" href="<?php echo $retreat_data['general_info']['retreat_location_url'] ?>">view
                compound on
                map</a>
        </div>
    </div>
    <?php
}
add_action('get_email_body_address', 'get_email_body_address_callback', 10, 1);

function get_email_body_guests_callback($order)
{
    // $retreat_id = $order->get_meta('retreat_id');
    // $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $guests = $order->get_meta('guests');
    // $second_participant = $order->get_meta('second_participant');
    $number_of_guests = count($guests);
    ?>
    <div class="guests float-wrapper">
        <div class="left">
            <p class="guests-heading wrapper-heading">Guests</p>
            <p class="guests-content" style="font-size:18px">
                <?php echo $number_of_guests ?>
            </p>
        </div>
        <div class="right">
            <p class="guests-heading wrapper-heading">Main Contact</p>
            <p class="guests-content">
                <?php echo $order->get_billing_email() ?>
                <br>
                <?php echo $order->get_billing_phone() ?>
            </p>
        </div>
    </div>
    <?php
}
add_action('get_email_body_guests', 'get_email_body_guests_callback', 10, 1);

function get_email_body_added_items_callback($order)
{
    $added_items = $order->get_meta('added_items');
    $added_items_str = '';
    if (!empty ($added_items)) {
        foreach ($added_items as $item_id) {
            $item_name = get_the_title($item_id);
            empty ($added_items_str)
                ? $added_items_str .= $item_name
                : $added_items_str .= ', ' . $item_name;
        }
        ?>
        <div class="added-items">
            <p class="added-items-heading wrapper-heading">Completing Experiences</p>
            <p class="added-items-content" style="font-size:14px;">
                <?php echo $added_items_str ?>
            </p>
        </div>
        <?php
    }
}
add_action('get_email_body_added_items', 'get_email_body_added_items_callback', 10, 1);

function get_email_body_deposit_callback($order)
{
    $deposit_payment = $order->get_meta('deposit_payment');
    $remaining_payment = $order->get_meta('remaining_payment');
    $expiration_time = $order->get_meta('expiration_time');
    ?>
    <div class="deposit float-wrapper">
        <div class="left">
            <p class="deposit-heading wrapper-heading">Status</p>
            <p class="deposit-content">You've paid
                <strong>
                    <?php echo get_woocommerce_currency_symbol() . number_format($deposit_payment) ?>
                </strong>
                out of
                <strong>
                    <?php echo get_woocommerce_currency_symbol() . number_format($deposit_payment + $remaining_payment) ?>.
                </strong>
                <br>
                <br>
                You can complete your reservation at any time.
            </p>
        </div>
        <div class="right">
            <p>the reservation for the room will hold until
                <strong>
                    <?php echo date('F j, Y', strtotime($expiration_time)) ?>
                </strong> at
                <strong>
                    <?php echo date('H:i', strtotime($expiration_time)) ?>.
                </strong>
                <br>After this date, the room will be available for other guests to book.
            </p>
        </div>
    </div>
    <?php
}

add_action('get_email_body_deposit', 'get_email_body_deposit_callback', 10, 1);
function get_email_body_order_id_callback($order)
{
    ?>
    <div class="order-id float-wrapper">
        <div class="left">
            <p class="order-heading wrapper-heading">Order ID</p>
        </div>
        <div class="right">
            <p class="order-heading wrapper-heading">
                <?php echo '#' . $order->get_id() ?>
            </p>
        </div>
    </div>
    <?php
}
add_action('get_email_body_order_id', 'get_email_body_order_id_callback', 10, 1);

function get_email_body_complete_reservation_callback($order)
{
    $complete_reservation_url = $order->get_meta('complete_reservation_url');
    ?>
    <div class="complete-reservation button-wrapper">
        <a href="<?php echo $complete_reservation_url ?>" class="complete-reservation-btn block-link">Complete Your
            Reservation</a>
    </div>
    <?php
}
add_action('get_email_body_complete_reservation', 'get_email_body_complete_reservation_callback', 10, 1);

function expired_reservation_notice_content_callback($order)
{
    $rooms = $order->get_meta('rooms');
    $rooms_str = '';

    foreach ($rooms as $idx => $room) {
        $seperator = $idx + 1 === count($rooms) ? ' & ' : ', ';
        if (count($rooms) === 1) {
            $rooms_str = get_the_title($room['room_id']);
            break;
        }
        empty ($rooms_str)
            ? $rooms_str .= get_the_title($room['room_id'])
            : $rooms_str .= $seperator . get_the_title($room['room_id']);
    }

    $rooms_str .= count($rooms) > 1
        ? ' rooms are'
        : ' room is';
    ?>
    <div class="expired-notice">
        <p class="wrapper-heading">What does it means?</p>
        <p>It means that "
            <?php echo $rooms_str ?>" now available for other guests to book.
        </p>
        <p><strong>But it's not lost!</strong> As long as the room is still available, You can complete your reservation by
            clicking the <i>"Complete Your Reservation"</i> button and paying the remaining amount.</p>
    </div>
    <?php
}
add_action('expired_reservation_notice_content', 'expired_reservation_notice_content_callback', 10, 1);

function completed_reservation_notice_content_callback($order)
{
    $rooms = $order->get_meta('rooms');
    $rooms_str = '';

    foreach ($rooms as $idx => $room) {
        $seperator = $idx + 1 === count($rooms) ? ' & ' : ', ';
        if (count($rooms) === 1) {
            $rooms_str = get_the_title($room['room_id']);
            break;
        }
        empty ($rooms_str)
            ? $rooms_str .= get_the_title($room['room_id'])
            : $rooms_str .= $seperator . get_the_title($room['room_id']);
    }

    $rooms_str .= count($rooms) > 1
        ? ' rooms'
        : ' room';

    ?>
    <div class="expired-notice">
        <p class="wrapper-heading">You have Completed the your booking for "
            <?php echo $rooms_str ?>"
        </p>
        <p>Now you can start packing your bags and get ready for your retreat!</p>
    </div>
    <?php
}
add_action('completed_reservation_notice_content', 'completed_reservation_notice_content_callback', 10, 1);


function retreat_message_template($order_id, $message_id, $event, $email, $name)
{
    $message_data = get_post_meta($message_id, 'retreat_message_data', true);
    $attachment = $message_data['attachment'];
    $attachment_url = !empty ($attachment) ? $attachment['url'] : '';

    $subject = !empty ($message_data['subject']) ? $message_data['subject'] : get_the_title($message_id);
    $message = get_post_field('post_content', $message_id, 'raw');
    $headers[] = 'Content-Type: text/html; charset=UTF-8;From: Eleusinia Retreat <info@eleusiniaretreat.com>';

    $fname = explode(' ', $name)[0];
    $lname = implode(' ', array_slice(explode(' ', $name)[0], 1));

    $message = str_replace('{{first_name}}', $fname, $message);
    $message = str_replace('{{last_name}}', $lname, $message);
    $message = str_replace('{{name}}', $name, $message);

    $sent = wp_mail($email, $subject, $message, $headers, $attachment_url);
}
add_action('scheduled_retreat_message_template', 'retreat_message_template', 10, 5);

function deposit_order_email($order_id, $action)
{
    $order = wc_get_order($order_id);
    $status = $order->get_status();
    if ($status !== 'deposit-paid' && $status !== 'wc-deposit-paid')
        return;

    order_custom_email($order, $action);
}
add_action('scheduled_deposit_order_email', 'deposit_order_email', 10, 2);


function remaining_payment_reminder_content_callback($order)
{
    $rooms = $order->get_meta('rooms');
    $rooms_str = '';

    foreach ($rooms as $idx => $room) {
        $seperator = $idx + 1 === count($rooms) ? ' & ' : ', ';
        if (count($rooms) === 1) {
            $rooms_str = get_the_title($room['room_id']);
            break;
        }
        empty ($rooms_str)
            ? $rooms_str .= get_the_title($room['room_id'])
            : $rooms_str .= $seperator . get_the_title($room['room_id']);
    }

    $rooms_str .= count($rooms) > 1
        ? ' rooms'
        : ' room';

    ?>
    <div class="expired-notice">
        <p class="wrapper-heading">Payment Reminder</p>

        <p>
            You've paid the deposit for
            <?php echo $rooms_str ?>.
            <br>
            The remaining amount is
            <?php echo get_woocommerce_currency_symbol() . number_format($order->get_meta('remaining_payment')) ?>.
            <i>"Complete Your Reservation"</i> button and paying the remaining amount.
        </p>
    </div>
    <?php
}
add_action('remaining_payment_reminder_content', 'remaining_payment_reminder_content_callback', 10, 1);