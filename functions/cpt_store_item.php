<?php
add_action('init', function() {
    register_post_type('store_item', [
        'label' => 'Store Items',
        'public' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-cart',
    ]);
});
