<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/public
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_Public {
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
     * Update the status of a post on Stork
     *
     * @param string $new_status
     * @param string $old_status
     * @param WP_Post $post
     * @return bool
     */
    public function update_post_status( $new_status, $old_status, $post ) {
        if( empty( $new_status ) ) {
            return false;
        }

        // check if the content is connected to linkbird content
        if ( !empty( $post ) ) {
            $linkbird_content_id = get_post_meta( $post->ID, 'lbcm_content_id' );

            if ( empty( $linkbird_content_id ) ) {
                return false;
            }
        }

        // get API token
        $api_token = get_option( 'lbcm_api_token', null );

        if ( empty( $api_token ) ) {
            return false;
        }

        // set matching status for API
        switch ( $new_status ) {
            case 'publish':
                $status = 'published';
                break;

            default:
                $status = '';
                break;
        }

        if ( empty( $status ) ) {
            return false;
        }

        // build payload for Stork
        $instance = $this->get_instance_from_token( $api_token );

        $payload = array(
            'cms_content_id'         => $post->ID,
            'content_id'             => (int)get_post_meta( $post->ID, 'lbcm_content_id', true ),
            'content_url'            => get_permalink( $post->ID ),
            'content_published_date' => $post->post_date_gmt,
            'content_status'         => $status,
            'instance_domain'        => $instance['domain'],
            'future_post'            => $this->is_post_in_future( $post->post_date_gmt ),
        );

        // send request (fire and forget)
        $stork = new Linkbird_Stork_Api( 'PUT', '/public/content/status', $payload );
        $stork->fire();
    }

    /**
     * Extract instance data from token
     *
     * @param string $token
     * @return array|mixed|object
     */
    private function get_instance_from_token( $token ) {
        $token_parts = explode( '.', $token );
        return json_decode( base64_decode( $token_parts[1] ), true );
    }

    /**
     * Check if a given post date is in the future
     *
     * @param string $post_date
     * @return bool
     */
    private function is_post_in_future( $post_date ) {
        if ( !empty( $post_date ) ) {
            $post_date_obj = new DateTime( $post_date );
            $current_date_obj = new DateTime();

            if ( $post_date_obj->format( 'Ymd' ) > $current_date_obj->format( 'Ymd' ) ) {
                return true;
            }
        }

        return false;
    }
}
