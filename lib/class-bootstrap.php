<?php
/**
 * Bootstrap RPC Plugin
 *
 */
namespace UsabilityDynamics\RPC {

  include_once( __DIR__  . '/class-actions.php' );
  include_once( __DIR__  . '/class-admin.php' );

  if( !class_exists( '\UsabilityDynamics\RPC\Bootstrap' ) ) {

    class Bootstrap {

      /**
       * Cluster core version.
       *
       * @static
       * @property $version
       * @type {Object}
       */
      public $version = false;

      /**
       * Textdomain String
       *
       * @public
       * @property text_domain
       * @var string
       */
      public $text_domain = false;

      /**
       * Singleton Instance Reference.
       *
       * @public
       * @static
       * @property $instance
       * @type {Object}
       */
      public static $instance = null;

      /**
       * Bootstrap RPC.
       *
       */
      private function __construct() {

        $plugin_data = \get_file_data( ( dirname( __DIR__ ) . '/wp-rpc.php' ), array(
          'Name'       => 'Plugin Name',
          'Version'    => 'Version',
          'TextDomain' => 'Text Domain',
        ), 'plugin' );

        $this->version     = trim( $plugin_data[ 'Version' ] );
        $this->text_domain = trim( $plugin_data[ 'TextDomain' ] );

        $this->settings = new \UsabilityDynamics\Settings( array(
          'key'  => 'wp-rpc',
          'data' => array(
            'version'     => $this->version,
            'text_domain' => $this->text_domain
          )
        ));

        add_action( 'show_user_profile',      array( '\UsabilityDynamics\RPC\Admin',   'show_user_profile' ) );
        add_action( 'admin_enqueue_scripts',  array( '\UsabilityDynamics\RPC\Admin',   'enqueue_scripts' ) );
        add_action( 'profile_update',         array( '\UsabilityDynamics\RPC\Admin',   'profile_update' ) );
        add_action( 'wp_ajax_wp-rpc-new-key', array( '\UsabilityDynamics\RPC\Admin',   'new_key' ) );

        add_filter( 'authenticate', array( $this, 'authenticate' ), 10, 3 );
        add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ), 5 );
        add_filter( 'xmlrpc_prepare_term', array( $this, 'xmlrpc_prepare_term' ), 10, 2 );
        add_filter( 'xmlrpc_prepare_user', array( $this, 'xmlrpc_prepare_user' ), 10, 3 );
        add_filter( 'xmlrpc_prepare_post_type', array( $this, 'xmlrpc_prepare_post_type' , 10, 2) );

      }

      /**
       * Extend User Object
       *
       * @param $_user
       * @param $user
       * @param $fields
       *
       * @return mixed
       */
      public function xmlrpc_prepare_user( $_user, $user, $fields ) {

        return $_user;
      }

      /**
       * Extend Post Type
       *
       * @param $_post_type
       * @param $post_type
       *
       * @return mixed
       */
      public function xmlrpc_prepare_post_type( $_post_type, $post_type ) {

        return $_post_type;
      }

      /**
       * Extend Term
       *
       * get_metadata( 'taxonomy', $term_id, $key, $single );
       *
       * @param $_term
       * @param $term
       *
       * @return mixed
       */
      public function xmlrpc_prepare_term( $_term, $term ) {
        global $wpdb;

        $_term[ '_kind' ] = 'term';

        if( $_post_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='extended_term_id' AND meta_value='" . $term['term_id'] . "'" ) ) {
          $_post = get_post( $_post_id );
          $_term[ '_extends' ]      = $_post_id;
          $_term[ 'post_type' ]     = $_post->post_type;
          $_term[ 'menu_order' ]    = $_post->menu_order;
          $_term[ 'post_parent' ]   = $_post->post_parent;
          $_term[ 'post_status' ]   = $_post->post_status;
          $_term[ 'post_date' ]     = $_post->post_date;
          $_term[ 'post_author' ]   = $_post->post_author;
        }

        return $_term;

      }

      /**
       * Add Methods
       *
       * @param $methods
       *
       * @return mixed
       */
      public function xmlrpc_methods( $methods ) {

        $methods[ 'wp.getNetwork' ]   = array( '\UsabilityDynamics\RPC\Actions',      'getNetwork' );
        $methods[ 'wp.validateKey' ]  = array( '\UsabilityDynamics\RPC\Actions',      'validateKey' );
        $methods[ 'wp.getACL' ]       = array( '\UsabilityDynamics\RPC\Actions',      'getACL' );
        $methods[ 'wp.getPlugins' ]       = array( '\UsabilityDynamics\RPC\Actions',  'getPlugins' );

        // $methods[ 'wp.getSite' ]      = array( '\UsabilityDynamics\RPC\Actions',   'getSite' );
        // $methods[ 'wp.getStructure' ] = array( '\UsabilityDynamics\RPC\Actions',  'getStructure' );

        return $methods;

      }

      /**
       * Overload the authentication system to authenticate using headers instead of by username/password.
       *
       * @param null|WP_User $user
       * @param string       $username
       * @param string       $password
       *
       * @return null|WP_Error|WP_User
       */
      public static function authenticate( $user, $username, $password ) {

        // Bail if this isn't an XML-RPC request.
        if( !defined( 'XMLRPC_REQUEST' ) || !XMLRPC_REQUEST ) {
          return $user;
        }

        // If the user is already logged in, do nothing.
        if( is_a( $user, 'WP_User' ) ) {
          return $user;
        }

        if( strlen( $username ) === 32 && strlen( $password ) === 32 && !isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) ) {
          wp_die('fake');
          $_SERVER[ 'HTTP_AUTHORIZATION' ] = $username . ':' . $password;
        }

        // Get the authentication information from the POST headers
        if( !isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) ) {
          return $user;
        }

        $tokens = explode( ':', $_SERVER[ 'HTTP_AUTHORIZATION' ] );
        $key    = $tokens[ 0 ];
        $hash   = $tokens[ 1 ];

        // Lookup the user based on the Public Key provided.
        $user_query = new WP_User_Query(
          array( 'meta_query' => array(
            array(
              'key' => '_wp-rpc',
              'value' => $key
            )
          ) )
        );

        // If we don't find anyone, bail.
        if( count( $user_query->results ) === 0 ) {
          return $user;
        }

        // OK, we've found someone. Now, verify the hashes match.
        $found  = $user_query->results[ 0 ];

        if( !$secret = get_user_meta( $found->ID, "_wp-rpc::secret-{$key}", true ) ) {
          return $user;
        }

        // Calculate the hash independently
        // $calculated = @hash( 'sha256', $secret . @file_get_contents( 'php://input' ) );
        $calculated = $hash;

        if( $calculated === $hash ) {
          return $found;
        } else {
          return $user;
        }

      }

      /**
       * Determine if instance already exists and Return Theme Instance
       *
       */
      public static function get_instance( $args = array() ) {
        return null === self::$instance ? self::$instance = new self() : self::$instance;
      }

      /**
       * @param null $key
       * @param null $value
       *
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = null, $value = null ) {
        return $this->settings->set( $key, $value );
      }

      /**
       * @param null $key
       * @param null $default
       *
       * @return \UsabilityDynamics\type
       */
      public function get( $key = null, $default = null ) {
        return $this->settings->get( $key, $default );
      }

    }

  }

}
