<?php
/**
 * Generates json files
 */
if ( ! class_exists( 'WP_Parser_JSON_File' ) ) {
	class WP_Parser_JSON_File extends WP_Parser_JSON_Reference_Query {

		public function __construct() {
			parent::__construct();
		}

		/**
		 * Generate json files for WP Parser post types.
		 *
		 * Uses the WP_Filesystem API.
		 *
		 * @since 0.1
		 * @return bool Returns true if there's no access to the WP_Filesystem API.
		 */
		function generate_files( $post_types = array() ) {
			$wp_cli = defined( 'WP_CLI' ) && WP_CLI;

			// Okay, let's see about getting credentials.
			$form_fields = array();
			$method = ''; // TODO TESTING

			$url = wp_nonce_url( 'options-general.php?page=wp-parser-json', 'wp-parser-json_nonce' );
			if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $form_fields ) ) ) {
				return true;
			}

			// Now we have some credentials, try to get the wp_filesystem running.
			if ( ! WP_Filesystem( $creds ) ) {
				// Our credentials were no good, ask the user for them again.
				request_filesystem_credentials( $url, $method, true, false, $form_fields );
				return true;
			}

			global $wp_filesystem;

			// Directories that will be generated inside this plugin folder.
			$dirs = array(
				'json-files'      => plugin_dir_path( __FILE__ ) . 'json-files',
				'json-files-temp' => plugin_dir_path( __FILE__ ) . 'json-files-temp',
			);

			foreach ( $dirs as $key => $dir ) {

				// Delete directory (and files) if it exists.
				if ( $wp_filesystem->exists( $dir ) ) {
					if ( ! $wp_filesystem->rmdir( $dir, true ) ) {
						$error = esc_html__( "Unable to delete directory $dir", 'wp-parser-json' );
						add_settings_error( 'wp-parser-json', 'delete_directory', $error, 'error' );
						return false;
					}
				}

				// Create new directory.
				if ( ! $wp_filesystem->mkdir( $dir ) ) {
					$error = esc_html__( "Unable to create the directory {$dir}"  , 'wp-parser-json' );
					add_settings_error( 'wp-parser-json', 'create_directory', $error, 'error' );
					return false;
				}
			}

			$post_types = apply_filters( 'wp_parser_json_parse_post_types', $post_types );
			if ( ! $post_types ) {
				$error = esc_html__( "No post type JSON files generated. Please provide a valid post type", 'wp-parser-json' );
				add_settings_error( 'wp-parser-json', 'post_type_fail', $error, 'error' );
				return false;
			}

			$files_created    = false;
			$phpdoc_post_type = false;
			$error  = '';

			// Create the post_type_slug.json files.
			foreach ( $post_types as $slug => $post_type ) {
				if ( ! post_type_exists( $post_type ) ) {
					$error .= sprintf( __( "No %s file created", 'wp-parser-json' ), $slug . '.json' );
					$error .= ' ' . sprintf( __( "Please make sure post type %s exists", 'wp-parser-json' ), $post_type  ) . '<br/>';
					continue;
				}

				if ( $wp_cli ) {
					WP_CLI::log( "Generating {$slug}.json file..." );
				}

				$content  = $this->get_post_type_content( $slug, $post_type );
				$filename = sanitize_file_name(  $slug . '.json' );

				$file = trailingslashit( $dirs['json-files'] ) . $filename;
				if ( ! $wp_filesystem->put_contents( $file, $content, FS_CHMOD_FILE ) ) {
					$error = esc_html__( "Unable to create the file: {$slug}.json", 'wp-parser-json' );
					add_settings_error( 'wp-parser-json', 'create_file', $error, 'error' );
					return false;
				}

				$files_created = true;
				if ( wppj_is_phpdoc_parser_post_type( $post_type ) ) {
					$phpdoc_post_type = true;
				}
			}

			if ( $error ) {
				add_settings_error( 'wp-parser-json', 'no_post_type', $error, 'error' );
			}

			if ( $phpdoc_post_type ) {
				if ( $wp_cli ) {
					WP_CLI::log( "Generating version.json file..." );
				}

				$version = wppj_get_phpdoc_parser_version();

				//create version.json file
				$file = trailingslashit( $dirs['json-files'] ) . 'version.json';
				if ( ! $wp_filesystem->put_contents( $file, '{"version":"' . $version . '"}', FS_CHMOD_FILE ) ) {
					$error = esc_html__( "Unable to create the file: version.json", 'wp-parser-json' );
					add_settings_error( 'wp-parser-json', 'create_file', $error, 'error' );
					return false;
				}

				$files_created = true;
			}

			if ( ! $files_created ) {
				$error = esc_html__( "Error: No JSON files created.", 'wp-parser-json' );
				add_settings_error( 'wp-parser-json', 'created_files', $error, 'error' );

				if ( ! $wp_filesystem->rmdir( $dirs['json-files-temp'], true ) ) {
					$error = esc_html__( "Unable to delete directory /json-files-temp", 'wp-parser-json' );
					add_settings_error( 'wp-parser-json', 'delete_directory', $error, 'error' );
					return false;
				}

				return false;
			}

			// create zip file
			$args = array(
				'name'                 => 'wp-parser-json',
				'source_directory'     => $dirs['json-files'],
				'process_extensions'   => array( 'json' ),
				'zip_root_directory'   => "wp-parser-json",
				'zip_temp_directory'   => $dirs['json-files-temp'],
			);

			$zip_generator = new WP_Parser_JSON_Zip( $args );
			$zip_generator->generate();

			$zip_file = trailingslashit( $dirs['json-files-temp'] ) . 'wp-parser-json.zip';

			// Move zip file.
			if ( $wp_filesystem->exists( $zip_file ) ) {
				if ( ! rename( $zip_file, trailingslashit( $dirs['json-files'] ) . 'wp-parser-json.zip' ) ) {
					$error = esc_html__( "Unable to move file $zip_file", 'wp-parser-json' );
					add_settings_error( 'wp-parser-json', 'move_file', $error, 'error' );
					return false;
				}
			} else {
				// Zip file wasn't generated!
				$error = esc_html__( "Could not generate zip file with json files", 'wp-parser-json' );
				add_settings_error( 'wp-parser-json', 'no_file', $error, 'error' );
				return false;
			}

			// Delete temp directory.
			if ( ! $wp_filesystem->rmdir( $dirs['json-files-temp'], true ) ) {
				$error = esc_html__( "Unable to delete directory {$dirs['json-files-temp']}", 'wp-parser-json' );
				add_settings_error( 'wp-parser-json', 'delete_directory', $error, 'error' );
				return false;
			}

			// Hooray, we reached the end! Let's add a message with a download link.
			$download_link = '<a href="' . plugins_url( 'wp-parser-json' ) . '/json-files/wp-parser-json.zip">';
			$download_link .= __( 'Download the files', 'wp-parser-json' ) . '</a>';

			$success = esc_html__( "Success! JSON files created in {$dirs['json-files']}/", 'wp-parser-json' );
			$success .= "<br/><br/>$download_link";

			add_settings_error( 'wp-parser-json', 'wp_parser_json_updated', $success, 'updated' );

			return false;
		}

	} // class
} // class exists
