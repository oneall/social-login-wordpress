<?php

/**
 * Social Login Widget
 */
class oa_social_login_widget extends WP_Widget
{
	/**
	 * Constructor
	 */
	public function __construct ()
	{
		parent::__construct ('oa_social_login', 'Social Login', array (
			'description' => __ ('Allow your visitors to login and register with social networks like Twitter, Facebook, LinkedIn, Hyves, Google and Yahoo.', 'oa_social_login')
		));
	}

	/**
	 *  Display the widget
	 */
	public function widget ($args, $instance)
	{
		//Hide the widget for logged in users?
		if (empty ($instance ['widget_hide_for_logged_in_users']) OR !is_user_logged_in ())
		{
			//Before Widget
			echo $args ['before_widget'];

			//Title
			if (!empty ($instance ['widget_title']))
			{
				echo $args ['before_title'] . apply_filters ('widget_title', $instance ['widget_title']) . $args ['after_title'];
			}

			//Before Content
			if (!empty ($instance ['widget_content_before']))
			{
				echo $instance ['widget_content_before'];
			}

			//Content
			echo oa_social_login_render_login_form ('widget', $instance);

			//After Content
			if (!empty ($instance ['widget_content_after']))
			{
				echo $instance ['widget_content_after'];
			}

			//After Widget
			echo $args ['after_widget'];
		}
	}

	/**
	 * Show Widget Settings
	 */
	public function form ($instance)
	{
		//Default settings
		$default_settings = array (
			'widget_title' => __ ('Connect with', 'oa_social_login') . ':',
			'widget_content_before' => '',
			'widget_content_after' => '',
			'widget_use_small_buttons' => '0',
			'widget_hide_for_logged_in_users' => '1'
		);

		foreach ($instance as $key => $value)
		{
			$instance [$key] = oa_social_login_esc_attr ($value);
		}

		$instance = wp_parse_args ((array) $instance, $default_settings);
		?>
			<p>
				<label for="<?php echo $this->get_field_id ('widget_title'); ?>"><?php _e ('Title', 'oa_social_login'); ?>:</label>
				<input class="widefat" id="<?php echo $this->get_field_id ('widget_title'); ?>" name="<?php echo $this->get_field_name ('widget_title'); ?>" type="text" value="<?php echo $instance ['widget_title']; ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id ('widget_content_before'); ?>"><?php _e ('Insert text/html to add before the widget', 'oa_social_login'); ?>:</label>
				<textarea class="widefat" id="<?php echo $this->get_field_id ('widget_content_before'); ?>" name="<?php echo $this->get_field_name ('widget_content_before'); ?>"><?php echo $instance ['widget_content_before']; ?></textarea>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id ('widget_content_after'); ?>"><?php _e ('Insert text/html to add after the widget', 'oa_social_login'); ?>:</label>
				<textarea class="widefat" id="<?php echo $this->get_field_id ('widget_content_after'); ?>" name="<?php echo $this->get_field_name ('widget_content_after'); ?>"><?php echo $instance ['widget_content_after']; ?></textarea>
			</p>
			<p>
				<input type="checkbox" id="<?php echo $this->get_field_id ('widget_hide_for_logged_in_users', 'oa_social_login'); ?>" name="<?php echo $this->get_field_name ('widget_hide_for_logged_in_users'); ?>" type="text" value="1" <?php echo (!empty ($instance ['widget_hide_for_logged_in_users']) ? 'checked="checked"' : ''); ?> />
				<label for="<?php echo $this->get_field_id ('widget_hide_for_logged_in_users'); ?>"><?php _e ('Tick to hide widget for logged-in users', 'oa_social_login'); ?></label>
			</p>
			<p>
				<input type="checkbox" id="<?php echo $this->get_field_id ('widget_use_small_buttons', 'oa_social_login'); ?>" name="<?php echo $this->get_field_name ('widget_use_small_buttons'); ?>" type="text" value="1" <?php echo (!empty ($instance ['widget_use_small_buttons']) ? 'checked="checked"' : ''); ?> />
				<label for="<?php echo $this->get_field_id ('widget_use_small_buttons'); ?>"><?php _e ('Tick to use small buttons', 'oa_social_login'); ?></label>
			</p>
		<?php
	}


	/**
	 * Update Widget Settings
	 */
	public function update ($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance ['widget_title'] = trim (strip_tags ($new_instance ['widget_title']));
		$instance ['widget_content_before'] = trim ($new_instance ['widget_content_before']);
		$instance ['widget_content_after'] = trim ($new_instance ['widget_content_after']);
		$instance ['widget_hide_for_logged_in_users'] = (empty ($new_instance ['widget_hide_for_logged_in_users']) ? 0 : 1);
		$instance ['widget_use_small_buttons'] = (empty ($new_instance ['widget_use_small_buttons']) ? 0 : 1);
		return $instance;
	}
}

add_action ('widgets_init', create_function ('', 'return register_widget( "oa_social_login_widget" );'));