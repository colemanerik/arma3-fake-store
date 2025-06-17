<?php
add_action('init', function() {
    if (!session_id()) session_start();

    if (isset($_POST['add_to_cart']) && is_user_logged_in()) {
        $item_id = intval($_POST['add_to_cart']);
        $qty = max(1, intval($_POST['cart_quantity']));
        if (!isset($_SESSION['arma_cart'])) $_SESSION['arma_cart'] = [];
        if (!isset($_SESSION['arma_cart'][$item_id])) {
            $_SESSION['arma_cart'][$item_id] = 0;
        }
        $_SESSION['arma_cart'][$item_id] += $qty;
    }

    if (isset($_POST['clear_cart'])) {
        $_SESSION['arma_cart'] = [];
    }

    if (isset($_POST['confirm_purchase']) && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $cart = isset($_SESSION['arma_cart']) ? $_SESSION['arma_cart'] : [];
        $balance = intval(get_user_meta($user_id, 'arma_balance', true));
        $total = 0;
        foreach ($cart as $item_id => $qty) {
            $total += intval(get_field('arma_item_cost', $item_id)) * $qty;
        }
        if ($balance >= $total) {
            update_user_meta($user_id, 'arma_balance', $balance - $total);
            $inventory = get_user_meta($user_id, 'arma_inventory', true);
            if (!is_array($inventory)) $inventory = [];
            foreach ($cart as $item_id => $qty) {
                for ($i = 0; $i < $qty; $i++) {
                    $inventory[] = $item_id;
                }
            }
            update_user_meta($user_id, 'arma_inventory', $inventory);
            $_SESSION['arma_cart'] = [];
        }
    }
});

add_shortcode('arma_store', function() {
    if (!is_user_logged_in()) return "<p>You must be logged in to view the store.</p>";
    if (!session_id()) session_start();

    $output = '<div class="arma-store"><h2>Store</h2>';
    $items = get_posts(['post_type' => 'store_item', 'posts_per_page' => -1]);
    $user_id = get_current_user_id();
    $balance = intval(get_user_meta($user_id, 'arma_balance', true));
    $output .= "<p><strong>Your Balance:</strong> $balance</p>";

    foreach ($items as $item) {
        $cost = intval(get_field('arma_item_cost', $item->ID));
        $output .= "<div class='arma-store-item'><h3>{$item->post_title} - $cost</h3><p>{$item->post_content}</p>";
        $output .= "<form method='POST'>";
        $output .= "<input type='hidden' name='add_to_cart' value='{$item->ID}' />";
        $output .= "Qty: <input type='number' name='cart_quantity' value='1' min='1' style='width: 60px;' />";
        $output .= " <button type='submit'>Add to Cart</button></form></div><hr>";
    }

    $cart = isset($_SESSION['arma_cart']) ? $_SESSION['arma_cart'] : [];
    if (count($cart) > 0) {
        $output .= "<h3>Your Cart</h3><ul>";
        $total = 0;
        foreach ($cart as $item_id => $qty) {
            $post = get_post($item_id);
            if (!$post) continue;
            $cost = intval(get_field('arma_item_cost', $item_id));
            $line_total = $cost * $qty;
            $output .= "<li>{$post->post_title} (x{$qty}) - $line_total</li>";
            $total += $line_total;
        }
        $output .= "</ul><p><strong>Total:</strong> $total</p>";
        $output .= "<form method='POST'><button name='confirm_purchase' type='submit'>Confirm Purchase</button> ";
        $output .= "<button name='clear_cart' type='submit'>Clear Cart</button></form>";
    }

    $output .= '</div>';
    return $output;
});