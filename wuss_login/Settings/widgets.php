<?php
// Creating the widget
class wuss_games_list_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'wuss_games_list_widget',
			__('WUSS Games', 'wuss_domain'),
			array( 'description' => __( 'Displays all the games available on this site', 'wuss_domain' ), )
		);
	}

// Widget front-end
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		$games = new WussGames();
		$link_path = get_option("wuss_product_page_url");
		$link_path .= (strpos($link_path,"?") > 0) ? '&gid=' : '?gid=';

		$last_game = $games->GameCount() - 1;
		if ($last_game >= 0) {
			foreach ( $games->games->posts as $game ) {
				echo '<a href="'.$link_path.$game->ID.'">' . $game->post_title . '</a>';
				if ($game != $games->games->posts[$last_game])
					echo '<br>';
			}
		} else echo 'No games found';
		echo $args['after_widget'];
	}

// Widget Backend
	public function form( $instance )
	{
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'wuss_domain' );
		}
// Widget admin form
		?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php
			echo $this->get_field_id( 'title' ); ?>" name="<?php
			echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
		<?php
	}

// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}

} // Class wuss_games_list_widget ends here

// Register and load the widget
function wuss_login_widgets() {
	register_widget( 'wuss_games_list_widget' );
}
add_action( 'widgets_init', 'wuss_login_widgets' );
