<?php
add_shortcode('arma_gift', function() {
    if (!is_user_logged_in()) return "<p>Please log in to send gifts.</p>";
    $user_id = get_current_user_id();
    $inventory = get_user_meta($user_id, 'arma_inventory', true);
    if (!is_array($inventory) || empty($inventory)) return "<p>You have no items to gift.</p>";

    $users = get_users(['fields' => ['ID', 'display_name']]);
    $counts = array_count_values($inventory);
    $items = get_posts(['post_type' => 'store_item', 'posts_per_page' => -1]);

    ob_start();
    echo "<h2>Gift Items</h2><form method='POST'>";
    echo "<table><tr><th>Item</th><th>Owned</th><th>Quantity to Send</th></tr>";
    foreach ($items as $item) {
        $id = $item->ID;
        if (!isset($counts[$id])) continue;
        $name = esc_html($item->post_title);
        $owned = $counts[$id];
        echo "<tr><td>{$name}</td><td>{$owned}</td>";
        echo "<td><input type='number' name='gift_qty[{$id}]' min='0' max='{$owned}' value='0'></td></tr>";
    }
    echo "</table><label for='gift_target'>Send To:</label> ";
    echo "<select name='gift_target' required><option value=''>--Select Player--</option>";
    foreach ($users as $user) {
        if ($user->ID == $user_id) continue;
        echo "<option value='{$user->ID}'>" . esc_html($user->display_name) . "</option>";
    }
    echo "</select><br><br><button type='submit' name='submit_gift'>Send Gift</button></form>";

    return ob_get_clean();
});

add_action('init', function() {
    if (!is_user_logged_in() || !isset($_POST['submit_gift'])) return;

    $sender_id = get_current_user_id();
    $recipient_id = intval($_POST['gift_target']);
    if (!$recipient_id || $recipient_id === $sender_id) return;

    $sender_inv = get_user_meta($sender_id, 'arma_inventory', true);
    if (!is_array($sender_inv)) $sender_inv = [];
    $counts = array_count_values($sender_inv);

    $to_transfer = [];
    foreach ($_POST['gift_qty'] as $item_id => $qty) {
        $item_id = intval($item_id);
        $qty = intval($qty);
        $available = $counts[$item_id] ?? 0;
        if ($qty > 0 && $qty <= $available) {
            $to_transfer[$item_id] = $qty;
        }
    }

    // Transfer items
    if (!empty($to_transfer)) {
        // Remove from sender
        $new_sender = [];
        foreach ($counts as $item_id => $owned) {
            $give = $to_transfer[$item_id] ?? 0;
            for ($i = 0; $i < ($owned - $give); $i++) $new_sender[] = $item_id;
        }

        // Add to recipient
        $recipient_inv = get_user_meta($recipient_id, 'arma_inventory', true);
        if (!is_array($recipient_inv)) $recipient_inv = [];
        foreach ($to_transfer as $item_id => $qty) {
            $recipient_inv = array_merge($recipient_inv, array_fill(0, $qty, $item_id));
        }

        update_user_meta($sender_id, 'arma_inventory', $new_sender);
        update_user_meta($recipient_id, 'arma_inventory', $recipient_inv);

        // Optional: Log transfer
        $log = get_user_meta($sender_id, 'arma_gift_log', true);
        if (!is_array($log)) $log = [];
        $log[] = ['to' => $recipient_id, 'items' => $to_transfer, 'time' => current_time('mysql')];
        update_user_meta($sender_id, 'arma_gift_log', $log);
    }
});