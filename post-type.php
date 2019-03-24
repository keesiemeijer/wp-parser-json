<?php
/**
 * Returns all public post types.
 *
 * format: post_type => Post Type Label.
 *
 * @version 1.0.0
 *
 * @return array Array with public post types
 */
function wppj_get_post_types() {
	$post_types = array();
	$post_types_obj = get_post_types( array( 'public' => true ), 'objects', 'and' );
	foreach ( (array) $post_types_obj as $key => $value ) {
		$post_types[ $key ] = esc_attr( $value->labels->menu_name );
	}
	return $post_types;
}

/**
 * Get the post types for parsing JSON files.
 *
 * Adds labels for phpdoc parser post types (back compat)
 *
 * @param array $post_types Array with post types.
 * @return array Array with post types.
 */
function wppj_get_json_post_types( $post_types ) {
	$phpdoc_post_types = wppj_get_phpdoc_parser_post_types();
	foreach ( $post_types as $key => $value ) {
		if ( wppj_is_phpdoc_parser_post_type( $key ) ) {
			$phpdoc_key = array_search( $key, $phpdoc_post_types );
			if ( false !== $phpdoc_key ) {
				unset( $post_types[ $key ] );
				$post_types[ $phpdoc_key ] = $key;
				continue;
			}
		}

		$post_types[ $key ] = $key;
	}

	return $post_types;
}

/**
 * Returns phpdoc parser post types
 *
 * format: slug => post_type
 *
 * @version 1.0.0
 *
 * @return array Array with phpdoc parser post types.
 */
function wppj_get_phpdoc_parser_post_types() {
	return array(
		'functions' => 'wp-parser-function',
		'hooks'     => 'wp-parser-hook',
		'classes'   => 'wp-parser-class',
		'methods'   => 'wp-parser-method',
	);
}

/**
 * Returns phpdoc parser hook type slugs
 *
 * @version 1.0.0
 *
 * @return array Array with phpdoc parser hook types.
 */
function wppj_get_phpdoc_parser_hook_types() {
	return array( 'actions', 'filters' );
}

/**
 * Returns parsed WordPress version.
 *
 * @version 1.0.0
 *
 * @return string WordPress version
 */
function wppj_get_phpdoc_parser_version() {
	global $wp_version;

	if ( function_exists( 'DevHub\get_current_version' ) ) {
		return DevHub\get_current_version();
	}

	$current_version = get_option( 'wp_parser_imported_wp_version' );

	if ( ! empty( $current_version )  ) {
		return $current_version;
	}

	return $wp_version;
}

/**
 * Checks if a post type is a phpdoc parser post type.
 *
 * @version 1.0.0
 *
 * @param string $post_type Post Type.
 * @return bool True if it's a phpdoc parser post type.
 */
function wppj_is_phpdoc_parser_post_type( $post_type ) {
	$phpdoc_post_types = wppj_get_phpdoc_parser_post_types();
	if ( in_array( $post_type, array_values( $phpdoc_post_types ) ) ) {
		return true;
	}

	return false;
}

/**
 * Checks if **all** of the post types from WP parser exist.
 *
 * @version 1.0.0
 *
 * @return bool Returns false if one or more of the post types are missing.
 */
function wppj_phpdoc_parser_post_types_exists() {
	$post_types = wppj_get_phpdoc_parser_post_types();
	foreach ( $post_types as $type => $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Checks if a hook reference type slug is a duplicate.
 *
 * @version 1.0.0
 *
 * @param string $ref_type  WP Parser type of reference.
 * @param string $post_name Post slug.
 * @return boolean True if slug has "-{$int}"" appended to slug.
 */
function wppj_is_hook_duplicate( $ref_type, $post_name ) {
	$return = false;

	$hook_types = wppj_get_phpdoc_parser_hook_types();
	if ( in_array( $ref_type, $hook_types ) || ( 'hooks' === $ref_type ) ) {

		// match -number at end of slug
		if ( preg_match( '/\-\d+$/', $post_name ) ) {
			$return  = true;
		}
	}
	return $return;
}

/**
 * Checks if a function, hook, action, filter or class is deprecated.
 *
 * @version 1.0.0
 *
 * @param int $post_id Post ID
 * @return boolean Returns true if a function, hook or class is deprecated.
 */
function wppj_is_deprecated( $post_id ) {
	$return  = false;
	$source_file_object = wp_get_post_terms( $post_id, 'wp-parser-source-file', array( 'fields' => 'names' ) );
	if ( isset( $source_file_object[0] ) && $source_file_object[0] ) {
		if ( false !== strpos( $source_file_object[0], 'deprecated' ) ) {
			$return  = true;
		}
	}
	return $return;
}
