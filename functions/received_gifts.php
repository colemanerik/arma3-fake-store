<?php
// Track received gifts when sending
add_action('init', function() {
    if (!is_user_logged_in() || !isset($_POST['submit_gift'])) return;

    $sender_id = get_current_user_id();
    $recipient_id = intval($_POST['gift_target']);
    if (!$recipient_id || $recipient_id === $sender_id) return;

    $gift_data = [];
    foreach ($_POST['gift_qty'] as $item_id => $qty) {
        $qty = intval($qty);
        if ($qty > 0) {
            $gift_data[$item_id] = $qty;
        }
    }

    if (!empty($gift_data)) {
        $inbox = get_user_meta($recipient_id, 'arma_received_gifts', true);
        if (!is_array($inbox)) $inbox = [];

        $inbox[] = [
            'from' => $sender_id,
            'items' => $gift_data,
            'time' => current_time('mysql'),
            'read' => false
        ];

        update_user_meta($recipient_id, 'arma_received_gifts', $inbox);
    }
}, 20); // run after gift processing

// Display received gifts
add_shortcode('arma_received_gifts', function() {
    if (!is_user_logged_in()) return "<p>Please log in to view received gifts.</p>";

    $user_id = get_current_user_id();

    if (isset($_POST['mark_gift_read'])) {
        $index = intval($_POST['gift_index']);
        $inbox = get_user_meta($user_id, 'arma_received_gifts', true);
        if (isset($inbox[$index])) {
            $inbox[$index]['read'] = true;
            update_user_meta($user_id, 'arma_received_gifts', $inbox);
        }
    }

    $inbox = get_user_meta($user_id, 'arma_received_gifts', true);
    if (!is_array($inbox) || empty($inbox)) return "<p>No gifts received yet.</p>";

    $output = "<h2>Gifts Received</h2><table><tr><th>From</th><th>Items</th><th>Date</th><th>Status</th></tr>";

    foreach ($inbox as $i => $gift) {
        $from_user = get_user_by('ID', $gift['from']);
        $from_name = $from_user ? esc_html($from_user->display_name) : "Unknown";

        $items = "";
        foreach ($gift['items'] as $item_id => $qty) {
            $title = get_the_title($item_id);
            $items .= esc_html($title) . " x {$qty}, ";
        }
        $items = rtrim($items, ', ');
        $date = esc_html($gift['time']);
        $status = $gift['read'] ? "Read" : "<form method='POST' style='display:inline;'><input type='hidden' name='gift_index' value='{$i}'><button type='submit' name='mark_gift_read'>Mark as Read</button></form>";
        $output .= "<tr><td>{$from_name}</td><td>{$items}</td><td>{$date}</td><td>{$status}</td></tr>";
    }

    $output .= "</table>";
    return $output;
});