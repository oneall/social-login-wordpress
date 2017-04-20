<?php
/*
Plugin Name: Social Login
Plugin URI: http://www.oneall.com/
Description: Allow your visitors to <strong>comment, login and register with 30+ social networks</strong> like Twitter, Facebook, LinkedIn, Instagram, Вконтакте, Google or Yahoo.
Version: 5.3
Author: Claude Schlesser
Author URI: http://www.oneall.com/
License: GPL2
 */

define ('OA_SOCIAL_LOGIN_PLUGIN_URL', plugins_url () . '/' . basename (dirname (__FILE__)));
define ('OA_SOCIAL_LOGIN_BASE_PATH', dirname (plugin_basename (__FILE__)));
define ('OA_SOCIAL_LOGIN_VERSION', '5.3');

/**
 * Check technical requirements before activating the plugin (Wordpress 3.0 or newer required)
 */
function oa_social_login_activate ()
{
	if (!function_exists ('register_post_status'))
	{
		deactivate_plugins (basename (dirname (__FILE__)) . '/' . basename (__FILE__));
		echo sprintf (__ ('This plugin requires WordPress %s or newer. Please update your WordPress installation to activate this plugin.', 'oa_social_login'), '3.0');
		exit;
	}
	update_option ('oa_social_login_activation_message', 0);
}
register_activation_hook (__FILE__, 'oa_social_login_activate');


/**
 * Add Setup Link
 **/
function oa_social_login_add_setup_link ($links, $file)
{
	static $oa_social_login_plugin = null;

	if (is_null ($oa_social_login_plugin))
	{
		$oa_social_login_plugin = plugin_basename (__FILE__);
	}

	if ($file == $oa_social_login_plugin)
	{
		$settings_link = '<a href="admin.php?page=oa_social_login_setup">' . __ ('Setup', 'oa_social_login') . '</a>';
		array_unshift ($links, $settings_link);
	}
	return $links;
}
add_filter ('plugin_action_links', 'oa_social_login_add_setup_link', 10, 2);


/**
 * This file only has to be included for versions before 3.1.
 * Deprecated since version 3.1, the functions are included by default
 */
if (!function_exists ('email_exists'))
{
	require_once(ABSPATH . WPINC . '/registration.php');
}


/**
 * Include required files
 */
require_once(dirname (__FILE__) . '/includes/settings.php');
require_once(dirname (__FILE__) . '/includes/communication.php');
require_once(dirname (__FILE__) . '/includes/toolbox.php');
require_once(dirname (__FILE__) . '/includes/admin.php');
require_once(dirname (__FILE__) . '/includes/user_interface.php');
require_once(dirname (__FILE__) . '/includes/widget.php');


/**
 * Initialise
 */
add_action ('init', 'oa_social_login_init', 9);
