<?php

namespace Konga;

ob_start();

/** General settings page **/
class Settings_Page {

	static $instance;

	/** class constructor */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'register_settings_page' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'maybe_self_deactivate' ) );
        add_action('admin_notices', array($this, 'license_invalid_notice'));
	}

    public function license_invalid_notice()
    {
        if (!kg_is_licence_active()) {
            echo '<div class="error"><p><strong>' . sprintf(
                    __('Your KongaShop license is invalid or has expired and as such, the plugin has stopped working. %sLogin to your account%s to renew or purchase a new license', 'konga'),
                    '<a href="https://affiliateshop.com.ng/my-account/" target="_blank">',
                    '</a>'
                )
                . '.</strong></p></div>';
        }
    }

	public function register_settings_page() {
		add_menu_page(
			'Konga Affiliate Shop',
			'Konga Shop',
			'manage_options',
			'konga-shop',
			array(
				$this,
				'settings_page_callback',
			),
			KG_INCLUDES_URL . '/images/icon.png'
		);
	}

	public function settings_page_callback() {
		$this->save_settings_data();
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e( 'Konga Shop Settings', 'konga' ); ?></h2>
			<?php if ( isset( $_GET['settings-update'] ) && $_GET['settings-update'] ) : ?>
				<div id="message" class="updated notice is-dismissible"><p>
						<strong><?php _e( 'Settings saved', 'konga' ); ?>.</strong></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['force-sync'] ) && 'true' == $_GET['force-sync'] ) : ?>
				<div id="message" class="updated notice is-dismissible"><p>
						<strong><?php _e( 'Synchronisation Completed.', 'konga' ); ?>.</strong>
					</p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['force-sync'] ) && 'false' == $_GET['force-sync'] ) : ?>
				<div id="message" class="updated notice is-dismissible"><p>
						<strong><?php _e( 'Synchronisation failed. Ensure the activate checkbox is checked', 'konga' ); ?>.</strong>
					</p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['delete-product'] ) && $_GET['delete-product'] ) : ?>
				<div id="message" class="updated notice is-dismissible"><p>
						<strong><?php _e( 'Products Deleted', 'konga' ); ?>.</strong>
					</p></div>
			<?php endif;

			$db_data = kg_db_data();
			?>

			<div id="poststuff" class="ppview">

				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<div class="postbox">
									<div class="handlediv" title="Click to toggle"><br></div>
									<h3 class="hndle ui-sortable-handle"><span>Configuration</span></h3>

									<div class="inside">
										<table class="form-table">
											<tr>
												<th scope="row"><label for="activate">Activate Plugin</label></th>
												<td>
													<label for="activate"><strong>Activate</strong></label>
													<input type="checkbox" id="activate" name="activate" value="yes" <?php checked( @$db_data['activate'], 'yes' ); ?>"/>
													<p class="description">Check to activate and un-check to deactivate the plugin momentarily.</p>
												</td>
											</tr>
											<tr>
												<th scope="row"><label for="license_key">License Key</label></th>
												<td>
													<input type="text" id="license_key" name="license_key" class="all-options" value="<?php echo ! empty( $db_data['license_key'] ) ? $db_data['license_key'] : esc_url_raw( @$_POST['license_key'] ); ?>"/>

													<p class="description">Enter your license key to receive plugin updates.</a></p>
												</td>
											</tr>
											<tr>
												<th scope="row"><label for="aff_id">Affiliate ID</label></th>
												<td>
													<input type="text" id="aff_id" name="aff_id" class="all-options" value="<?php echo ! empty( $db_data['aff_id'] ) ? $db_data['aff_id'] : esc_url_raw( @$_POST['aff_id'] ); ?>"/>

													<p class="description">Enter your Konga affiliate id.
														<a target="_blank" href="http://affiliateshop.com.ng/documentation/konga-affiliate-shop-setup/#affiliate_url">Learn how to get it</a>
													</p>
												</td>
											</tr>
											<tr>
												<th scope="row"><label for="crawl_interval">Crawl Interval</label></th>
												<td>
													<select id="crawl_interval" name="crawl_interval">
														<option value="hourly" <?php isset( $_POST["crawl_interval"] ) && $_POST["crawl_interval"] == 'hourly' ? selected( $_POST["crawl_interval"], 'hourly' ) : selected( @$db_data['crawl_interval'], 'hourly' ); ?>>Hourly</option>
														<option value="twicedaily" <?php isset( $_POST["crawl_interval"] ) && $_POST["crawl_interval"] == 'twicedaily' ? selected( $_POST["crawl_interval"], 'twicedaily' ) : selected( @$db_data['crawl_interval'], 'twicedaily' ); ?>>Twice Daily</option>
														<option value="daily" <?php isset( $_POST["crawl_interval"] ) && $_POST["crawl_interval"] == 'daily' ? selected( $_POST["crawl_interval"], 'daily' ) : selected( @$db_data['crawl_interval'], 'daily' ); ?>>Daily</option>
													</select>

													<p class="description">How often to crawl Konga website.</p>
												</td>
											</tr>
											<tr>
												<th scope="row"><label for="crawl_interval">SEO Plugin</label></th>
												<td>
													<select id="seo_plugin" name="seo_plugin">
														<option>Select..</option>
														<option value="yoast_seo" <?php isset( $_POST["seo_plugin"] ) && $_POST["seo_plugin"] == 'yoast_seo' ? selected( $_POST["seo_plugin"], 'yoast_seo' ) : selected( @$db_data['seo_plugin'], 'yoast_seo' ); ?>>WordPress SEO by Yoast</option>
														<option value="seo_ultimate" <?php isset( $_POST["seo_plugin"] ) && $_POST["seo_plugin"] == 'seo_ultimate' ? selected( $_POST["seo_plugin"], 'seo_ultimate' ) : selected( @$db_data['seo_plugin'], 'seo_ultimate' ); ?>>SEO Ultimate</option>
													</select>

													<p class="description">Select the WordPress SEO Plugin this site is using.</p>
												</td>
											</tr>
											<tr>
												<th scope="row"><label for="force_sync">Force Sync</label></th>
												<td>
													<?php submit_button( 'Force Sync', 'secondary', 'force_sync', false ); ?>

													<p class="description">Click to force the plugin to fetch products.</p>
												</td>
											</tr>
											<tr>
												<th scope="row"><label for="product_age">Delete Old Product</label>
												</th>
												<td>
													<input type="number" id="product_age" style="width: 60px;" name="product_age" class="all-options" value="<?php echo ! empty( $db_data['product_age'] ) ? $db_data['product_age'] : '30'; ?>"/>
													<?php submit_button( 'Delete', 'secondary', 'delete_product', false ); ?>
													<p class="description">Delete WooCommerce products older than the number of days entered in the input field above.</p>
												</td>
											</tr>
										</table>
										<p>
											<?php wp_nonce_field( 'settings_nonce' ); ?>
											<input class="button-primary" type="submit" name="settings_submit" value="Save All Changes">
										</p>
									</div>
								</div>
								<div class="postbox">
									<div class="handlediv" title="Click to toggle"><br></div>
									<h3 class="hndle ui-sortable-handle">
										<span><?php _e( 'Product Setup', 'konga' ); ?></span></h3>

									<div class="inside">
										<table class="form-table">
											<?php if ( count( $this->get_woo_categories() ) < 1 ) {
												echo 'No WooCommerce product category found. Consider <a href="' . admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) . '">creating one</a>.';
											} else { ?>
												<?php for ( $i = 0; $i < count( $this->get_woo_categories() ); $i ++ ) : ?>
													<tr>
														<td><label for="pd-<?php echo $i; ?>">Product Category</label>
														</td>
														<td>
															<div><?php $this->category_dropdown( $i ); ?></div>
														</td>
													</tr>
													<tr>
														<td>
															<label for="source-<?php echo $i; ?>">Product Sources (URLs)</label>
														</td>
														<td>
															<div>
																<textarea rows="5" id="source-<?php echo $i; ?>" name="kg_sources[<?php echo $i; ?>]"><?php echo ! empty( $db_data['kg_sources'][ $i ] ) ? $db_data['kg_sources'][ $i ] : esc_url_raw( @$_POST['kg_sources'][ $i ] ); ?></textarea>
															</div>
														</td>
													</tr>

													<tr>
														<th scope="row"></th>
													</tr>
												<?php endfor;
											} ?>
										</table>
										<?php if ( count( $this->get_woo_categories() ) >= 1 ) : ?>
											<p>
												<?php wp_nonce_field( 'settings_nonce' ); ?>
												<input class="button-primary" type="submit" name="settings_submit" value="Save All Changes">
											</p>
										<?php endif; ?>
									</div>
								</div>
							</form>
						</div>

					</div>
					<?php include_once 'settings-sidebar.php'; ?>

				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	/**
	 * Save the settings page data
	 *
	 */
	function save_settings_data() {
		if ( isset( $_POST['_wpnonce'] ) && check_admin_referer( 'settings_nonce', '_wpnonce' ) ) {

			if ( isset( $_POST['force_sync'] ) ) {
				$status = konga_scrap_and_create_product();

				if ( 'false' == $status ) {
					$redirect = add_query_arg( 'force-sync', 'false' );
				} else {
					$redirect = add_query_arg( 'force-sync', 'true' );
				}
				wp_redirect( esc_url_raw( $redirect ) );
				exit;
			}
			if ( isset( $_POST['delete_product'] ) && ! empty( $_POST['product_age'] ) ) {
				konga_delete_old_product( absint( $_POST['product_age'] ) );
				wp_redirect( esc_url_raw( add_query_arg( 'delete-product', 'true' ) ) );
				exit;
			}

			if ( isset( $_POST['settings_submit'] ) ) {
				kg_data_update( $_POST );

				$settings_data = array();
				foreach ( $_POST as $key => $value ) {

					// do not save the nonce value to DB
					if ( $key == '_wpnonce' ) {
						continue;
					}
					// do not save the nonce referer to DB
					if ( $key == '_wp_http_referer' ) {
						continue;
					}
					// do not save the submit button value
					if ( $key == 'settings_submit' ) {
						continue;
					}
					// do not save the submit button value
					if ( $key == 'force_sync' ) {
						continue;
					}
					// do not save the submit button value
					if ( $key == 'delete_product' ) {
						continue;
					}

					$settings_data[ $key ] = $value;
				}

				$data_to_save = array();

				foreach ( $settings_data as $key => $value ) {
					if ( $key == 'kg_products' && is_array( $settings_data[ $key ] ) ) {
						$data_to_save['kg_products'] = $settings_data[ $key ];
					}

					if ( $key == 'kg_sources' && is_array( $settings_data[ $key ] ) ) {
						$data_to_save['kg_sources'] = $settings_data[ $key ];
					}

					if ( is_string( $settings_data[ $key ] ) ) {
						$data_to_save[ $key ] = $settings_data[ $key ];
					}
				}


				update_option( 'kg_settings_data', $data_to_save );

				wp_redirect( esc_url_raw( add_query_arg( 'settings-update', 'true' ) ) );
				exit;
			}
		}
	}

	/**
	 * Array of woocommerce created product categories.
	 *
	 * @return array
	 */
	public function get_woo_categories() {
		return get_categories(
			array(
				'taxonomy'     => 'product_cat',
				'show_count'   => 0,
				'pad_counts'   => 0,
				'hierarchical' => 1,
				'hide_empty'   => 0
			)
		);
	}


	public function category_dropdown( $key ) {
		$db_data = kg_db_data();
		?>
		<select name="kg_products[<?php echo $key; ?>]" id="pd-<?php echo $key; ?>">
			<?php foreach ( $this->get_woo_categories() as $category ) : ?>
				<option value="<?php echo $category->term_id; ?>"
					<?php isset( $_POST["kg_products"][ $key ] ) && $_POST["kg_products"][ $key ] == $category->term_id ? selected( $_POST["kg_products"][ $key ], $category->term_id ) : selected( $db_data['kg_products'][ $key ], $category->term_id ); ?>>
					<?php echo $category->name; ?></option>
			<?php endforeach; ?>
		</select>
	<?php }


	/**
	 * If dependency requirements are not satisfied, self-deactivate
	 */
	public static function maybe_self_deactivate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( plugin_basename( KG_SYSTEM_FILE_PATH ) );
			add_action( 'admin_notices', array( __CLASS__, 'self_deactivate_notice' ) );
		}
	}


	/**
	 * Display an error message when the plugin deactivates itself.
	 */
	public static function self_deactivate_notice() {
		echo '<div class="error"><p><strong>' . __( 'Konga Shop', 'konga' ) . '</strong> ' . __( 'requires WooCommerce activated to work', 'konga' ) . '.</p></div>';
	}

	static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

ob_clean();
