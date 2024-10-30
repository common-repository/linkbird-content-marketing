<?php
/**
 * Class Linkbird_Stork_Api
 *
 * @author contentbird GmbH
 */
class Linkbird_Stork_Api {
    /** Api configuration */
    const STORK_API_URL               = 'https://api.stork.mylinkbird.com/api';
    const STORK_API_TIMEOUT           = 60;
    const STORK_API_REDIRECTION_COUNT = 5;

    /** Error codes */
    const STATUS_OKAY                  = 0;
    const ERROR_NO_TITLE_GIVEN         = 1;
    const ERROR_NO_CONTENT_GIVEN       = 2;
    const ERROR_NO_AUTHOR_GIVEN        = 3;
    const ERROR_NO_STATUS_GIVEN        = 4;
    const ERROR_UNKNOWN_POST_TYPE      = 5;
    const ERROR_CREATE_USER            = 7;
    const ERROR_USER_PERMISSION_DENIED = 8;
    const ERROR_USER_NOT_FOUND         = 9;
    const ERROR_PUBLISH_POST           = 10;
    const ERROR_OBJECT_INVALID         = 11;
    const ERROR_UNKNOWN_METHOD         = 12;
    const ERROR_NOT_FOUND              = 13;
    const ERROR_GENERAL                = 14;
    const ERROR_NO_BODY                = 15;

    /** @var string jwt token */
    private $api_token;

    /** @var string URL endpoint, following the base path set in config */
    protected $endpoint;

    /** @var string HTTP Method (GET, POST, PUT, ...) */
    protected $http_method;

    /** @var array payload to send with requests  */
    protected $payload;

    /**
     * Linkbird_Stork_Api constructor
     *
     * @param string $method    The HTTP method used for request
     * @param string $endpoint  The endpoint used for requests
     * @param array $payload    The payload to send with requests
     */
    public function __construct( $method = '', $endpoint = '', $payload = array() ) {
        $api_token = get_option( 'lbcm_api_token', '' );

        if ( empty( $api_token ) ) {
            $this->api_token = '';
        }

        $this->api_token = get_option( 'lbcm_api_token', '' );

        $this->set_http_method( $method );
        $this->set_endpoint( $endpoint );
        $this->set_payload( $payload );
    }

    /**
     * @param string $token
     */
    public function set_token( $token ) {
        $token = trim( $token );

        if ( !empty( $token ) ) {
            $this->api_token = $token;
        }
    }

    /**
     * @return string
     */
    public function get_token() {
        return $this->api_token;
    }

    /**
     * @param string $http_method
     */
    public function set_http_method( $http_method ) {
        $http_method = trim( $http_method );

        if ( !empty( $http_method ) ) {
            $this->http_method = $http_method;
        }
    }

    /**
     * @return string
     */
    public function get_http_method() {
        return $this->http_method;
    }

    /**
     * @param string $endpoint
     */
    public function set_endpoint( $endpoint ) {
        $endpoint = trim( $endpoint );

        if ( !empty( $endpoint ) ) {
            $this->endpoint = $endpoint;
        }
    }

    /**
     * @return string
     */
    public function get_endpoint() {
        return $this->endpoint;
    }

    /**
     * @param array $payload
     */
    public function set_payload( array $payload ) {
        $this->payload = $payload;
    }

    /**
     * @param array $payload
     */
    public function add_payload( array $payload ) {
        $this->payload = $this->payload + $payload;
    }

    /**
     * @return array
     */
    public function get_payload() {
        return $this->payload;
    }

    /**
     * Call the API using HTTP requests
     *
     * @return mixed Array of results including HTTP headers or WP_Error if the request failed.
     */
    public function fire() {
        if ( empty( $this->http_method ) ) {
            throw new RuntimeException( 'Missing http method!' );
        }

        if ( empty( $this->endpoint ) ) {
            throw new RuntimeException( 'Missing endpoint!' );
        }

        if ( !is_array( $this->payload ) ) {
            throw new RuntimeException( 'Missing payload!' );
        }

        // set default post data
        if ( !isset( $this->payload['code'] ) ) {
            $this->payload['code'] = Linkbird_Content_Marketing_Api::STATUS_OKAY;
        }

        if ( !isset( $this->payload['message'] ) ) {
            $this->payload['message'] = '';
        }

        // build completed payload
        $postData = [
            'method'        => $this->http_method,
            'timeout'       => self::STORK_API_TIMEOUT,
            'redirection'   => self::STORK_API_REDIRECTION_COUNT,
            'httpversion'   => '1.0', // 1.0 to prevent connection reuse
            'blocking'      => true,
            'headers'       => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $this->payload ),
        ];

        // call the API endpoint
        return wp_remote_post( self::STORK_API_URL . $this->endpoint, $postData );
    }
}
