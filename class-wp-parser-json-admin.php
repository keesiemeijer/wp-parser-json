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
			add_options_page(
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

			$files = new WP_Parser_JSON_File();

			if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
				check_admin_referer( 'wp-parser-json_nonce' );

				// remove the magic quotes
				$_POST = stripslashes_deep( $_POST );

				$generate_files = false;
				if ( isset( $_POST['submit'] ) && ( 'Generate json files!' === $_POST['submit'] ) ) {
					$generate_files = true;
				}

				// abort if we cannot access the WP_Filesystem API
				if ( $generate_files && ( true === $files->generate_files() ) ) {
					return;
				}
			}

			echo '<div class="wrap">';
			echo '<h2>' . __( 'WP Parser JSON', 'wp-parser-json' ) . '</h2>';
			echo '<p>' . __( 'Generate json files from all functions, hooks, filters, actions and classes', 'wp-parser-json' ) . '</p>';

			settings_errors();

			// don't show the form if the post types from WP Parser don't exist
			if ( ! $files->post_types_exists() ) {
				return;
			}

			echo '<form method="post" action="">';
			wp_nonce_field( 'wp-parser-json_nonce' );
			echo get_submit_button( esc_html__( 'Generate json files!', 'wp-parser-json' ) );
			echo '</form>';

			$errors =  get_settings_errors();
			$settings_updated = true;
			// Check if new files were generated.
			if ( ! ( isset( $errors[0]['code'] ) && ( 'wp_parser_json_updated' === $errors[0]['code'] ) ) ) {

				// Offer download link to old zip file if it exists.
				$zip_dir = plugin_dir_path( __FILE__ ) . 'json-files/wp-parser-json.zip';
				$version = plugin_dir_path( __FILE__ ) . 'json-files/version.json';
				if ( file_exists(  $zip_dir ) && file_exists(  $version ) ) {
					$version = json_decode( file_get_contents( $version ) );
					$version = isset( $version->version ) ? '(WP ' . $version->version . ')' : '';
					echo '<a href="' . plugins_url( 'wp-parser-json' ) . '/json-files/wp-parser-json.zip">';
					printf( __( 'download %s files', 'wp-parser-json' ), $version ) . '</a>';
				}
				$settings_updated = false;

			}

			if ( $settings_updated ) {
				do_action( 'wp_parser_json_afer_form' );
			}

			echo '</div>';
		}

	} // class

	WP_Parser_JSON_Admin::init();
} // class exists
