<?php
namespace WP_CLI\BigMig\Posts;

use WP_CLI\BigMig\Logger;

class WebHoseArticlePost extends Post {

	public function prepare() {
		$this->type = 'WebHoseArticle';
		$this->set_insert_param_from_content( 'post_content', 'body' );
		$this->set_insert_param_from_content( 'post_title', 'title' );

		$this->insert_params[ 'meta_input' ] = $this->meta_input;
	}
}
