<?php
/**
 * This class is used for handling plugin uninstallation.
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/includes
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_Uninstaller {
    /**
     * Removing options, etc.
     */
    public static function uninstall() {
        // Remove options
        delete_option( 'lbcm_api_token' );
    }
}
