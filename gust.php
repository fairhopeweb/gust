<?php 
/*
Plugin Name: Gust
Plugin URI: https://github.com/ideag/gust
Description: A port of the Ghost admin interface
Author: Arūnas Liuiza
Version: 0.3.3
Author URI: http://wp.tribuna.lt/
*/
//error_reporting(-1);
define ('GUST_NAME',          'gust');
define ('GUST_SUBPATH',       gust_get_subpath());
define ('GUST_ROOT',          GUST_SUBPATH.'/'.GUST_NAME);
define ('GUST_API_ROOT',      '/api/v0\.1');
define ('GUST_TITLE',         'Gust');
define ('GUST_VERSION',       'v0.3.3');
define ('GUST_PHP_REQUIRED',  '5.3.0');
define ('GUST_PLUGIN_PATH',   plugin_dir_path(__FILE__));
define ('GUST_PLUGIN_URL',    plugin_dir_url(__FILE__));

register_activation_hook(__FILE__,'gust_install');
function gust_install(){
  gust_init_rewrites();
  flush_rewrite_rules();
  gust_permalink_check();
  gust_version_check();
} 

add_action('init','gust_init_rewrites');
add_action('pre_get_posts','gust_drop_in',1);
// monitor for PHP version and permalink changes
add_action('admin_init','gust_version_check');
add_action('admin_init','gust_permalink_check');

function gust_version_check() {
  // check for PHP >= 5.3
  if (version_compare(phpversion(), GUST_PHP_REQUIRED) < 0) {
    add_action( 'admin_notices', 'gust_bad_version_notice',1000 );   
    $basename = plugin_basename(__FILE__);
    deactivate_plugins($basename); 
    do_action('deactivate_'.$basename);
    $deactivated[$basename] = time(); 
    update_option( 'recently_activated', $deactivated + (array) get_option( 'recently_activated' ) );
  }
}
function gust_bad_version_notice() {
    ?>
    <div class="error">
        <p><?php echo sprintf(__('Gust: You PHP (%s) version is not supported, at least %s is required. Plugin deactivated.', 'gust' ),phpversion(),GUST_PHP_REQUIRED); ?></p>
    </div>
    <?php
}
function gust_permalink_check(){
  if (!gust_is_pretty_permalinks()) {
    add_action( 'admin_notices', 'gust_no_permalink_notice',1000 );   
  }
}
function gust_no_permalink_notice() {
    ?>
    <div class="error">
        <p><?php _e('Gust: You do not use pretty permalinks. Please enable them <a href="options-permalink.php">here</a> to use Gust.', 'gust' ); ?></p>
    </div>
    <?php
}

function gust_is_pretty_permalinks(){
  global $wp_rewrite;
  if ($wp_rewrite->permalink_structure == '')
    return false;
  else
    return true;
}

function gust_init_rewrites() {
  add_rewrite_tag( '%gust_api%', '(ghost|'.GUST_NAME.'|api)'); 
  add_rewrite_tag( '%gust_q%', '(.*)'); 
  add_permastruct('gust_calls', '%gust_api%/%gust_q%',array('with_front'=>false));
}

function gust_drop_in($q) {
  if ((get_query_var('gust_api')=='ghost'||get_query_var('gust_api')==GUST_NAME||get_query_var('gust_api')=='api' )&& $q->is_main_query()) {
    define('WP_ADMIN',true);
    require_once(GUST_PLUGIN_PATH.'/assets/flight/Flight.php');
    Flight::set('flight.views.path', GUST_PLUGIN_PATH.'/views');
    if (get_query_var('gust_api')=='api' && $q->is_main_query()) {
      require_once('gust.class.php');
      require_once('gust-api.php');
    } else if (get_query_var('gust_api')==GUST_NAME && $q->is_main_query()) {
      require_once('gust.class.php');
      require_once('gust-views.php');
    } else if (get_query_var('gust_api')=='ghost' && $q->is_main_query()) {
      require_once('gust.class.php');
      require_once('gust-views.php');
    }
    Flight::start();
    die('');
  }
}

/*
function gust_uuid_post($post_id) {
  $uuid = get_post_meta($post_id,'_uuid',true);
  if (!$uuid) {
    $uuid = gust_gen_uuid();
    update_post_meta( $post_id, '_uuid', $uuid );
  }
  return $uuid;
}

function gust_gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}
*/
function get_avatar_url($id_or_email, $size=96, $default='', $alt=false){
    $get_avatar = get_avatar( $id_or_email, $size, $default, $alt );
    preg_match("/src='(.*?)'/i", $get_avatar, $matches);
    return $matches[1];
}

function gust_get_subpath(){
  $url = get_bloginfo('url');
  $url = parse_url($url);
  $url = isset($url['path'])?$url['path']:'';
  return $url;
}

?>