<?php

/**
 * Handle the callback
 */
function oa_social_login_callback()
{
    //Callback Handler
    if (isset($_REQUEST) and !empty($_REQUEST['oa_action']) and $_REQUEST['oa_action'] == 'social_login' and !empty($_REQUEST['connection_token']))
    {
        //OneAll Connection token
        $connection_token = trim($_REQUEST['connection_token']);

        //Read settings
        $settings = get_option('oa_social_login_settings');

        //API Settings
        $api_connection_handler = ((!empty($settings['api_connection_handler']) and $settings['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
        $api_connection_use_https = ((!isset($settings['api_connection_use_https']) or $settings['api_connection_use_https'] == '1') ? true : false);
        $api_subdomain = (!empty($settings['api_subdomain']) ? trim($settings['api_subdomain']) : '');

        //We cannot make a connection without a subdomain
        if (!empty($api_subdomain))
        {
            //See: http://docs.oneall.com/api/resources/connections/read-connection-details/
            $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.com/connections/' . $connection_token . '.json';

            //API Credentials
            $api_opts = array();
            $api_opts['api_key'] = (!empty($settings['api_key']) ? $settings['api_key'] : '');
            $api_opts['api_secret'] = (!empty($settings['api_secret']) ? $settings['api_secret'] : '');

            //Retrieve connection details
            $result = oa_social_login_do_api_request($api_connection_handler, $api_resource_url, $api_opts);

            //Check result
            if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200 and property_exists($result, 'http_data'))
            {
                // Decode result.
                $decoded_result = @json_decode($result->http_data);
                if (is_object($decoded_result) and isset($decoded_result->response->result->data->user))
                {
                    // User data.
                    $user_data = $decoded_result->response->result->data->user;

                    // Social network profile data.
                    $identity = $user_data->identity;

                    // Unique user token provided by OneAll.
                    $user_token = apply_filters('oa_social_login_filter_get_user_token', $user_data->user_token);

                    // Identity Provider.
                    $user_identity_provider = $identity->source->name;

                    // Thumbnail.
                    $user_thumbnail = (!empty($identity->thumbnailUrl) ? trim($identity->thumbnailUrl) : '');

                    // Picture.
                    $user_picture = (!empty($identity->pictureUrl) ? trim($identity->pictureUrl) : '');

                    // About Me.
                    $user_about_me = (!empty($identity->aboutMe) ? trim($identity->aboutMe) : '');

                    // Note.
                    $user_note = (!empty($identity->note) ? trim($identity->note) : '');

                    // Firstname.
                    $user_first_name = (!empty($identity->name->givenName) ? $identity->name->givenName : '');

                    // Lastname.
                    $user_last_name = (!empty($identity->name->familyName) ? $identity->name->familyName : '');

                    // Fullname.
                    if (!empty($identity->name->formatted))
                    {
                        $user_full_name = $identity->name->formatted;
                    }
                    elseif (!empty($identity->name->displayName))
                    {
                        $user_full_name = $identity->name->displayName;
                    }
                    else
                    {
                        $user_full_name = trim($user_first_name . ' ' . $user_last_name);
                    }

                    // Email Address.
                    $user_email = '';
                    $user_email_is_verified = false;
                    if (property_exists($identity, 'emails') && is_array($identity->emails))
                    {
                        foreach ($identity->emails as $email)
                        {
                            if ($user_email_is_verified !== true)
                            {
                                $user_email = $email->value;
                                $user_email_is_verified = ($email->is_verified == '1');
                            }
                        }
                    }

                    // Website.
                    $user_website = '';
                    $user_websites = array();

                    // Profile URL.
                    if (!empty($identity->profileUrl))
                    {
                        $user_websites[] = trim($identity->profileUrl);
                    }

                    // Website URLs.
                    if (isset($identity->urls) && is_array($identity->urls))
                    {
                        foreach ($identity->urls as $identity_url)
                        {
                            if (!empty($identity_url->value))
                            {
                                $user_websites[] = trim($identity_url->value);
                            }
                        }
                    }

                    // Do we have any websites?
                    if (count($user_websites) > 0)
                    {
                        // Remove duplcates.
                        $user_websites = array_unique($user_websites);

                        // Compute a website to be used.
                        foreach ($user_websites as $value)
                        {
                            if (empty($user_website))
                            {
                                if (!empty($value) && strlen($value) < 100)
                                {
                                    $user_website = $value;
                                }
                            }
                        }
                    }

                    // Preferred Username.
                    if (!empty($identity->preferredUsername))
                    {
                        $user_login = $identity->preferredUsername;
                    }
                    elseif (!empty($identity->displayName))
                    {
                        $user_login = $identity->displayName;
                    }
                    else
                    {
                        $user_login = $user_full_name;
                    }

                    // New user created?
                    $new_registration = false;

                    // Sanitize Login.
                    $user_login = str_replace('.', '-', $user_login);
                    $user_login = sanitize_user($user_login, true);

                    // Get user by token.
                    $user_id = oa_social_login_get_userid_by_token($user_token);

                    // Allow override of user
                    $user_id = apply_filters ('oa_social_login_retrieve_userid', $user_id, $identity);

                    // Try to link to existing account.
                    if (!is_numeric($user_id))
                    {
                        // This is a new user!
                        $new_registration = true;

                        // Linking enabled?
                        if (!isset($settings['plugin_link_verified_accounts']) or $settings['plugin_link_verified_accounts'] == '1')
                        {
                            // Only link if email is verified.
                            if (!empty($user_email) && $user_email_is_verified === true)
                            {
                                // Read existing user.
                                if (($user_id_tmp = email_exists($user_email)) !== false)
                                {
                                    $user_data = get_userdata($user_id_tmp);
                                    if ($user_data !== false)
                                    {
                                        $user_id = $user_data->ID;
                                        $user_login = $user_data->user_login;

                                        // Refresh the meta data.
                                        delete_metadata('user', null, 'oa_social_login_user_token', $user_token, true);
                                        update_user_meta($user_id, 'oa_social_login_user_token', $user_token);
                                        update_user_meta($user_id, 'oa_social_login_identity_provider', $user_identity_provider);

                                        // Refresh the cache.
                                        wp_cache_delete($user_id, 'users');
                                        wp_cache_delete($user_login, 'userlogins');
                                    }
                                }
                            }
                        }
                    }

                    // New User?
                    if (!is_numeric($user_id))
                    {
                        // Username is mandatory.
                        if (!isset($user_login) or strlen(trim($user_login)) == 0)
                        {
                            $user_login = $user_identity_provider . 'User';
                        }

                        // BuddyPress : See bp_core_strip_username_spaces()
                        if (function_exists('bp_core_strip_username_spaces'))
                        {
                            $user_login = str_replace(' ', '-', $user_login);
                        }

                        // Username must be unique.
                        if (username_exists($user_login))
                        {
                            $i = 1;
                            $user_login_tmp = $user_login;
                            do
                            {
                                $user_login_tmp = $user_login . ($i++);
                                $user_login_tmp = apply_filters('oa_social_login_filter_new_user_generic_login', $user_login_tmp);
                            } while (username_exists($user_login_tmp));
                            $user_login = $user_login_tmp;
                        }

                        // Email Filter.
                        $user_login = apply_filters('oa_social_login_filter_new_user_login', $user_login);

                        // Email Filter.
                        $user_email = apply_filters('oa_social_login_filter_new_user_email', $user_email);

                        // Email must be unique.
                        $placeholder_email_used = false;
                        if (!is_email($user_email) || email_exists($user_email))
                        {
                            $user_email = oa_social_login_create_rand_email();
                            $user_email = apply_filters('oa_social_login_filter_new_user_random_email', $user_email);
                            $placeholder_email_used = true;
                        }

                        // Setup the user's password.
                        $user_pass = wp_generate_password();
                        $user_pass = apply_filters('oa_social_login_filter_new_user_password', $user_pass);

                        // Setup the user's role.
                        $user_role = get_option('default_role');
                        $user_role = apply_filters('oa_social_login_filter_new_user_role', $user_role);

                        // Setup the name to display.
                        $user_display_name = (!empty($user_full_name) ? $user_full_name : $user_login);

                        // Build user data.
                        $user_fields = array(

                            // User table.
                            'user_login' => substr($user_login, 0, 60),
                            'user_pass' => $user_pass,
                            'display_name' => substr($user_display_name, 0, 250),
                            'user_email' => substr($user_email, 0, 100),
                            'user_url' => substr($user_website, 0, 100),

                            // Meta mable.
                            'first_name' => $user_first_name,
                            'last_name' => $user_last_name,
                            'role' => $user_role
                        );

                        // Filter for user_data.
                        $user_fields = apply_filters('oa_social_login_filter_new_user_fields', $user_fields);

                        // Hook before adding the user.
                        do_action('oa_social_login_action_before_user_insert', $user_fields, $identity);

                        // Create a new user.
                        $user_id = wp_insert_user($user_fields);
                        if (is_numeric($user_id) and ($user_data = get_userdata($user_id)) !== false)
                        {
                            // Refresh the meta-data.
                            delete_metadata('user', null, 'oa_social_login_user_token', $user_token, true);

                            // Save the OneAll meta-data.
                            update_user_meta($user_id, 'oa_social_login_user_token', $user_token);
                            update_user_meta($user_id, 'oa_social_login_identity_provider', $user_identity_provider);

                            // Save the WordPress meta-data.
                            if (!empty($user_about_me) or !empty($user_note))
                            {
                                $user_description = (!empty($user_about_me) ? $user_about_me : $user_note);
                                update_user_meta($user_id, 'description', $user_description);
                            }

                            // Email is required.
                            if (!empty($settings['plugin_require_email']))
                            {
                                //We don't have the real email
                                if ($placeholder_email_used)
                                {
                                    update_user_meta($user_id, 'oa_social_login_request_email', 1);
                                }
                            }

                            // Notify Administrator.
                            if (!empty($settings['plugin_notify_admin']))
                            {
                                oa_social_login_user_notification($user_id, $user_identity_provider);
                            }

                            // Refresh the cache.
                            wp_cache_delete($user_id, 'users');
                            wp_cache_delete($user_login, 'userlogins');

                            // Native WordPress hook.
                            do_action('user_register', $user_id);

                            // Social Login Hook.
                            do_action('oa_social_login_action_after_user_insert', $user_data, $identity);
                        }
                    }

                    // Sucess.
                    $user_data = get_userdata($user_id);
                    if ($user_data !== false)
                    {
                        // Hooks to be used by third parties.
                        do_action('oa_social_login_action_before_user_login', $user_data, $identity, $new_registration);

                        // Update user thumbnail.
                        if (!empty($user_thumbnail))
                        {
                            update_user_meta($user_id, 'oa_social_login_user_thumbnail', $user_thumbnail);
                        }

                        // Update user picture.
                        if (!empty($user_picture))
                        {
                            update_user_meta($user_id, 'oa_social_login_user_picture', $user_picture);
                        }

                        // Set the cookie and login.
                        wp_clear_auth_cookie();
                        wp_set_auth_cookie($user_data->ID, true);
                        do_action('wp_login', $user_data->user_login, $user_data);

                        // Where did the user come from?
                        $oa_social_login_source = (!empty($_REQUEST['oa_social_login_source']) ? strtolower(trim($_REQUEST['oa_social_login_source'])) : '');

                        // Use safe redirection?
                        $redirect_to_safe = false;

                        // Build the url to redirect the user to.
                        switch ($oa_social_login_source)
                        {
                            //*************** Registration ***************
                            case 'registration':
                                // Default redirection.
                                $redirect_to = admin_url();

                                // Redirection in URL.
                                if (!empty($_GET['redirect_to']))
                                {
                                    $redirect_to = $_GET['redirect_to'];
                                    $redirect_to_safe = true;
                                }
                                else
                                {
                                    // Redirection customized.
                                    if (isset($settings['plugin_registration_form_redirect']))
                                    {
                                        switch (strtolower($settings['plugin_registration_form_redirect']))
                                        {
                                            // Current.
                                            case 'current':
                                                $redirect_to = oa_social_login_get_current_url();
                                                break;

                                            // Homepage.
                                            case 'homepage':
                                                $redirect_to = home_url();
                                                break;

                                            // Custom.
                                            case 'custom':
                                                if (isset($settings['plugin_registration_form_redirect_custom_url']) and strlen(trim($settings['plugin_registration_form_redirect_custom_url'])) > 0)
                                            {
                                                    $redirect_to = trim($settings['plugin_registration_form_redirect_custom_url']);
                                                }
                                                break;

                                            // Default/Dashboard.
                                            default:
                                            case 'dashboard':
                                                $redirect_to = admin_url();
                                                break;
                                        }
                                    }
                                }
                                break;

                            //*************** Login ***************
                            case 'login':
                                // Default redirection.
                                $redirect_to = home_url();

                                // Redirection in URL.
                                if (!empty($_GET['redirect_to']))
                                {
                                    $redirect_to = $_GET['redirect_to'];
                                    $redirect_to_safe = true;
                                }
                                else
                                {
                                    // Redirection customized.
                                    if (isset($settings['plugin_login_form_redirect']))
                                    {
                                        switch (strtolower($settings['plugin_login_form_redirect']))
                                        {
                                            // Current.
                                            case 'current':
                                                global $pagenow;

                                                // Do not redirect to the login page as this would logout the user.
                                                if (empty($pagenow) or $pagenow != 'wp-login.php')
                                            {
                                                    $redirect_to = oa_social_login_get_current_url();
                                                }
                                                // In this case just go to the homepage.
                                                else
                                            {
                                                    $redirect_to = home_url();
                                                }
                                                break;

                                            // Dashboard.
                                            case 'dashboard':
                                                $redirect_to = admin_url();
                                                break;

                                            // Custom.
                                            case 'custom':
                                                if (isset($settings['plugin_login_form_redirect_custom_url']) and strlen(trim($settings['plugin_login_form_redirect_custom_url'])) > 0)
                                            {
                                                    $redirect_to = trim($settings['plugin_login_form_redirect_custom_url']);
                                                }
                                                break;

                                            // Default/Homepage.
                                            default:
                                            case 'homepage':
                                                $redirect_to = home_url();
                                                break;
                                        }
                                    }
                                }
                                break;

                            // *************** Comments ***************
                            case 'comments':
                                $redirect_to = oa_social_login_get_current_url() . '#comments';
                                break;

                            //*************** Widget/Shortcode ***************
                            default:
                            case 'widget':
                            case 'shortcode':

                                // Is this is a new user?
                                $opt_key = ($new_registration === true ? 'register' : 'login');

                                // Default redirection.
                                $redirect_to = oa_social_login_get_current_url();

                                // Redirection customized.
                                if (isset($settings['plugin_shortcode_' . $opt_key . '_redirect']))
                                {
                                    switch (strtolower($settings['plugin_shortcode_' . $opt_key . '_redirect']))
                                    {
                                        // Current.
                                        case 'current':
                                            $redirect_to = oa_social_login_get_current_url();
                                            break;

                                        // Homepage.
                                        case 'homepage':
                                            $redirect_to = home_url();
                                            break;

                                        // Dashboard.
                                        case 'dashboard':
                                            $redirect_to = admin_url();
                                            break;

                                        // Custom.
                                        case 'custom':
                                            if (isset($settings['plugin_shortcode_' . $opt_key . '_redirect_url']) and strlen(trim($settings['plugin_shortcode_' . $opt_key . '_redirect_url'])) > 0)
                                        {
                                                $redirect_to = trim($settings['plugin_shortcode_' . $opt_key . '_redirect_url']);
                                            }
                                            break;
                                    }
                                }
                                break;
                        }

                        // Check if url set.
                        if (!isset($redirect_to) or strlen(trim($redirect_to)) == 0)
                        {
                            $redirect_to = home_url();
                        }

                        // New User -> Registration.
                        if ($new_registration === true)
                        {
                            // Apply the WordPress filters.
                            if (empty($settings['plugin_protect_registration_redirect_url']))
                            {
                                $redirect_to = apply_filters('registration_redirect', $redirect_to);
                            }

                            // Apply our filters.
                            $redirect_to = apply_filters('oa_social_login_filter_registration_redirect_url', $redirect_to, $user_data);
                        }
                        // Existing User -> Login.
                        else
                        {
                            // Apply the WordPress filters.
                            if (empty($settings['plugin_protect_login_redirect_url']))
                            {
                                $redirect_to = apply_filters('login_redirect', $redirect_to, (!empty($_GET['redirect_to']) ? $_GET['redirect_to'] : ''), $user_data);
                            }

                            // Apply our filters.
                            $redirect_to = apply_filters('oa_social_login_filter_login_redirect_url', $redirect_to, $user_data);
                        }

                        // Hooks for other plugins.
                        do_action('oa_social_login_action_before_user_redirect', $user_data, $identity, $redirect_to);

                        // Use safe redirection?
                        if ($redirect_to_safe === true)
                        {
                            wp_safe_redirect($redirect_to);
                        }
                        else
                        {
                            wp_redirect($redirect_to);
                        }
                        exit();
                    }
                }
                else
                {
                    oa_social_login_log('Callback failed, invalid data ' . $result->http_code);
                }
            }
            else
            {
                oa_social_login_log('Callback failed, HTTP code');
            }
        }
    }
}

/**
 * Send an API request by using the given handler
 */
function oa_social_login_do_api_request($handler, $url, $opts = array(), $timeout = 25)
{
    // Proxy Settings
    if (defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT'))
    {
        $opts['proxy_url'] = (defined('WP_PROXY_HOST') ? WP_PROXY_HOST : '');
        $opts['proxy_port'] = (defined('WP_PROXY_PORT') ? WP_PROXY_PORT : '');
        $opts['proxy_username'] = (defined('WP_PROXY_USERNAME') ? WP_PROXY_USERNAME : '');
        $opts['proxy_password'] = (defined('WP_PROXY_PASSWORD') ? WP_PROXY_PASSWORD : '');
    }

    //FSOCKOPEN
    if ($handler == 'fsockopen')
    {
        return oa_social_login_fsockopen_request($url, $opts, $timeout);
    }
    //CURL
    else
    {
        return oa_social_login_curl_request($url, $opts, $timeout);
    }
}

/**
 * **************************************************************************************************************
 * ************************************************* FSOCKOPEN **************************************************
 * **************************************************************************************************************
 */

/**
 * Check if fsockopen is available.
 */
function oa_social_login_check_fsockopen_available()
{
    //Make sure fsockopen has been loaded
    if (function_exists('fsockopen') and function_exists('fwrite'))
    {
        $disabled_functions = oa_social_login_get_disabled_functions();

        //Make sure fsockopen has not been disabled
        if (!in_array('fsockopen', $disabled_functions) and !in_array('fwrite', $disabled_functions))
        {
            //Loaded and enabled
            return true;
        }
    }

    //Not loaded or disabled

    return false;
}

/**
 * Check if fsockopen is enabled and can be used to connect to OneAll.
 */
function oa_social_login_check_fsockopen($secure = true)
{
    if (oa_social_login_check_fsockopen_available())
    {
        $result = oa_social_login_do_api_request('fsockopen', ($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
        if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
                {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Send an fsockopen request.
 */
function oa_social_login_fsockopen_request($url, $options = array(), $timeout = 15)
{
    //Store the result
    $result = new stdClass();

    //Make sure that this is a valid URL
    if (($uri = parse_url($url)) === false)
    {
        $result->http_error = 'invalid_uri';

        return $result;
    }

    //Check the scheme
    if ($uri['scheme'] == 'https')
    {
        $port = (isset($uri['port']) ? $uri['port'] : 443);
        $url = ($uri['host'] . ($port != 443 ? ':' . $port : ''));
        $url_protocol = 'https://';
        $url_prefix = 'ssl://';
    }
    else
    {
        $port = (isset($uri['port']) ? $uri['port'] : 80);
        $url = ($uri['host'] . ($port != 80 ? ':' . $port : ''));
        $url_protocol = 'http://';
        $url_prefix = '';
    }

    //Construct the path to act on
    $path = (isset($uri['path']) ? $uri['path'] : '/') . (!empty($uri['query']) ? ('?' . $uri['query']) : '');

    //HTTP Headers
    $headers = array();

    // We are using a proxy
    if (!empty($options['proxy_url']) && !empty($options['proxy_port']))
    {
        // Open Socket
        $fp = @fsockopen($options['proxy_url'], $options['proxy_port'], $errno, $errstr, $timeout);

        //Make sure that the socket has been opened properly
        if (!$fp)
        {
            $result->http_error = trim($errstr);

            return $result;
        }

        // HTTP Headers
        $headers[] = "GET " . $url_protocol . $url . $path . " HTTP/1.0";
        $headers[] = "Host: " . $url . ":" . $port;

        // Proxy Authentication
        if (!empty($options['proxy_username']) && !empty($options['proxy_password']))
        {
            $headers[] = 'Proxy-Authorization: Basic ' . base64_encode($options['proxy_username'] . ":" . $options['proxy_password']);
        }
    }
    // We are not using a proxy
    else
    {
        // Open Socket
        $fp = @fsockopen($url_prefix . $url, $port, $errno, $errstr, $timeout);

        //Make sure that the socket has been opened properly
        if (!$fp)
        {
            $result->http_error = trim($errstr);

            return $result;
        }

        // HTTP Headers
        $headers[] = "GET " . $path . " HTTP/1.0";
        $headers[] = "Host: " . $url;
    }

    //Enable basic authentication
    if (isset($options['api_key']) and isset($options['api_secret']))
    {
        $headers[] = 'Authorization: Basic ' . base64_encode($options['api_key'] . ":" . $options['api_secret']);
    }

    //Build and send request
    fwrite($fp, (implode("\r\n", $headers) . "\r\n\r\n"));

    //Fetch response
    $response = '';
    while (!feof($fp))
    {
        $response .= fread($fp, 1024);
    }

    //Close connection
    fclose($fp);

    //Parse response
    list($response_header, $response_body) = explode("\r\n\r\n", $response, 2);

    //Parse header
    $response_header = preg_split("/\r\n|\n|\r/", $response_header);
    list($header_protocol, $header_code, $header_status_message) = explode(' ', trim(array_shift($response_header)), 3);

    //Build result
    $result->http_code = $header_code;
    $result->http_data = $response_body;

    //Done

    return $result;
}

/**
 * **************************************************************************************************************
 ** *************************************************** CURL ****************************************************
 * **************************************************************************************************************
 */

/**
 * Check if cURL has been loaded and is enabled.
 */
function oa_social_login_check_curl_available()
{
    //Make sure cURL has been loaded
    if (in_array('curl', get_loaded_extensions()) and function_exists('curl_init') and function_exists('curl_exec'))
    {
        $disabled_functions = oa_social_login_get_disabled_functions();

        //Make sure cURL not been disabled
        if (!in_array('curl_init', $disabled_functions) and !in_array('curl_exec', $disabled_functions))
        {
            //Loaded and enabled
            return true;
        }
    }

    //Not loaded or disabled

    return false;
}

/**
 * Check if CURL is available and can be used to connect to OneAll
 */
function oa_social_login_check_curl($secure = true)
{
    if (oa_social_login_check_curl_available())
    {
        $result = oa_social_login_do_api_request('curl', ($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
        if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
                {
                    return true;
                }
            }
        }
    }

    return false;
}

/**
 * Send a CURL request.
 */
function oa_social_login_curl_request($url, $options = array(), $timeout = 15)
{
    //Store the result
    $result = new stdClass();

    //Send request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, 'SocialLogin/' . OA_SOCIAL_LOGIN_VERSION . ' WordPress/' . oa_social_login_get_wp_version() . ' (+http://www.oneall.com/)');

    // BASIC AUTH?
    if (isset($options['api_key']) and isset($options['api_secret']))
    {
        curl_setopt($curl, CURLOPT_USERPWD, $options['api_key'] . ":" . $options['api_secret']);
    }

    // Proxy Settings
    if (!empty($options['proxy_url']) && !empty($options['proxy_port']))
    {
        // Proxy Location
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($curl, CURLOPT_PROXY, $options['proxy_url']);

        // Proxy Port
        curl_setopt($curl, CURLOPT_PROXYPORT, $options['proxy_port']);

        // Proxy Authentication
        if (!empty($options['proxy_username']) && !empty($options['proxy_password']))
        {
            curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $options['proxy_username'] . ':' . $options['proxy_password']);
        }
    }

    //Make request
    if (($http_data = curl_exec($curl)) !== false)
    {
        $result->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result->http_data = $http_data;
        $result->http_error = null;
    }
    else
    {
        $result->http_code = -1;
        $result->http_data = null;
        $result->http_error = curl_error($curl);
    }

    //Done

    return $result;
}
