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
			'label'         => null,
			'labels'        => [
				// Name for the post type.
				'name'              => __( 'Links Shortener', 'linkssh' ),
				// Name for single post of that type.
				'singular_name'     => __( 'Links Shortener', 'linkssh' ),
				// To add a new post.
				'add_new'           => __( 'Add Links Shortener Item', 'linkssh' ),
				// Title for a newly created post in the admin panel.
				'add_new_item'      => __( 'Adding Links Shortener Item', 'linkssh' ),
				// For editing post type.
				'edit_item'         => __( 'Edit Links Shortener Item', 'linkssh' ),
				// New post's text.
				'new_item'          => __( 'New Links Shortener Item', 'linkssh' ),
				// For viewing this post type.
				'view_item'         => __( 'See Links Shortener Item', 'linkssh' ),
				// Search for these post types.
				'search_items'      => __( 'Find Links Shortener Items', 'linkssh' ),
				// If search has not found anything.
				'not_found'         => __( 'Not Found', 'linkssh' ),
				// For parents (for hierarchical post types).
				'parent_item_colon' => '',
				// Menu name.
				'menu_name'         => __( 'Links shortener', 'linkssh' ),
			],
			'description'   => '',
			'public'        => false,  // Disable public access
			'show_ui'       => true,   // Still show in admin panel
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 80,
            'menu_icon'     => 'dashicons-admin-links',
			'hierarchical'  => false,
			'supports'      => [ 'author' ],
			'has_archive'   => false,  // Disable archives
			'rewrite'       => false,  // Disable URL rewrite
			'query_var'     => false,  // Disable query variable
			'capabilities'  => [
				'edit_post'          => 'edit_linkssh',
				'read_post'          => 'read_linkssh',
				'delete_post'        => 'delete_linkssh',
				'edit_posts'         => 'edit_linksshs',
				'edit_others_posts'  => 'edit_others_linksshs',
				'publish_posts'      => 'publish_linksshs',
				'read_private_posts' => 'read_private_linksshs',
			],
			'map_meta_cap'  => true,
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
		$long_url             = get_post_meta( $post->ID, LINKSH_LONG_URL_META_NAME, true );
		$short_url_slug       = get_post_meta( $post->ID, LINKSH_SHORT_URL_META_NAME, true );
		$redirects_count      = get_post_meta( $post->ID, LINKSH_REDIRECT_COUNT_META_NAME, true );
		$linkssh_extended_log = get_post_meta( $post->ID, LINKSH_EXTENDED_LOG_META_NAME, true );

		// Use nonce for verification
		wp_nonce_field( 'linksh_save_meta_box_data', 'linksh_meta_box_nonce' );

		// HTML for the meta box
		?>
        <div class="single-field">
            <label class="label" for="linksh_long_url"><?php _e( 'Long URL:', 'linkssh' ) ?></label>
            <input type="text" id="linksh_long_url" name="linksh_long_url"
                   value="<?php echo esc_attr( $long_url ); ?>"/>
        </div>

        <div class="single-field">
            <p class="label"><?php _e( 'Short URL:', 'linkssh' ) ?></p>
            <p class="value"><?php echo home_url() . '/' . esc_attr( $short_url_slug ); ?></p>
        </div>
        <div class="single-field">
            <p class="label"><?php _e( 'Redirect Count:', 'linkssh' ) ?></p>
            <p class="value"><?php echo esc_attr( $redirects_count ); ?></p>
        </div>
        <div class="single-field">
            <label class="label" for="linksh_extended_log"><?php _e( 'Extended Log:', 'linkssh' ) ?></label>
			<?php echo $this->render_redirects_table( $post->ID ) ?>
            <!--<textarea id="linksh_extended_log" name="linksh_extended_log" rows="10" cols="80"
                      readonly><?php /*echo esc_textarea($linkssh_extended_log); */ ?></textarea>-->
        </div>
		<?php
	}

	/**
	 * Generate table with redirection log
	 *
	 * @param $redirect_id
	 *
	 * @return string
	 */
	public function render_redirects_table($redirect_id): string {
		global $wpdb;

		// Get the table name
		$table_name = $wpdb->prefix . LINKSH_LOG_TABLE_NAME;

		// Query to fetch data for the given redirect_id
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT datetime, target_url, ip_address, referrer, user_agent, accept_language, os, device_type 
             FROM {$table_name} 
             WHERE redirect_id = %d",
				$redirect_id
			)
		);

		// Start building the HTML table
		$html = '<table class="redirects-log-table" style="width: 100%; border-collapse: collapse;">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th>Datetime</th>';
		$html .= '<th>Target URL</th>';
		$html .= '<th>IP Address</th>';
		$html .= '<th>Referrer</th>';
		$html .= '<th>User Agent</th>';
		$html .= '<th>Accept Language</th>';
		$html .= '<th>OS</th>';
		$html .= '<th>Device Type</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		// Check if we have results
		if (!empty($results)) {
			foreach ($results as $row) {
				$html .= '<tr>';
				$html .= '<td>' . esc_html($row->datetime) . '</td>';
				$html .= '<td><a href="' . esc_url($row->target_url) . '" target="_blank">' . esc_html($row->target_url) . '</a></td>';
				$html .= '<td>' . esc_html($row->ip_address) . '</td>';
				$html .= '<td>' . esc_html($row->referrer) . '</td>';
				$html .= '<td>' . esc_html($row->user_agent) . '</td>';
				$html .= '<td>' . esc_html($row->accept_language) . '</td>';
				$html .= '<td>' . esc_html($row->os) . '</td>';
				$html .= '<td>' . esc_html($row->device_type) . '</td>';
				$html .= '</tr>';
			}
		} else {
			$html .= '<tr><td colspan="11" style="text-align: center;">No redirects found for this ID.</td></tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';

		// Return the generated HTML
		return $html;
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
		if ( ! isset( $_POST['linksh_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['linksh_meta_box_nonce'], 'linksh_save_meta_box_data' ) ) {
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
			update_post_meta( $post_id, LINKSH_LONG_URL_META_NAME, sanitize_text_field( $_POST['linksh_long_url'] ) );
		}
	}
}

new LinksSh_CPT();