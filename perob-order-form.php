<?php
/*
Plugin Name: Perob Order Form Plugin
Plugin URI:  https://perob.vn/
Description: Perob order form is plugin of wordpress. It's allow admin of wordpress site add Order Form will make sure all client info will pass to Perob CRM
Version:     1.0
Author:      Trang Nguyen
Author URI:  https://trangnn.com
Text Domain: perob
License:     GPL2

{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {License URI}.
 */

if (!class_exists('PerobOrderFormPlugin')) {
    class PerobOrderFormPlugin
    {
        const VERSION = '1.0';

        // Admin page
        const PEROB_SETTING_DBKEY = 'perob_options';
        const PEROB_SETTING_PAGE_NAME = 'perob-setting';
        const PEROB_SETTING_TITLE = 'PEROB CRM order form config';
        const PEROB_SETTING_DESCRIPTION = '';
        const PEROB_CRM_SECTION = 'perob_section_crm';
        const PEROB_FIELD_PREFIX = 'perob_field_';
        const PEROB_FIELDS = [
            'endpoint' => [
                'title' => 'API Endpoint',
                'description' => 'This api endpoint to add Potential Customer to CRM'
            ],
            'token' => [
                'title' => 'API TOKEN',
                'description' => 'This token get received from CRM system'
            ],
            'default_product_code' => [
                'title' => 'Default Product',
                'description' => 'If you use shortcode [perob_form] without attribute product_code, this config will used by default.'
            ],
            'utm' => [
                'title' => 'Default UTM Link',
                'description' => 'This link will be used as default utml_link to detect marketing campain on CRM for all potential customer come from this site'
            ],
            'source' => [
                'title' => 'Marketing Source',
                'description' => 'This source be set to all potential customer come from this site on CRM'
            ],
            'use_recaptcha' => [
                'type' => 'select',
                'title' => 'Use reCaptcha',
                'description' => 'Use <a href="https://developers.google.com/recaptcha/intro" target="_blank">Google reCaptcha</a> to protects you against spam and other types of automated abuse',
                'options' => ['Yes', 'No']
            ],
            'recaptcha_site_key' => [
                'title' => 'reCaptcha SITE KEY',
                'description' => 'Google reCaptcha Site key'
            ],
            'recaptcha_secret' => [
                'title' => 'reCaptcha SECRET',
                'description' => 'Google reCaptcha Secret'
            ],
            'submit_via' => [
                'type' => 'select',
                'title' => 'Submit Via',
                'description' => 'Form will use form submit or ajax request to post data.',
                'options' => ['ajax', 'form']
            ],
            'redirect_to' => [
                'title' => 'Redirect To',
                'description' => 'Address that client will be redirected to after form post success'
            ]
        ];

        // Client Page
        const PEROB_SHORTCODE = 'perobform';
        const PEROB_NONCE_SALT = 'perob!@#$';
        const PEROB_ASSETS_NAME = 'perob-assets';

        public static $id = 1;

        public static function init()
        {
            /**
             * register our tag to the admin_menu action hook
             */
            add_shortcode(self::PEROB_SHORTCODE, 'PerobOrderFormPlugin::shortcode_order_form');

            /**
             * register our admin_init action hook
             */
            add_action('admin_init', 'PerobOrderFormPlugin::admin_init');


            /**
             * register our admin_menu action hook
             */
            add_action('admin_menu', 'PerobOrderFormPlugin::admin_menu');

            add_action('wp_enqueue_scripts', 'PerobOrderFormPlugin::add_scripts_and_styles');

            /**
             * register our admin_post action hook
             * For handle post to this form
             */
            add_action('admin_post_nopriv_' . self::PEROB_SHORTCODE, 'PerobOrderFormPlugin::handle_post');
            add_action('admin_post_' . self::PEROB_SHORTCODE, 'PerobOrderFormPlugin::handle_post');
            add_action('wp_ajax_nopriv_' . self::PEROB_SHORTCODE, 'PerobOrderFormPlugin::handle_post');
            add_action('wp_ajax_' . self::PEROB_SHORTCODE, 'PerobOrderFormPlugin::handle_post');
        }

        public static function add_scripts_and_styles()
        {
            $config = get_option(self::PEROB_SETTING_DBKEY);

            // Register the JS file with a unique handle, file location, and an array of dependencies
            wp_register_script(self::PEROB_ASSETS_NAME, plugin_dir_url(__FILE__) . 'assets/js.js', ['jquery'], self::VERSION);
            // enqueue jQuery library and the script you registered above
            wp_enqueue_script(self::PEROB_ASSETS_NAME);

            // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
            wp_localize_script(self::PEROB_ASSETS_NAME, 'perob_vars', [
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::PEROB_NONCE_SALT),
                'form' => '.' . self::PEROB_SHORTCODE,
                'use_ajax' => $config['submit_via'] == 'ajax' ? 1 : 0
            ]);

            wp_register_style(self::PEROB_ASSETS_NAME, plugin_dir_url(__FILE__) . 'assets/css.css', [], self::VERSION);
            wp_enqueue_style(self::PEROB_ASSETS_NAME);
        }

        public static function handle_post()
        {
            if (!wp_verify_nonce($_POST['_wpnonce'], self::PEROB_NONCE_SALT)) {
                self::end_post_handle(false, 'You do not have permission!');
                return;
            }
            $perob_options = get_option(self::PEROB_SETTING_DBKEY);
            if (
                $perob_options['use_recaptcha'] == 'Yes' &&
                $perob_options['recaptcha_site_key'] && $perob_options['recaptcha_secret']
            ) {
                $response_data = self::get_data_from_api_by_curl_post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    [
                        'secret' => $perob_options['recaptcha_secret'],
                        'response' => $_POST['recaptcha']
                    ]
                );

                if(!$response_data['success'] || $response_data['action'] != self::PEROB_SHORTCODE) {
                    self::end_post_handle(false, 'Robot verification failed, please try again. ' . json_encode($response_data));
                    return;
                }
            }

            if (!$_POST['name'] || !$_POST['phone']) {
                self::end_post_handle(false, 'Inputs are invalid');
                return;
            }

            if (!extension_loaded('curl')) {
                self::end_post_handle(false, 'Server is not supported this function!');
                return;
            }

            $response = self::call_crm_api($_POST);
            self::end_post_handle($response['success'], $response['message'], $perob_options['redirect_to']);
            return;
        }

        public static function get_data_from_api_by_curl_post($endpoint, $payload = [], $headers = [])
        {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $endpoint);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

            $response = curl_exec($curl);

            $response = json_decode($response, true);

            return $response;
        }

        public static function end_post_handle($status, $message, $redirect_to = '')
        {
            $data = [
                'success' => $status,
                'message' => $message,
                'redirect_to' => $redirect_to
            ];

            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                wp_die(json_encode($data));
            } else {
                $url = $status && $redirect_to ? $redirect_to : $_POST['_wp_http_referer'];
                wp_redirect($url);
            }
        }

        public static function call_crm_api($data)
        {
            $config = get_option(self::PEROB_SETTING_DBKEY);

            $utm_data = [];
            foreach ($_POST as $key => $value) {
                if (strstr($key, 'utm_') !== false || $key == 'fid') {
                    $utm_data[$key] = $value;
                }
            }

            if (!empty($utm_data)) {
                $utm_link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/?' . http_build_query($utm_data);
            } else {
                $utm_link = $config['utm'];
            }

            $post_data = $data;
            unset($post_data['_wpnonce']);
            unset($post_data['_wp_http_referer']);
            unset($post_data['recaptcha']);
            unset($post_data['action']);
            $post_data['link'] = $utm_link;
            $post_data['source'] = $config['source'];

            $response = self::get_data_from_api_by_curl_post(
                $config['endpoint'],
                json_encode($post_data),
                [
                    'Authorization: Token ' . $config['token'],
                    'Content-Type: application/json'
                ]
            );

            $response['message'] = $response['error_message'];
            if ($response['success']) {
                $response['message'] = 'Order is created succecced!';
            }
            return $response;
        }

        public static function shortcode_order_form($atts = [], $content = null, $tag = '')
        {
            ob_start();
            $perob_options = get_option(self::PEROB_SETTING_DBKEY);

            $atts = array_change_key_case((array)$atts, CASE_LOWER);

            $default_atts = shortcode_atts([
                 'product_code' => $perob_options['default_product_code']
             ], $atts, $tag);

            $all_atts = array_filter(array_merge($default_atts, $atts));

            if ($content) {
                echo $content;
            } else {
                ?>
                <div class="<?php echo self::PEROB_SHORTCODE?>-container">
                    <?php if ($perob_options['use_recaptcha'] == 'Yes' && self::$id == 1): ?>
                    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $perob_options['recaptcha_site_key']; ?>"></script>
                    <script>
                        grecaptcha.ready(function () {
                            grecaptcha.execute('<?php echo $perob_options['recaptcha_site_key']; ?>', { action: '<?php echo self::PEROB_SHORTCODE; ?>' }).then(function (token) {
                                document.querySelectorAll(".<?php echo self::PEROB_SHORTCODE; ?>-recaptcha").forEach(elem => (elem.value = token));
                            });
                        });
                    </script>
                    <?php endif; ?>
                    <form class="<?php echo self::PEROB_SHORTCODE; ?>" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="<?php echo self::PEROB_SHORTCODE; ?>" />
                        <?php foreach ($all_atts as $attr => $value) : ?>
                        <input type="hidden" name="<?php echo $attr ?>" value="<?php echo esc_attr($value); ?>" />
                        <?php endforeach; ?>
                        <?php wp_nonce_field(self::PEROB_NONCE_SALT); ?>
                        <div class="form-group">
                            <label>Tên (<span class="required">*</span>)</label>
                            <input type="text" class="form-control" name="name" value="" size="100" required />
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại (<span class="required">*</span>)</label>
                            <input type="tel" class="form-control" name="phone" pattern="[0-9\+]+" value="" size="40" required />
                        </div>
                        <div class="form-group">
                            <label>Nội dung</label>
                            <textarea rows="3" class="form-control" name="message"></textarea>
                        </div>
                        <?php if ($perob_options['use_recaptcha'] == 'Yes'): ?>
                        <input type="hidden" class="<?php echo self::PEROB_SHORTCODE; ?>-recaptcha" name="recaptcha" value=""/ >
                        <?php endif; ?>
                        <div class="message"></div>
                        <div class="form-group">
                            <button type="submit" class="submit">Đặt hàng</button>
                        </div>
                    </form>
                </div>
                <?php
            }
            self::$id += 1;
            return ob_get_clean();
        }

        public static function admin_init()
        {
            // register a new setting for PEROB_SETTING_PAGE_NAME page
            register_setting(self::PEROB_SETTING_PAGE_NAME, self::PEROB_SETTING_DBKEY);

            // register a new section in the PEROB_SETTING_PAGE_NAME page
            add_settings_section(
                self::PEROB_CRM_SECTION,
                __(self::PEROB_SETTING_TITLE, self::PEROB_SETTING_PAGE_NAME),
                'PerobOrderFormPlugin::get_crm_section_desc',
                self::PEROB_SETTING_PAGE_NAME
            );

            foreach (self::PEROB_FIELDS as $field => $args) {
                self::add_settings_field($field, $args);
            }
        }

        public static function get_crm_section_desc($args)
        {
            ?>
            <p id="<?php echo esc_attr($args['id']); ?>">
                <?php esc_html_e(self::PEROB_SETTING_DESCRIPTION, self::PEROB_SETTING_PAGE_NAME);?>
            </p>
            <?php
        }

        public static function add_settings_field($field, $args = [])
        {
            $args['id'] = $field;
            add_settings_field(
                self::PEROB_FIELD_PREFIX . $field,
                __($args['title'], self::PEROB_SETTING_PAGE_NAME),
                'PerobOrderFormPlugin::perob_field_callback',
                self::PEROB_SETTING_PAGE_NAME,
                self::PEROB_CRM_SECTION,
                $args
            );
        }

        /**
        * perob_field_callback
        * field callbacks can accept an $args parameter, which is an array.
        * $args is defined at the add_settings_field() function.
        * "id" key use for name of field
        * "class" key use for class of field
        * "descriptioin" key use for help text block
        */
        public static function perob_field_callback($args)
        {
            // get the value of the setting we've registered with register_setting()
            $options = get_option(self::PEROB_SETTING_DBKEY);
            // output the field
            if (!$args['type'] || $args['type'] == 'text'):
            ?>
            <input
                id="<?php echo esc_attr($args['id']); ?>"
                name="perob_options[<?php echo esc_attr($args['id']); ?>]"
                type="text"
                placeholder="<?php echo esc_attr($args['placeholder'])?>"
                value="<?php echo $options[$args['id']]; ?>"
            />
            <?php else: ?>
            <select
                id="<?php echo esc_attr($args['id']); ?>"
                name="perob_options[<?php echo esc_attr($args['id']); ?>]"
            >
                <?php foreach ($args['options'] as $opt): ?>
                <option<?php if ($options[$args['id']] == $opt) { echo ' selected'; } ?> value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <p class="description">
                <?php echo $args['description']; ?>
            </p>
            <?php
        }

        public static function admin_menu()
        {
            // add top level menu page
            add_options_page(
                'Perob Options',
                'Perob Options',
                'manage_options',
                self::PEROB_SETTING_PAGE_NAME,
                'PerobOrderFormPlugin::admin_config_page'
            );
        }

        public static function admin_config_page()
        {
            // check user capabilities
            if (!current_user_can('manage_options')) {
                return;
            }

            // add error/update messages

            // check if the user have submitted the settings
            // wordpress will add the "settings-updated" $_GET parameter to the url
            if (isset($_GET['settings-updated'])) {
                // add settings saved message with the class of "updated"
                // add_settings_error('perob_messages', 'perob_message', __('Settings Saved', self::PEROB_SETTING_PAGE_NAME), 'updated');
            }

            // show error/update messages
            settings_errors('perob_messages');
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="options.php" method="post">
                <?php
                    // output security fields for the registered setting PEROB_SETTING_PAGE_NAME
                    settings_fields(self::PEROB_SETTING_PAGE_NAME);
                    // output setting sections and their fields
                    // (sections are registered for PEROB_SETTING_PAGE_NAME, each field is registered to a specific section)
                    do_settings_sections(self::PEROB_SETTING_PAGE_NAME);
                    // output save settings button
                    submit_button('Save Settings');
                ?>
                </form>
            </div>
            <?php
        }
    }

    /**
     * register our init action hook
     */
    add_action('init', 'PerobOrderFormPlugin::init');
}
