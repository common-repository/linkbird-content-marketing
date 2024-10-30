<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/admin
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_Admin {
    /** @var string $plugin_name  The string used to uniquely identify this plugin. */
    protected $plugin_name;

    /** @var string $version  The current version of the plugin. */
    protected $version;

    /** @var string $plugin_directory  The absolute path to the plugin's directory */
    protected $plugin_directory;

    /**
     * Initialize the class and set its properties.
     *
     * @param string    $plugin_name       The name of this plugin.
     * @param string    $version           The version of this plugin.
     * @param string    $plugin_directory  The directory of this plugin.
     */
    public function __construct( $plugin_name, $version, $plugin_directory ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_directory = $plugin_directory;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/linkbird-content-marketing-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Add options page to the "Settings" menu.
     */
    public function register_options_page() {
        add_options_page(
            'contentbird Content Marketing',
            'contentbird Content Marketing',
            'manage_options',
            'linkbird-content-marketing',
            [ $this, 'load_options_page' ]
        );
    }

    /**
     * Show the options page.
     */
    public function load_options_page() {
        $form_send = false;
        $errors = array(
            'input' => array(),
            'save' => array(),
        );

        if ( isset( $_REQUEST['submit'] ) ) {
            $form_send = true;
            $options = [
                'lbcm_api_token' => trim( $_REQUEST['lbcm_options']['lbcm_api_token'] ),
            ];

            $validate_errors = self::validate_options( $options );

            if ( !empty( $validate_errors ) ) {
                $errors['input'] = $validate_errors;
            } else {
                $save_errors = self::save_options( $options );

                if ( !empty( $save_errors ) ) {
                    $errors['save'] = $save_errors;
                }
            }
        } else {
            $options = [
                'lbcm_api_token' => get_option( 'lbcm_api_token', '' ),
            ];
        }

        require $this->plugin_directory . 'admin/views/linkbird-content-marketing-options.php';
    }

    /**
     * Validate POST data when the options page is submitted.
     *
     * @param array $options  post data
     * @return array  list of errors or empty
     */
    private function validate_options( array $options ) {
        $errors = [];
        $api_token = trim( $options['lbcm_api_token'] );

        if ( empty( $api_token ) ) {
            $errors['lbcm_api_token'] = __( 'Bitte geben Sie einen Installations Token ein.', 'linkbird-content-marketing' );
        } else {
            $instance = $this->get_instance_from_token( $api_token );

            if ( empty( $instance['domain'] ) ) {
                $errors['lbcm_api_token'] = __( 'Der eingegebene Token ist ungültig.', 'linkbird-content-marketing' );
            }
        }

        return $errors;
    }

    /**
     * Update options in wordpress database and call Stork API
     *
     * @param array $options  options to update
     * @return array  list of errors or empty
     */
    public function save_options( array $options ) {
        $errors = [];

        // update to new value and call stork
        update_option( 'lbcm_api_token', $options['lbcm_api_token'] );

        // build payload
        $instance = $this->get_instance_from_token( $options['lbcm_api_token'] );

        $payload = [
            'instance_domain'  => $instance['domain'],
            'plugin_status'    => 'activated',
            'cms_frontend_url' => get_site_url(),
            'cms_admin_url'    => get_admin_url(),
            'cms_editor_url'   => admin_url('post.php?action=edit&post=%CMS_POST_ID%'),
            'cms_plugin_api_url'  => get_site_url(),
        ];

        // send request to Stork
        $stork = new Linkbird_Stork_Api( 'POST', '/public/plugin/status', $payload );
        $response = $stork->fire();
        $response_code = null;

        if ( !empty( $response['response']['code'] ) ) {
            $response_code = $response['response']['code'];
        }

        if ( empty( $response ) || empty( $response_code ) ) {
            $errors['save'][] = __( 'Fehler bei der Anfrage. Bitte kontaktieren Sie den Support.', 'linkbird-content-marketing' );
            return $errors;
        }

        if ( is_wp_error( $response ) || $response_code !== 200 ) {
            // reset option, because the stork call failed or returned an error
            update_option( 'lbcm_api_token', '' );

            // define error message
            switch( $response_code ) {
                case 400:
                    $error_message = __( 'Fehler bei der Anfrage. Bitte kontaktieren Sie den Support.', 'linkbird-content-marketing' );
                    break;

                case 401:
                    $error_message = __( 'Der Installations Token ist ungültig.', 'linkbird-content-marketing' );
                    break;

                default:
                    $error_message = __( 'Unbekannter Fehler. Bitte kontaktieren Sie den Support.', 'linkbird-content-marketing' );
                    break;
            }

            $errors[] = $error_message;
        }

        return $errors;
    }

    /**
     * Show errors / notices of the plugin to the user in a box on top of all
     * admin pages.
     */
    public function display_error_messages() {
        // Do not show messages on plugin setup pages
        if ( strpos($_SERVER['REQUEST_URI'], 'linkbird-content-marketing') !== false ) {
            return;
        }

        $api_token = get_option( 'lbcm_api_token', null );

        // show initial setup notice
        if ( empty( $api_token ) ) {
            $setup_notice = true;
            require $this->plugin_directory . 'admin/views/linkbird-content-marketing-messages.php';
            // show all other messages
        } else {
            $messages = array();

            if ( !empty( $messages ) ) {
                require $this->plugin_directory . 'admin/views/linkbird-content-marketing-messages.php';
            }
        }
    }

    /**
     * Extract instance data from token
     *
     * @param string $token
     * @return array|mixed|object
     */
    private function get_instance_from_token($token) {
        $token_parts = explode( '.', $token );
        return json_decode( base64_decode( $token_parts[1] ), true );
    }
}
