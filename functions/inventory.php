<?php
add_shortcode('arma_inventory', function() {
    if (!is_user_logged_in()) return "<p>You must be logged in to view your inventory.</p>";

    $user_id = get_current_user_id();
    $inventory = get_user_meta($user_id, 'arma_inventory', true);
    $balance = get_user_meta($user_id, 'arma_balance', true);

    $output = '<div class="arma-inventory"><h2>Your Inventory</h2>';
    $output .= "<p><strong>Balance:</strong> $" . intval($balance) . "</p>";

    if (!is_array($inventory) || empty($inventory)) {
        return $output . "<p>You have no items.</p></div>";
    }

    // Group items by ID and count them
    $item_counts = array_count_values($inventory);

    foreach ($item_counts as $item_id => $quantity) {
        $item = get_post($item_id);
        if ($item && $item->post_type === 'store_item') {
            $output .= "<div class='arma-inventory-item'>
                <h3>{$item->post_title} (x{$quantity})</h3>
                <p>{$item->post_content}</p>
            </div><hr>";
        }
    }

    return $output . '</div>';
});