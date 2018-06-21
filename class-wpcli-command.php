<?php

/**
 * Generate Posts
 */
class Parser_JSON_Generate {


	public function __invoke( $args ) {

		WP_CLI::log( 'Generating JSON files...' );
		$files = new WP_Parser_JSON_File();

		if ( $files->generate_files() === true ) {
			WP_CLI::error( 'Could not access the WP_Filesystem API' );
			return;
		}

		$errors = get_settings_errors( 'wp-parser-json' );

		foreach ( $errors as $error ) {
			if ( 'updated' === $error['type'] ) {
				WP_CLI::success( 'JSON files generated' );
			} else {
				WP_CLI::error( mb_convert_encoding( $error['message'], 'UTF-8', 'HTML-ENTITIES' ) );
			}
		}
	}

}

WP_CLI::add_command( 'parser-json generate', 'Parser_JSON_Generate' );
