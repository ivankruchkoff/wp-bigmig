<?php

namespace WP_CLI\BigMig;

class Image {

	/**
	 * Ingest a nonexistent image
	 *
	 * @param $filename
	 *
	 * @return string
	 */
	public static function ingest_nonexistent_image_and_get_attachment_id( $image_path ) {
		$attachment_id = DBMappings::get_post_id_for_path( $image_path, 'image' );
		if ( false === $attachment_id ) {
			/**
			 * @TODO: figure out what we're doing for image processing.
			 */
			DBMappings::old_content_migrated( $image_path, 1, 'image' );
			$attachment_id = DBMappings::get_post_id_for_path( $image_path, 'image' );
		}
		return $attachment_id;
	}

}
