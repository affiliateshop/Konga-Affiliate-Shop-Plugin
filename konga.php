<?php

/*
Plugin Name: Konga Shop
Plugin URI: http://affiliateshop.com.ng/pricing/konga/
Description: Build a Konga affiliate shop in minutes.
Version: 1.1.3
Author: Agbonghama Collins (W3Guy LLC)
Author URI: http://w3guy.com
License: GPL2
*/

define( 'KG_SYSTEM_FILE_PATH', __FILE__ );
define( 'KG_ROOT', plugin_dir_path( __FILE__ ) );
define( 'KG_ROOT_URL', plugin_dir_url( __FILE__ ) );
define( 'KG_TEMPLATES', KG_ROOT . 'includes/' );
define( 'KG_INCLUDES', KG_ROOT . 'includes' );
define( 'KG_INCLUDES_URL', KG_ROOT_URL . 'includes' );

// EDD ish
define( 'KG_STORE_URL', 'https://affiliateshop.com.ng' );
define( 'KG_ITEM_NAME', 'Konga Affiliate Shop Plugin' );
define( 'KG_PLUGIN_DEVELOPER', 'Collins Agbonghama' );
define( 'KG_VERSION_NUMBER', '1.1.3' );

require_once KG_ROOT . '/includes/vendor/autoload.php';
require_once KG_ROOT . '/includes/functions.php';

// instantiate settings page class
Konga\Settings_Page::get_instance();