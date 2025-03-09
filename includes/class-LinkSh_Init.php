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

		add_action( 'admin_notices', [ $this, 'permalinks_structure_message' ] );
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
			], filemtime( LINKSH_PLUGIN_BASEPATH . '/assets/js/admin.js' ), true );
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

	public function permalinks_structure_message(): void {
		// Check the current admin screen
		$screen = get_current_screen();
		if ( $screen && $screen->id === 'edit-links_shrt' ) {
			// Get the current permalink structure
			if ( get_option( 'permalink_structure' ) === '' ) {
				?>
                <div class="notice notice-warning is-dismissible">
                    <p><strong>Warning:</strong> Your permalink structure is set to the default (?p=123). This may cause
                        issues with short links. Please change it to "Post name" or another structure in <a
                                href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ) ?>">Permalink
                            Settings</a></p>
                </div>
				<?php
			}
		}
	}
}

new LinkSh_Init();