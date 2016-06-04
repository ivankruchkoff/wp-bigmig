<?php
namespace WP_CLI\BigMig;

use Analog\Analog;
use WP_CLI\BigMig\Logger;
use WP_CLI;

class FileMover {
	public static $current_folder;
	public static $next_folder;
	public static $investigate_folder;
	public static $processed_folder;
	public static $ignored_folder;

	public static function init( $base_folder ) {
		self::prune_empty_pending_directories( $base_folder );
		self::$current_folder = self::get_oldest_pending_folder( $base_folder );
		self::$next_folder = self::make_new_pending_folder( $base_folder );
		self::$investigate_folder = self::get_investigate_folder( $base_folder );
		self::$processed_folder = self::get_processed_folder( $base_folder );
		self::$ignored_folder = self::get_ignored_folder( $base_folder );
	}

	public static function move_to_folder( $filename, $destination, $status ) {
		$filename = realpath( $filename );
		$new_filename = str_replace( self::$current_folder, $destination, $filename );
		$new_dirname =  str_replace( self::$current_folder, $destination, dirname( $filename ) );
		if ( ! file_exists( $new_dirname ) ) {
			mkdir( $new_dirname, 0777, true);
		}
		$return = rename( $filename, $new_filename );
		if ( $return ) {
			Logger::log( "Moved {$filename} to {$new_filename} status: {$status}");
		}

		return $return;
	}

	public static function move_to_pending( $filename ) {
		return self::move_to_folder( $filename, self::$next_folder, 'pending' );
	}


	public static function move_to_processed( $filename ) {
		return self::move_to_folder( $filename, self::$processed_folder, 'processed' );

	}

	public static function move_to_investigate( $filename ) {
		return self::move_to_folder( $filename, self::$investigate_folder, 'investigate' );
	}

	public static function move_to_ignored( $filename ) {
		return self::move_to_folder( $filename, self::$ignored_folder, 'ignored' );
	}

	public static function prune_empty_pending_directories( $base_folder ) {
		$dirs = array_filter( glob( $base_folder . '/pending/*' ), 'is_dir');
		set_error_handler(function() { /* ignore errors */ });
		foreach ( $dirs as $dir ) {
			rmdir( $dir ); // Try and delete all empty subdirs, ignore errors;
		}
		restore_error_handler();
	}

	public static function get_oldest_pending_folder( $base_folder ) {
		$dirs = array_filter( glob( $base_folder . '/pending/*' ), 'is_dir');
		if ( ! count( $dirs ) ) {
			Logger::log( 'No pending folders found in ' . realpath( $base_folder . '/pending/' ) );
			exit;
		}
		return realpath( array_shift( $dirs ) );
	}

	public static function make_new_pending_folder( $base_folder ) {
		return self::get_folder( $base_folder, 'pending/' . time() );
	}

	public static function get_investigate_folder( $base_folder ) {
		return self::get_folder( $base_folder, 'investigate' );
	}

	public static function get_processed_folder( $base_folder ) {
		return self::get_folder( $base_folder, 'processed' );
	}

	public static function get_ignored_folder( $base_folder ) {
		return self::get_folder( $base_folder, 'ignored' );
	}

	public static function get_folder( $base_folder, $extension ) {
		$folder_name = "{$base_folder}/{$extension}";
		if ( ! file_exists( $folder_name ) ) {
			mkdir( $folder_name );
		}
		return realpath( $folder_name );
	}
}
