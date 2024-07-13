<?php

class LinkSh_Init {
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_assets' ] );

		add_action( 'admin_post_process_link_shortener', [ $this, 'process_link_shortener_form' ] );

		add_filter( 'manage_' . LINKSH_POST_TYPE . '_posts_columns', [ $this, 'add_custom_columns' ] );
		add_action( 'manage_' . LINKSH_POST_TYPE . '_posts_custom_column', [ $this, 'custom_columns_content' ], 10, 2 );
		add_filter( 'manage_edit-' . LINKSH_POST_TYPE . '_sortable_columns', [ $this, 'custom_sortable_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'custom_orderby' ] );

		add_filter( 'the_title', [ $this, 'custom_title_column_content' ], 10, 2 );
	}

	function add_custom_columns( $columns ) {
		$columns['short_url']       = __( 'Short URL', 'linkssh' );
		$columns['redirects_count'] = __( 'Redirects Count', 'linkssh' );

		return $columns;
	}

	function custom_columns_content( $column, $post_id ): void {
		switch ( $column ) {
			case 'short_url':
				$short_url = home_url() . '/' . get_post_meta( $post_id, 'short_url_slug', true );
				$short_url = sprintf( '<a href="%s" target="_blank">%s</a>',
					$short_url, $short_url );
				echo $short_url;
				break;
			case 'redirects_count':
				$redirects = get_post_meta( $post_id, REDIRECT_COUNT_META_NAME, true ) ?? '0';
				if ( empty( $redirects ) ) {
					$redirects = '0';
				}

				echo $redirects;
				break;
		}
	}

	function custom_sortable_columns( $columns ) {
		$columns['short_url']       = 'short_url';
		$columns['redirects_count'] = REDIRECT_COUNT_META_NAME;

		return $columns;
	}

	function custom_orderby( $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $orderby = $query->get( 'orderby' ) ) {
			switch ( $orderby ) {
				case 'short_url':
					$query->set( 'meta_key', 'short_url_slug' );
					$query->set( 'orderby', 'meta_value' );
					break;
				case 'redir_count':
					$query->set( 'meta_key', 'redir_count' );
					$query->set( 'orderby', 'meta_value_num' );
					break;
			}
		}
	}

	function custom_title_column_content( $title, $post_id ) {
		if ( get_post_type( $post_id ) === LINKSH_POST_TYPE ) {
			$long_url = get_post_meta( $post_id, 'long_url', true );

			return $title . ' - ' . $long_url;
		}

		return $title;
	}

	public function add_admin_assets( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'linksh-admin-script', LINKSH_PLUGIN_BASEURI . '/assets/styles/admin.css', [], filemtime( LINKSH_PLUGIN_BASEPATH . '/assets/styles/admin.css' ) );

		wp_enqueue_script( 'linksh-admin-script', LINKSH_PLUGIN_BASEURI . '/assets/js/admin.js', [
			'jquery',
			'wp-util'
		], filemtime( LINKSH_PLUGIN_BASEPATH . '/assets/js/admin.js' ) );

	}


	/**
	 * Function to generate a random URL-friendly slug
	 *
	 * @param $length
	 *
	 * @return string
	 */
	function generate_random_slug( $length = 8 ): string {
		$characters        = '0123456789abcdefghijklmnopqrstuvwxyz';
		$characters_length = strlen( $characters );
		$random_slug       = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$random_slug .= $characters[ rand( 0, $characters_length - 1 ) ];
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
	function get_page_title( $url ) {
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
	 * @param $long_url
	 * @param $short_url_slug
	 *
	 * @return string
	 */
	public function add_shorted_link_post( $long_url, $short_url_slug ): string {
		$response = '';

		// If $short_url_slug is empty, generate a random value
		if ( empty( $short_url_slug ) ) {
			$short_url_slug = $this->generate_random_slug();
		}

		// Check for the uniqueness of short_url_slug
		$existing_posts = get_posts( array(
			'post_type'      => LINKSH_POST_TYPE,
			'meta_key'       => 'short_url_slug',
			'meta_value'     => $short_url_slug,
			'posts_per_page' => 1,
			'fields'         => 'ids'
		) );

		if ( empty( $existing_posts ) ) {
			// Get the page title
			$post_title = $this->get_page_title( $long_url );

			// Data for the new post
			$post_data = array(
				'post_title'   => $post_title,
				// Use the retrieved title
				'post_content' => 'Post content',
				// Replace with the desired content
				'post_status'  => 'publish',
				// Can be changed to 'draft' if the post should not be published immediately
				'post_type'    => LINKSH_POST_TYPE,
			);

			// Create the post
			$post_id = wp_insert_post( $post_data );
			// Check if the post was successfully created
			if ( $post_id && ! is_wp_error( $post_id ) ) {
				// Add meta fields
				update_post_meta( $post_id, 'long_url', $long_url );
				update_post_meta( $post_id, 'short_url_slug', $short_url_slug );

				$response = 'Post successfully created with ID: ' . $post_id;
			} else {
				$response = 'Error creating post';
			}

		} else {
			$response = 'A post with this short_url_slug already exists';
		}

		file_put_contents( 'd:\linkssh.log', $long_url . PHP_EOL, FILE_APPEND );
		file_put_contents( 'd:\linkssh.log', $short_url_slug . PHP_EOL, FILE_APPEND );

		return $response;
	}

	function process_link_shortener_form(): void {
		status_header( 200 );

		// Check if the necessary fields are set
		if ( isset( $_POST['long_url'] ) ) {
			// Sanitize the input data
			$long_url       = sanitize_url( $_POST['long_url'] );
			$short_url_slug = sanitize_text_field( $_POST['short_url'] ) ?? '';

			$this->add_shorted_link_post( $long_url, $short_url_slug );

			// You can add your processing logic here. For now, we'll just output the sanitized values.
			// Note: Be cautious with outputting data like this directly, it's just for demonstration purposes.
			//echo 'Long URL: ' . esc_html( $long_url ) . '<br>';
			//echo 'Short URL: ' . esc_html( $short_url );
		} else {
			echo 'Required fields are missing.';
		}

		// Redirect back to the previous page
		wp_redirect( wp_get_referer() );
		exit();
	}
}

new LinkSh_Init();