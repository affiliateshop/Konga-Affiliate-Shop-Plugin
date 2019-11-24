<?php

/**
 * Saved settings
 *
 * @return mixed|void
 */
function kg_db_data() {
	return get_option( 'kg_settings_data' );
}


/**
 * Returns the currently active SEO plugin.
 *
 * @return mixed
 */
function kg_seo_plugin() {
	$data = kg_db_data();

	return $data['seo_plugin'];
}


/**
 * Plugin License key
 *
 * @return string
 */
function kg_license_key() {
	$data = kg_db_data();

	return $data['license_key'];
}

/**
 * License key status
 *
 * @return string
 */
function kg_license_status() {
	return get_option( 'kg_license_status' );
}

/**
 * Output the external product add to cart area.
 *
 * @subpackage    Product
 */

if ( ! function_exists( 'woocommerce_external_add_to_cart' ) ) {
	function woocommerce_external_add_to_cart() {
		global $product;

		if ( ! $product->get_product_url() ) {
			return;
		}

		kg_wc_get_template( 'templates/buy-button.php', array(
			'product_url' => $product->get_product_url(),
			'button_text' => $product->single_add_to_cart_text()
		) );
	}
}


/**
 * Our very own implementation of wc_get_template function so we can override the buy product button.
 *
 * @param string $template_name
 * @param array $args
 * @param string $template_path
 * @param string $default_path
 */
function kg_wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( $args && is_array( $args ) ) {
		extract( $args );
	}

	$located = KG_TEMPLATES . $template_name;

	// Allow 3rd party plugin filter template file from their plugin
	$located = apply_filters( 'wc_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'woocommerce_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'woocommerce_after_template_part', $template_name, $template_path, $located, $args );
}


add_action( 'init', 'konga_crawl_schedule' );
add_action( 'konga_crawl_cron_job', 'konga_scrap_and_create_product' );

/**
 * Setup crawl cron-job
 */
function konga_crawl_schedule() {
	$db_data  = kg_db_data();
	$interval = ! empty( $db_data['crawl_interval'] ) ? $db_data['crawl_interval'] : 'hourly';
	if ( ! wp_next_scheduled( 'konga_crawl_cron_job' ) ) {
		wp_schedule_event( time(), $interval, 'konga_crawl_cron_job' );
	}
}

/**
 * Crawl the store.
 */
function konga_scrap_and_create_product() {

	set_time_limit( 0 );

	$db_data = kg_db_data();
	if ( empty( $db_data['activate'] ) && @$db_data['activate'] != 'yes' ) {
		return 'false';
	}
	$konga_cat_and_urls = konga_cat_and_urls();

	if ( empty( $konga_cat_and_urls ) ) {
		return;
	}
	foreach ( $konga_cat_and_urls as $cat => $urls ) {
		// explode the urls to arrays
		$urls = explode( ',', $urls );
		$urls = array_map( 'trim', $urls );

		if ( is_array( $urls ) ) {

			foreach ( $urls as $url ) {

				if ( ! kg_is_url( $url ) ) {
					continue;
				}

				$instance = new Konga\Scrapper( $url );
				$instance->crawl_shop( $cat );
			}
		}
	}
}


/**
 * Return an array of product category and its data source.
 *
 * @return array
 */
function konga_cat_and_urls() {
	$db_data = kg_db_data();

	$categories  = $db_data['kg_products'];
	$data_source = array_map( 'trim', $db_data['kg_sources'] );

	return array_combine( $categories, $data_source );
}


/**
 * Delete WooCommerce product older than X days.
 *
 * @param int $days
 */
