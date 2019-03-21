<?php
if ( ! class_exists( 'WP_Parser_JSON_Admin' ) ) {
	class WP_Parser_JSON_Admin {

		/**
		 * Class instance.
		 *
		 * @since 0.1
		 * @see get_instance()
		 * @var object
		 */
		private static $instance = null;

		/**
		 * Returns this plugin's class instance.
		 *
		 * @since 0.1
		 *
		 * @return object
		 */
		public static function get_instance() {
			is_null( self::$instance ) && self::$instance = new self;
			return self::$instance;
		}

		/**
		 * Get plugin class instance on action hook wp_loaded.
		 *
		 * @since 0.1
		 */
		public static function init() {
			add_action( 'wp_loaded', array( self::get_instance(), '_setup' ) );
		}

		/**
		 * Sets up class properties.
		 *
		 * @since 0.1
		 */
		public function _setup() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

		/**
		 * Adds admin menu
		 *
		 * @since 0.1
		 */
		public function admin_menu() {
			add_submenu_page(
				'tools.php',
				__( 'WP Parser JSON', 'wp-parser-json' ),
				__( 'WP Parser JSON', 'wp-parser-json' ),
				'manage_options',
				'wp-parser-json',
				array( $this, 'admin_page' )
			);
		}

		/**
		 * Admin page html
		 *
		 * @since 0.1
		 */
		function admin_page() {
			$post_types       = array();
			$admin_post_types = wppj_get_post_types();
			$files            = new WP_Parser_JSON_File();

			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				check_admin_referer( 'wp-parser-json_nonce' );

				// remove the magic quotes
				$_POST             = stripslashes_deep( $_POST );
				$post_types        = isset( $_POST['post_type'] ) ? $_POST['post_type'] : array();
				$json_post_types   = wppj_get_json_post_types( $post_types );
				$phpdoc_post_types = wppj_get_phpdoc_parser_post_types();
				$phpdoc_hook_types = wppj_get_phpdoc_parser_hook_types();

				if ( array_key_exists( 'phpdoc_parser_post_types', $post_types ) && wppj_phpdoc_parser_post_types_exists() ) {
					// Add actions and filters
					$json_post_types = array_merge( $json_post_types, array_fill_keys( $phpdoc_hook_types, 'wp-parser-hook' ) );

					// Remove phpdoc post types
					$json_post_types = array_diff_key( $json_post_types, array_flip( array_values( $phpdoc_post_types ) ) );

					// Add phpdoc post types
					$json_post_types = array_merge( $json_post_types, $phpdoc_post_types );

					// Remove post types (for back compat)
					unset( $json_post_types['phpdoc_parser_post_types'] );
					unset( $json_post_types['methods'] );
				}

				$generate_files  = false;
				if ( isset( $_POST['submit'] ) && ( 'Generate json files!' === $_POST['submit'] ) ) {
					$generate_files = true;
				}

				// abort if we cannot access the WP_Filesystem API
				if ( $generate_files && ( true === $files->generate_files( $json_post_types ) ) ) {
					return;
				}
			}

			if ( wppj_phpdoc_parser_post_types_exists() ) {
				$admin_post_types = array( 'phpdoc_parser_post_types' => 'WP Parser Post Types' ) + $admin_post_types;
			}

			$post_types_str = __( 'Post Types', 'wp-parser-json' );

			$html = '<table class="form-table"><tbody><tr><th scope="row">' . $post_types_str . '</th>';
			$html .= '<td><fieldset><legend class="screen-reader-text"><span>' . $post_types_str . '</span></legend>';
			foreach ( (array) $admin_post_types as $key => $value ) {
				$checked = '';
				if ( isset( $post_types[ $key ] ) && 'on' === $post_types[ $key ] ) {
					$checked = ' checked="checked"';
				}

				$html .= "<label for='post_type_{$key}'>";
				$html .= "<input type='checkbox' class='checkbox' id='post_type_{$key}' name='post_type[{$key}]' {$checked} />";
				$html .= $value  . "</label><br/>";

			}
			$html .= '</fieldset></td></tr></tbody></table>';

			$phpdoc_post_types = apply_filters( 'wp_parser_json_parse_phpdoc_post_types', true );

			echo '<div class="wrap">';
			echo '<h2>' . __( 'WP Parser JSON', 'wp-parser-json' ) . '</h2>';
			echo '<p>' . __( 'Generate json files with post type posts', 'wp-parser-json' ) . '</p>';

			settings_errors();

			echo '<form method="post" action="">';
			wp_nonce_field( 'wp-parser-json_nonce' );
			echo $html;
			echo get_submit_button( esc_html__( 'Generate json files!', 'wp-parser-json' ) );
			echo '</form>';

			$errors =  get_settings_errors();
			$settings_updated = true;
			// Check if new files were generated.
			if ( ! ( isset( $errors[0]['code'] ) && ( 'wp_parser_json_updated' === $errors[0]['code'] ) ) ) {

				// Offer download link to old zip file if it exists.
				$zip_dir = plugin_dir_path( __FILE__ ) . 'json-files/wp-parser-json.zip';
				$version = plugin_dir_path( __FILE__ ) . 'json-files/version.json';
				if ( file_exists(  $zip_dir ) ) {
					$version = file_exists(  $version ) ? json_decode( file_get_contents( $version ) ) : '';
					$version = isset( $version->version ) ? '(WP ' . $version->version . ')' : '';
					echo '<a href="' . plugins_url( 'wp-parser-json' ) . '/json-files/wp-parser-json.zip">';
					printf( __( 'download %s files', 'wp-parser-json' ), $version ) . '</a>';
				}
				$settings_updated = false;

			}

			do_action( 'wp_parser_json_afer_form' );

			echo '</div>';
		}

	} // class

	WP_Parser_JSON_Admin::init();
} // class exists
