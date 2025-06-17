<?php
// Admin menu to export finalized loadouts
add_action('admin_menu', function() {
    add_submenu_page('arma-admin', 'Export Loadouts', 'Export Loadouts', 'manage_options', 'arma_export_loadouts', function() {
        echo '<div class="wrap"><h1>Finalized Loadouts</h1>';
        $users = get_users(['fields' => ['ID', 'display_name']]);
        $data = [];

        foreach ($users as $user) {
            $final = get_user_meta($user->ID, 'arma_loadout', true);
            if (!$final || !is_array($final)) continue;

            $counts = array_count_values($final);
            $items = [];
            foreach ($counts as $item_id => $qty) {
                $title = get_the_title($item_id);
                if ($title) {
                    $items[] = ['item' => $title, 'quantity' => $qty];
                }
            }
            $data[$user->display_name] = $items;
        }

        echo '<textarea rows="20" cols="100" readonly>' . esc_textarea(json_encode($data, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '</div>';
    });
});
