<?php
/**
 * Custom Order Email Template
 */

function order_custom_email($order, $action)
{

    $recipient = $order->get_billing_email();
    // $recipient = 'gushap2021@gmail.com';
    // Email recipient
    $to = $recipient;

    // Email subject
    $subject = get_email_subject($order, $action);

    // Email headers
    $headers[] = 'Content-Type: text/html; charset=UTF-8';

    // Get the email template
    $message = get_email_body_template($order, $action);

    // Send the email
    $sent = wp_mail($to, $subject, $message, $headers);

    $email_data = $order->get_meta('emails_sent');
    // Check if the email was sent successfully
    if ($sent) {
        $email_data[$action] = true;
    } else {
        $email_data[$action] = 'error';
    }
    $order->update_meta_data('emails_sent', $email_data);
    $order->save();
}

/**
 * Function to get the custom email template
 */

function get_email_subject($order, $action)
{
    $subject_text = '';
    if ($action == 'deposit_payment_recieved') {
        $subject_text = deposit_email_subject($order);
    }
    if($action === 'expired_reservation_notice'){
        $subject_text = expired_reservation_notice_subject($order);
    }
    if($action === 'full_payment_recieved'){
        $subject_text = full_payment_email_subject($order);
    }
    if($action ==='remaining_payment_reminder'){
        $subject_text = remaining_payment_reminder_email_subject($order);
    }
    return $subject_text;
}

function get_email_body_template($order, $action)
{
    ob_start();
    if ($action == 'deposit_payment_recieved') {
        deposit_payment_complete_template($order);
    }
    if ($action === 'expired_reservation_notice') {
        expired_reservation_notice_template($order);
    }
    if ($action === 'full_payment_recieved') {
        full_payment_email_template($order);
    }
    if($action ==='remaining_payment_reminder'){
        remaining_payment_reminder_template($order);
    }
    return ob_get_clean();
}

function deposit_email_subject($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    return $order->get_billing_first_name() . ', You Deposit for ' . get_the_title($retreat_id) . ' Retreat has been received';
}

function expired_reservation_notice_subject($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    return $order->get_billing_first_name() . ', Your Reservation for ' . get_the_title($retreat_id) . ' Retreat has Expired';
}

function remaining_payment_reminder_email_subject($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    return $order->get_billing_first_name() . ', Your Remaining Payment for ' . get_the_title($retreat_id) . ' Retreat is Due';
}

function full_payment_email_subject($order)
{
    $retreat_id = $order->get_meta('retreat_id');
    return $order->get_billing_first_name() . ', You have completed your reservation for ' . get_the_title($retreat_id) . ' Retreat!';
}

function deposit_payment_complete_template($order)
{

    do_action('get_order_email_head', $order, 'Deposit Payment Complete');

    do_action('open_email_body');

    do_action('get_order_email_body_heading', $order);

    do_action('open_email_content_section');

    do_action('get_order_email_body_title', $order);

    do_action('get_email_body_dates', $order);

    // do_action('get_email_body_information_button', $order);

    do_action('get_email_body_address', $order);

    do_action('get_email_body_guests', $order);

    do_action('get_email_body_added_items', $order);

    do_action('get_email_body_deposit', $order);

    do_action('get_email_body_order_id', $order);

    do_action('get_email_body_complete_reservation', $order);

    do_action('close_email_content_section');

    do_action('get_email_body_footing');

    do_action('close_email_body');
}

function expired_reservation_notice_template($order)
{
    do_action('get_order_email_head', $order, 'Your Reservation has Expired');

    do_action('open_email_body');

    do_action('get_order_email_body_heading', $order);

    do_action('open_email_content_section');

    do_action('get_order_email_body_title', $order, 'Your Reservation for', 'is Expired');

    do_action('expired_reservation_notice_content', $order);

    do_action('get_email_body_complete_reservation', $order);

    do_action('get_email_body_dates', $order);
    
    do_action('get_email_body_guests', $order);
    
    do_action('get_email_body_added_items', $order);

    do_action('get_email_body_order_id', $order);

    do_action('close_email_content_section');

    do_action('get_email_body_footing');

    do_action('close_email_body');
}

function full_payment_email_template($order)
{
    do_action('get_order_email_head', $order, 'Reservation Complete');

    do_action('open_email_body');

    do_action('get_order_email_body_heading', $order);

    do_action('open_email_content_section');

    do_action('get_order_email_body_title', $order, 'Your Reservation for', 'is Complete');

    do_action('completed_reservation_notice_content', $order);

    do_action('get_email_body_dates', $order);

    // do_action('get_email_body_information_button', $order);

    do_action('get_email_body_address', $order);

    do_action('get_email_body_guests', $order);

    do_action('get_email_body_added_items', $order);

    do_action('get_email_body_order_id', $order);

    do_action('close_email_content_section');

    do_action('get_email_body_footing');

    do_action('close_email_body');
}

function remaining_payment_reminder_template($order)
{
    do_action('get_order_email_head', $order, 'Remaining Payment Reminder');

    do_action('open_email_body');

    do_action('get_order_email_body_heading', $order);

    do_action('open_email_content_section');

    do_action('get_order_email_body_title', $order, 'Your Remaining Payment for', 'is Due');

    do_action('remaining_payment_reminder_content', $order);
    
    do_action('get_email_body_complete_reservation', $order);

    do_action('get_email_body_dates', $order);

    do_action('get_email_body_address', $order);

    do_action('get_email_body_guests', $order);

    do_action('get_email_body_added_items', $order);

    do_action('get_email_body_order_id', $order);

    do_action('close_email_content_section');

    do_action('get_email_body_footing');

    do_action('close_email_body');
}