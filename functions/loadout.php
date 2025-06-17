<?php
add_shortcode('arma_loadout', function() {
    if (!is_user_logged_in()) return "<p>Please log in to setup your loadout.</p>";
    $user_id = get_current_user_id();

    // Reset button
    if (isset($_POST['reset_loadout'])) {
        delete_user_meta($user_id, 'arma_loadout_pending');
    }

    $pending = get_user_meta($user_id, 'arma_loadout_pending', true);
    $output = '';

    if (is_array($pending) && !empty($pending)) {
        $output .= "<div class='arma-confirmed-loadout'><h2>Pending Loadout</h2><ul>";
        $counts = array_count_values($pending);
        foreach ($counts as $item_id => $qty) {
            $title = get_the_title($item_id);
            $output .= "<li>{$title} x {$qty}</li>";
        }
        $output .= "</ul><form method='POST'><button type='submit' name='reset_loadout'>Reset Loadout</button></form></div><hr>";
    } else {
        $inventory = get_user_meta($user_id, 'arma_inventory', true);
        if (!is_array($inventory) || empty($inventory)) return "<p>You have no items in your inventory.</p>";

        $counts = array_count_values($inventory);
        $items = get_posts(['post_type' => 'store_item', 'posts_per_page' => -1]);

        $output .= "<form method='POST'><h2>Select Loadout</h2><table><tr><th>Item</th><th>Owned</th><th>Quantity</th></tr>";
        foreach ($items as $item) {
            $id = $item->ID;
            if (!isset($counts[$id])) continue;
            $owned = $counts[$id];
            $name = esc_html($item->post_title);
            $output .= "<tr><td>{$name}</td><td>{$owned}</td>";
            $output .= "<td><input type='number' name='loadout_qty[{$id}]' min='0' max='{$owned}' value='0'></td></tr>";
        }
        $output .= "</table><br><button type='submit' name='review_loadout'>Review Loadout</button></form>";

        if (isset($_POST['review_loadout']) && isset($_POST['loadout_qty'])) {
            $review = $_POST['loadout_qty'];
            $output .= "<hr><h3>Review Your Loadout</h3><form method='POST'><ul>";
            foreach ($review as $item_id => $qty) {
                $qty = intval($qty);
                if ($qty > 0) {
                    $title = get_the_title($item_id);
                    $output .= "<li>{$title} x {$qty}</li>";
                    $output .= "<input type='hidden' name='confirm_qty[{$item_id}]' value='{$qty}'>";
                }
            }
            $output .= "</ul><button type='submit' name='confirm_loadout'>Confirm Loadout</button></form>";
        }
    }

    return $output;
});

add_action('init', function() {
    if (!is_user_logged_in()) return;
    if (isset($_POST['confirm_loadout']) && isset($_POST['confirm_qty'])) {
        $user_id = get_current_user_id();
        $result = [];
        foreach ($_POST['confirm_qty'] as $item_id => $qty) {
            $qty = intval($qty);
            for ($i = 0; $i < $qty; $i++) {
                $result[] = intval($item_id);
            }
        }
        update_user_meta($user_id, 'arma_loadout_pending', $result);
    }
});