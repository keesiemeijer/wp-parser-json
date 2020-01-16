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

This is the JSON file (movies.json) structure for a `movies` post type.

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

Use the `--posts_per_page` option if you need to add a lot of post fields with the `wp_parser_json_content_item` filter above. This stops the JSON files from getting to big. if `--posts_per_page` is used everything you add with the filter will be put in the paginated files.

Example of a `movies.json` file created with the  `--posts_per_page=2` option.

```json
{
  "post_type": "movies",
  "url": "https:\/\/my-website.com\/movie",
  "found_posts": 3,
  "max_pages": 2,
  "posts_per_page": 2,
  "content":[
      {"title": "Die Hard", "slug": "die-hard", "page": 1},
      {"title": "Mad Max Fury Road", "slug": "mad-max-fury-road", "page": 1}
      {"title": "The Terminator", "slug": "the-terminator", "page": 2}
    ]
}
```

In this example you see that you can access the The Terminator post fields in the `movies-2.json` file.

### Filters

* [wp_parser_json_content_item](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-query.php#L77)
* [wp_parser_json_base_url](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-query.php#L284)
* [wp_parser_json_posts_per_page](https://github.com/keesiemeijer/wp-parser-json/blob/549b70e92225606d82f05199cd5e26d0c15a9385/class-wp-parser-json-file.php#L180)
* [wp_parser_json_parse_post_types](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-file.php#L144)
* [wp_parser_json_query_args](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-query.php#L213)
* [wp_parser_json_file_limit](https://github.com/keesiemeijer/wp-parser-json/blob/9d002c10cc0b8a5a2e587de0fd477ddb6ae4205a/class-wp-parser-json-file.php#L178)
* [wp_parser_json_posts_page_index](https://github.com/keesiemeijer/wp-parser-json/blob/9c27e8affbb21c9e3258da7e4c7f4c41adb7678d/class-wp-parser-json-file.php#L266)
* [wp_parser_json_index_page_index](https://github.com/keesiemeijer/wp-parser-json/blob/41f0be8eafd333a858e1a6681198d0f8e476b827/class-wp-parser-json-file.php#L302)

