<?php

class KMZ_Favorite_Widget extends WP_Widget{

	// Setup widget
	public function __construct(){
		$args = array(
			'classname' => 'favorite_posts',
			'description' => 'Display block with user\'s favorite posts'
		);
		parent::__construct('favorite_posts', 'Favorite Posts', $args);
	}

	// Display widget in front end
	public function widget($args, $instance){
		if( !is_user_logged_in() );
		echo $args['before_widget'];
			echo $args['before_title'];
				echo $instance['title'];
			echo $args['after_title'];
			kmz_widget_content();
		echo $args['after_widget'];
	}
	// Form widget in admin area
	public function form($instance){
		extract($instance);
		$title = !empty($title) ? esc_attr($title) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title')?>">Title</label>
			<input type="text" name="<?php echo $this->get_field_name('title'); ?>" id="<?php echo $this->get_field_id('title')?>" value="<?php echo $title ?>" class="widefat">
		</p>
		<?php
	}

	// Save/update widget data

}

