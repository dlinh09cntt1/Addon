<?php
/**
 * Plugin Name: My custom plugin
 * Description: A plugin demonstrating how to add a new WooCommerce integration.
 * Author:  Linh D. Tran.
 * Version: 1.0
 */
if ( ! class_exists( 'WC_my_custom_plugin' ) ) :
class WC_my_custom_plugin {
  /**
  * Construct the plugin.
  */
  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'init' ) );
  }
  /**
  * Initialize the plugin.
  */
  public function init() {
    // Checks if WooCommerce is installed.
    if ( class_exists( 'WC_Integration' ) ) {
		// Include our integration class.
		include_once 'function.php';
		// Register the integration.
		add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		// Set the plugin slug
		define( 'MY_PLUGIN_SLUG', 'wc-settings' );
		// Setting action for plugin
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'WC_my_custom_plugin_action_links' );
    }
  }
  /**
   * Add a new integration to WooCommerce.
   */
  public function add_integration( $integrations ) {
	$integrations[] = 'WC_My_plugin_Integration';
    return $integrations;
  }
  function WC_my_custom_plugin_action_links( $links ) {
    $links[] = '<a href="'. menu_page_url( MY_PLUGIN_SLUG, false ) .'&tab=integration">Settings</a>';
    return $links;
  }
}
$WC_my_custom_plugin = new WC_my_custom_plugin( __FILE__ );
endif;