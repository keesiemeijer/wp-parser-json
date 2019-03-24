<table class="form-table">
	<tbody>
		<tr>
			<th scope="row"><?php _e( 'Post Types', 'wp-parser-json' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text">
						<span><?php _e( 'Post Types', 'wp-parser-json' ); ?></span>
					</legend>
					<?php echo $checkboxes; ?>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('Pagination', 'wp-parser-json'); ?>
			</th>
			<td>
				<label for="paginate">
					<input name="paginate" type="checkbox" id="paginate" value="on"<?php echo $paginate_checked; ?>>
					<?php _e('Enable paginated JSON files', 'wp-parser-json'); ?>
				</label>
				<label for="posts_per_page">
					<?php _e('with ', 'wp-parser-json'); ?>
					<input name="posts_per_page" type="number" min="1" step="1" id="posts_per_page" value="<?php echo $posts_per_page; ?>" class="small-text">
					<?php _e('posts per page', 'wp-parser-json'); ?>
				</label>
			</td>
		</tr>
	</tbody>
</table>