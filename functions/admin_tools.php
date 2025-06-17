<?php
add_action('admin_menu', function() {
    add_menu_page('Arma 3 Admin', 'Arma Admin', 'manage_options', 'arma-admin', 'arma3_admin_page');
});

function arma3_admin_page() {
    $users = get_users();
    echo '<div class="wrap"><h1>Arma 3 Admin Panel</h1><form method="POST">';
    echo '<select name="apply_user_id">';
    foreach ($users as $user) {
        echo "<option value='{$user->ID}'>{$user->display_name}</option>";
    }
    echo '</select> <button type="submit" name="load_user">Load</button></form><hr>';

    if (isset($_POST['apply_user_id'])) {
        $uid = intval($_POST['apply_user_id']);
        $balance = intval(get_user_meta($uid, 'arma_balance', true));
        $inventory = get_user_meta($uid, 'arma_inventory', true);
        $item_counts = array_count_values(is_array($inventory) ? $inventory : []);

        if (isset($_POST['new_balance'])) {
            update_user_meta($uid, 'arma_balance', intval($_POST['new_balance']));
            $balance = intval($_POST['new_balance']);
        }

        if (isset($_POST['add_item']) && isset($_POST['item_id']) && isset($_POST['item_qty'])) {
            $item_id = intval($_POST['item_id']);
            $qty = max(1, intval($_POST['item_qty']));
            for ($i = 0; $i < $qty; $i++) $inventory[] = $item_id;
            update_user_meta($uid, 'arma_inventory', $inventory);
            $item_counts = array_count_values($inventory);
        }

        if (isset($_POST['remove_inventory']) && is_array($_POST['remove_inventory'])) {
            foreach ($_POST['remove_inventory'] as $item_id => $qty) {
                $qty = intval($qty);
                $item_id = intval($item_id);
                for ($i = 0; $i < $qty; $i++) {
                    $index = array_search($item_id, $inventory);
                    if ($index !== false) unset($inventory[$index]);
                }
            }
            $inventory = array_values($inventory);
            update_user_meta($uid, 'arma_inventory', $inventory);
            $item_counts = array_count_values($inventory);
        }

        if (isset($_POST['finalize_loadout'])) {
            $pending = get_user_meta($uid, 'arma_loadout_pending', true);
            if (is_array($pending) && count($pending) > 0) {
                foreach ($pending as $item_id) {
                    $index = array_search($item_id, $inventory);
                    if ($index !== false) unset($inventory[$index]);
                }
                update_user_meta($uid, 'arma_inventory', array_values($inventory));
                update_user_meta($uid, 'arma_loadout', $pending);
                delete_user_meta($uid, 'arma_loadout_pending');

                $history = get_user_meta($uid, 'arma_loadout_history', true);
                if (!is_array($history)) $history = [];
                $history[] = ['timestamp' => current_time('mysql'), 'loadout' => $pending];
                update_user_meta($uid, 'arma_loadout_history', $history);

                echo "<p><strong>Loadout finalized and inventory updated.</strong></p>";
            }
        }

        $player = get_userdata($uid);
echo "<h2>Player: " . esc_html($player->display_name) . "</h2>";
echo "<h2>Balance: {$balance}</h2>";
        echo "<form method='POST'><input type='hidden' name='apply_user_id' value='{$uid}' />";
        echo "New Balance: <input type='number' name='new_balance' value='{$balance}' />";
        echo " <button type='submit'>Update</button></form><hr>";

        echo "<h3>Inventory</h3><form method='POST'><input type='hidden' name='apply_user_id' value='{$uid}' />";
        foreach ($item_counts as $item_id => $count) {
            $post = get_post($item_id);
            if (!$post) continue;
            echo "<label>{$post->post_title} (x{$count}) - Remove <input type='number' name='remove_inventory[{$item_id}]' min='0' max='{$count}' value='0'></label><br>";
        }
        echo "<button type='submit'>Apply Changes</button></form><hr>";

        echo "<h3>Add Items</h3><form method='POST'><input type='hidden' name='apply_user_id' value='{$uid}' />";
        echo "<select name='item_id'>";
        $items = get_posts(['post_type' => 'store_item', 'posts_per_page' => -1]);
        foreach ($items as $item) {
            echo "<option value='{$item->ID}'>{$item->post_title}</option>";
        }
        echo "</select> Qty: <input type='number' name='item_qty' value='1' min='1' />";
        echo " <button type='submit' name='add_item'>Add</button></form><hr>";

        $pending = get_user_meta($uid, 'arma_loadout_pending', true);
        if (is_array($pending) && count($pending) > 0) {
            echo "<h3>Pending Loadout</h3><ul>";
            $counts = array_count_values($pending);
            foreach ($counts as $item_id => $qty) {
                $post = get_post($item_id);
                if ($post) echo "<li>{$post->post_title} x{$qty}</li>";
            }
            echo "</ul><form method='POST'><input type='hidden' name='apply_user_id' value='{$uid}' />";
            echo "<button type='submit' name='finalize_loadout'>Finalize Loadout</button></form><hr>";
        }

        $history = get_user_meta($uid, 'arma_loadout_history', true);
        if (is_array($history) && count($history) > 0) {
            echo "<h3>Loadout History</h3>";
            foreach (array_reverse($history) as $entry) {
                $ts = esc_html($entry['timestamp']);
                echo "<p><strong>{$ts}</strong><ul>";
                $counts = array_count_values($entry['loadout']);
                foreach ($counts as $item_id => $qty) {
                    $post = get_post($item_id);
                    if ($post) echo "<li>{$post->post_title} x{$qty}</li>";
                }
                echo "</ul></p><hr>";
            }
        }
    }
    echo '</div>';
}