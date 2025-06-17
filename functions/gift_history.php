<?php
add_shortcode('arma_gift_history', function() {
    if (!is_user_logged_in()) return "<p>Please log in to view your gift history.</p>";

    $user_id = get_current_user_id();
    $log = get_user_meta($user_id, 'arma_gift_log', true);
    if (!is_array($log) || empty($log)) return "<p>You haven't sent any gifts yet.</p>";

    $output = "<h2>Gift History</h2><table><tr><th>Date</th><th>To</th><th>Items</th></tr>";
    foreach (array_reverse($log) as $entry) {
        $to_user = get_user_by('ID', $entry['to']);
        $to_name = $to_user ? esc_html($to_user->display_name) : "Unknown";
        $item_list = "";
        foreach ($entry['items'] as $item_id => $qty) {
            $item_name = get_the_title($item_id);
            $item_list .= esc_html($item_name) . " x {$qty}, ";
        }
        $item_list = rtrim($item_list, ", ");
        $time = esc_html($entry['time']);
        $output .= "<tr><td>{$time}</td><td>{$to_name}</td><td>{$item_list}</td></tr>";
    }
    $output .= "</table>";

    return $output;
});