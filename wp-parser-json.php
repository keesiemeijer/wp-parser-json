<?php
/*
Plugin Name: WP Parser JSON
Description: A plugin to create JSON files with post type posts.
Version: 1.0.0
Author: keesiemeijer
Author URI:
License: GPL v2
Text Domain: wp-parser-json
Domain Path: /lang

Copyright 2013  Kees Meijer  (email : keesie.meijer@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version. You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'WP_PARSER_JSON_DIR' ) ) {
	define( 'WP_PARSER_JSON_DIR',  plugin_dir_path( __FILE__ ) );
}

// admin page
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-admin.php';
	WP_Parser_JSON_Admin::init();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once plugin_dir_path( __FILE__ ) . '/class-wpcli-command.php';
}

// class to create zip files
require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-zip.php';

// class to create files
require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-query.php';

// class to create files
require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-file.php';

// Post type functions
require_once plugin_dir_path( __FILE__ ) . 'post-type.php';
