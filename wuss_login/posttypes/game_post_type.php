<?php
function wuss_games_post() {
	$labels = array(
		'name'                => _x( 'WUSS Game', 'Post Type General Name', 'text_domain' ),
		'singular_name'       => _x( 'WUSS Game', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'           => __( 'WUSS Games', 'text_domain' ),
		'parent_item_colon'   => __( 'Parent Game:', 'text_domain' ),
		'all_items'           => __( 'All Games', 'text_domain' ),
		'view_item'           => __( 'View WUSS Game', 'text_domain' ),
		'add_new_item'        => __( 'Add New WUSS Game', 'text_domain' ),
		'add_new'             => __( 'Add New', 'text_domain' ),
		'edit_item'           => __( 'Edit WUSS Game', 'text_domain' ),
		'update_item'         => __( 'Update WUSS Game', 'text_domain' ),
		'search_items'        => __( 'Search WUSS Game', 'text_domain' ),
		'not_found'           => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'  => __( 'Not found in Trash', 'text_domain' ),
	);
	$rewrite = array(
		'slug'                => 'wuss_game',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);
	$args = array(
        'label'               => __( 'wuss_game', 'text_domain' ),
        'description'         => __( 'WUSS Games', 'text_domain' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
		'hierarchical'        => true,
		'public'              => true,
		'show_ui'             => true,
        'show_in_menu'        => "wuss_framework",
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'can_export'          => false,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
	);
	register_post_type( 'wuss_game', $args );
}

add_action( 'init', 'wuss_games_post', 0 );


if (is_multisite())
	add_action('network_admin_menu', 'add_wuss_games_submenu');

function add_wuss_games_submenu() {
	add_submenu_page( 'wuss_framework', 'wuss_games_overview', 'Wuss Games', 'manage-wuss', admin_url('/edit.php?post_type=wuss_game'), null );
}

add_action( 'add_meta_boxes', 'wuss_game_select_box' );
add_action( 'save_post', 'wuss_save_game_select_meta_data' );

function wuss_game_select_box() {

	$screens = array('wuss_consumable', 'wuss_persist' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'wuss_game_select_id',
			__( 'Linked game', 'wuss_textdomain' ),
			'wuss_game_select_meta_box_callback',
			$screen,
			'side'
		);
	}
}

function wuss_game_select_meta_box_callback( $post ) {
	$games = new WussGames(true);
	wp_nonce_field( 'wuss_game_select_meta_box', 'wuss_game_select_meta_box_nonce' );
	$value = get_post_meta( $post->ID, '_wuss_game_select_value_key', true );
	echo '<br><table width="100%"><tr><td align="center">';
	echo $games->GamesDropDownList($value, true, "_wuss_game_select_field", "All games");
	echo '</td></tr></table>';
}

function wuss_save_game_select_meta_data( $post_id ) {

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( ! current_user_can( 'manage_wuss', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['wuss_game_select_meta_box_nonce'] ) && wp_verify_nonce( $_POST['wuss_game_select_meta_box_nonce'], 'wuss_game_select_meta_box' ) )
	{
		if ( isset( $_POST['_wuss_game_select_field'] ) )
		{
			$my_data = sanitize_text_field( $_POST['_wuss_game_select_field'] );
			update_post_meta( $post_id, '_wuss_game_select_value_key', $my_data );
		}
	}
}