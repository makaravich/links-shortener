<?php

/**
 * Registers new custom post type for the plugin
 */
class LinksSh_CPT {
	public function __construct() {
		// Register custom post type
		add_action( 'init', [ $this, 'register_post_types' ] );

		// Meta-box for single post
		add_action( 'add_meta_boxes', [ $this, 'linksh_add_meta_box' ] );

		// Save the meta-box data into the post
		add_action( 'save_post', [ $this, 'linksh_save_meta_box_data' ] );
	}

	/**
	 * The post-type registration
	 *
	 * @return void
	 */
	function register_post_types(): void {
		register_post_type( LINKSH_POST_TYPE, [
			'label'        => null,
			'labels'       => [
				// Name for the post type.
				'name'              => __( 'Links Shortener', 'links-shortener' ),
				// Name for single post of that type.
				'singular_name'     => __( 'Links Shortener', 'links-shortener' ),
				// To add a new post.
				'add_new'           => __( 'Add Links Shortener Item', 'links-shortener' ),
				// Title for a newly created post in the admin panel.
				'add_new_item'      => __( 'Add Links Shortener Item', 'links-shortener' ),
				// For editing post type.
				'edit_item'         => __( 'Edit Links Shortener Item', 'links-shortener' ),
				// New post's text.
				'new_item'          => __( 'New Links Shortener Item', 'links-shortener' ),
				// For viewing this post type.
				'view_item'         => __( 'See Links Shortener Item', 'links-shortener' ),
				// Search for these post types.
				'search_items'      => __( 'Find Links Shortener Items', 'links-shortener' ),
				// If search has not found anything.
				'not_found'         => __( 'Not Found', 'links-shortener' ),
				// For parents (for hierarchical post types).
				'parent_item_colon' => '',
				// Menu name.
				'menu_name'         => __( 'Links shortener', 'links-shortener' ),
			],
			'description'  => '',
			'public'       => false,  // Disable public access
			'show_ui'      => true,   // Still show in admin panel
			'show_in_menu' => 'tools.php',
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-admin-links',
			'hierarchical' => false,
			'supports'     => [ 'author' ],
			'has_archive'  => false,  // Disable archives
			'rewrite'      => false,  // Disable URL rewrite
			'query_var'    => false,  // Disable query variable
			'capabilities' => [
				'edit_post'          => 'edit_linkssh',
				'read_post'          => 'read_linkssh',
				'delete_post'        => 'delete_linkssh',
				'edit_posts'         => 'edit_linksshs',
				'edit_others_posts'  => 'edit_others_linksshs',
				'publish_posts'      => 'publish_linksshs',
				'read_private_posts' => 'read_private_linksshs',
			],
			'map_meta_cap' => true,
		] );
	}


	/**
	 * Adds the meta-box for single post
	 *
	 * @return void
	 */
	function linksh_add_meta_box(): void {
		add_meta_box(
			'linksh_meta_box',
			'Link Info',
			[ $this, 'linksh_meta_box_callback' ],
			LINKSH_POST_TYPE,
			'normal',
			'high'
		);
	}


	/**
	 * Callback function for the meta-box
	 *
	 * @param $post
	 *
	 * @return void
	 */

