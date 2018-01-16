<?php

add_action( 'add_meta_boxes', 'wuss_images_box' );
add_action( 'save_post', 'wuss_save_meta_data' );

function wuss_images_box() {

	$screens = array('wuss_item', 'wuss_game', 'product' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'wuss_images_fields_id',
			__( 'Banners', 'wuss_textdomain' ),
			'wuss_images_fields_meta_box_callback',
			$screen,
			'advanced',
			'high'
		);
	}
}

function wuss_games_list_meta_dropdown( $post ) {

	wp_nonce_field( 'wuss_games_list_meta_box', 'wuss_games_list_meta_box_nonce' );

	$value = get_post_meta( $post->ID, '_wuss_games_list_value_key', true );
	$games = get_posts(array('post_type' => 'wuss_game', 'orderby' => 'title', 'post_status' => 'publish'));
	$options = array(0 => 'Global...');
	foreach($games as $game)
		$options[$game->ID] = $game->post_title;
	
	echo '<table width="100%" style="border:0px; padding:5px"><tr><td style="padding:5px">Available within game</td>'
	     .'<td style="padding:5px"><select style="width:200px" name="_wuss_games_list_value_field">';
	foreach($options as $option => $val)
		echo '<option value="'.$option.'" '. ($option == $value ? "selected" : "") .'>'.$val.'</option>';
	echo '</select></td></tr></table>';
}

function wuss_images_fields_meta_box_callback( $post ) {

	wp_nonce_field( 'wuss_images_meta_box', 'wuss_images_meta_box_nonce' );
	$valuel = get_post_meta( $post->ID, '_wuss_wide_banner_value_key', true );
	$valueu = get_post_meta( $post->ID, '_wuss_tall_banner_value_key', true );
	
	echo '<br><table width="100%"><tr><td align="center">';
	if ($valuel == 0)
		echo '<img style="width:150px; height:150px; border:solid 1px #666;" id="img_wuss_wide_banner_field">';
	else
	{
		$img_src = wp_get_attachment_image_url( $valuel, 'medium' );
		echo '<img src="'.$img_src.'" id="img_wuss_wide_banner_field" class="wuss_preview_banner">';
	}
	echo '</td>';
	
	echo '<td align="center">';
	if ($valueu == 0)
		echo '<img style="height:150px; width:150px; border:solid 1px #666;" id="img_wuss_tall_banner_field">';
	else
	{
		$img_src = wp_get_attachment_image_url( $valueu, 'medium' );
		echo '<img src="'.$img_src.'" id="img_wuss_tall_banner_field" class="wuss_preview_banner">';
	}
	echo '</td></tr> <tr>
	<td align="center"><input type="hidden" id="_wuss_wide_banner_field" name="_wuss_wide_banner_field" value="'.$valuel.'">
		<input class="button-secondary" type="button" target="_wuss_wide_banner_field" title="locked icon" id="_unique_wuss_button" value="Wide Banner" style="width:200px">
	</td>
	<td align="center"><input type="hidden" id="_wuss_tall_banner_field" name="_wuss_tall_banner_field" value="'.$valueu.'">
		<input class="button-secondary" type="button" target="_wuss_tall_banner_field" title="icon" id="_unique_wuss_button" value="Tall Banner" style="width:200px">
	</td>
	</tr>
	</table>';
}

function wuss_save_meta_data( $post_id ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( ! current_user_can( 'manage_wuss', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['wuss_images_meta_box_nonce'] ) && wp_verify_nonce( $_POST['wuss_images_meta_box_nonce'], 'wuss_images_meta_box' ) )
	{
		if ( isset( $_POST['_wuss_wide_banner_field'] ) )
		{
			$my_data = sanitize_text_field( $_POST['_wuss_wide_banner_field'] );
			update_post_meta( $post_id, '_wuss_wide_banner_value_key', $my_data );
		}

		if ( isset( $_POST['_wuss_tall_banner_field'] ) )
		{
			$my_data = sanitize_text_field( $_POST['_wuss_tall_banner_field'] );
			update_post_meta( $post_id, '_wuss_tall_banner_value_key', $my_data );
		}
	}
}
