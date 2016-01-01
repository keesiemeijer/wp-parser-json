<?php
if ( !class_exists( 'WP_Parser_JSON_Reference_Query' ) ) {
	class WP_Parser_JSON_Reference_Query {

		/**
		 * Post types from WP Parser.
		 *
		 * @since 0.1
		 * @var array
		 */
		public $post_types;

		/**
		 * Hook types (action and filter).
		 *
		 * @since 0.1
		 * @var array
		 */
		public $hook_types;

		/**
		 * base url to reference posts.
		 *
		 * @since 0.1
		 * @var array
		 */
		public $base_url;

		/**
		 * Parsed WordPress version.
		 *
		 * @since 0.1
		 * @var array
		 */
		public $wp_version;

		/**
		 * Debug.
		 *
		 * @since 0.1
		 * @var bool
		 */
		public $debug = true;

		/**
		 * Debug output for testing.
		 *
		 * @since 0.1
		 * @var string
		 */
		public $debug_msg = '';


		public function __construct() {

			/**
			 * Reference slugs and post type names from WP Parser.
			 *
			 * slug => post_type
			 *
			 * The slug is appended to $base_url below.
			 * Files are created with the slug as name (e.g. functions.json).
			 */
			$this->post_types = array(
				'functions' => 'wp-parser-function',
				'hooks'     => 'wp-parser-hook',
				'classes'   => 'wp-parser-class',
			);

			$this->wp_version = $this->get_wp_version();

			/**
			 * slugs from WP Parser for actions and filters.
			 *
			 * The slug is appended to $base_url below.
			 * Files are created with the slug as name (e.g. actions.json).
			 */
			$this->hook_types = array( 'actions', 'filters' );

			/**
			 * The base url to use for the result pages in the alfred app.
			 * todo: get the url from theme or WP Parser
			 */
			$base_url = 'https://developer.wordpress.org/reference';

			$this->base_url = apply_filters( 'wp_parser_json_base_url', $base_url );

			/* prints debug information after creating the files */
			add_action( 'wp_parser_json_afer_form', array( $this, 'debug_output' ) );
		}


		/**
		 * Returns parsed WordPress version.
		 *
		 * @since 0.1
		 *
		 * @return string WordPress version
		 */
		function get_wp_version() {
			global $wp_version;

			if ( function_exists( 'DevHub\get_current_version' ) ) {
				return DevHub\get_current_version();
			}

			$current_version = get_option( 'wp_parser_imported_wp_version' );

			if ( !empty( $current_version )  ) {
				return $current_version;
			}

			return $wp_version;
		}


		/**
		 * Returns reference posts in json format
		 *
		 * @since 0.1
		 *
		 * @param string  $ref_type  WP Parser type of reference.
		 * @param string  $post_type Post type used for the reference.
		 * @return string            [description]
		 */
		function get_reference_content( $ref_type, $post_type ) {
			return json_encode( $this->query_reference_posts( $ref_type, $post_type ) );
		}


		/**
		 * Query WP Parser reference posts and return array with slug and title for each post.
		 *
		 * @since 0.1
		 *
		 * @param string  $ref_type  WP Parser type of reference.
		 * @param string  $post_type Post type used for the reference.
		 * @return string            Json encoded string with post titles and slugs.
		 */
		function query_reference_posts( $ref_type, $post_type ) {

			$content = array(
				'version' => $this->wp_version,
				'url'     => esc_url( trailingslashit( $this->base_url ) . $ref_type ),
				'content' => '',
			);

			if ( ( 'actions' === $ref_type ) || ( 'filters' === $ref_type ) ) {
				$content['url'] = esc_url( trailingslashit( $this->base_url ) . 'hooks' );
			}

			if ( !in_array( $post_type, array_values( $this->post_types ) ) ) {
				return $content;
			}

			$posts = $this->get_reference_posts( $ref_type, $post_type );

			if ( $posts ) {

				// debug information
				$debug_count = count( $posts );
				$deprcated_count = 0;
				$duplicate_count = 0;

				$i = 0;
				$file_content = array();

				foreach ( $posts as $key => $post ) {

					$file_source = wp_get_post_terms( $post->ID, 'wp-parser-source-file', array( 'fields' => 'names' ) );
					$tags = get_post_meta( $post->ID, '_wp-parser_tags', true );

					// skip deprecated
					if ( $this->is_deprecated( $post->ID ) ) {
						++$deprcated_count;
						continue;
					}

					// skip duplicate hook post_name (slugs)
					if ( $this->is_hook_duplicate( $ref_type, $post->post_name ) ) {
						++$duplicate_count;
						continue;
					}

					$slug = basename( get_the_permalink( $post->ID ) );

					$file_content[$i]['title'] = trim( $post->post_title, '"' );
					$file_content[$i]['slug'] = $slug;
					++$i;
				}

				wp_reset_postdata();

				// debug information
				$msg = "<li><h3>post type: {$ref_type}</h3><ul>";
				$msg .= "<li>found: {$debug_count}</li>";
				$msg .= '<li>used: ' . count( $file_content ) . '</li>';
				$msg .= "<li>deprecated: $deprcated_count</li>";
				$msg .= "<li>duplicates: $duplicate_count</li></ul></li>";
				$this->debug_msg .=  $msg ;

				$content['content'] = $file_content;
			}

			return $content;
		}


		/**
		 * Gets posts for reference Functions, Hooks, Actions, Filters, Classes
		 *
		 * @since 0.1
		 *
		 * @param string  $ref_type  WP Parser type of reference.
		 * @param string  $post_type Post type used for the reference.
		 * @return array            Array with post objects.
		 */
		function get_reference_posts( $ref_type, $post_type ) {

			$args = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_type'      => $post_type,
			);

			if ( in_array( $ref_type, $this->hook_types ) ) {

				if ( 'actions' === $ref_type ) {
					$value = array( 'action', 'action_reference' );
				} else {
					$value = array( 'filter', 'filter_reference' );
				}

				$args['meta_query'] =  array(
					array(
						'key' => '_wp-parser_hook_type',
						'value' => $value,
						'compare' => 'IN'
					)
				);
			}

			return get_posts( $args );
		}


		/**
		 * Checks if **all** of the post types from WP parser exist.
		 *
		 * @since 0.1
		 *
		 * @return bool Returns false if one or more of the post types are missing.
		 */
		public function post_types_exists() {
			foreach ( $this->post_types as $type => $post_type ) {
				if ( !post_type_exists( $post_type ) ) {
					return false;
				}
			}
			return true;
		}


		/**
		 * Checks if a hook reference type slug is a duplicate.
		 *
		 * @since 0.1
		 *
		 * @param string  $ref_type  WP Parser type of reference.
		 * @param string  $post_name Post slug.
		 * @return boolean           True if slug has "-{$int}"" appended to slug.
		 */
		function is_hook_duplicate( $ref_type, $post_name ) {
			$return  = false;
			if ( in_array( $ref_type, $this->hook_types ) || ( 'hooks' === $ref_type ) ) {

				// match -number at end of slug
				if ( preg_match( '/\-\d+$/', $post_name ) ) {
					$return  = true;
				}
			}
			return $return;
		}

		/**
		 * Retrieve arguments as an array
		 *
		 * @param int     $post_id
		 *
		 * @return array
		 */
		function get_arguments( $post_id = null ) {

			if ( empty( $post_id ) ) {
				$post_id = get_the_ID();
			}
			$arguments = array();
			$args = get_post_meta( $post_id, '_wp-parser_args', true );

			if ( $args ) {
				foreach ( $args as $arg ) {
					if ( ! empty( $arg['type'] ) ) {
						$arguments[ $arg['name'] ] = $arg['type'];
					}
				}
			}

			return $arguments;
		}


		/**
		 * Checks if a function, hook, action, filter or class is deprecated.
		 *
		 * @since 0.1
		 *
		 * @param int     $post_id Post ID
		 * @return boolean         Returns true if a function, hook or class is deprecated.
		 */
		function is_deprecated( $post_id ) {
			$return  = false;
			$source_file_object = wp_get_post_terms( $post_id, 'wp-parser-source-file', array( 'fields' => 'names' ) );
			if ( isset( $source_file_object[0] ) && $source_file_object[0] ) {
				if ( false !== strpos( $source_file_object[0], 'deprecated' ) ) {
					$return  = true;
				}
			}
			return $return;
		}


		/**
		 * Prints debug information to the screen
		 *
		 * @since 0.1
		 */
		function debug_output( ) {
			if ( $this->debug ) {
				echo '<ul>' . $this->debug_msg . '</ul>';
			}
		}

	} // class
} // class exists
