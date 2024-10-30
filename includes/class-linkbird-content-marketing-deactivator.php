<?php
/**
 * This is fired on plugin deactivation
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/includes
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_Deactivator {
    /**
     * Cleanup token data and tell Stork that the plugin was deactivated
     */
    public static function deactivate() {
        $api_token = get_option( 'lbcm_api_token', null );

        if ( !empty( $api_token ) ) {
            $payload = [
                'plugin_status'    => 'deactivated',
                'cms_frontend_url' => get_site_url(),
                'cms_admin_url'    => get_admin_url(),
                'cms_editor_url'   => admin_url( 'post.php?action=edit&post=%CMS_POST_ID%' ),
            ];

            $stork = new Linkbird_Stork_Api( 'POST', '/public/plugin/status', $payload );
            $stork->fire();

            update_option( 'lbcm_api_token', '' );
        }
    }
}
