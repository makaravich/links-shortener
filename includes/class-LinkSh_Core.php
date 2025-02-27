<?php

class LinkSh_Core {
	public function __construct() {
		// Filter Links Shortener posts in the Dashboard
		add_action( 'pre_get_posts', [ $this, 'modify_admin_search_query' ] );

		// User permissions
		add_action( 'set_current_user', [ $this, 'add_linksh_capabilities' ] );

		// Processing adding link form data
		add_action( 'admin_post_process_link_shortener', [ $this, 'process_link_shortener_form' ] );

		// Update the table of posts (short links)
		add_filter( 'manage_' . LINKSH_POST_TYPE . '_posts_columns', [ $this, 'update_posts_table_columns' ] );
		add_action( 'manage_' . LINKSH_POST_TYPE . '_posts_custom_column', [ $this, 'custom_columns_content' ], 10, 2 );
		add_filter( 'manage_edit-' . LINKSH_POST_TYPE . '_sortable_columns', [ $this, 'custom_sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'custom_order_by' ] );

		// Display messages after adding link
		add_action( 'admin_notices', [ $this, 'display_link_shortener_message' ] );
	}

	/**
	 * Modify search query to filter Links Shortener posts in the Dashboard
	 *
	 * @param $query
	 *
	 * @return void
	 */
	function modify_admin_search_query( $query ): void {
		// Check if we are in the admin area, this is the main query, and it is a search query
		if ( is_admin() && $query->is_main_query() && $query->is_search() ) {

			// Check if the post type matches the LINKSH_POST_TYPE constant
			if ( $query->get( 'post_type' ) == LINKSH_POST_TYPE ) {
				// Set up meta_query to search by meta fields long_url and short_url_slug
				$meta_query = array(
					'relation' => 'OR',
					array(
						'key'     => LINKSH_LONG_URL_META_NAME,
						'value'   => $query->query_vars['s'],
						'compare' => 'LIKE'
					),
					array(
						'key'     => LINKSH_SHORT_URL_META_NAME,
						'value'   => $query->query_vars['s'],
						'compare' => 'LIKE'
					)
				);

				// Set query parameters
				$query->set( 'meta_query', $meta_query );

				// Clear the main search query to avoid conflict with meta_query
				$query->set( 's', '' );
			}
		}
	}

	/**
	 * Add permissions to users by their roles
	 *
	 * @return void
	 */
	function add_linksh_capabilities(): void {
		// Allow updating user roles programmatically
		$roles = apply_filters( 'linkssh_allowed_user_roles', [ 'administrator', 'editor', 'author' ] );

		// Set capabilities to user roles
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );

			if ( $role ) {
				$role->add_cap( 'edit_linkssh' );
				$role->add_cap( 'read_linkssh' );
				$role->add_cap( 'delete_linkssh' );
				$role->add_cap( 'edit_linksshs' );
				$role->add_cap( 'edit_others_linksshs' );
				$role->add_cap( 'publish_linksshs' );
				$role->add_cap( 'read_private_linksshs' );
			}
		}
	}

	/**
	 * Update columns in the post table
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	function update_posts_table_columns( $columns ): mixed {
		return apply_filters( 'linksh_posts_table_columns', [

			'cb'              => '<input type="checkbox" />',
			'ls_title'        => __( 'Title', 'linkssh' ),
			'short_url'       => __( 'Short URL', 'linkssh' ),
			'date'            => __( 'Date', 'linkssh' ),
			'redirects_count' => __( 'Redirects Count', 'linkssh' )
		] );
	}

	/**
	 * Update the content uf custom columns
	 *
	 * @param $column
	 * @param $post_id
	 *
	 * @return void
	 */
	function custom_columns_content( $column, $post_id ): void {

		switch ( $column ) {
			case 'short_url':
				$short_url = home_url() . '/' . get_post_meta( $post_id, LINKSH_SHORT_URL_META_NAME, true );
				$short_url = sprintf( '<a href="%s" target="_blank">%s</a>',
					$short_url, $short_url );
				echo $short_url;
				break;
			case 'redirects_count':
				$redirects = get_post_meta( $post_id, LINKSH_REDIRECT_COUNT_META_NAME, true ) ?? '0';
				if ( empty( $redirects ) ) {
					$redirects = '0';
				}

				echo $redirects;
				break;
			case 'ls_title':
				$the_title    = get_the_title( $post_id );
				$the_long_url = get_post_meta( $post_id, LINKSH_LONG_URL_META_NAME, true ) ?? '';
				$ls_title     = '<a class="row-title" href="' . get_edit_post_link( $post_id ) . '" aria-label="“' . $the_title . '” (Edit)">' . $the_title . '</a><br>';
				if ( ! empty( $the_long_url ) ) {
					$ls_title .= '<span class="long-url">' . $the_long_url . '</span>';
				}

				echo $ls_title;
				break;
		}
	}

