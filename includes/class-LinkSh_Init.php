<?php

/**
 * Plugin initialization
 */
class LinkSh_Init {
	public function __construct() {
		// Add styles and scripts on admin side
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_assets' ] );

		// Add custom column to User's table
		add_filter( 'manage_users_columns', [ $this, 'custom_add_user_columns' ] );
		add_action( 'manage_users_custom_column', [ $this, 'custom_show_user_posts' ], 10, 3 );
	}

	/**
	 * Enqueue styles and scripts on admin side
	 *
	 * @param $hook
	 *
	 * @return void
	 */
	public function add_admin_assets( $hook ): void {
		wp_enqueue_style( 'linksh-admin-script', LINKSH_PLUGIN_BASEURI . '/assets/styles/admin.css', [], filemtime( LINKSH_PLUGIN_BASEPATH . '/assets/styles/admin.css' ) );

		if ( 'edit.php' == $hook ) {
			wp_enqueue_script( 'linksh-admin-script', LINKSH_PLUGIN_BASEURI . '/assets/js/admin.js', [
				'jquery',
				'wp-util'
			], filemtime( LINKSH_PLUGIN_BASEPATH . '/assets/js/admin.js' ) );
		}
	}

	/**
	 * Adds column with short links count (Title)
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function custom_add_user_columns( $columns ): mixed {
		$columns['shlinks_count'] = __( 'Short Links', 'links-shortener' );

		return $columns;
	}

	/**
	 * Adds column with short links count (Values)
	 *
	 * @param $value
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return mixed|string
	 */
	function custom_show_user_posts( $value, $column_name, $user_id ): mixed {
		if ( $column_name === 'shlinks_count' ) {
			return count_user_posts( $user_id, LINKSH_POST_TYPE );
		}

		return $value;
	}
}

new LinkSh_Init();