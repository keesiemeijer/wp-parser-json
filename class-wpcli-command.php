<?php

/**
 * Generate Posts
 */
class Parser_JSON_Generate {

	/**
	 * Generate JSON files
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<post_type>]
	 * : Comma seperated post types to generate JSON files for.
	 *
	 * ## EXAMPLES
	 *
	 *     wp parser-json generate --post_type=post,page
	 */
	public function __invoke( $args, $assoc_args ) {
		$post_types = isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : '';
		$post_types = preg_split( '/[,]+/', trim( $post_types ), -1, PREG_SPLIT_NO_EMPTY );
		$post_types = array_map( 'trim', array_unique( $post_types ) );

		if ( $post_types ) {
			$post_types = array_fill_keys( array_values( $post_types ), '' );
			$post_types = wppj_get_json_post_types( $post_types );
		} else {
			// Back compatibility
			if ( wppj_phpdoc_parser_post_types_exists() ) {
				$hook_types = wppj_get_phpdoc_parser_hook_types();
				$post_types = wppj_get_phpdoc_parser_post_types();
				$post_types = array_merge( $post_types, array_fill_keys( $hook_types, 'wp-parser-hook' ) );
				unset( $post_types['methods'] );
			}
		}

		WP_CLI::log( 'Generating JSON files...' );
		$files = new WP_Parser_JSON_File();

		if ( true === $files->generate_files( $post_types ) ) {
			WP_CLI::error( 'Could not access the WP_Filesystem API' );
			return;
		}

		$errors = get_settings_errors( 'wp-parser-json' );
		foreach ( $errors as $error ) {
			if ( 'updated' === $error['type'] ) {
				WP_CLI::success( 'JSON files generated' );
			} else {
				$error_msg = mb_convert_encoding( $error['message'], 'UTF-8', 'HTML-ENTITIES' );
				if ( 'no_post_type' === $error['code'] ) {
					$warnings = explode( '<br/>', $error_msg );
					$warnings = array_filter( $warnings );
					foreach ( $warnings as $value ) {
						WP_CLI::warning( $value );
					}

					WP_CLI::error( 'Not all JSON files are created' );
					continue;
				}

				WP_CLI::error( $error_msg );
			}
		}
	}

}

WP_CLI::add_command( 'parser-json generate', 'Parser_JSON_Generate' );
