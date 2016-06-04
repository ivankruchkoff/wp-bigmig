<?php

namespace WP_CLI\BigMig;

class DBMappings {

	public static $table_name;

	public static function init() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'bigmigration';
		self::$table_name = $table_name;
		$create_table_query = "
			CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `post_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `path` varchar(255) NOT NULL,
            `type` varchar(48) NOT NULL,
		    PRIMARY KEY ( `post_id` ),
		    INDEX `post_type` (`type` ASC)
            )";

		return $wpdb->query( $create_table_query );
	}

	/**
	 * Have we already migrated this path?
	 * @param $path
	 *
	 * @return bool
	 */
	public static function is_file_already_migrated( $path ) {
		global $wpdb;
		$table_name = self::$table_name;
		$query = $wpdb->prepare( "SELECT COUNT(*) FROM `{$table_name}` where `path` = '%s'", $path );
		return '0' !== $wpdb->get_var( $query );
	}

	/**
	 * @param        $path
	 * @param string $type
	 *
	 * @return bool
	 */
	public static function get_post_id_for_path( $path, $type = 'article' ) {
		global $wpdb;
		$table_name = self::$table_name;
		$query = $wpdb->prepare( "SELECT `post_id` FROM `{$table_name}` where `path` = '%s' and `type` = '%s'", $path, $type );
		$post_id = $wpdb->get_var( $query );
		return is_null( $post_id ) ? false : absint( $post_id );
	}

	/**
	 * Set this path to migrated with the specified post_id in WP.
	 */
	public static function old_content_migrated( $path, $post_id, $type = 'article' ) {
		global $wpdb;
		return false !== $wpdb->insert(
			self::$table_name,
			array( 'path' => $path, 'post_id' => $post_id, 'type' => $type ),
			array( '%s', '%d', '%s' )
		);
	}

	/**
	 * Get array mapping of tag description keys to values (WP Term ID)
	 * Set your tag description as the term tag description
	 * @return array
	 */
	public static function get_tag_mappings() {
		global $wpdb;
		$query = "SELECT term_id as id, description FROM `{$wpdb->prefix}term_taxonomy` where taxonomy = 'post_tag' order by count desc";
		/**
		 * If we're getting problems with this list, you may want to track before / after memory usage with memory_get_usage()
		 */
		$tags = $wpdb->get_results( $query, ARRAY_A );
		$tags = wp_list_pluck( $tags, 'id', 'description' );
		$tags = array_map( 'absint', $tags );
		return $tags;
	}

}
