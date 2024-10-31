<?php
/**
 * Plugin Name:     Send Custom Fields From Store To Active Campaign
 * Plugin URI:      https://wordpress.org/plugins/send-custom-fields-from-store-to-active-campaign
 * Description:     A plugin to send custom fields from a WooCommerce installation to an ActiveCampaign
 * Author:          Daniel Prada
 * Author URI:      https://danielprada.co
 * Text Domain:     send-custom-fields-from-store-to-active-campaign
 * Domain Path:     /languages
 * Version:         1.0.0
 * @package         WooCommrce_To_ActiveCampaign
 */

if (!function_exists('is_admin')) {
	exit();
}

require('admin-page-controller.php');

if (is_admin()) {
	add_action( 'admin_menu', array('SCFTAC_Admin_Page_Controller', 'add_menu_item'));
}
