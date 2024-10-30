<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.

 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/includes
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_i18n {
    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'linkbird-content-marketing',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}
