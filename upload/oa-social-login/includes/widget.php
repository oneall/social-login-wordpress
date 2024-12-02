<?php

/**
 * Social Login \ Widget
 */
class oa_social_login_widget extends WP_Widget
{
	/**
	 * Constructor.
	 */
	public function __construct ()
	{
		parent::__construct ('oa-social-login', 'Social Login', array (
			'description' => __ ('Allows your users to login and register with their social network accounts.', 'oa-social-login')
		));
	}

	/**
	 *  Display the widget itself.
	 */
	public function widget ($args, $instance)
	{
		//Hide the widget for logged in users?
		if (empty ($instance ['widget_hide_for_logged_in_users']) OR !is_user_logged_in ())
		{
            // Before widget
            /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			echo $args ['before_widget'];

			//Title
			if (!empty ($instance ['widget_title']))
			{
                /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
				echo $args ['before_title'] . apply_filters ('widget_title', $instance ['widget_title']) . $args ['after_title'];
			}

			//Before Content
			if (!empty ($instance ['widget_content_before']))
			{
                /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
				echo $instance ['widget_content_before'];
			}

			//Content
            /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			echo oa_social_login_render_login_form ('widget', $instance);

			//After Content
			if (!empty ($instance ['widget_content_after']))
			{
                /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
				echo $instance ['widget_content_after'];
			}

			//After Widget
            /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			echo $args ['after_widget'];
		}
	}

	/**
	 * Display the widget settings.
	 */
	public function form ($instance)
	{
		//Default settings
		$default_settings = array (
			'widget_title' => __ ('Connect with', 'oa-social-login') . ':',
		    'widget_icon_theme' => '0',
			'widget_content_before' => '',
			'widget_content_after' => '',
			'widget_hide_for_logged_in_users' => '1'
		);

		foreach ($instance as $key => $value)
		{
			$instance [$key] = oa_social_login_esc_attr ($value);
		}

		$instance = wp_parse_args ((array) $instance, $default_settings);
		?>
			<p>
				<label for="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_title'); ?>"><?php esc_html_e ('Title', 'oa-social-login'); ?>:</label>
				<input class="widefat" id="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_title'); ?>" name="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_name ('widget_title'); ?>" type="text" value="<?php echo $instance ['widget_title']; ?>" />
			</p>
			<p>
				<label for="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_icon_theme'); ?>"><?php esc_html_e ('Icon Theme', 'oa-social-login'); ?>:</label>
				<select class="widefat" id="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_icon_theme'); ?>" name="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_name ('widget_icon_theme'); ?>">
				    <option value="0"<?php echo ($instance ['widget_icon_theme'] == 0 ? ' selected="selected"' : ''); ?>><?php esc_html_e ('Classic Icons', 'oa-social-login'); ?></option>
				    <option value="1"<?php echo ($instance ['widget_icon_theme'] == 1 ? ' selected="selected"' : ''); ?>><?php esc_html_e ('Modern Icons', 'oa-social-login'); ?></option>
				    <option value="2"<?php echo ($instance ['widget_icon_theme'] == 2 ? ' selected="selected"' : ''); ?>><?php esc_html_e ('Small Icons', 'oa-social-login'); ?></option>
				</select>
			</p>
			<p>
				<label for="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_content_before'); ?>"><?php esc_html_e ('Insert text/html to add before the widget', 'oa-social-login'); ?>:</label>
				<textarea class="widefat" id="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_content_before'); ?>" name="<?php echo $this->get_field_name ('widget_content_before'); ?>"><?php echo $instance ['widget_content_before']; ?></textarea>
			</p>
			<p>
				<label for="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_content_after'); ?>"><?php esc_html_e ('Insert text/html to add after the widget', 'oa-social-login'); ?>:</label>
				<textarea class="widefat" id="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_content_after'); ?>" name="<?php echo $this->get_field_name ('widget_content_after'); ?>"><?php echo $instance ['widget_content_after']; ?></textarea>
			</p>
			<p>
				<input type="checkbox" id="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_hide_for_logged_in_users', 'oa-social-login'); ?>" name="<?php echo $this->get_field_name ('widget_hide_for_logged_in_users'); ?>" type="text" value="1" <?php echo (!empty ($instance ['widget_hide_for_logged_in_users']) ? 'checked="checked"' : ''); ?> />
				<label for="<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ echo $this->get_field_id ('widget_hide_for_logged_in_users'); ?>"><?php esc_html_e ('Tick to hide the widget for logged-in users', 'oa-social-login'); ?></label>
			</p>
		<?php
	}


	/**
	 * Update the widget settings.
	 */
	public function update ($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance ['widget_title'] = trim (wp_strip_all_tags ($new_instance ['widget_title']));
		$instance ['widget_content_before'] = trim ($new_instance ['widget_content_before']);
		$instance ['widget_content_after'] = trim ($new_instance ['widget_content_after']);
		$instance ['widget_hide_for_logged_in_users'] = (empty ($new_instance ['widget_hide_for_logged_in_users']) ? 0 : 1);
		$instance ['widget_icon_theme'] = $new_instance ['widget_icon_theme'];
		return $instance;
	}
}

/**
 * Social Login \ Initialise widget.
 */
function oa_social_login_init_widget ()
{
    return register_widget('oa_social_login_widget');
}
add_action ('widgets_init', 'oa_social_login_init_widget');