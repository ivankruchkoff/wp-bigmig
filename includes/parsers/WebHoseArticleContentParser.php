<?php
namespace WP_CLI\BigMig\Parsers;

class WebHoseArticleContentParser {

	protected $filename;
	protected $parsed_content = array();
	protected $content;

	/**
	 * Parse a news article json file, returns an array for creating a story WP_Post.
	 */
	public function parse() {
		$this->set_title();
		$this->set_body();

		return $this->get_parsed_content();
	}

	public function __construct( &$content, &$filename ) {
		$this->content  = $content;
		$this->filename = $filename;
	}

	public function get_parsed_content() {
		return $this->parsed_content;
	}

	function set_title() {
		$this->parsed_content[ 'title' ] = $this->content['title'];
	}

	function set_body() {
		$this->parsed_content[ 'body' ] = $this->content['text'];
	}

}
