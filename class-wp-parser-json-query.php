<?php
if ( ! class_exists( 'WP_Parser_JSON_Reference_Query' ) ) {
	class WP_Parser_JSON_Reference_Query {

		/**
		 * JSON File information.
		 *
		 * @since 0.1
		 * @var string
		 */
		public $file_info = '';

		/**
		 * File index
		 *
		 * @var array
		 */
		private $index = array();

		public function __construct() {
			/* prints JSON file information after creating the files */
			add_action( 'wp_parser_json_afer_form', array( $this, 'file_info_output' ) );
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
		public function get_post_type_items( $ref_type, $post_type, $args = array() ) {
			$args = $this->sanitize_query_args( $args );

			if ( wppj_is_phpdoc_parser_post_type( $post_type ) ) {
				$content = $this->get_phpdoc_post_type_posts( $ref_type, $post_type, $args );
			} else {
				$content = $this->get_post_type_posts( $ref_type, $post_type, $args );
			}

			return $content;
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
		private function get_post_type_posts( $ref_type, $post_type, $args = array() ) {
			$this->set_index( $ref_type, $post_type, $args );

			$posts = $this->get_posts( $ref_type, $post_type, $args );
			if ( ! $posts ) {
				return array();
			}

			$i = 0;
			$items = array();
			foreach ( $posts as $key => $post ) {
				$slug = get_the_permalink( $post->ID );
				if ( $this->index['url'] && ( 0 === strpos( $slug, $this->index['url'] ) ) ) {
					$slug = str_replace( $this->index['url'], '', $slug );
				}

				$slug = trim( $slug, '/ ' );

				$items[ $i ]['title'] = trim( $post->post_title, '"' );
				$items[ $i ]['slug']  = $slug;

				$items[ $i ] = apply_filters( 'wp_parser_json_content_item', $items[ $i ], $post );

				++$i;
			}

			wp_reset_postdata();

			return $items;
		}

		private function get_phpdoc_post_type_posts( $ref_type, $post_type, $args = array() ) {
			$this->set_index( $ref_type, $post_type, $args, 'php_doc_parser' );

			$posts = $this->get_posts( $ref_type, $post_type, $args );
			if ( ! $posts ) {
				return array();
			}

			$skip_deprecated = apply_filters( 'wp_parser_json_skip_deprecated', true );
			$init_deprecated = isset( $this->index['deprecated'] ) && isset( $this->index['duplicate_hooks'] );
			if ( $skip_deprecated && ! $init_deprecated ) {
				// Initialize index for deprecated and duplicate hooks
				$this->index['deprecated'] = 0;
				$this->index['duplicate_hooks'] = 0;
			}

			$count = count( $posts );
			$deprecated_count = 0;
			$duplicate_count = 0;

			$i = 0;
			$items = array();

			foreach ( $posts as $key => $post ) {
				// skip deprecated
				if ( $skip_deprecated && wppj_is_deprecated( $post->ID ) ) {
					++$deprecated_count;
					continue;
				}

				// skip duplicate hook post_name (slugs)
				if ( wppj_is_hook_duplicate( $ref_type, $post->post_name ) ) {
					++$duplicate_count;
					continue;
				}

				$slug = basename( get_the_permalink( $post->ID ) );

				$items[ $i ]['title'] = trim( $post->post_title, '"' );
				$items[ $i ]['slug'] = $slug;

				$items[ $i ] = apply_filters( 'wp_parser_json_content_item', $items[ $i ], $post );
				++$i;
			}

			wp_reset_postdata();

			if ( $skip_deprecated ) {
				$this->index['deprecated']      = $this->index['deprecated'] + $deprecated_count;
				$this->index['duplicate_hooks'] = $this->index['duplicate_hooks'] + $duplicate_count;
			}

			if ( $count !== count( $items ) ) {
				if ( ! $items ) {
					// If content is empty all (current page) posts were excluded.

					// Make sure to get the next page of posts.
					$items = 'continue';
				}
			}

			return $items;
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
		public function get_posts( $ref_type, $post_type, $args = array() ) {
			$phpdoc_hook_types   = wppj_get_phpdoc_parser_hook_types();
			$is_phpdoc_post_type = wppj_is_phpdoc_parser_post_type( $post_type );
			$args                = $this->sanitize_query_args( $args );
			$posts_per_page      = $args['posts_per_page'];

			$query_args = array(
				'post_type'      => $post_type,
				'posts_per_page' => $posts_per_page,
				'orderby'        => 'title',
				'order'          => 'ASC',
			);

			$offset = '';
			if ( 1 < $args['page'] ) {
				if ( -1 === (int) $posts_per_page ) {
					return array();
				} else {
					$offset               = ( ( $args['page'] - 1 ) * $posts_per_page );
					$query_args['offset'] = $offset;
				}
			}

			if ( $is_phpdoc_post_type && in_array( $ref_type, $phpdoc_hook_types ) ) {
				if ( 'actions' === $ref_type ) {
					$value = array( 'action', 'action_reference' );
				} else {
					$value = array( 'filter', 'filter_reference' );
				}

				$query_args['meta_query'] =  array(
					array(
						'key' => '_wp-parser_hook_type',
						'value' => $value,
						'compare' => 'IN'
					)
				);
			}
			$query_args = apply_filters( 'wp_parser_json_query_args', $query_args, $args );

			// Unfilterable query args
			$query_args['post_type']      = $post_type;
			$query_args['posts_per_page'] = $posts_per_page;
			if ( $offset ) {
				$query_args['offset'] = $offset;
			}

			return get_posts( $query_args );
		}


		public function get_index() {
			return $this->index;
		}

		public function reset_index() {
			$this->index = array();
		}

		public function set_file_info( $index ) {
			$file_name = isset( $index['ref_type'] ) ? $index['ref_type'] : $index['post_type'];

			$msg = "<li><h3>{$file_name}.json</h3>";
			$msg .= "<ul><li>post type: {$index['post_type']}</li>";
			$msg .= "<li>posts found: {$index['found_posts']}</li>";
			$msg .= "<li>pages: {$index['max_pages']}</li>";
			if ( isset( $index['duplicate_hooks'] ) && isset( $index['deprecated'] ) ) {
				$msg .= "<li>deprecated: {$index['deprecated']}</li>";
				$msg .= "<li>duplicates: {$index['duplicate_hooks']}</li>";
			}
			$msg .= '</ul></li>';

			$this->file_info .=  $msg;
		}

		private function set_index( $ref_type, $post_type, $args = array(), $php_doc_parser = '' ) {
			if ( isset( $this->index['post_type'] ) && ( $post_type === $this->index['post_type'] ) ) {
				return;
			}

			$this->index['post_type'] = $post_type;

			if ( 'php_doc_parser' === $php_doc_parser ) {
				$this->index['ref_type'] = $ref_type;
				$this->index['version']  = wppj_get_phpdoc_parser_version();

				$base_url = 'https://developer.wordpress.org/reference';
				$base_url = esc_url( trailingslashit( $base_url ) );

				if ( ( 'actions' === $ref_type ) || ( 'filters' === $ref_type ) ) {
					$base_url = $base_url . 'hooks';
				} else {
					$base_url = $base_url . $ref_type;
				}
			} else {
				$base_url = get_post_type_archive_link( $post_type );
				$base_url = $base_url ? $base_url : get_home_url();
			}

			$this->index['url'] = apply_filters( 'wp_parser_json_base_url', $base_url, $post_type );
		}

		public function sanitize_query_args( $args ) {
			$defaults = array(
				'posts_per_page' => -1,
				'page'           => '',
			);

			$args = wp_parse_args( $args, $defaults );

			$args['page'] = absint( $args['page'] );
			if ( -1 !== (int) $args['posts_per_page'] ) {
				$posts_per_page         = absint( $args['posts_per_page'] );
				$args['posts_per_page'] = $posts_per_page ? $posts_per_page : -1;
			}

			return $args;
		}

		/**
		 * Prints JSON file information to the screen
		 *
		 * @since 0.1
		 */
		public function file_info_output( ) {
			if ( $this->file_info ) {
				echo '<h2>JSON files</h2>';
				echo '<ul>' . $this->file_info . '</ul>';
			}
		}

	} // class
} // class exists
