<?php
namespace WP_CLI\BigMig;

use WP_CLI;
use Analog\Analog;

Analog::handler (\Analog\Handler\File::init ( WP_CONTENT_DIR . '/migration-' . time() . '-' . rand(1000,9999) . '.log'));


class Logger {

	static function log( $message, $level = Analog::ALERT ) {
		Analog::log( $message, $level);
	}
}
