<?php
/*
Plugin Name: EM Casino
Description: liste over kasino
Version: 0.0.1
GitHub Plugin URI: zeah/EM-casinolist
*/

defined('ABSPATH') or die('Blank Space');

// constant for plugin location
define('EM_CASINO_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once 'inc/casino-posttype.php';
require_once 'inc/casino-shortcode.php';

function init_emcasinolist() {
	Casino_posttype::get_instance();
	Casino_shortcode::get_instance();
}
add_action('plugins_loaded', 'init_emcasinolist');