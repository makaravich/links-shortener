<?php

/**
 * Plugin initialization
 */
class LinkSh_Init {
	public function __construct() {
		// Add styles and scripts on admin side
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_assets' ] );
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
}

new LinkSh_Init();