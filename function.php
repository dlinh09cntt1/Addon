<?php
/**
 * Integration Demo.
 *
 * @package   Woocommerce My plugin Integration
 * @category Integration
 * @author   Linh D. Tran.
 */
if ( ! class_exists( 'WC_My_plugin_Integration' ) ) :
class WC_My_plugin_Integration extends WC_Integration {
  /**
   * Init and hook in the integration.
   */
  public function __construct() {
    global $woocommerce;
    $this->id                 = 'my-plugin-integration';
    $this->method_title       = __( 'My Plugin Integration');
    $this->method_description = __( 'My Plugin Integration to show you how easy it is to extend WooCommerce.');
    // Load the settings.
    $this->init_form_fields();
	$this ->add_admin_pages();
	$this->generate_csv();
	$this->exclude_data();
    $this->init_settings();
    // Define user set variables.
    $this->custom_name          = $this->get_option( 'custom_name' );
    // Actions.
    add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
  }
  /**
   * Initialize integration settings form fields.
   */
  public function init_form_fields() {
    $this->form_fields = array(
      'custom_name' => array(
        'title'             => __( 'Custom Name'),
        'type'              => 'text',
        'description'       => __( 'Enter Custom Name'),
        'desc_tip'          => true,
        'default'           => '',
        'css'      => 'width:170px;',
      ),
    );
  }
  public function add_admin_pages() {
		add_users_page( __( 'Export to CSV', 'export-users-to-csv' ), __( 'Export to CSV', 'export-users-to-csv' ), 'list_users', 'export-users-to-csv', array( $this, 'users_page' ) );
  }
	public function generate_csv() {
		if ( isset( $_POST['_wpnonce-pp-eu-export-users-users-page_export'] ) ) {
			check_admin_referer( 'pp-eu-export-users-users-page_export', '_wpnonce-pp-eu-export-users-users-page_export' );
			global $wpdb,$woocommerce;
			$statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
			$customer_ids = $wpdb->get_col("
			   SELECT DISTINCT pm.meta_value FROM {$wpdb->posts} AS p
			   INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
			   INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
			   INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			   WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
			   AND pm.meta_key IN ( '_billing_email' )
			   AND im.meta_key IN ( '_product_id', '_variation_id' )
			   AND im.meta_value > 0
			");
			$first_names = $wpdb->get_col("
			   SELECT DISTINCT pm.meta_value FROM {$wpdb->posts} AS p
			   INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
			   INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
			   INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			   WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
			   AND pm.meta_key IN ( '_billing_first_name' )
			   AND im.meta_key IN ( '_product_id', '_variation_id' )
			   AND im.meta_value > 0
			");
			$last_names = $wpdb->get_col("
			   SELECT DISTINCT pm.meta_value FROM {$wpdb->posts} AS p
			   INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
			   INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
			   INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			   WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
			   AND pm.meta_key IN ( '_billing_last_name' )
			   AND im.meta_key IN ( '_product_id', '_variation_id' )
			   AND im.meta_value > 0
			");
			
			add_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
			remove_action( 'pre_user_query', array( $this, 'pre_user_query' ) );
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) )
				$sitename .= '.';
			$filename = $sitename . 'users.' . date( 'Y-m-d-H-i-s' ) . '.csv';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
			$data_keys = array(
				'first_name', 'last_name', 'email'
			);
			$meta_keys = $wpdb->get_results( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_customer_user' AND meta_value > 0" );
			$meta_keys = wp_list_pluck( $meta_keys, 'meta_key' );
			$fields = array_merge( $data_keys, $meta_keys );
			
			$headers = array();
			
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field, $exclude_data ) )
					unset( $fields[$key] );
				else
					$headers[] = '"' . strtolower( $field ) . '"';
			}
			echo implode( ',', $headers ) . "\n";
			array_map(function($v1, $v2, $v3){
				$data = array();
				$first_name = isset( $v1) ? $v1 : '';
				$last_name = isset( $v2) ? $v2 : '';
				$email = isset( $v3) ? $v3 : '';
				$first_name = is_array( $first_name ) ? serialize( $first_name ) : $first_name;
				$last_name = is_array( $last_name ) ? serialize( $last_name ) : $last_name;
				$email = is_array( $email ) ? serialize( $email ) : $email;
				$data[0] = '"' . str_replace( '"', '""', $first_name ) . '"';
				$data[1] = '"' . str_replace( '"', '""', $last_name ) . '"';
				$data[2] = '"' . str_replace( '"', '""', $email ) . '"';
				echo implode( ',', $data ) . "\n";
			},$first_names, $last_names, $customer_ids);
			exit;
		}
	}
	public function users_page() {
		if ( ! current_user_can( 'list_users' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'export-users-to-csv' ) );
		?>
		<div class="wrap">
			<h2><?php _e( 'Export users to a CSV file', 'export-users-to-csv' ); ?></h2>
			<?php
			if ( isset( $_GET['error'] ) ) {
				echo '<div class="updated"><p><strong>' . __( 'No user found.', 'export-users-to-csv' ) . '</strong></p></div>';
			}
			?>
			<form method="post" action="" enctype="multipart/form-data">
				<?php wp_nonce_field( 'pp-eu-export-users-users-page_export', '_wpnonce-pp-eu-export-users-users-page_export' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for"pp_eu_users_role"><?php _e( 'Role', 'export-users-to-csv' ); ?></label></th>
						<td>
							<select name="role" id="pp_eu_users_role">
								<?php
								echo '<option value="">' . __( 'Customer', 'export-users-to-csv' ) . '</option>';
								?>
							</select>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="hidden" name="_wp_http_referer" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
					<input type="submit" class="button-primary" value="<?php _e( 'Export', 'export-users-to-csv' ); ?>" />
				</p>
			</form>
		<?php
	}
	public function exclude_data() {
		$exclude = array( 'first_name', 'last_name', 'email' );

		return $exclude;
	}

	public function pre_user_query( $user_search ) {
		global $wpdb;

		$where = '';

		if ( ! empty( $_POST['start_date'] ) )
			$where .= $wpdb->prepare( " AND $wpdb->users.user_registered >= %s", date( 'Y-m-d', strtotime( $_POST['start_date'] ) ) );

		if ( ! empty( $_POST['end_date'] ) )
			$where .= $wpdb->prepare( " AND $wpdb->users.user_registered < %s", date( 'Y-m-d', strtotime( '+1 month', strtotime( $_POST['end_date'] ) ) ) );

		if ( ! empty( $where ) )
			$user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1$where", $user_search->query_where );

		return $user_search;
	}
}
endif; 