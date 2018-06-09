<?php
/*
Plugin Name: WP Parser JSON
Description: A plugin to create json files with all WP functions, hooks and classes (post types) from the WP Parser plugin.
Version: 0.1
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
if ( is_admin() ) {

	if(! defined('WP_PARSER_JSON_DIR')) {
		define('WP_PARSER_JSON_DIR',  plugin_dir_path( __FILE__ ) );
	}

	// class to create zip files
	require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-zip.php';

	// class to create files
	require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-query.php';

	// class to create files
	require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-file.php';

	// admin page
	require_once plugin_dir_path( __FILE__ ) . 'class-wp-parser-json-admin.php';

}
