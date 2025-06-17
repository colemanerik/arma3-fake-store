<?php
/**
 * Plugin Name: Arma 3 Fake Store
 * Description: Modular plugin for Arma 3 gear purchasing, inventory, and admin tools.
 * Version: 4.8.29
 * Author: Erik
 */

foreach (glob(plugin_dir_path(__FILE__) . 'functions/*.php') as $file) {
    require_once $file;
}