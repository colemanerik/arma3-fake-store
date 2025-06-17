<?php
// Log purchases when items are bought
add_action('init', function() {
    if (isset($_POST['arma_purchase_item']) && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $item_id = intval($_POST['arma_purchase_item']);
        $item = get_post($item_id);
        if ($item && $item->post_type === 'store_item') {
            $history = get_user_meta($user_id, 'arma_purchase_history', true);
            if (!is_array($history)) $history = [];
            $history[] = [
                'item_id' => $item_id,
                'time' => current_time('mysql')
            ];
            update_user_meta($user_id, 'arma_purchase_history', $history);
        }
    }
});

// Shortcode to show purchase history
add_shortcode('arma_purchase_history', function() {
    if (!is_user_logged_in()) return "<p>Please log in to view your purchase history.</p>";
    $user_id = get_current_user_id();
    $history = get_user_meta($user_id, 'arma_purchase_history', true);
    if (!is_array($history) || empty($history)) return "<p>No purchases yet.</p>";

    $output = "<div class='arma-history'><h2>Purchase History</h2><ul>";
    foreach (array_reverse($history) as $entry) {
        $item = get_post($entry['item_id']);
        if (!$item) continue;
        $title = esc_html($item->post_title);
        $time = esc_html($entry['time']);
        $output .= "<li><strong>{$title}</strong> on {$time}</li>";
    }
    $output .= "</ul></div>";
    return $output;
});