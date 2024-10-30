<?php
/**
 * Class for handling API requests from the contentbird tool to the plugin
 *
 * @package    linkbird-content-marketing
 * @subpackage linkbird-content-marketing/includes
 * @author     contentbird GmbH
 */
class Linkbird_Content_Marketing_Api {
    /** @var string api token */
    private $api_token;

    /** @var string $version  The current version of the plugin. */
    protected $version;

    /** @var string Name of the endpoint that gets registered with wordpress */
    const API_ENDPOINT = 'lbcm';

    /** Status and error codes */
    const STATUS_OKAY                  = 0;
    const ERROR_NO_TITLE_GIVEN         = 1;
    const ERROR_NO_CONTENT_GIVEN       = 2;
    const ERROR_NO_AUTHOR_GIVEN        = 3;
    const ERROR_NO_STATUS_GIVEN        = 4;
    const ERROR_UNKNOWN_POST_TYPE      = 5;
    const ERROR_UNKNOWN_POST_CATEGORY  = 6;
    const ERROR_CREATE_USER            = 7;
    const ERROR_USER_PERMISSION_DENIED = 8;
    const ERROR_USER_NOT_FOUND         = 9;
    const ERROR_PUBLISH_POST           = 10;
    const ERROR_OBJECT_INVALID         = 11;
    const ERROR_UNKNOWN_METHOD         = 12;
    const ERROR_NOT_FOUND              = 13;
    const ERROR_GENERAL                = 14;
    const ERROR_NO_BODY                = 15;

    /**
     * Linkbird_Content_Marketing_Api constructor.
     *
     * @param string $plugin_version  The current version of the plugin
     */
    public function __construct( $plugin_version ) {
        $this->version = $plugin_version;
        $this->api_token = get_option( 'lbcm_api_token', null );
    }

    /**
     * Register the endpoint for the public accessible API
     * and flush the rewrite rules.
     *
     * @return bool
     */
    public function register_endpoint() {
        if ( !empty( $this->api_token) ) {
            add_rewrite_endpoint( self::API_ENDPOINT, EP_NONE );
            flush_rewrite_rules();
        }
    }

    /**
     * Handle all API requests and call needed methods.
     */
    public function handle_request() {
        global $wp_query;

        // check if we have a plugin API request at all
        if ( !isset( $wp_query->query_vars[ self::API_ENDPOINT ] ) ) {
            return;
        }

        // check if plugin is fully installed (with activated token)
        if ( empty( $this->api_token ) ) {
            $this->handle_error(
                self::ERROR_GENERAL,
                'Please enter installation token on plugin settings page.',
                422
            );
        }

        // validate token
        if ( empty( $_REQUEST['token'] ) ) {
            $this->handle_error(
                self::ERROR_GENERAL,
                'Missing token',
                422
            );
        }

        if ( false === $this->validate_token( $_REQUEST['token'] ) ) {
            $this->handle_error(
                self::ERROR_GENERAL,
                'Invalid token',
                401
            );
        }

        // get plugin action
        $action = $wp_query->query_vars[ self::API_ENDPOINT ];

        if ( empty( $action ) ) {
            $this->handle_error(
                self::ERROR_UNKNOWN_METHOD,
                'Unknown method',
                400
            );
        }

        // handle plugin actions
        switch ( $action ) {
            case 'meta':
                $this->get_meta();
                break;

            case 'users':
                $this->get_users();
                break;

            case 'plugin/status':
                $this->get_plugin_status();
                break;

            case 'content/create':
                $this->create_content( $_POST );
                break;

            case 'content/get':
                $this->get_content( $_GET );
                break;

            case 'content/update':
                $this->update_content( $_POST );
                break;

            default:
                $this->handle_error(
                    self::ERROR_UNKNOWN_METHOD,
                    'Unknown method',
                    400
                );
                break;
        }
    }

    /**
     * Return errors as JSON result with optionally changed HTTP status code.
     *
     * @param int $error_code
     * @param string $error_message
     * @param int|null $http_status_code
     */
    private function handle_error( $error_code, $error_message, $http_status_code = null ) {
        $payload = array(
            'code' => $error_code,
            'message' => $error_message,
        );

        switch ( $http_status_code ) {
            case 400:
                header( 'Status: 400 Bad Request', true, 400 );
                break;

            case 401:
                header( 'Status: 401 Unauthorized', true, 401 );
                break;

            case 422:
                header( 'Status: 422 Unprocessable Entity', true, 422 );
                break;

            case 500:
                header( 'Status: 500 Internal Server Error', true, 400 );
                break;

            default:
                break;
        }

        $this->json( $payload );
    }

