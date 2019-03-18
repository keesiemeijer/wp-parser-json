# wp-parser-json
A WP plugin to create json files with all the functions, hooks, filters, actions and classes (post types posts) from the [WP Parser plugin](https://github.com/WordPress/phpdoc-parser).

The files created are:
* functions.json
* hooks.json
* actions.json
* filters.json
* classes.json
* version.json
* wp-parser-json.zip (zip file containing all files above)

The files are saved in this plugin's directory in the folder `json-files`.

The JSON files have this structure (example functions.json)
```json
{
  "version":"5.1",
  "url":"https:\/\/developer.wordpress.org\/reference\/functions",
  "content":[
      {"title":"get_posts","slug":"get_posts"},
      {"title":"get_posts_by_author_sql","slug":"get_posts_by_author_sql"},

      {"title":"etc...","slug":"etc..."}
    ]
}
```

To add more post fields use the `wp_parser_json_content_item` filter. Example of adding the post ID field.

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
  "version":"5.1",
  "url":"https:\/\/developer.wordpress.org\/reference\/functions",
  "content":[
      {"title":"get_posts","slug":"get_posts","post_id": 1288},
      {"title":"get_posts_by_author_sql","slug":"get_posts_by_author_sql","post_id": 2768},

      {"title":"etc...","slug":"etc...","post_id":"etc..."}
    ]
}
```

The version.json file has the WordPress version that was parsed
```json
{"version":"5.1"}
```
