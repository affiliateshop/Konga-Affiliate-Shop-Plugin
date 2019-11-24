<?php

namespace Konga;

use Symfony\Component\DomCrawler\Crawler;


set_time_limit( 0 );

class Scrapper {

	private $shop;

	public function __construct( $shop_url ) {
		$this->shop = $shop_url;
		$db_data    = kg_db_data();

		if ( ! defined( 'KG_AFF_ID' ) ) {
			define( 'KG_AFF_ID', $db_data['aff_id'] );
		}
	}


	/**
	 * Domcrwaler
	 *
	 * @param string $cat product WooCommerce product category.
	 *
	 * @return array
	 */
	public function crawl_shop($cat) {

        if (!kg_is_licence_active()) return;

		$request  = wp_remote_get( $this->shop );
		$response = wp_remote_retrieve_body( $request );

		// instantiate DomCrawler
		$crawlerObject = new Crawler( $response );

		/** @var array $crawler list of products */
		$crawler = $crawlerObject->filter( 'ul.catalog > li' )->each( function ( Crawler $node, $i ) {
			return $node->html();
		} );

		foreach ( $crawler as $content ) {

			try {
				$obj         = new Crawler( $content );
				$title       = trim( $obj->filter( '.product-name span' )->text() );
				$link        = 'http://www.konga.com' . trim( $obj->filter( 'div.product-block > a' )->attr( 'href' ) );
				$old_price   = trim( $this->remove_non_printable_characters($obj->filter( '.original-price' )->text() ));
				$new_price   = trim( $this->remove_non_printable_characters($obj->filter( '.special-price' )->text()) );
				$image_url   = $this->get_feature_image( trim( $obj->filter( 'img.catalog-product-image' )->attr( 'src' ) ) );
				$description = $this->get_description_details( $link );

				$product = array(
					'title'         => $title,
					'image_url'     => trim( $image_url ),
					'product_url'   => $link . '?k_id=' . KG_AFF_ID,
					'regular_price' => $old_price,
					'sale_price'    => $new_price,
					'description'   => trim( $description ),
				);

				// insert product to DB
				Woo_Product::insert( $product, $cat );

			}
			catch ( \InvalidArgumentException $e ) {
				// do nothing
			}
		}
	}


	function get_description_details( $url ) {
		$request  = wp_remote_get( $url );
		$response = wp_remote_retrieve_body( $request );
		$obj      = new Crawler( $response );

		$desc = trim( $obj->filter( 'div.product-description .product-long-description-brief' )->html() );

		return $desc;

		//	$string_to_remove = '<a href="#" class="product-detail-less-click">See less<i class="fa fa-caret-up"></i></a>';
		//	return str_replace( $string_to_remove, '', $desc );
	}

	public function get_feature_image( $url ) {
		return substr( $url, 0, strpos( $url, '?' ) );
	}


	/**
	 * Remove non printable characters fro string
	 *
	 * @param $string
	 *
	 * @return mixed
	 */
	public function remove_non_printable_characters( $string ) {
		// strip unicode out of string
		$output = preg_replace( '/[\x00-\x1F\x80-\xFF]/', ' ', $string );

		// remove comma from price
		$output = str_replace( ',', '', $output );

		// ensure no double space exist
		return preg_replace( '/\s+/', ' ', $output );
	}


	/**
	 * The price of a product returned after scraping do contain some unicode characters eg �?�5,300.
	 *
	 * This function returns the integer value of the price.
	 *
	 * @param $string
	 *
	 * @return int|string
	 */
	public function get_price_value( $string ) {
		preg_match_all( '!\d+!', $string, $matches );

		return implode( '', $matches[0] );
	}
}