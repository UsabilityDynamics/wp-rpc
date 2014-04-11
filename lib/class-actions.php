<?php
/**
 * RPC Actions
 *
 */
namespace UsabilityDynamics\RPC {

  if( !class_exists( '\UsabilityDynamics\RPC\Actions' ) ) {

    class Actions {

      static public function getSite( $args ) {
        global $wp_xmlrpc_server;

        return null;
      }

      static public function getPlugins( $args ) {
        global $wp_xmlrpc_server;

        return array( array(
          "site" => get_option( 'active_plugins' ),
          "network" => get_option( 'active_plugins' )
        ) );

      }

      static public function getACL( $args ) {
        global $wp_xmlrpc_server;

        return null;
      }

      static public function getStructure( $args ) {
        global $wp_xmlrpc_server;

        return null;
      }

      static public function validateKey( $args ) {
        global $wp_xmlrpc_server;

        return array();
      }

      static public function getNetwork( $args ) {
        global $wp_xmlrpc_server;

        $wp_xmlrpc_server->escape( $args );

        $blog_id  = $args[0];
        $username = $args[1];
        $password = $args[2];

        if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) ) {
          return $wp_xmlrpc_server->error;
        }

        // do_action( 'xmlrpc_call', 'wp.getNetwork', $args );

        return $user->ID;

      }

    }

  }

}
