<?php
add_action('acf/init', function() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group([
            'key' => 'group_store_item',
            'title' => 'Store Item Fields',
            'fields' => [[
                'key' => 'field_item_cost',
                'label' => 'Item Cost',
                'name' => 'arma_item_cost',
                'type' => 'number',
            ]],
            'location' => [[[
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'store_item',
            ]]],
        ]);
    }
});
