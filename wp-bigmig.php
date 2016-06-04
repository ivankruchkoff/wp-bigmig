<?php
/**
 * Plugin Name: WP Big Mig
 * Plugin URI: http://kruchkoff.com
 * Description: This plugin adds a wp-cli command for content migrations
 * Version: 1.0.0
 * Author: Ivan Kruchkoff
 * License: MIT
 */
if ( ! defined('WP_CLI') || ! WP_CLI ) {
    return;
}
// If you install via package, the autoloader is already included and doesn't live in the root folder.
if ( ! class_exists('WP_CLI\BigMig\Command') ) {
    if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
        require_once __DIR__ . '/vendor/autoload.php';
    } else {
        WP_CLI::error( "Please, run composer install first" );
    }
}
WP_CLI::add_command( 'bigmig', 'WP_CLI\BigMig\Command' );