	/**
	 * Make custom columns sortable
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	function custom_sortable_columns( $columns ): mixed {
		$columns['ls_title']        = 'title';
		$columns['short_url']       = 'short_url';
		$columns['redirects_count'] = LINKSH_REDIRECT_COUNT_META_NAME;

		return $columns;
	}

	/**
	 * Sort custom columns
	 *
	 * @param $query
	 *
	 * @return void
	 */
	function custom_order_by( $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $orderby = $query->get( 'orderby' ) ) {
			switch ( $orderby ) {
				case 'short_url':
					$query->set( 'meta_key', LINKSH_SHORT_URL_META_NAME );
					$query->set( 'orderby', 'meta_value' );
					break;
				case 'redirects_count':
					$query->set( 'meta_key', LINKSH_REDIRECT_COUNT_META_NAME );
					$query->set( 'orderby', 'meta_value_num' );
					break;
			}
		}
	}


	/**
	 * Generates a random URL-friendly slug
	 *
	 * @param int $length
	 * @param int $iteration
	 *
	 * @return string
	 */
	public static function generate_random_slug( int $length = 4, int $iteration = 0 ): string {
		if ( $iteration >= 10 ) {
			$length ++;
			$iteration = 0;
		}

		$characters        = '0123456789abcdefghijklmnopqrstuvwxyz';
		$characters_length = strlen( $characters );
		$random_slug       = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$random_slug .= $characters[ rand( 0, $characters_length - 1 ) ];
		}

		if ( ! self::is_slug_unique( $random_slug ) ) {
			$random_slug = self::generate_random_slug( $length, $iteration + 1 );
		}

		return $random_slug;
	}

	/**
	 * Function to get the page title
	 *
	 * @param $url
	 *
	 * @return mixed|string
	 */
	public static function get_page_title( $url ): mixed {
		// Perform an HTTP request
		$response = wp_remote_get( $url );

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return $url;
		}

		// Get the body of the response
		$body = wp_remote_retrieve_body( $response );

		// Check for the presence of the body
		if ( empty( $body ) ) {
			return $url;
		}

		// Search for the <title> tag
		if ( preg_match( '/<title>([^<]*)<\/title>/i', $body, $matches ) ) {
			return $matches[1];
		} else {
			return $url;
		}
	}

	/**
	 * @param string $long_url URL to be shorted
	 * @param string $short_url_slug slug for the new short link
	 * @param array $args array of arguments for the function. Available keys:
	 * - post_author: ID of user
	 *
	 * @return object|string
	 */
	public static function add_shorted_link_post( string $long_url, string $short_url_slug = '', array $args = [] ): object|string {
		//Exit if $long_url is invalid
		if ( ! wp_http_validate_url( $long_url ) ) {
			return (object) [
				'success' => false,
				'message' => __( 'The URL is invalid', 'linkssh' ),
			];
		}

		$args = apply_filters( 'create_short_link_args', $args );

		$message  = '';
		$success  = false;
		$new_link = '';

		// If $short_url_slug is empty, generate a random value
		if ( empty( $short_url_slug ) ) {
			$short_url_slug = self::generate_random_slug();
		}

		if ( self::is_slug_unique( $short_url_slug ) ) {
			// Get the page title
			$post_title = self::get_page_title( $long_url );

			//Define the author
			$post_author = $args['post_author'] ?? get_current_user_id();

			// Data for the new post
			$post_data = array(
				'post_title'  => $post_title,
				'post_status' => 'publish',
				'post_type'   => LINKSH_POST_TYPE,
				'post_author' => $post_author,
			);

			// Create the post
			$post_id = wp_insert_post( $post_data );
			// Check if the post was successfully created
			if ( $post_id && ! is_wp_error( $post_id ) ) {
				// Add meta fields
				update_post_meta( $post_id, LINKSH_LONG_URL_META_NAME, $long_url );
				update_post_meta( $post_id, LINKSH_SHORT_URL_META_NAME, $short_url_slug );
				update_post_meta( $post_id, LINKSH_REDIRECT_COUNT_META_NAME, 0 );

				$message  = __( 'Short link successfully created with ID: ', 'linkssh' ) . $post_id;
				$new_link = get_home_url( null, $short_url_slug );
				$success  = true;
			} else {
				$message = __( 'Error creating short link', 'linkssh' );
			}

		} else {
			$message = __( 'Short link with this slug already exists', 'linkssh' );
		}

		return (object) [
			'success' => $success,
			'message' => $message,
			'newLink' => $new_link,
		];
	}

	/**
	 * Check for the uniqueness of short_url_slug
	 *
	 * @param $slug
	 *
	 * @return bool
	 */
	private static function is_slug_unique( $slug ): bool {
		$existing_posts = get_posts( array(
			'post_type'      => LINKSH_POST_TYPE,
			'meta_key'       => LINKSH_SHORT_URL_META_NAME,
			'meta_value'     => $slug,
			'posts_per_page' => 1,
			'fields'         => 'ids'
		) );

		return empty( $existing_posts );
	}

	/**
	 * Processing of data got from form when user adds new link
	 *
	 * @return void
	 */
	function process_link_shortener_form(): void {
		status_header( 200 );

		// Check if the necessary fields are set
		if ( isset( $_POST['long_url'] ) ) {
			// Sanitize the input data
			$long_url       = sanitize_url( $_POST['long_url'] );
			$short_url_slug = sanitize_text_field( $_POST['short_url'] ) ?? '';

			// Try to add the shorted link post and capture the result
			$result = self::add_shorted_link_post( $long_url, $short_url_slug );

			// Check the result and add the appropriate message
			if ( is_object( $result ) && $result->success ) {
				// Add a success message
				$message      = $result->message;
				$message_type = 'success';
			} else {
				// Add an error message
				$message      = $result->message ?? __( 'There was an error adding the link.', 'linkssh' );
				$message_type = 'error';
			}
		} else {
			$message      = __( 'Required fields are missing.', 'linkssh' );
			$message_type = 'error';
		}

		// Save the message to transient
		set_transient( 'link_shortener_message', [ 'message' => $message, 'type' => $message_type ], 30 );

		// Redirect back to the previous page
		wp_redirect( wp_get_referer() );
		exit();
	}


	/**
	 * Display the message stored in transient
	 *
	 * @return void
	 */
	function display_link_shortener_message(): void {
		// Get the message from transient
		$transient = get_transient( 'link_shortener_message' );
		if ( $transient ) {
			// Display the message
			if ( $transient['type'] === 'success' ) {
				add_settings_error( 'link_shortener_messages', 'link_shortener_message', $transient['message'], 'updated' );
			} else {
				add_settings_error( 'link_shortener_messages', 'link_shortener_message', $transient['message'], 'error' );
			}

			// Delete the transient
			delete_transient( 'link_shortener_message' );
		}

		// Display the settings errors
		settings_errors( 'link_shortener_messages' );
	}

	/**
	 * Returns short link by post ID
	 *
	 * @param $post_id
	 *
	 * @return string|false
	 */
	public static function get_short_link_by_post_id( $post_id ): string|false {
		$slug = get_post_field( LINKSH_SHORT_URL_META_NAME, $post_id );

		if ( ! $slug ) {
			return false;
		} else {
			return get_home_url( null, $slug );
		}
	}

	/**
	 * Returns long link by post ID
	 *
	 * @param $post_id
	 *
	 * @return string|false
	 */
	public static function get_long_link_by_post_id( $post_id ): string|false {
		$link = get_post_field( LINKSH_LONG_URL_META_NAME, $post_id );

		if ( ! $link ) {
			return false;
		} else {
			return $link;
		}
	}

	/**
	 * Return link redirect count by post ID
	 *
	 * @param $post_id
	 *
	 * @return string|false
	 */
	public static function get_link_use_count( $post_id ): string|false {
		return get_post_field( LINKSH_REDIRECT_COUNT_META_NAME, $post_id );
	}

	/**
	 * Generate and save a CSV file with redirection log data
	 *
	 * @param $redirect_id
	 *
	 * @return string|WP_Error Path to the generated CSV file or WP_Error on failure
	 */
	public static function generate_redirects_log_csv( $redirect_id ): WP_Error|string {
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

		// Check if we have results
		if ( empty( $results ) ) {
			return new WP_Error( 'no_data', 'No redirects found for this ID.' );
		}

		// Define the file name
		$file_name = 'redirects_log_' . sanitize_file_name( wp_strip_all_tags( $redirect_id ) ) . '_' . date( 'Y-m-d_H-i-s' ) . '.csv';

		// Get the uploads directory
		$upload_dir = wp_upload_dir();
		$file_path  = trailingslashit( $upload_dir['basedir'] ) . $file_name;

		// Open the file for writing
		if ( ! $handle = fopen( $file_path, 'w' ) ) {
			return new WP_Error( 'file_error', 'Unable to create the CSV file.' );
		}

		// Write the header row
		fputcsv( $handle, [
			'Datetime',
			'Target URL',
			'IP Address',
			'Referrer',
			'User Agent',
			'Language',
			'OS'
		] );

		// Write each result as a row in the CSV file
		foreach ( $results as $row ) {
			fputcsv( $handle, [
				esc_html( $row->datetime ),
				esc_url( $row->target_url ),
				esc_html( $row->ip_address ),
				esc_attr( $row->referrer ),
				esc_html( $row->user_agent ),
				esc_html( $row->accept_language ),
				esc_html( $row->os ),
				esc_html( $row->device_type )
			] );
		}

		// Close the file handle
		fclose( $handle );

		// Return the path to the generated file
		return $file_path;
	}
}

new LinkSh_Core();