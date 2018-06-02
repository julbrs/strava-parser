<?php

/*
Manage settings of the plugin
*/

if ( is_admin() ) {
  //$this->settings->hook();
  add_action( 'admin_menu', 'sparser_menu' );
  add_action( 'admin_init', 'sparser_settings' );
} else {
}

function sparser_menu() {

	//create new top-level menu
  add_options_page('Strava Parser', 'Strava Parser',
    'manage_options',
    'strava_parser',
    'sparser_settings_page'
  );
}

function sparser_settings_page() {
?>
<div class="wrap">
<h1>Strava Parser</h1>

<form method="post" action="options.php">
    <?php settings_fields( 'sparser_group' ); ?>
    <?php do_settings_sections( 'sparser_group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">MapBox API</th>
        <td><input type="text" name="mapbox_api" value="<?php echo esc_attr( get_option('mapbox_api') ); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Strava URL Custom Field Name</th>
        <td><input type="text" name="strava_cf" value="<?php echo esc_attr( get_option('strava_cf') ); ?>" /></td>
        </tr>

        <!--<tr valign="top">
        <th scope="row">Replace the following user by external contributor</th>
        <td><input type="text" name="user_to_replace" value="<?php echo esc_attr( get_option('user_to_replace') ); ?>" /></td>
      </tr>-->
    </table>

    <?php submit_button(); ?>

</form>
</div>
<?php }

function sparser_settings() { // whitelist options
  register_setting( 'sparser_group', 'mapbox_api' );
  register_setting( 'sparser_group', 'strava_cf' );
  //register_setting( 'sparser_group', 'user_to_replace' );
  }

  function general_admin_notice(){
      if ( get_option('strava_cf')==null || get_option('mapbox_api')==null) {
           echo '<div class="notice notice-error is-dismissible">
               <p>You must setup the Strava Parser plugin correctly (missing parameters)</p>
           </div>';
      }
  }
  add_action('admin_notices', 'general_admin_notice');

