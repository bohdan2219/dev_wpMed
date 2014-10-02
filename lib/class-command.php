<?php

namespace WP_Parser;

use WP_CLI;
use WP_CLI_Command;

/**
 * Converts PHPDoc markup into a template ready for import to a WordPress blog.
 */
class Command extends WP_CLI_Command {

	/**
	 * Generate a JSON file containing the PHPDoc markup, and save to filesystem.
	 *
	 * @synopsis <directory> [<output_file>]
	 */
	public function export( $args ) {
		$directory = $args[0];

		$output_file = 'phpdoc.json';

		if ( ! empty( $args[1] ) ) {
			$output_file = $args[1];
		}

		$directory = realpath( $directory );
		WP_CLI::line();

		// Get data from the PHPDoc
		$json = $this->_get_phpdoc_data( $directory );

		// Write to $output_file
		$error = ! file_put_contents( $output_file, $json );

		if ( $error ) {
			WP_CLI::error( sprintf( 'Problem writing %1$s bytes of data to %2$s', strlen( $json ), $output_file ) );
			exit;
		}

		WP_CLI::success( sprintf( 'Data exported to %1$s', $output_file ) );
		WP_CLI::line();
	}

	/**
	 * Read a JSON file containing the PHPDoc markup, convert it into WordPress posts, and insert into DB.
	 *
	 * @synopsis <file> [--quick] [--import-internal]
	 */
	public function import( $args, $assoc_args ) {
		list( $file ) = $args;
		WP_CLI::line();

		// Get the data from the <file>, and check it's valid.
		$phpdoc = false;

		if ( is_readable( $file ) ) {
			$phpdoc = file_get_contents( $file );
		}

		if ( ! $phpdoc ) {
			WP_CLI::error( sprintf( "Can't read %1\$s. Does the file exist?", $file ) );
			exit;
		}

		$phpdoc = json_decode( $phpdoc, true );
		if ( is_null( $phpdoc ) ) {
			WP_CLI::error( sprintf( "JSON in %1\$s can't be decoded :(", $file ) );
			exit;
		}

		// Import data
		$this->_do_import( $phpdoc, isset( $assoc_args['quick'] ), isset( $assoc_args['import-internal'] ) );
	}

	/**
	 * Generate JSON containing the PHPDoc markup, convert it into WordPress posts, and insert into DB.
	 *
	 * @subcommand create
	 * @synopsis   <directory> [--quick] [--import-internal] [--user]
	 */
	public function create( $args, $assoc_args ) {
		list( $directory ) = $args;
		$directory = realpath( $directory );

		if ( empty( $directory ) ) {
			WP_CLI::error( sprintf( "Can't read %1\$s. Does the file exist?", $directory ) );
			exit;
		}

		WP_CLI::line();

		// Import data
		$this->_do_import( $this->_get_phpdoc_data( $directory, 'array' ), isset( $assoc_args['quick'] ), isset( $assoc_args['import-internal'] ) );
	}

	/**
	 * Generate the data from the PHPDoc markup.
	 *
	 * @param string $path   Directory to scan for PHPDoc
	 * @param string $format Optional. What format the data is returned in: [json*|array].
	 *
	 * @return string
	 */
	protected function _get_phpdoc_data( $path, $format = 'json' ) {
		$is_file = is_file( $path );
		WP_CLI::line( sprintf( 'Extracting PHPDoc from %1$s. This may take a few minutes...', $is_file ? $path : "$path/" ) );

		// Find the files to get the PHPDoc data from. $path can either be a folder or an absolute ref to a file.
		if ( $is_file ) {
			$files = array( $path );
			$path  = dirname( $path );
		} else {
			ob_start();
			$files = get_wp_files( $path );
			$error = ob_get_clean();

			if ( $error ) {
				WP_CLI::error( sprintf( 'Problem with %1$s: %2$s', $path, $error ) );
				exit;
			}
		}

		// Extract PHPDoc
		$output = parse_files( $files, $path );

		if ( $format == 'json' ) {
			$output = json_encode( $output );
		}

		return $output;
	}

	/**
	 * Import the PHPDoc $data into WordPress posts and taxonomies
	 *
	 * @param array $data
	 * @param bool  $skip_sleep                Optional; defaults to false. If true, the sleep() calls are skipped.
	 * @param bool  $import_internal_functions Optional; defaults to false. If true, functions marked @internal will be imported.
	 */
	protected function _do_import( array $data, $skip_sleep = false, $import_internal_functions = false ) {

		if ( ! wp_get_current_user()->exists() ) {
			WP_CLI::error( 'Please specify a valid user: --user=<id|login>' );
			exit;
		}

		// Run the importer
		$importer = new WP_CLI_Importer;
		$importer->import( $data, $skip_sleep, $import_internal_functions );

		WP_CLI::line();
	}
}
