<?php
/**
 * RPC Admin
 *
 */
namespace UsabilityDynamics\RPC {

  if( !class_exists( '\UsabilityDynamics\RPC\Admin' ) ) {

    class Admin {
      
      /**
       * Enqueue our admin-side scripts, styles, and localizations
       */
      public static function enqueue_scripts() {

        if ( 'profile' !== get_current_screen()->base ) {
          return;
        }

        // wp_enqueue_script( 'wp-rpc', XMLRPCS_URL . "/assets/js/secure_xml_rpc{$ext}", array( 'jquery' ), XMLRPCS_VERSION, true );
        // wp_enqueue_style( 'wp-rpc', XMLRPCS_URL . "/assets/css/src/secure_xml_rpc.css", array(), XMLRPCS_VERSION );
        // wp_localize_script( 'wp-rpc', 'wp-rpc', array() );

      }

      /**
       * Append the new UI to the user profile.
       *
       * @param WP_User $profileuser
       */
      public static function show_user_profile( $profileuser ) {
        ?>
        <h3><?php esc_html_e( 'Remote Publishing Permissions', 'wp-rpc' ); ?></h3>
        <table class="form-table wp-rpc_permissions">
          <tbody>
            <tr>
              <th scope="row"><?php esc_html_e( 'Allowed applications', 'wp-rpc' ); ?></th>
              <td><?php echo self::secure_keys_list( $profileuser ); ?></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e( 'Add a new application', 'wp-rpc' ); ?></th>
              <td><a id="wp-rpc-generate" href="#"><?php esc_html_e( 'Generate New', 'wp-rpc' ); ?></a></td>
            </tr>
          </tbody>
        </table>
        <script>

          /**
           * Add a new row to the UI.
           *
           * @param {event} e
           */
          function add_row( e ) {
            e.preventDefault();

            // First, remove the "no applications" row
            jQuery( document.getElementById( 'wp-rpc-no-apps' ) ).remove();

            // Fetch a new row from the server and inject it.
            var $request = jQuery.ajax({
              'type': 'POST',
              'url': ajaxurl,
              'data': { 'action': 'wp-rpc-new-key', '_nonce': "<?php echo wp_create_nonce( 'wp-rpc-new-key' ); ?>" },
              'dataType': 'html'
            }).done( function( data ) {
              jQuery( data ).insertAfter( document.getElementById( 'wp-rpc-app_body' ) );
            });
          }

          /**
           * Remove a row from the UI.
           *
           * @param {event} e
           */
          function remove_row( e ) {
            jQuery( this ).parents( 'tr' ).first().remove();
          }

          // Bind events
          jQuery( document.getElementById( 'wp-rpc-generate' ) ).on( 'click', add_row );
          jQuery( '.wp-rpc-delete' ).on( 'click', remove_row );

        </script>
      <?php
      }

      /**
       * Generate a table of the secure keys for the given user.
       *
       * @param WP_User $profileuser
       *
       * @return string
       */
      public static function secure_keys_list( $profileuser ) {
        $keys = get_user_meta( $profileuser->ID, '_wp-rpc' );

        $output = '<table id="wp-rpc-app_body">';
        $output .= '<thead>';
        $output .= '<tr><th>' . esc_html__( 'Application', 'wp-rpc' ) . '</th><th>' . esc_html__( 'Public Key', 'wp-rpc' ) . '</th><th>' . esc_html__( 'Secret Key', 'wp-rpc' ) . '</th></tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        foreach( (array) $keys as $key ) {
          $app    = get_user_meta( $profileuser->ID, '_wp-rpc::public-' . $key, true );
          $secret = get_user_meta( $profileuser->ID, '_wp-rpc::secret-' . $key, true );
          $output .= '<tr>';
          $output .= '<td><input name="wp-rpc-app[]" class="app-name" type="text" value="' . esc_attr( $app ) . '" /></td>';
          $output .= '<td><input name="wp-rpc-public-key[]" class="public-key" size="30" type="text" value="' . esc_attr( $key ) . '" readonly /></td>';
          $output .= '<td><input class="secret-key" type="text" size="30" value="' . esc_attr( $secret ) . '" readonly /></td>';
          $output .= '<td><span class="dashicons dashicons-no wp-rpc-delete"></span></td>';
          $output .= '</tr>';
        }

        // $output .= '<tr id="wp-rpc-no-apps"><td colspan="4">' . esc_html__( 'No applications currently authorized' ) . '</td></tr>';

        $output .= '</tbody></table>';

        return $output;
      }

      /**
       * Create a new app for the current user.
       */
      public static function new_key() {

        if ( ! wp_verify_nonce( $_POST['_nonce'] , 'wp-rpc-new-key' ) ) {
          wp_send_json_error();
        }

        // Get the current user
        $user     = wp_get_current_user();

        $public      = apply_filters( 'wp-rpc::public-key', wp_hash( time() . rand(), 'auth' ) );;
        $secret   = apply_filters( 'wp-rpc::secret-key', wp_hash( time() . rand() . $public, 'auth' ) );

        add_user_meta( $user->ID, '_wp-rpc', $public, false );
        add_user_meta( $user->ID, "_wp-rpc::secret-{$public}", $secret, true );
        add_user_meta( $user->ID, "_wp-rpc::public-{$public}", __( 'New Application', 'wp-rpc' ), true );

        $_key = (object) array(
          "public" => $public,
          "secret" => $secret
        );

        // Generate the output
        echo '<tr>';
        echo '<td><input class="app-name" name="wp-rpc-app[]" type="text" value="' . esc_attr__( 'New Application', 'wp-rpc' ) . '" /></td>';
        echo '<td><input class="public-key" size="30" name="wp-rpc-public-key[]" type="text" value="' . esc_attr( $_key->public ) . '" readonly /></td>';
        echo '<td><input class="secret-key" size="30" type="text" value="' . esc_attr( $_key->secret ) . '" readonly /></td>';
        echo '<td><span class="dashicons dashicons-no wp-rpc-delete"></span></td>';
        echo '</tr>';
        die();

      }

      /**
       * Update the user's secure keys.
       *
       * @param $user_id
       */
      public static function profile_update( $user_id ) {
        // Get the current user
        $user = wp_get_current_user();

        // Can only edit your own profile!!!
        if( $user_id !== $user->ID ) {
          return;
        }

        // Get the POSTed data
        $apps = array_map( 'sanitize_text_field', $_POST[ 'wp-rpc-app' ] );
        $keys = array_map( 'sanitize_text_field', $_POST[ 'wp-rpc-public-key' ] );

        // Get the user's existing keys so we can remove any that have been deleted
        $to_remove = array_diff( get_user_meta( $user_id, '_wp-rpc' ), $keys );

        foreach( (array) $to_remove as $remove ) {
          delete_user_meta( $user_id, "_wp-rpc::secret-{$remove}" );
          delete_user_meta( $user_id, "_wp-rpc::public-{$remove}" );
        }

        // Remove existing keys so we can update just the ones we want to keep
        delete_user_meta( $user_id, '_wp-rpc' );

        // Update the application names
        foreach( (array) $keys as $index => $key ) {
          add_user_meta( $user_id, '_wp-rpc', $key );
          update_user_meta( $user_id, "_wp-rpc::public-{$key}", $apps[ $index ] );
        }

      }

    }

  }

}
