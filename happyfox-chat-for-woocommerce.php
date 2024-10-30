<?php
/*
Plugin Name: HappyFox Chat for WooCommerce
Plugin URI: https://happyfoxchat.com
Description: This plugin adds the HappyFox Chat widget to your WooCommerce site
Version: 1.2.1
Author: HappyFox Inc.
Author URI: http://happyfoxchat.com
License: MIT
*/

add_action('init', 'hfc_setup_widget');
add_action('admin_init', 'hfc_register_settings' );
add_action('admin_menu', 'hfc_admin_menu');
add_action('wp_footer', 'hfc_add_visitor_widget' );

function hfc_register_settings() {
    register_setting('happyfox-chat-for-woocommerce-settings', 'hfc_api_key');
    register_setting('happyfox-chat-for-woocommerce-settings', 'hfc_embed_token');
    register_setting('happyfox-chat-for-woocommerce-settings', 'hfc_access_token');
    if (is_plugin_active('woocommerce/woocommerce.php')) {
      include('happyfox-chat-for-woocommerce-cart-info.php');
    }
}

function hfc_admin_menu() {
    add_menu_page('HappyFox Chat Settings', 'HappyFox Chat', 'administrator', 'happyfox-chat-for-woocommerce-settings', 'happyfox_chat_settings_page', 'dashicons-format-chat');
    wp_enqueue_style('happyfox-chat-for-woocommerce-settings', WP_PLUGIN_URL . '/happyfox-chat-for-woocommerce/css/style.css');
}

function happyfox_chat_settings_page() {
  include('happyfox-chat-for-woocommerce-config.php');
  include('happyfox-chat-for-woocommerce-settings.php');
}

function hfc_setup_widget() {
  include('happyfox-chat-for-woocommerce-config.php');
  if( !session_id() )
    session_start();
  if (isset( $_POST['hfc_api_key_submission'] ) && $_POST['hfc_api_key_submission'] == '1') {
    $url = HFC_APP_INTEGRATION_URL . $_POST['hfc_api_key'];
    error_log($url);
    $result = wp_remote_get($url);
    $authorization_success = False;
    $json = json_decode($result['body']);
    if(isset($json->embedToken)) {
        update_option('hfc_api_key', $_POST['hfc_api_key']);
        update_option('hfc_embed_token', $json->embedToken);
        update_option('hfc_access_token', $json->accessToken);
        $authorization_success = True;
    }
    if(!$authorization_success) {
        status_header(400);
    }
  }
}

function hfc_add_visitor_widget() {
    include('happyfox-chat-for-woocommerce-config.php');
    $embed_token = get_option('hfc_embed_token');
    $access_token = get_option('hfc_access_token');
    $host_url = HFC_HOST_URL;
    $asset_url = HFC_ASSETS_URL;

    if($embed_token && $access_token) {
        echo <<<HTML
<!--Start of HappyFox Live Chat Script-->
<script>
 window.HFCHAT_CONFIG = {
     EMBED_TOKEN: "{$embed_token}",
     ASSETS_URL: "{$asset_url}"
 };

(function() {
  var scriptTag = document.createElement('script');
  scriptTag.type = 'text/javascript';
  scriptTag.async = true;
  scriptTag.src = window.HFCHAT_CONFIG.ASSETS_URL + '/js/widget-loader.js';

  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(scriptTag, s);
})();
</script>
<!--End of HappyFox Live Chat Script-->

HTML;
    }
}

?>
