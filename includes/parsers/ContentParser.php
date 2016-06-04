<?php
namespace WP_CLI\BigMig\Parsers;

use Analog\Analog;
use WP_CLI\BigMig\Logger;
use WP_CLI\BigMig\Mappings;

abstract class ContentParser {

	/**
	 * @var contains the relevant parts of the json file that we will be mapping into a post.
	 */
	protected $parsed_content = array();


	/**
	 * @var the file we are processing
	 */
	protected $filename;

	/**
	 * @var the contents of the file json_decoded into an associative array
	 */
	protected $content;

	/**
	 * @var map of $parsed_content keys, to where they are in the $content assoc array.
	 */
	protected $content_mappings = array(
		'title' => 'jcr:title',
		'description' => 'jcr:description',
	);

	/**
	 * Parse the json file content, and return the content we need for creating a WP_Post.
	 * @return mixed
	 */
	abstract public function parse();

	public function __construct( &$content, &$filename, $mappings = array() ) {
		$this->content          = $content;
		$this->filename         = $filename;
		$this->content_mappings = array_merge( $this->content_mappings, $mappings );
	}

	public function get_parsed_content() {
		return $this->parsed_content;
	}


	/**
	 * Sets the parsed_content[$key] using mapping in $content_mappings for parsing the json $content
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function set_mapped_content( $key ) {
		if ( ! is_string( $key ) || !strlen( $key ) || ! array_key_exists( $key, $this->content_mappings ) ) {
			return false;
		}
		$value = $this->maybe_get_content_key( $this->content_mappings[ $key ] );

		if ( $value ) {
			$this->parsed_content[ $key ] = $value;
			return true;
		}
		return false;

	}

	public function set_title() {
		return $this->set_mapped_content( 'title' );
	}

	/**
	 * Return $key from content if it exists, or false.
	 * @param      $key
	 * @param null $content
	 *
	 * @return bool
	 */
	public function maybe_get_content_key( $key, &$content = null ) {
		if ( is_null( $content ) ) {
			$content = $this->content;
		}
		if ( is_string( $content ) ) {
			return false;
		}
		return array_key_exists( $key, $content ) ? $content[ $key ] : false;
	}

	/**
	 * Recursive method of getting content, with separate levels denoted by #,
	 * e.g. articleContent#story#articleBody is the equivalent in JS of content.articleContent.story.articleBody (if they all exist) or false if not existent.
	 *
	 * @param           $key_path
	 * @param bool|true $log_errors whether or not we log a message when we don't find the key
	 * @param null      $content optional content if we're recursing.
	 *
	 * @return bool
	 */
	public function maybe_get_nested_content_key( $key_path, $log_errors = true, &$content = null ) {
		if ( is_null( $content ) ) {
			$content = $this->content;
		}
		$keys = explode( '#', $key_path );
		$key = array_shift( $keys );

		$value = $this->maybe_get_content_key( $key, $content );
		if ( false === $value ) {
			if ( $log_errors ) {
				$all_keys = json_encode( array_keys( $content ) );
				Logger::log( "Unable to find {$key} of {$key_path} in content with keys {$all_keys} in {$this->filename}" );
			}

			return false;
		}
		if ( count( $keys ) ) {
			$key_path = implode( '#', $keys );
			return $this->maybe_get_nested_content_key( $key_path, $log_errors, $value );
		}
		return $value;

	}

	/**
	 * Get the tags from the story and add them to $this->content
	 */
	public function set_tags() {
		$tags = $this->maybe_get_nested_content_key( 'cq:tags' );
		$tags = is_array( $tags ) ? $tags : array();
		$tag_ids = array();
		foreach( $tags as $tag ) {
			if ( array_key_exists( $tag, Mappings::$tags ) ) {
				$tag_ids[] = Mappings::$tags[ $tag ];
			} else {
				Logger::log( "Missing tag {$tag} for filename {$this->filename}" );
			}
		}
		if ( count( $tag_ids ) ) {
			$this->parsed_content[ 'tags' ] = $tag_ids;
		}
	}

}
