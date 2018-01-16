<?php
// Creating the widget
class wuss_scoring_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'wuss_scoring_widget',
			__('WUSS High Scores', 'wuss_domain'),
			array( 'description' => __( 'Displays high scores of WUSS games', 'wuss_domain' ), )
		);
	}

// Widget front-end
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];

		$args['widget'] = 'true';
		$args['showname'] = $instance[ 'showname' ] ? 'true' : 'false';
		$args['random'] = $instance[ 'random' ] ? 'true' : 'false';
		$args['sort_order'] = $instance[ 'order' ] ? 'DESC' : 'ASC';
		$args['width'] = $instance[ 'width' ];
		$args['limit'] = $instance[ 'limit' ];

		$gid = $instance['gameid'];
		if ('' == $gid || intval($gid)  < 1)
			$gid = Postedi('gid');
		if ($gid == 0)
		{
			$args['random'] = 'true';
		}
		$args['gid'] = $gid;

		echo _wuss_generate_leaderboard($args);
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

		if ( isset( $instance[ 'gameid' ] ) ) {
			$gameid = $instance[ 'gameid' ];
		}
		else {
			$gameid = '0';
		}

		if ( isset( $instance[ 'width' ] ) ) {
			$width = $instance[ 'width' ];
		}
		else {
			$width = '200';
		}

		if ( isset( $instance[ 'limit' ] ) ) {
			$limit = $instance[ 'limit' ];
		}
		else {
			$limit = '5';
		}

// Widget admin form
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'title' ); ?>" name="<?php
			echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /><br>

			<label for="<?php echo $this->get_field_id( 'gameid' ); ?>"><?php _e('Game To Show:'); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'gameid' ); ?>" name="<?php
			echo $this->get_field_name( 'gameid' ); ?>" type ="number" min="0" value="<?php echo esc_attr($gameid); ?>" /><br>

			<label for="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e('Width:'); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'width' ); ?>" name="<?php
			echo $this->get_field_name( 'width' ); ?>" type ="number" min="200" value="<?php echo esc_attr($width); ?>" /><br>

			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e('Limit:'); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'limit' ); ?>" name="<?php
			echo $this->get_field_name( 'limit' ); ?>" type ="number" min="3" value="<?php echo esc_attr($limit); ?>" /><br>

			<label for="<?php echo $this->get_field_id( 'random' ); ?>"><?php _e('Random Game:'); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'random' ); ?>" name="<?php
			echo $this->get_field_name( 'random' ); ?>" type="checkbox" <?php checked( $instance[ 'random' ], 'on' ); ?> /><br>

			<label for="<?php echo $this->get_field_id( 'showname' ); ?>"><?php _e('Show Game Title:'); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'showname' ); ?>" name="<?php
			echo $this->get_field_name( 'showname' ); ?>" type="checkbox" <?php checked( $instance[ 'showname' ], 'on' ); ?> /><br>

			<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e('Order High To Low:'); ?></label>
			<input class="widefat" id="<?php
			echo $this->get_field_id( 'order' ); ?>" name="<?php
			echo $this->get_field_name( 'order' ); ?>" type="checkbox" <?php checked( $instance[ 'order' ], 'on' ); ?> /><br>
		</p>
		<?php
	}

// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance[ 'title' ] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance[ 'gameid' ] = ( ! empty( $new_instance['gameid'] ) ) ? strip_tags( $new_instance['gameid'] ) : '0';
		$instance[ 'width' ] = ( ! empty( $new_instance['width'] ) ) ? strip_tags( $new_instance['width'] ) : '200';
		$instance[ 'limit' ] = ( ! empty( $new_instance['limit'] ) ) ? strip_tags( $new_instance['limit'] ) : '5';
		$instance[ 'random' ] = $new_instance[ 'random' ];
		$instance[ 'showname' ] = $new_instance[ 'showname' ];
		$instance[ 'order' ] = $new_instance[ 'order' ];

		if (!is_numeric(($instance['gameid']))) $instance['gameid'] = 0;
		if (!is_numeric(($instance['width']))) $instance['width'] = 200;
		if (!is_numeric(($instance['limit']))) $instance['limit'] = 5;
		return $instance;
	}

} // Class wuss_games_list_widget ends here

// Register and load the widget
function wuss_scoring_widgets() {
	register_widget( 'wuss_scoring_widget' );
}
add_action( 'widgets_init', 'wuss_scoring_widgets' );
