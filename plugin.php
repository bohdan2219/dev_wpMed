<?php
/**
 * Plugin Name: WP Parser
 * Description: Create a function reference site powered by WordPress
 * Author: Ryan McCue and Paul Gibbs
 * Plugin URI: https://github.com/rmccue/WP-Parser
 * Version: 1.0
 */

require __DIR__ . '/importer.php';

WP_PHPDoc::bootstrap();

class WP_PHPDoc {
	public static function bootstrap() {
		if ( defined('WP_CLI') && WP_CLI ) {
			require __DIR__ . '/cli.php';
			WP_CLI::add_command( 'phpdoc', 'WP_PHPDoc_Command' );
		}

		add_action( 'init', __CLASS__ . '::register_post_types' );
		add_action( 'init', __CLASS__ . '::register_taxonomies' );
	}

	public static function register_post_types() {
		// Functions
		register_post_type( 'wpapi-function', array(
			'has_archive'  => true,
			'hierarchical' => true,
			'label'        => __( 'Functions' ),
			'public'       => true,
			'rewrite'      => array( 'slug' => 'functions' ),
			'supports'     => array( 'comments', 'custom-fields', 'editor', 'excerpt', 'page-attributes', 'revisions', 'title' ),
			'taxonomies'   => array( 'wpapi-source-file' ),
		) );

		// Classes
		register_post_type( 'wpapi-class', array(
			'has_archive'  => true,
			'hierarchical' => false,
			'label'        => __( 'Classes' ),
			'public'       => true,
			'rewrite'      => array( 'slug' => 'classes' ),
			'supports'     => array( 'comments', 'custom-fields', 'editor', 'excerpt', 'revisions', 'title' ),
			'taxonomies'   => array( 'wpapi-source-file' ),
		) );
	}

	public static function register_taxonomies() {
		// Files
		register_taxonomy( 'wpapi-source-file', array( 'wpapi-class', 'wpapi-function' ), array(
			'hierarchical'          => true,
			'label'                 => __( 'Files' ),
			'public'                => true,
			'rewrite'               => array( 'slug' => 'files' ),
			'sort'                  => false,
			'update_count_callback' => '_update_post_term_count',
		) );

		// Files
		register_taxonomy( 'wpapi-since', array( 'wpapi-class', 'wpapi-function' ), array(
			'hierarchical'          => true,
			'label'                 => __( '@since' ),
			'public'                => true,
			'sort'                  => false,
			'update_count_callback' => '_update_post_term_count',
		) );
	}
}
