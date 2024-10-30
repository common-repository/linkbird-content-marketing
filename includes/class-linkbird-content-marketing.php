<?php
/**
 * The core plugin class
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/includes
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing {
    /** @var string $plugin_name  The string used to uniquely identify this plugin. */
    private $plugin_name;

    /** @var string $version  The current version of the plugin. */
    private $version;

    /** @var string $plugin_directory  The absolute path to the plugin's directory */
    private $plugin_directory;

    /** @var Linkbird_Content_Marketing_Api  The plugin api instance */
    private $plugin_api;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function __construct() {
        $this->plugin_name      = 'linkbird-content-marketing';
        $this->version          = '1.0.5';
        $this->plugin_directory = plugin_dir_path( dirname( __FILE__ ) );

        $this->load_dependencies();
        $this->set_locale();
        $this->load_api();
    }

    /**
     * Execute all hooks with WordPress.
     */
    public function run() {
        $this->define_public_hooks();

        if ( is_admin() ) {
            $this->define_admin_hooks();
        }
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Linkbird_Content_Marketing_i18n.    Defines internationalization functionality.
     * - Linkbird_Content_Marketing_Admin.   Defines all hooks for the admin area.
     * - Linkbird_Content_Marketing_Public.  Defines all hooks for the public side of the site.
     * - Linkbird_Content_Marketing_Api.     Defines all functionalities of the public API.
     */
    private function load_dependencies() {
        /**
         * The class responsible for defining internationalization functionality of the plugin.
         */
        require_once $this->plugin_directory . 'includes/class-linkbird-content-marketing-i18n.php';

        /**
         * The class responsible for defining the public API functionality of the plugin.
         */
        require_once $this->plugin_directory . 'includes/class-linkbird-content-marketing-api.php';

        /**
         * The class responsible for calling the public Stork API.
         */
        require_once $this->plugin_directory . 'includes/class-linkbird-stork-api.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once $this->plugin_directory . 'admin/class-linkbird-content-marketing-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing side of the site.
         */
        require_once $this->plugin_directory . 'public/class-linbird-content-marketing-public.php';
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
     * with WordPress.
     */
    private function set_locale() {
        $plugin_i18n = new Linkbird_Content_Marketing_i18n();
        add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ), 10, 1 );
    }

    /**
     * Initialize the plugin API and register needed hooks
     */
    private function load_api() {
        $this->plugin_api = new Linkbird_Content_Marketing_Api( $this->get_version() );

        add_action( 'init', array( $this->plugin_api, 'register_endpoint' ) );
        add_action( 'template_redirect', array( $this->plugin_api, 'handle_request') );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Linkbird_Content_Marketing_Admin(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->get_plugin_directory()
        );

        add_action( 'admin_menu', array( $plugin_admin, 'register_options_page' ) );
        add_action( 'admin_notices', array( $plugin_admin, 'display_error_messages' ) );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new Linkbird_Content_Marketing_Public(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->get_plugin_directory()
        );

        add_action( 'transition_post_status', array( $plugin_public, 'update_post_status' ), 10, 3 );
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string  The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string  The version number of the plugin
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Retrieve the directory of the plugin.
     *
     * @return string  The directory of the plugin
     */
    public function get_plugin_directory() {
        return $this->plugin_directory;
    }
}