	function linksh_meta_box_callback( $post ): void {
		// Retrieve meta field values
		$long_url        = get_post_meta( $post->ID, LINKSH_LONG_URL_META_NAME, true );
		$short_url_slug  = get_post_meta( $post->ID, LINKSH_SHORT_URL_META_NAME, true );
		$redirects_count = get_post_meta( $post->ID, LINKSH_REDIRECT_COUNT_META_NAME, true );

		// Use nonce for verification
		wp_nonce_field( 'linksh_save_meta_box_data', 'linksh_meta_box_nonce' );

		// HTML for the meta box
		?>
        <div class="single-field">
            <label class="label" for="linksh_long_url"><?php esc_html_e( 'Long URL:', 'links-shortener' ) ?></label>
            <input type="text" id="linksh_long_url" name="linksh_long_url"
                   value="<?php echo esc_url( $long_url ); ?>"/>
        </div>

        <div class="single-field">
            <p class="label"><?php esc_html_e( 'Short URL:', 'links-shortener' ) ?></p>
            <p class="value"><?php echo esc_url( home_url() . '/' . esc_attr( $short_url_slug ) ); ?></p>
        </div>
        <div class="single-field">
            <p class="label"><?php esc_html_e( 'Redirect Count:', 'links-shortener' ) ?></p>
            <p class="value"><?php echo esc_attr( $redirects_count ); ?></p>
        </div>
        <div class="single-field">
            <label class="label"
                   for="linksh_extended_log"><?php esc_html_e( 'Extended Log:', 'links-shortener' ) ?></label>
			<?php $this->render_redirects_table( $post->ID ) ?>
        </div>
		<?php
	}

	/**
	 * Generate table with redirection log
	 *
	 * @param $redirect_id
	 *
	 */
	public function render_redirects_table( $redirect_id ): void {
		global $wpdb;

		// Get the table name
		$table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

		// Cache key for the query results
		$cache_key = "redirect_logs_{$redirect_id}";
		$results   = wp_cache_get( $cache_key, 'linksh_plugin' );

		if ( $results === false ) {
			// Results are not in cache, querying the database

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT datetime, target_url, ip_address, referrer, user_agent, accept_language, os, device_type 
                        FROM %i WHERE redirect_id = %d",
				[ $table_name, $redirect_id ]
			) );
			// Save the results in cache for 5 minutes (300 seconds)
			wp_cache_set( $cache_key, $results, 'linksh_plugin', 30 );
		}

		// Start building the HTML table
		?>
        <table class="redirects-log-table" style="width: 100%; border-collapse: collapse;">
            <thead>
            <tr>
                <th>Datetime</th>
                <th>Target URL</th>
                <th>IP Address</th>
                <th>Referrer</th>
                <th>User Agent</th>
                <th>Accept Language</th>
                <th>OS</th>
                <th>Device Type</th>
            </tr>
            </thead>
            <tbody>
			<?php
			// Check if we have results
			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					?>
                    <tr>
                        <td><?php echo esc_html( $row->datetime ) ?> </td>
                        <td>
                            <a href="<?php echo esc_url( $row->target_url ) ?>"
                               target="_blank"><?php echo esc_html( $row->target_url ) ?></a>
                        </td>
                        <td><?php echo esc_html( $row->ip_address ) ?></td>
                        <td><?php echo esc_html( $row->referrer ) ?></td>
                        <td><?php echo esc_html( $row->user_agent ) ?></td>
                        <td><?php echo esc_html( $row->accept_language ) ?></td>
                        <td><?php echo esc_html( $row->os ) ?></td>
                        <td><?php echo esc_html( $row->device_type ) ?></td>
                    </tr>
					<?php
				}
			} else {
				?>
                <tr>
                    <td colspan="11" style="text-align: center;">No redirects found for this ID.</td>
                </tr>
				<?php
			}

			?>
            </tbody>
        </table>
		<?php
	}

	/**
	 * Save meta box data
	 *
	 * @param $post_id
	 *
	 * @return void
	 */
	function linksh_save_meta_box_data( $post_id ): void {
		// Check nonce
		if ( ! isset( $_POST['linksh_meta_box_nonce'] ) ||
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['linksh_meta_box_nonce'] ) ), 'linksh_save_meta_box_data' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permissions
		if ( ! current_user_can( 'edit_linkssh', $post_id ) ) {
			return;
		}

// Save long_url field
		if ( isset( $_POST['linksh_long_url'] ) ) {
			update_post_meta( $post_id, LINKSH_LONG_URL_META_NAME, sanitize_text_field( wp_unslash( $_POST['linksh_long_url'] ) ) );
		}
	}
}

new LinksSh_CPT();