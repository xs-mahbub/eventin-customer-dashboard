<?php
/*
Plugin Name: Eventin Customer Dashboard
Description: A shortcode to display Eventin customer purchase and attendee details.
Version: 1.0
Author: M Mahbub
*/

if (!defined('ABSPATH')) exit;

// Register shortcode
add_shortcode('eventin_customer_dashboard', 'eventin_customer_dashboard_callback');

function eventin_customer_dashboard_callback() {
    if (!is_user_logged_in()) {
        return '<p>You must be <a href="' . wp_login_url(get_permalink()) . '">logged in</a> to view your dashboard.</p>';
    }

    $current_user = wp_get_current_user();
    $customer_id = $current_user->ID;
    $paged = max(1, get_query_var('paged', 1));
    $per_page = 10;

    global $wpdb;

    // Get orders for current user
    $order_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'customer_id' AND meta_value = %d", $customer_id));

    if (empty($order_ids)) {
        return '<p>No purchases found.</p>';
    }

    $offset = ($paged - 1) * $per_page;
    $orders = array_slice($order_ids, $offset, $per_page);

    ob_start();
    echo '<div class="etn-dashboard-wrapper">';
    echo '<h2>Your Purchases</h2>';

    
  

    foreach ($orders as $order_id) {
        echo '<table class="etn-order-table" border="1" margin-bottom="5px;" cellpadding="5" cellspacing="0">';
          echo '<thead>
        <tr>
            <th>Purchase Date</th>
            <th>Purchaser</th>
            <th>Email</th>
            <th>Event</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Total Price</th>
            
        </tr>
    </thead><tbody> ';
        $first_name = get_post_meta($order_id, 'customer_fname', true);
        $last_name = get_post_meta($order_id, 'customer_lname', true);
        $email = get_post_meta($order_id, 'customer_email', true);
        $status = get_post_meta($order_id, 'status', true);
        $total_price = get_post_meta($order_id, 'total_price', true);
        $date_time = get_post_meta($order_id, 'date_time', true);
        $payment_method = get_post_meta($order_id, 'payment_method', true);

        $event_id = get_post_meta($order_id, 'event_id', true);
        $event_name = ($event_id) ? get_the_title($event_id) : '—';

        echo '<tr>';
        echo "<td>{$date_time}</td>";
        echo "<td>{$first_name} {$last_name}</td>";
        echo "<td>{$email}</td>";
        echo "<td>{$event_name}</td>";
        echo "<td>{$payment_method}</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$total_price}</td>";
        echo '</tr>';

        // Attendee section per order
        $attendee_post_ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'eventin_order_id' AND meta_value = %d", $order_id));
        $attendee_post_ids = array_filter($attendee_post_ids);

        if (!empty($attendee_post_ids)) {
            echo '<tr><td colspan="7">';
            echo '<strong>Attendees:</strong>';
            echo '<table class="etn-attendee-table" border="1" cellpadding="5" cellspacing="0">';
            echo '<thead><tr><th>Name</th><th>Email</th><th>Ticket</th><th>Scan</th><th>Status</th><th>Download</th></tr></thead><tbody>';

            foreach ($attendee_post_ids as $attendee_id) {
                $name = get_post_meta($attendee_id, 'etn_name', true);
                $attendee_email = get_post_meta($attendee_id, 'etn_email', true);
                $ticket = get_post_meta($attendee_id, 'ticket_name', true);
                $attendee_status = get_post_meta($attendee_id, 'etn_status', true);
                $scan_status = get_post_meta($attendee_id, 'etn_attendeee_ticket_status', true);
                $token = get_post_meta($attendee_id, 'etn_info_edit_token', true);

                if (empty($name) && empty($attendee_email)) continue;

                $download_url = home_url("/etn-attendee/?etn_action=download_ticket&attendee_id={$attendee_id}&etn_info_edit_token={$token}");
                echo "<tr><td>{$name}</td><td>{$attendee_email}</td><td>{$ticket}</td><td>{$scan_status}</td><td>{$attendee_status}</td><td><a href='{$download_url}' class='etn-download-btn' target='_blank'>Download</a></td></tr>";
            }

            echo '</tbody></table>';
            echo '</td></tr>';
        }
    }

    echo '</tbody></table>';

    // Pagination
    $total_pages = ceil(count($order_ids) / $per_page);
    if ($total_pages > 1) {
        echo '<div class="etn-pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<a href="' . get_pagenum_link($i) . '" class="etn-page-link">' . $i . '</a> ';
        }
        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
}

// Add basic styles
add_action('wp_head', function () {
    echo '<style>
    .etn-dashboard-wrapper { font-family: Arial, sans-serif; margin: 20px; }
    .etn-order-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .etn-order-table th, .etn-order-table td { border: 1px solid #ccc; padding: 10px; text-align: left; }
    .etn-order-table th { background-color: #f9f9f9; }
    .etn-attendee-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .etn-attendee-table th, .etn-attendee-table td { border: 1px solid #ddd; padding: 8px; }
    .etn-attendee-table th { background-color: #f4f4f4; }
    .etn-download-btn { background: #21759b; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; }
    .etn-download-btn:hover { background: #1d6a8a; }
    .etn-pagination { margin-top: 20px; }
    .etn-page-link { margin-right: 10px; text-decoration: none; color: #21759b; font-weight: bold; }
    </style>';
});