function konga_delete_old_product( $days ) {
	global $wpdb;
	$table = $wpdb->prefix . 'posts';

	$product_ids = $wpdb->get_col( "SELECT ID FROM $table WHERE post_type = 'product' AND DATEDIFF(NOW(), post_date) > $days" );
	$delete_sql  = "DELETE FROM $table WHERE post_type = 'product' AND DATEDIFF(NOW(), post_date) > $days";

	// Delete products
	$wpdb->query( $delete_sql );

	foreach ( $product_ids as $id ) {
		// find the product attachment id
		$attachment_id = $wpdb->get_var( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id' AND post_id = $id" );

		// delete the attachment
		wp_delete_attachment( $attachment_id, true );

		// delete the attachement postmeta relationship
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id' AND post_id = $id" );
	}

}


/**
 * URL validation.
 *
 * @param string $url
 *
 * @return mixed|void
 */
function kg_is_url( $url ) {
	return ( false !== filter_var( $url, FILTER_VALIDATE_URL ) );
}

/**
 * Settings page CSS
 */
function kg_admin_css() {
	wp_enqueue_style( 'kg-admin', KG_INCLUDES_URL . '/css/admin-style.css' );
}

add_action( 'admin_enqueue_scripts', 'kg_admin_css' );


/**
 * EDD Plugin update method
 */
function kg_plugin_updater() {

	// retrieve our license key from the DB
	$license_key = kg_license_key();

	if ( class_exists( 'Konga\EDD_SL_Plugin_Updater' ) && is_admin() ) {

		// setup the updater
		$edd_updater = new Konga\EDD_SL_Plugin_Updater(
			KG_STORE_URL,
			KG_SYSTEM_FILE_PATH,
			array(
				'version'   => KG_VERSION_NUMBER,            // current version number
				'license'   => $license_key,        // license key (used get_option above to retrieve from DB)
				'item_name' => KG_ITEM_NAME,    // name of this plugin
				'author'    => KG_PLUGIN_DEVELOPER  // author of this plugin
			)
		);
	}

}

add_action( 'admin_init', 'kg_plugin_updater', 0 );

function kg_is_licence_active()
{
    return get_option('kg_license_status', '') == 'valid';
}

/**
 * Activate license
 */
function kg_activate_license() {
	$license_key = kg_license_key();
	// only run update if license status isn't valid
	if ( empty( $license_key ) || 'valid' == kg_license_status() ) {
		return;
	}

	// retrieve the license from the database
	$license = kg_license_key();

	// data to send in our API request
	$api_params = array(
		'edd_action' => 'activate_license',
		'license'    => $license,
		'item_name'  => urlencode( KG_ITEM_NAME ), // the name of our product in EDD
		'url'        => home_url()
	);

	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, KG_STORE_URL ), array(
		'timeout'   => 15,
		'sslverify' => false
	) );

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "valid" or "invalid"
	update_option( 'kg_license_status', $license_data->license );

}

add_action( 'admin_init', 'kg_activate_license', 0 );

function _kg_check_license()
{
    $api_params = array(
        'edd_action' => 'check_license',
        'license' => kg_license_key(),
        'item_name' => urlencode(JU_ITEM_NAME),
        'url' => home_url()
    );

    // Call the custom API.
    $response = wp_remote_get(add_query_arg($api_params, JU_STORE_URL));

    // make sure the response came back okay
    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        if (is_wp_error($response)) {
            $error = $response->get_error_message();
        } else {
            $error = __('An error occurred, please try again.');
        }

        return new WP_Error('license_check_error', $error);
    }

    $license_data = json_decode(wp_remote_retrieve_body($response));

    return $license_data;
}

/**
 * Check if the plugin license is active
 */
function kg_plugin_check_license()
{
    // only check license if transient doesn't exist
    if (false === get_transient('kg_license_check')) {

        $response = _kg_check_license();

        if (is_wp_error($response)) {
            return false;
        }

        if (!empty($response->license)) {
            if ($response->license == 'valid') {
                update_option('kg_license_status', 'valid');
            } else {
                update_option('kg_license_status', 'invalid');
            }
        }

        set_transient('kg_license_check', 'active', 24 * HOUR_IN_SECONDS);
    }
}

add_action('admin_init', 'kg_plugin_check_license', 0);

/**
 * Deactivate license
 */
function kg_deactivate_license() {

	// retrieve the license from the database
	$license = kg_license_key();

	// data to send in our API request
	$api_params = array(
		'edd_action' => 'deactivate_license',
		'license'    => $license,
		'item_name'  => urlencode( KG_ITEM_NAME ),
		'url'        => home_url()
	);

	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, KG_STORE_URL ), array(
		'timeout'   => 15,
		'sslverify' => false
	) );

	// make sure the response came back okay
	if ( is_wp_error( $response ) ) {
		return;
	}

	// decode the license data
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "deactivated" or "failed"
	if ( $license_data->license == 'deactivated' ) {
		delete_option( 'kg_license_status' );
	}
}


/**
 * Deactivate license and license status when license key is changed.
 *
 * @param array $posted_data $_POST form data
 *
 * @return mixed
 */
function kg_data_update( $posted_data ) {
	$db_options = kg_db_data();

	$new = trim( esc_attr( $posted_data['license_key'] ) );
	$old = $db_options['license_key'];

	if ( $new != $old ) {
		kg_deactivate_license();
		delete_option( 'kg_license_status' );
	}

	return $posted_data;
}


// delete cron job on deactivation
register_deactivation_hook( KG_SYSTEM_FILE_PATH, 'kg_delete_cron' );

function kg_delete_cron() {
	wp_clear_scheduled_hook( 'konga_crawl_cron_job' );
}