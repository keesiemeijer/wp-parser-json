# WP Parser JSON

A WordPress plugin to create JSON files from posts.

The settings page for this plugin is at `Tools` -> `WP Parser JSON`. Here you can create JSON files for any post type. Or use the `wp parser-json generate` [WP-CLI](https://wp-cli.org/) command to create JSON files.

Example
```bash
wp parser-json generate --post_type=post,page --posts_per_page=100
```

The JSON files are saved in this plugin's directory in the folder `json-files`. If you use the `--posts_per_page` option multiple (numbered) files are created for a post type instead of a single file (per post type).

### Settings page

![Screen shot of settings page](https://user-images.githubusercontent.com/1436618/54931180-d271e600-4f18-11e9-896d-497efb249b11.png)

### Backward Compatibility

Originally this plugin only created JSON files for the post types of the the [WP Parser](https://github.com/WordPress/phpdoc-parser) plugin. For backward compatibility if you do not provide a post type and the WP Parser post types exist it will create the JSON files for the `wp-parser-function`,`wp-parser-hook` and`wp-parser-class` post types. It will also create a `version.json` file with the WP version that was parsed.

### JSON

This is a JSON file structure example for a `movies` post type. 

```json
{
  "post_type": "movies",
  "url": "https:\/\/my-website.com\/movie",
  "found_posts": 100,
  "max_pages": 1,
  "posts_per_page": -1,
  "content":[
      {"title": "Die Hard", "slug": "die-hard"},
      {"title": "Mad Max Fury Road", "slug": "mad-max-fury-road"},

      {"title": "etc...", "slug":"etc..."}
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
  "post_type": "movies",
  "url": "https:\/\/my-website.com\/movie",
  "found_posts": 2,
  "max_pages": 1,
  "posts_per_page": -1,
  "content":[
      {"title": "Die Hard", "slug": "die-hard", "post_id": 1288},
      {"title": "Mad Max Fury Road", "slug": "mad-max-fury-road", "post_id": 2768}
    ]
}
```

The `url` and `posts_per_page` values can also be filtered. See [Filters](https://github.com/keesiemeijer/wp-parser-json#filters) below.

### Pagination

When the `--posts_per_page` option is used an extra index file `{post_type}-index.json` is created to access the posts in the paginated (numbered) JSON files.

Example of a `movies-index.json` file created with `--posts_per_page=2`.

```json
{
  "post_type": "movies",
  "url": "https:\/\/my-website.com\/movie",
  "found_posts": 3,
  "max_pages": 2,
  "posts_per_page": 2,
  "pages": {
    "1": ["die-hard", "mad-max-fury-road"],
    "2": ["the-terminator"]
  }
}
```

In this example you see that you can access the Die Hard post in the `movies-1.json` file.

### Filters

* [wp_parser_json_content_item](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-query.php#L77)
* [wp_parser_json_base_url](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-query.php#L284)
* [wp_parser_json_posts_per_page](https://github.com/keesiemeijer/wp-parser-json/blob/549b70e92225606d82f05199cd5e26d0c15a9385/class-wp-parser-json-file.php#L180)
* [wp_parser_json_parse_post_types](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-file.php#L144)
* [wp_parser_json_query_args](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-query.php#L213)
* [wp_parser_json_file_limit](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-file.php#L178)
* [wp_parser_json_index_key](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-file.php#L27)