    /**
     * Check if a given token matches the stored token in the database to
     * authenticate requests.
     *
     * @param string $token
     * @return bool
     */
    private function validate_token( $token ) {
        if ( !empty( $this->api_token ) && $this->api_token === $token ) {
            return true;
        }

        return false;
    }

    /**
     * Return JSON with correct headers.
     *
     * @param mixed $data
     */
    private function json( $data ) {
        header( 'Content-Type: application/json; charset=UTF-8' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        header( 'Connection: Close' );

        echo json_encode( $data );
        exit;
    }

    /**
     * Fetch content meta data and return JSON to client
     */
    public function get_meta() {
        $this->json( array(
            'code'      => self::STATUS_OKAY,
            'message'   => '',
            'meta_data' => array(
                'post_types' => get_post_types(
                    array(
                        'public' => true,
                    ),
                    'objects'
                ),
                'post_categories' => get_categories( array(
                    'hide_empty' => false
                ) ),
            )
        ) );
    }

    /**
     * Fetch users and return JSON to client
     */
    public function get_users() {
        $users = get_users();
        $userList = array();

        foreach ( $users as $user ) {
            $userList[$user->ID] = array(
                'display_name' => $user->display_name,
                'email'        => $user->user_email
            );
        }

        $this->json( array(
            'code'    => self::STATUS_OKAY,
            'message' => '',
            'users'   => $userList,
        ) );
    }

    /**
     * Fetch plugin information and return as JSON to client
     */
    public function get_plugin_status() {
        $this->json( array(
            'code'           => self::STATUS_OKAY,
            'message'        => '',
            'version'        => $this->version,
            'token_inserted' => !empty( $this->api_token )
        ) );
    }

    /**
     * Create a new post to publish
     *
     * @param array $data
     */
    function create_content( array $data ) {
        // validate data
        if ( empty( $data['content_data'] ) ) {
            $this->handle_error(
                self::ERROR_OBJECT_INVALID,
                'Invalid content data object',
                422
            );
        }

        if ( empty( $data['content_data']['post_meta']['cms_user_id'] ) ) {
            $this->handle_error(
                self::ERROR_NO_AUTHOR_GIVEN,
                'No author given',
                422
            );
        }

        if ( empty( $data['content_data']['post_title'] ) ) {
            $this->handle_error(
                self::ERROR_NO_TITLE_GIVEN,
                'No title given',
                422
            );
        }

        if ( !empty( $data['content_data']['post_meta']['cms_post_type'] ) ) {
            if ( !post_type_exists( $data['content_data']['post_meta']['cms_post_type'] ) ) {
                $this->handle_error(
                    self::ERROR_UNKNOWN_POST_TYPE,
                    'Unknown post type',
                    422
                );
            }
        }

        if ( !empty( $data['content_data']['post_meta']['cms_post_categories'] ) ) {
            foreach ( $data['content_data']['post_meta']['cms_post_categories'] as $category_id ) {
                if ( !get_term_by( 'id', $category_id, 'category' ) ) {
                    $this->handle_error(
                        self::ERROR_UNKNOWN_POST_CATEGORY,
                        'Unknown post category ' . $category_id,
                        422
                    );
                }
            }
        }

        $content_data = array(
            'post_title'   => $data['content_data']['post_title'],
            'post_content' => $data['content_data']['post_content'],
        );

        // get user data
        $user = get_user_by( 'ID', $data['content_data']['post_meta']['cms_user_id'] );

        if ( false === $user ) {
            $this->handle_error(
                self::ERROR_USER_NOT_FOUND,
                'Author user not found',
                422
            );
        } else {
            $content_data['post_author'] = $user->ID;
        }

        // Set post status to draft, if not set in given data
        if ( empty($data['content_data']['post_status']) ) {
            $content_data['post_status'] = 'draft';
        }

        // Set post type to 'post', if not set in given data
        if ( empty( $data['content_data']['post_meta']['cms_post_type'] ) ) {
            $content_data['post_type'] = 'post';
        } else {
            $content_data['post_type'] = $data['content_data']['post_meta']['cms_post_type'];
        }

        // Set scheduled date, if given
        if( !empty($data['content_data']['planned_publish_date']) ) {
            $content_data['post_date'] = $data['content_data']['planned_publish_date'];

            // For a scheduled draft some special fields have to be set
            if ( $data['content_data']['post_status'] === 'draft' ) {
                // Check if planned date is in the past, if not we can skip this part
                $now = strtotime( date( 'Y-m-d' ) );
                $postDate = strtotime( $data['content_data']['planned_publish_date'] );

                if ( $postDate >= $now ) {
                    $content_data['edit_date'] = 'true';
                    $content_data['post_status'] = 'future';
                }
            }
        }

        // Remove some filters to prevent wordpress to remove e.g. iframes
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        $insert_id = wp_insert_post( $content_data );

        // Return the status
        if ( $insert_id !== 0 ) {
            // Connect post to categories
            if ( !empty( $data['content_data']['post_meta']['cms_post_categories'] ) ) {
                wp_set_post_categories( $insert_id, $data['content_data']['post_meta']['cms_post_categories'] );
            }

            // Save the content ID of the instance
            if ( !empty( $data['content_id'] ) ) {
                update_post_meta( $insert_id, 'lbcm_content_id', (int)$data['content_id'] );
            }

            $this->json( array(
                'code'           => self::STATUS_OKAY,
                'message'        => '',
                'cms_content_id' => $insert_id,
            ) );
        }

        if ( is_wp_error( $insert_id ) ) {
            $this->handle_error(
                self::ERROR_GENERAL,
                $insert_id->get_error_message(),
                500
            );
        }

        $this->handle_error(
            self::ERROR_GENERAL,
            'Unknown error',
            500
        );
    }

    /**
     * Get content
     *
     * @param array $data
     */
    function get_content( array $data ) {
        // validate data
        if ( empty( $data['cms_content_id'] ) ) {
            $this->handle_error(
                self::ERROR_NO_CONTENT_GIVEN,
                'No content given',
                422
            );
        }

        // get content data
        $content = get_post( $data['cms_content_id'], 'ARRAY_A' );

        if ( empty($content) ) {
            $this->handle_error(
                self::ERROR_NOT_FOUND,
                'Content not found',
                422
            );
        } else {
            remove_filter( 'the_content', 'do_shortcode', 11 );
            $contentData = [
                'title'   => $content['post_title'],
                'content' => apply_filters('the_content', $content['post_content']),
            ];

            $this->json( array(
                'code'     => self::STATUS_OKAY,
                'message'  => '',
                'content'  => $contentData,
            ) );
        }

        $this->handle_error(
            self::ERROR_GENERAL,
            'Unknown error',
            500
        );
    }

    /**
     * Update content
     *
     * @param array $data
     */
    function update_content( array $data ) {
        // validate data
        if ( empty( $data['cms_content_id'] ) ) {
            $this->handle_error(
                self::ERROR_NO_CONTENT_GIVEN,
                'No content given',
                422
            );
        }

        if ( empty( $data['post_title'] ) ) {
            $this->handle_error(
                self::ERROR_NO_TITLE_GIVEN,
                'No title given',
                422
            );
        }

        if ( empty( $data['post_content'] ) ) {
            $this->handle_error(
                self::ERROR_NO_CONTENT_GIVEN,
                'No content given',
                422
            );
        }

        // get content data
        $content = get_post( $data['cms_content_id'] );

        if ( empty($content) ) {
            $this->handle_error(
                self::ERROR_NOT_FOUND,
                'Content not found',
                422
            );
        } else {
            // check status, if not already published or planned we will update
            // content directly. if published already, we will create revision
            if ( $content->post_status != 'publish' && $content->post_status != 'future' ) {
                $updateData = [
                    'ID'           => $data['cms_content_id'],
                    'post_title'   => $data['post_title'],
                    'post_content' => $data['post_content'],
                ];

                // Remove some filters to prevent wordpress to remove e.g. iframes
                remove_filter('content_save_pre', 'wp_filter_post_kses');
                remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

                $updateContent = wp_update_post($updateData);
            } else {
                $updateData = [
                    'post_parent'  => $data['cms_content_id'],
                    'post_title'   => $data['post_title'],
                    'post_content' => $data['post_content'],
                    'post_type'    => 'revision',
                    'post_status'  => 'inherit',
                    'post_name'    => $data['cms_content_id'] . '-autosave-' . uniqid(),
                ];

                $updateContent = wp_insert_post($updateData);
            }

            if ( !empty($updateContent) ) {
                $this->json( array(
                    'code'     => self::STATUS_OKAY,
                    'message'  => '',
                ) );
            }
        }

        $this->handle_error(
            self::ERROR_GENERAL,
            'Unknown error',
            500
        );
    }
}
