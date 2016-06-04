<?php

namespace WP_CLI\BigMig;

class Mappings {

	public static $post_types; // Content that will map to a WP post (post_type = post )

	public static $page_types;

	public static $valid_types;

	/**
	 * Map of old_cms category -> WP category ID
	 */
	public static $categories;

	/**
	 * Map of old_cms tag -> WP tag ID
	 */
	public static $tags;

	public static function init() {
		self::$post_types = array(
			// Fill me in
		);

		self::$page_types = array(
			// Fill me in
		);

		self::$valid_types = array_merge( self::$post_types, self::$page_types );

		self::set_categories();

		self::$tags = DBMappings::get_tag_mappings(); // For large taxonomies, handle via DB
	}

	public static function set_categories() {

		/**
		 * Manual mapping of Old CMS category to Category Name. The category name will correspond to WP
		 */

		$category_to_name_mapping = array(
			'/foo/bar/SomeCategory' => 'FooCat',
		);

		$wp_category_mapping = get_terms( array(
				'taxonomy' => 'category',
				'hide_empty' => false,
				'fields' => 'id=>name',
		) );

		$categories = array();

		/*
		 * Map the nice category names to category IDs
		 */
		foreach( $category_to_name_mapping as $old_cms_category => $category_name ) {
			$category_id = array_search( $category_name, $wp_category_mapping );
			if ( ! $category_id ) {
				Logger::log( "Missing category mapping for {$category_name} from old category {$old_cms_category}");
				/**
				 * Missing the category here, so let's just take the first one... Replace this functionality :)
				 */
				reset( $wp_category_mapping );
				$category_id = key( $wp_category_mapping );
			}
			$categories[$old_cms_category] = $category_id;
		}

		self::$categories = $categories;
	}

}
