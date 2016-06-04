<?php

namespace WP_CLI\BigMig;

class Util {

	/**
	 * Given a path to a folder, remove it only if it is empty, or it contains only empty folders.
	 * @param $path string to folder
	 *
	 * @return bool
	 */
	public static function remove_nested_empty_folders( $path ) {
		$empty = true;
		foreach ( glob( $path . DIRECTORY_SEPARATOR . "*" ) as $file ) {
			$empty &= is_dir( $file ) && self::remove_nested_empty_folders( $file );
		}
		return $empty && rmdir( $path );
	}

}
