# wp-parser-json
A WordPress plugin to create JSON files with post type posts.

The settings page for this plugin is at `Tools` -> `WP Parser JSON`. Here you can create JSON files for any post type. Or use the `wp parser-json generate` WP-CLI command to create JSON files.

```bash
wp parser-json generate --post_type=post,page
```

The JSON files are saved in this plugin's directory in the folder `json-files`.

### Backward Compatibility

Originally this plugin only created JSON files for the post types of the the [WP Parser plugin](https://github.com/WordPress/phpdoc-parser). For backward compatibility if you do not provide a post type and the WP Parser post types exist it will create the JSON files for the `wp-parser-function`,`wp-parser-hook` and`wp-parser-class` post types.

The ***WP Parser plugin*** post type files are:

* functions.json
* hooks.json
* actions.json
* filters.json
* classes.json
* version.json
* wp-parser-json.zip (zip file containing all files above)

The version.json file has the WordPress version that was parsed with the WP Parser plugin.
```json
{"version":"5.1"}
```

### JSON

The JSON files have this structure (example movies.json)
```json
{
  "post_type":"movies",
  "url":"https:\/\/my-website.com\/movie",
  "content":[
      {"title":"Die Hard","slug":"die-hard"},
      {"title":"Mad Max Fury Road","slug":"mad-max-fury-road"},

      {"title":"etc...","slug":"etc..."}
    ]
}
```
As you can see, by default only the post title and slug are included. To add more post fields use the `wp_parser_json_content_item` filter.

Example of adding the post ID field.

```php
add_filter( 'wp_parser_json_content_item', 'json_parser_add_post_id', 10, 2 );

function json_parser_add_post_id( $item, $post ) {
	$item['post_id'] = $post->ID;

	return $item;
}
```

This will result with the post ID added in the JSON files.

```json
{
  "post_type":"movies",
  "url":"https:\/\/my-website.com\/movie",
  "content":[
      {"title":"Die Hard","slug":"die-hard","post_id": 1288},
      {"title":"Mad Max Fury Road","slug":"mad-max-fury-road","post_id": 2768},
    ]
}
```