<?php
/**
 * This class is used for handling plugin activation.
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/includes
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_Activator {
    /** @var string $php_version  The needed PHP version for the plugin. */
    const MIN_PHP_VERSION = '5.2';

    /**
     * Setting up needed options, etc.
     */
    public static function activate() {
        // Check for needed PHP version
        if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION ) < 1 ) {
            $message = sprintf(
                __(
                    'Es wird PHP in Version %s oder höher benötigt. Sie sind noch auf Version %s.',
                    'linkbird-content-marketing'
                ),
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
            exit( $message );
        }

        // Check if we can connect to the Stork API
        $stork = new Linkbird_Stork_Api( 'POST', '/public/plugin/status', [] );
        $response = $stork->fire();

        if ( empty( $response ) || empty( $response['body'] ) || is_wp_error( $response ) ) {
            $message = __(
                'Es konnte keine Verbindung zur contentbird Software hergestellt werden. Bitte melden Sie sich bei unserem Support.',
                'linkbird-content-marketing'
            );
            exit( $message );
        }

        // Add options
        add_option( 'lbcm_api_token', '', '', true );
    }
}
