<?php
namespace WP_CLI\BigMig\Posts;

use Analog\Analog;
use WP_CLI\BigMig\Logger;
use WP_CLI\BigMig\Util;

abstract class Post {

	protected $content; // Parsed content via /parsers/*ContentParser.php
	protected $insert_params = array(); // Params for wp_insert_post
	protected $meta_input = array(); // This will be in $this->insert_params['meta_input']
	protected $tax_input = array(); // This will NOT be in $this->insert_params['tax_input']
	protected $post_id; // The post ID after we've inserted it.
	protected $filename; // The content export file.
	protected $type; // The type of post enum('gallery', 'story') not the CPT name.

	/**
	 * Save this post.
	 * @return $post_id on success or false.
	 */
	public function save() {
		$this->prepare();

		$post_id = wp_insert_post( $this->insert_params );

		/**
		 * We set the tax_input after inserting on purpose, since wp_insert_post
		 * needs the author to have tax capabilities which it doesn't so we add
		 * taxonomy AFTER inserting the post with wp_set_object_terms
		 */
		$this->set_tax_param_from_content( 'tags', 'tags' );
		$this->set_tax_param_from_content( 'category_id', 'category_id' );

		if ( 0 === $post_id || is_wp_error( $post_id ) ) {
			$content = json_encode( $this->insert_params );
			Logger::log( "Unable to insert post type: {$this->type} for {$this->filename} here is the content: {$content}.");
			return false;
		} else {
			$this->post_id = $post_id;
			if ( count( $this->tax_input ) ) {
				$this->save_taxonomy();
			}
			return $post_id;
		}
	}

	/**
	 * Take the content that has been parsed from the content export and populate the $postarr for wp_insert_post.
	 * Includes meta, but not taxonomy as that is handled separately due to WP CLI restrictions.
	 * @return mixed
	 */
	abstract public function prepare();

	public function __construct( &$parsed_content = array(), &$filename ) {
		$this->content = $parsed_content;
		$this->filename = $filename;
		$this->meta_input[ '_bigmig'] = $filename; // Flag our post as a bigmig post and map it to the content export file it came from
	}

	/**
	 * Set a param for the main $postarr that @wp_insert_post uses.
	 * Do NOT set tax_input or meta_input here.
	 *
	 * @param $insert_key what the key needs to be in $postarr see @wp_insert_post
	 * @param $content_key what the key is in $this->content
	 */
	public function set_insert_param_from_content( $insert_key, $content_key ) {
		if ( array_key_exists( $content_key, $this->content ) ) {
			$this->insert_params[ $insert_key ] = $this->content[ $content_key ];
		}
	}

	/**
	 * Set a meta param for the main postarr['meta_input'] that @wp_insert_post uses.
	 *
	 * @param $insert_key what the key needs to be in $postarr['meta_input'] see @wp_insert_post
	 * @param $content_key what the key is in $this->content
	 */
	public function set_meta_param_from_content( $meta_key, $content_key ) {
		if ( array_key_exists( $content_key, $this->content ) ) {
			$this->meta_input[ $meta_key ] = $this->content[ $content_key ];
		}
	}

	/**
	 * Set a meta param for taxonomy input that @wp_set_object_terms uses.
	 *
	 * @param $insert_key what the key needs to be in $meta see @save_taxonomy
	 * @param $content_key what the key is in $this->content
	 */
	public function set_tax_param_from_content( $meta_key, $content_key ) {
		if ( array_key_exists( $content_key, $this->content ) ) {
			$this->tax_input[ $meta_key ] = $this->content[ $content_key ];
		}
	}

	/**
	 * Save the taxonomy based on what values exist in $this->tax_input
	 * $this->post_id must be set or we return silently.
	 */
	public function save_taxonomy() {
		if ( ! is_int( $this->post_id ) ) {
			$tax = json_encode( $this->tax_input );
			Logger::log( "Can't save taxonomy for {$this->filename} since we haven't saved the post yet, taxonomy is: {$tax}");
			return;
		}

		if ( array_key_exists( 'category_id', $this->tax_input ) ) {
			$category = wp_set_object_terms( $this->post_id, $this->tax_input[ 'category_id' ], 'category' );
			/**
			 * We only need to handle WP_Error case since we're setting based on term_id since WP doesn't care if term doesn't exist.
			 * We on the other hand DO know that term exists since the IDs are set in Mappings::init();
			 */
			if ( is_wp_error( $category ) ) {
				$cat_id = $this->tax_input[ 'category_id' ];
				Logger::log( "Unable to add category with ID {$cat_id} to post ID {$this->post_id} for export file: {$this->filename}");
			}
		}
		if ( array_key_exists( 'tags', $this->tax_input ) ) {
			$tags = wp_set_object_terms( $this->post_id, $this->tax_input['tags'], 'post_tag' );
			/**
			 * We only need to handle WP_Error case since we're setting based on term_id since WP doesn't care if term doesn't exist.
			 * We on the other hand DO know that term exists since the IDs are set in Mappings::init();
			 */
			if ( is_wp_error( $tags ) ) {
				$tag_ids = json_encode( $this->tax_input[ 'tags' ] );
				Logger::log( "Unable to add tags with IDs {$tag_ids} to post ID {$this->post_id} for export file: {$this->filename}");
			}
		}
	}

}
