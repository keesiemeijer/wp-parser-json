<?php
if ( ! class_exists( 'WP_Parser_JSON_Reference_Query' ) ) {
	class WP_Parser_JSON_Reference_Query {

		/**
		 * Debug output for testing.
		 *
		 * @since 0.1
		 * @var string
		 */
		public $debug_msg = '';

		public function __construct() {
			/* prints debug information after creating the files */
			add_action( 'wp_parser_json_afer_form', array( $this, 'debug_output' ) );
		}

		/**
		 * Returns post type posts in json format
		 *
		 * @since 1.0.0
		 *
		 * @param string $ref_type  File slug.
		 * @param string $post_type Post type used for the reference.
		 * @return string Json encoded post type content.
		 */
		public function get_post_type_content( $ref_type, $post_type ) {
			if ( wppj_is_phpdoc_parser_post_type( $post_type ) ) {
				$content = $this->get_phpdoc_content( $ref_type, $post_type );
			} else {
				$content = $this->get_content( $ref_type, $post_type );
			}

			return json_encode( $content );
		}

		/**
		 * Returns content for a post type.
		 *
		 * @since 0.1
		 *
		 * @param string $ref_type  File slug.
		 * @param string $post_type Post type.
		 * @return array Array with post type content.
		 */
		private function get_content( $ref_type, $post_type ) {
			$base_url = get_post_type_archive_link( $post_type );
			$base_url = $base_url ? $base_url : get_home_url();
			$base_url = apply_filters( 'wp_parser_json_base_url', $base_url, $post_type );

			$content = array(
				'post_type' => $post_type,
				'url'       => $base_url,
				'content'   => '',
			);

			$posts = $this->get_posts( $ref_type, $post_type );
			$count = count( $posts );

			$i = 0;
			$file_content = array();
			foreach ( $posts as $key => $post ) {
				$slug = get_the_permalink( $post->ID );
				if ( $base_url && ( 0 === strpos( $slug, $base_url ) ) ) {
					$slug = str_replace( $base_url, '', $slug );
				}

				$file_content[ $i ]['title'] = trim( $post->post_title, '"' );
				$file_content[ $i ]['slug']  = trim( $slug, '/ ' );

				$file_content[ $i ] = apply_filters( 'wp_parser_json_content_item', $file_content[ $i ], $post );
				++$i;
			}

			wp_reset_postdata();

			// debug information
			$msg = "<li><h3>post type: {$post_type}</h3>";
			$msg .= "<ul><li>posts found: {$count}</li>";
			$msg .= "<li>file: {$ref_type}.json</li></ul></li>";

			$this->debug_msg .=  $msg ;

			$content['content'] = $file_content;
			return $content;
		}

		private function get_phpdoc_content( $ref_type, $post_type ) {
			$base_url = 'https://developer.wordpress.org/reference';
			$base_url = apply_filters( 'wp_parser_json_base_url', $base_url, $post_type );

			$content = array(
				'version' => wppj_get_phpdoc_parser_version(),
				'url'     => esc_url( trailingslashit( $base_url ) . $ref_type ),
				'content' => array(),
			);

			if ( ( 'actions' === $ref_type ) || ( 'filters' === $ref_type ) ) {
				$content['url'] = esc_url( trailingslashit( $base_url ) . 'hooks' );
			}

			$posts = $this->get_posts( $ref_type, $post_type );
			if ( ! $posts ) {
				return $content;
			}

			$skip_deprecated = apply_filters( 'wp_parser_json_skip_deprecated', true );

			// debug information
			$debug_count = count( $posts );
			$deprcated_count = 0;
			$duplicate_count = 0;

			$i = 0;
			$file_content = array();

			foreach ( $posts as $key => $post ) {
				// skip deprecated
				if ( $skip_deprecated && wppj_is_deprecated( $post->ID ) ) {
					++$deprcated_count;
					continue;
				}

				// skip duplicate hook post_name (slugs)
				if ( wppj_is_hook_duplicate( $ref_type, $post->post_name ) ) {
					++$duplicate_count;
					continue;
				}

				$slug = basename( get_the_permalink( $post->ID ) );

				$file_content[ $i ]['title'] = trim( $post->post_title, '"' );
				$file_content[ $i ]['slug'] = $slug;

				$file_content[ $i ] = apply_filters( 'wp_parser_json_content_item', $file_content[ $i ], $post );
				++$i;
			}

			wp_reset_postdata();

			// debug information
			$msg = "<li><h3>post type: {$post_type}</h3><ul>";
			$msg .= "<li>file: {$ref_type}.json</li>";
			$msg .= "<li>posts found: {$debug_count}</li>";
			$msg .= '<li>posts used: ' . count( $file_content ) . '</li>';
			$msg .= "<li>deprecated: $deprcated_count</li>";
			$msg .= "<li>duplicates: $duplicate_count</li></ul></li>";
			$this->debug_msg .=  $msg ;

			$content['content'] = $file_content;


			return $content;
		}

		/**
		 * Gets posts for reference Functions, Hooks, Actions, Filters, Classes
		 *
		 * @since 0.1
		 *
		 * @param string $ref_type  WP Parser type of reference.
		 * @param string $post_type Post type used for the reference.
		 * @return array            Array with post objects.
		 */
		function get_posts( $ref_type, $post_type ) {
			$phpdoc_hook_types   = wppj_get_phpdoc_parser_hook_types();
			$is_phpdoc_post_type = wppj_is_phpdoc_parser_post_type( $post_type );

			$args = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_type'      => $post_type,
			);

			if ( $is_phpdoc_post_type && in_array( $ref_type, $phpdoc_hook_types ) ) {

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
		 * Prints debug information to the screen
		 *
		 * @since 0.1
		 */
		function debug_output( ) {
			if ( $this->debug_msg ) {
				echo '<h2>JSON files</h2>';
				echo '<ul>' . $this->debug_msg . '</ul>';
			}
		}

	} // class
} // class exists
