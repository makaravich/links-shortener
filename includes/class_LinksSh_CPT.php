<?php

/**
 * Registers new custom post type for the plugin
 */
class LinksSh_CPT {
	public function __construct() {
		// Register custom post type
		add_action( 'init', [ $this, 'register_post_types' ] );

		// Add custom content to the admin page of our CPT
		//add_action( 'restrict_manage_posts', [ $this, 'add_custom_content_before_posts_table' ],20 );


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
				'name'              => __( 'Links shortener', 'linkssh' ),
				// name for the post type.
				'singular_name'     => __( 'Links shortener', 'linkssh' ),
				// name for single post of that type.
				'add_new'           => __( 'Add Links shortener Item', 'linkssh' ),
				// to add a new post.
				'add_new_item'      => __( 'Adding Links shortener Item', 'linkssh' ),
				// title for a newly created post in the admin panel.
				'edit_item'         => __( 'Edit Links shortener Item', 'linkssh' ),
				// for editing post type.
				'new_item'          => __( 'New Links shortener Item', 'linkssh' ),
				// new post's text.
				'view_item'         => __( 'See Links shortener Item', 'linkssh' ),
				// for viewing this post type.
				'search_items'      => __( 'See Links shortener Item', 'linkssh' ),
				// search for these post types.
				'not_found'         => __( 'Not Found', 'linkssh' ),
				// if search has not found anything.
				'parent_item_colon' => '',
				// for parents (for hierarchical post types).
				'menu_name'         => __( 'Links shortener', 'linkssh' ),
				// menu name.
			],
			'description'   => '',
			'public'        => true,
			'show_in_menu'  => 'tools.php',
			'show_in_rest'  => false,
			'menu_position' => 80,
			'menu_icon'     => 'dashicons-book',
			//'capability_type'   => 'post',
			//'capabilities'  => 'post', // Array of additional rights for this post type.
			//'map_meta_cap'      => null, // Set to true to enable the default handler for meta caps.
			'hierarchical'  => false,
			'supports'      => [ 'custom-fields' ],
			'has_archive'   => false,
			'rewrite'       => true,
			'query_var'     => true,
		] );
	}

}

new LinksSh_CPT();