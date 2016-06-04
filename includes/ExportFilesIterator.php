<?php
namespace WP_CLI\BigMig;

use Analog\Analog;
use WP_CLI;

class ExportFilesIterator {

	/**
	 * @var Folder where all the content is stored.
	 */
	public $folder;

	public $valid_extensions = array(
		'json',
		/**
		 * 'xml',
		 * 'csv',
		 */
	);

	function __construct( $folder ) {
		$this->folder = $folder;
	}

	/**
	 * Kick off ingestion for the FileMover::$current_folder
	 */
	function run() {
		/**
		 * @param SplFileInfo $file
		 * @param mixed $key
		 * @param RecursiveCallbackFilterIterator $iterator
		 * @return bool True if you need to recurse or if the item is acceptable
		 */
		$filter = function ($file, $key, $iterator) {
			if ( $iterator->hasChildren() ) {
				return true;
			}
			// Filter non valid file extensions
			return $file->isFile() && in_array( $file->getExtension(), $this->valid_extensions );
		};

		$innerIterator = new \RecursiveDirectoryIterator(
				FileMover::$current_folder,
				\RecursiveDirectoryIterator::SKIP_DOTS
		);
		$iterator = new \RecursiveIteratorIterator(
				new \RecursiveCallbackFilterIterator($innerIterator, $filter)
		);

		/**
		 * Track the count of files processed (ignores ignored files).
		 */
		$counter = 0;

		foreach ($iterator as $pathname => $file_info) {
			if ( $this->content_is_processed( $pathname ) ) {
				Logger::log( "Already processed {$pathname}", Analog::ERROR );
				FileMover::move_to_processed( $pathname );
				continue;
			}
			$counter += 1;
			Logger::log( "Now processing {$pathname}, processed {$counter} files." );
			if ( substr($pathname, -5) === '.json') {
				$this->parse_json_file( $pathname );
			} elseif ( substr($pathname, -4) === '.xml') {
				// $this->parse_xml_file( $pathname );
			} elseif ( substr($pathname, -4) === '.csv') {
				// $this->parse_csv_file( $pathname );
			} else {
				Logger::log( "No way to parse {$pathname}.", Analog::ERROR );
				continue;
			}

			/**
			 * Remove folder if it's now empty.
			 */
			Util::remove_nested_empty_folders( dirname( $pathname ) );
		}

		$cwd = FileMover::$current_folder;
		Logger::log( "Finished processing {$cwd}, processed a total of {$counter} files." );
		Util::remove_nested_empty_folders( FileMover::$current_folder );
	}

	/**
	 * Parse a json file, and if it's valid call @maybe_insert_content
	 * @param $filename
	 *
	 * @return bool
	 */
	function parse_json_file( &$filename ) {
		$content_string = file_get_contents( $filename );
		$content = json_decode( $content_string, true);
		if ( ! is_array( $content ) ) {
			Logger::log( "Could not parse JSON file into array in {$filename}", Analog::ERROR );
			FileMover::move_to_investigate( $filename );
			return false;
		}
		$this->maybe_insert_content( $content, $filename );
	}

	/**
	 * Insert content if it's valid and non-existent in WP.
	 *
	 * @param $content
	 * @param $filename
	 *
	 * @return bool
	 */
	function maybe_insert_content( &$content, &$filename ) {
		if ( ! $this->is_valid_content_type( $content ) ) {
			FileMover::move_to_ignored( $filename );
			Logger::log( "Invalid content type in {$filename}", Analog::ERROR );
			return false;

		}

		$type = $this->get_content_type( $content );

		$contentParser = null;
		$post = null;

		switch( $type ) {
			case 'WebHoseArticle':
				$contentParser = new Parsers\WebHoseArticleContentParser( $content, $filename );
				$content = $contentParser->parse();
				if ( ! $content ) {
					Logger::log( "Unable to parse {$type} for content in {$filename}", Analog::ERROR );
					FileMover::move_to_investigate($filename);
					return false;
				}
				$post = new Posts\WebHoseArticlePost( $content, $filename );
				break;
			default:
				Logger::log( "Not currently parsing content type {$type} in {$filename}", Analog::ERROR );
				FileMover::move_to_ignored($filename); // TODO remove me
				return false;
		}

		$post_id = $post->save();

		if ( $post_id ) {
			/**
			 * Add mapping of old path > WP Post ID
			 */
			DBMappings::old_content_migrated( $filename, $post_id );
			FileMover::move_to_processed( $filename );
			Logger::log( "Finished processing content export {$filename} as WP Post {$post_id}.");
		} else if ( false === $post_id ) {
			FileMover::move_to_investigate( $filename );
			return false;
		}
		return true;
	}

	/**
	 * Valid article type encountered
	 *
	 * @param $content json content
	 *
	 * @return bool
	 */
	function is_valid_content_type( &$content ) {
		return array_key_exists( 'text', $content );
	}

	/**
	 * Get the content type
	 *
	 * @param $content parsed JSON content
	 *
	 * @return bool|string
	 */
	function get_content_type( &$content ) {
		return 'WebHoseArticle';
	}

	/**
	 * See if we've already processed this content export file.
	 * @param $filename
	 *
	 * @return bool
	 */
	function content_is_processed( &$filename ) {
		return DBMappings::is_file_already_migrated( $filename );
	}
}
