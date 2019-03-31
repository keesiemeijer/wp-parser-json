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
		 * Paginate posts
		 *
		 * @param array  $posts     Post items
		 * @param string $post_type Array with post items.
		 * @param array  $args      Arguments.
		 * @return array Array with paginated posts.
		 */
		function get_paginated_posts( $posts, $post_type, $args ) {
			$posts           = array_values( $posts );
			$paginated_posts = array();
			$count           = 0;
			$ppp             = 0;
			$i               = 0;

			/**
			 * Key to find posts in paginated JSON files.
			 *
			 * @param string $index_key Key to find posts in paginated JSON files. Default 'slug'
			 */
			$index_key = apply_filters( 'wp_parser_json_index_key', 'slug' );

			foreach ( $posts as $post_items ) {
				foreach ( $post_items as $post ) {
					if ( ! isset( $post[ $index_key ] ) ) {
						continue;
					}

					if ( -1 === (int) $args['posts_per_page'] ) {
						$paginated_posts[] = $post;
						continue;
					}

					$paginated_posts[ $i ][] = $post;
					++$ppp;

					if ( $ppp === (int) $args['posts_per_page'] ) {
						$ppp = 0;
						++$i;
					}
				}
			}

			if ( -1 === (int) $args['posts_per_page'] ) {
				$paginated_posts = array( $paginated_posts );
			}

			return $paginated_posts;
		}

		/**
		 * Get file index.
		 *
		 * @param array $posts Array with paginated posts.
		 * @return array Array with file information.
		 */
		function get_post_type_index( $posts ) {
			$index = array(
				'found_posts' => 0,
				'max_pages'   => count( $posts ),
				'pages'       => array(),
			);

			/** This filter is documented in class-wp-parser-json-file.php */
			$index_key = apply_filters( 'wp_parser_json_index_key', 'slug' );

			$posts = array_values( $posts );
			foreach ( $posts as $page => $value ) {
				foreach ( $value as $post ) {
					/**
					 * Filter the items in the index file.
					 *
					 * It's recommended to keep the items small.
					 * The values should only be used to find posts in paginated JSON files.
					 *
					 * @param int|string $slug      Slug to look up posts in paginated JSON files.
					 *                              Default post slug.
					 * @param string     $index_key Key to to look up posts in paginated JSON files.
					 *                              Default 'slug'
					 * @param array      $post      Post item in paginated JSON files.
					 */
					$item = apply_filters( 'wp_parser_json_index_content_item', $post[ $index_key ], $index_key, $post );
					$index['pages'][ $page + 1 ][] = $item;
					$index['found_posts'] = ++$index['found_posts'];
				}
			}

			return $index;
		}

		/**
		 * Generate json files for WP Parser post types.
		 *
		 * Uses the WP_Filesystem API.
		 *
		 * @since 0.1
		 * @return bool Returns true if there's no access to the WP_Filesystem API.
		 */
		function generate_files( $post_types = array(), $args = array() ) {
			$wp_cli = defined( 'WP_CLI' ) && WP_CLI;

			$args = $this->sanitize_query_args( $args );

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

			/**
			 * Filter the post types to create JSON files for
			 *
			 * @param array Array with post types.
			 */
			$post_types = apply_filters( 'wp_parser_json_parse_post_types', $post_types );
			if ( ! $post_types ) {
				$error = esc_html__( "No post type JSON files generated. Please provide a valid post type", 'wp-parser-json' );
				add_settings_error( 'wp-parser-json', 'post_type_fail', $error, 'error' );
				return false;
			}

			$posts_per_page   = (int) $args['posts_per_page'];
			$files_created    = false;
			$phpdoc_post_type = false;
			$error            = '';

			// Create the post_type JSON files.
			foreach ( $post_types as $slug => $post_type ) {
				if ( ! post_type_exists( $post_type ) ) {
					$error .= sprintf( __( "No %s file created", 'wp-parser-json' ), $slug . '.json' );
					$error .= ' ' . sprintf( __( "Please make sure post type %s exists", 'wp-parser-json' ), $post_type  ) . '<br/>';
					continue;
				}

				// Reset values for a post type
				$this->reset_index();
				$filename        = sanitize_file_name( $slug );
				$post_type_posts = array();
				$post_type_limit = 0;
				$args['page']    = 1;

				if ( -1 !== $posts_per_page ) {
					/**
					 * Filter the number of posts per JSON file.
					 *
					 * Can only be filtered if $posts_per_page is not -1.
					 *
					 * @param int    $posts_per_page Number of posts per page.
					 * @param string $post_type      Post type.
					 */
					$args['posts_per_page'] = apply_filters( 'wp_parser_json_posts_per_page', $posts_per_page, $post_type );

					$args = $this->sanitize_query_args( $args );
					$args['posts_per_page'] = ( -1 === $args['posts_per_page'] ) ? $posts_per_page : $args['posts_per_page'];
				}

				/**
				 * Limit the amount of JSON files that can be created per post type.
				 *
				 * This is a safety limit for your hard drive.
				 *
				 * @param int    $limit     Limit of JSON files that can be created. Default 1000.
				 * @param string $post_type Post type.
				 */
				$limit = apply_filters( 'wp_parser_json_file_limit', 1000, $post_type );
				$limit = absint( $limit ) ? absint( $limit ) : 1000;

				// Get (paginated) posts.
				do {
					if ( $post_type_limit >= $limit ) {
						// Safety limit for your hard drive.
						$error .= sprintf( __( "The file limit was reached for post type %s files", 'wp-parser-json' ), $post_type ) . '<br/>';
						break;
					}

					$post_type_items = $this->get_post_type_items( $slug, $post_type, $args );

					if ( 'continue' === $post_type_items ) {
						// Get next page of posts if content is 'continue'
						$args['page'] = $args['page'] + 1;
						continue;
					}

					if ( $post_type_items ) {
						$post_type_posts[] = $post_type_items;
					}

					if ( ( 1 < $args['page'] ) && ! $post_type_items ) {
						// No need to create a JSON file if no posts are found after initial page.
						break;
					}

					$args['page'] = $args['page'] + 1;

					// Update how maney files were created for this post type.
					++$post_type_limit;
				} while ( $post_type_items );

				$paginated_posts  = $this->get_paginated_posts( $post_type_posts, $post_type, $args );
				$page_index       = $this->get_post_type_index( $paginated_posts );
				$page_index_posts = $page_index['pages'];
				unset( $page_index['pages'] );

				$page_index                   = array_merge( $page_index, $this->get_index() );
				$page_index['posts_per_page'] = $args['posts_per_page'];
				$this->set_file_info( $page_index );

				$posts_index = $page_index;
				foreach ( array_values( $paginated_posts ) as $key => $posts ) {
					$posts_index['page']    = $key + 1;

					/**
					 * Filter the index of a JSON posts page file.
					 *
					 * @param array $posts_index Index of a JSON posts page file.
					 */
					$posts_index  = apply_filters( 'wp_parser_json_posts_page_index', $posts_index );

					$posts_index['content'] = $posts;
					$json_content           = json_encode( $posts_index );

					// File name number if posts_per_page is not -1
					$number = ( -1 !== (int) $args['posts_per_page'] ) ? '-' . ( $key + 1 ) : '';

					$file = trailingslashit( $dirs['json-files'] ) . $filename . $number . '.json';
					if ( $wp_cli ) {
						WP_CLI::log( "Generating {$filename}{$number}.json file..." );
					}

					if ( ! $wp_filesystem->put_contents( $file, $json_content, FS_CHMOD_FILE ) ) {
						$error = esc_html__( "Unable to create the file: {$slug}.json", 'wp-parser-json' );
						add_settings_error( 'wp-parser-json', 'create_file', $error, 'error' );
						return false;
					}

					$files_created = true;
				}

				if ( wppj_is_phpdoc_parser_post_type( $post_type ) ) {
					$phpdoc_post_type = true;
				}

				if ( -1 === (int) $args['posts_per_page'] ) {
					// Continue if not paged
					continue;
				}

				/**
				 * Filter the index of a JSON index page file.
				 *
				 * @param array $page_index Index of a JSON index page file.
				 */
				$page_index = apply_filters( 'wp_parser_json_index_page_index', $page_index );

				$page_index['pages'] = $page_index_posts;
				$page_index          = json_encode( $page_index );

				$file = trailingslashit( $dirs['json-files'] ) . $filename . '-index.json';

				if ( $wp_cli ) {
					WP_CLI::log( "Generating {$filename}-index.json file..." );
				}

				// Create index file for a post type.
				if ( ! $wp_filesystem->put_contents( $file, $page_index, FS_CHMOD_FILE ) ) {
					$error = esc_html__( "Unable to create the file: {$file}", 'wp-parser-json' );
					add_settings_error( 'wp-parser-json', 'create_file', $error, 'error' );
					return false;
				}
				$files_created = true;
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
