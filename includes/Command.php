<?php

namespace WP_CLI\BigMig;


use WP_CLI;
use WP_CLI\CommandWithDBObject;
use WP_CLI\BigMig\Prune;
use WP_CLI\BigMig\ContentFormatter;

/**
 * wp bigmig is a command to migrate content exports to WP
 *
 */
class Command extends CommandWithDBObject {

    protected $settings;

    /**
     * Migrate your file content to WordPress
     *
     * ## OPTIONS
     *
     * [-f <folder>]
     * : Which folder is the base folder and has a <folder>/pending/<timestamp> folder containing the content exports.
     *
     * ## EXAMPLES
     *
     * wp bigmig -l data-export
     *
     * @synopsis [<-f>] [<folder>]
     */
    function __invoke( $args = array(), $assoc_args = array() ) {
        if ( ! count( $args ) && ! count( $assoc_args ) ) {
            $this->show_usage();
            return;
        }
        while ( ob_get_level() > 0 ) {
            ob_end_flush();
        }

        $this->settings = new Settings();
        $this->settings->parse_arguments( $args, $assoc_args );
        $this->run();
        ob_start();

    }

    /**
     * Execute the WP BigMig command.
     */
    function run() {
        DBMappings::init();
        Mappings::init();
        if ( false !== $this->settings->folder && ! is_null( $this->settings->folder ) ) {
            FileMover::init( $this->settings->folder);
            $this->wp_setup();
            $folder = new ExportFilesIterator( $this->settings->folder );
            $folder->run();
        }
    }

    function wp_setup() {
        wp_defer_term_counting( true );
        wp_defer_comment_counting( true );
        define( 'WP_POST_REVISIONS', 0 );

    }

    function show_usage() {
        \WP_CLI::line( "usage: wp bigmig -f <folder>" );
        \WP_CLI::line( "" );
        \WP_CLI::line( "If you wanted to invoke this for a set of already setup directories, just add the -f param." );
        \WP_CLI::line( "" );
        \WP_CLI::line( "If you have a folder of content exports called ./exports here's what you need to do:" );
        \WP_CLI::line( "mkdir -p migrate/pending/123 && mv exports migrate/pending/123" );
        \WP_CLI::line( "wp bigmig -f migrate" );
        \WP_CLI::line( "" );
        \WP_CLI::line( "Big mig will go to the first unprocessed pending folder and start looking for content to handle" );
        \WP_CLI::line( "and after it processes content it will be stored in the following folders:" );
        \WP_CLI::line( "" );
        \WP_CLI::line( "migrate/ignored - Content that is invalid and is not going to be processed" );
        \WP_CLI::line( "migrate/investigate - Content that should have been processed but for some reason failed. Investigate it, update your parser and move the content to a folder back in /pending/*anything*" );
        \WP_CLI::line( "migrate/processed - Content that has been successfully processed, it can be deleted/archived. Your db mapping table has the path / post_id table, and your _bigmig post meta points to the filename" );
        \WP_CLI::line( "migrate/pending/ - Every time the script is run, a new pending folder is created, so that any content that depends on other content existing in WP can be moved here to be processed later." );
    }
}

