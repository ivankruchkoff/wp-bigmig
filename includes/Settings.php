<?php
namespace WP_CLI\BigMig;

use WP_CLI;

/**
 * Based on MIT licensed https://github.com/10up/wp-hammer authored by Ivan Kruchkoff
 */

class Settings {

	/**
	 * @var Active folder for import, we only allow one folder at a time.
	 */
	public $folder;

	/**
	 * @var Array of folders specified
	 */
	private $folders;


	/**
	 * Arguments parser for all supplied arguments
	 * @param $args
	 * @param $assoc_args
	 */
	function parse_arguments( $args, $assoc_args ) {
		while ( count( $args ) ) {
			$arg = array_shift( $args );
			switch ( $arg ) {
				case '-f':
					$this->parse_argument( $args, 'folders' );
					$this->folder = array_shift( $this->folders );
					break;
			}
		}
	}

	/**
	 * Parse an arg for an individual property, if it exists.
	 *
	 * @param $args
	 * @param $property
	 *
	 * @return mixed
	 */
	function parse_argument( $args, $property ) {
		if ( property_exists( $this, $property ) && count( $args ) && '-' !== substr( $args[0], 0, 1 ) ) {
			$arg_values = explode( ',', array_shift( $args ) );
			$this->{ "$property" } = array_unique( array_merge_recursive( (array) $this->{ "$property" }, $arg_values ) );
		}
		return $this->{ "$property" };
	}
}
