<?php
/**
 * Plugin Name:       wsb-user-check
 * Description:       check user against West Sound Brewers email list in MailChimp
 * Version:           0.1.1
 * Author:            Glenn Howald
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wsb-user-check
 *
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class WsbUserCheckPlugin
{
  function __construct()
  {
    global $wpdb;
    $this->charset = $wpdb->get_charset_collate();
    $this->tablename = $wpdb->prefix . "disallowed_emails";

    add_action('activate_wsb-user-check/wsb-user-check.php', array($this, 'onActivate'));
    add_action('register_post', array($this, 'wsb_user_checker'), 10, 3);
    add_action('admin_menu', array($this, 'setup_admin_menu'));
    add_action('amin_init', array($this, 'create_admin_options'));
  }

  function onActivate()
  {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta("CREATE TABLE $this->tablename (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      email varchar(60) NOT NULL DEFAULT '',
      PRIMARY KEY  (id)
    ) $this->charset;");
  }

  // admin action to set API key

  function wsb_user_checker($sanitized_user_login, $user_email, $errors)
  {
    global $wpdb;
    $dc = get_option('wsb_mail_dc');
    $apiKey = get_option('wsb_user_api_key');

    // return early if mailchimp API parameters $dc and $apiKey are missing/undefined?

    // call MailChimp API for exact match to $user_email
    $headers = array(
      'Authorization' => 'Basic ' . base64_encode("anystring:$apiKey"),
      'Accept' => 'application/json'
    );
    $response = json_decode(
      wp_remote_retrieve_body(
        wp_remote_get(
          "https://$dc.api.mailchimp.com/3.0/search-members?query=$user_email", 
          array('headers' => $headers)
        )
      )
    );

    // if not present, add to $this->tablename and error out
    if ($response->exact_matches->total_items !== 1) {
      $wpdb->insert($this->tablename, array(
        'email' => sanitize_text_field($user_email)
      ));
      $errors->add('email_error', 'Not a subscribed member of the West Sound Brewers. ' . $user_email . '. Follow signup process.');
    }

    // keep going.
    return $errors;
  }

  function setup_admin_menu()
  {
    add_options_page('Email Checker Page', 'West Sound Brewers Email Checker Plugin', 'manage_options', 'wsb-user-check', array($this, 'options_page'));
  }

  function options_page()
  {
    ?>
    <h2>WSB Email Checker</h2>
    <form action="options.php" method="post">
        <?php 
        settings_fields( 'wsb_user_options_group' ); 
        $option1 = get_option('wsb_mail_dc');
        echo '<span>DC</span><input type="text" name="wsb_mail_dc" value="' . esc_attr($option1) . '" />';
        $option2 = get_option('wsb_user_api_key');
        echo '<span>API Key</span><input type="text" name="wsb_user_api_key" value="' . esc_attr($option2) . '" />';
        submit_button(); ?>
    </form>
    <?php
  }

  function create_admin_options()
  {
    add_option('wsb_mail_dc', 'Mailchimp Data Center (DC) (us6)');
    add_option('wsb_user_api_key', 'Mailchimp API Key');
    register_setting('wsb_user_options_group', 'wsb_mail_dc');
    register_setting('wsb_user_options_group', 'wsb_user_api_key');

    add_settings_section('wsb_config_section', 'API Settings', null, 'wsb-user-check');

    add_settings_field('wsb_mail_dc', 'Mailchimp Data Center (DC) (us6)', array($this, 'cb_mail_dc'), 'wsb_user_options_group');
    add_settings_field('wsb_user_api_key', 'Mailchimp API Key', array($this, 'cb_mail_api_key'), 'wsb_user_options_group');

  }
  function cb_mail_dc()
  {
    $option = get_option('wsb_mail_dc');
    echo '<input type="text" name="wsb_mail_dc" value="' . esc_attr($option) . '" />';
  }

  function cb_mail_api_key() {
    $option = get_option('wsb_user_api_key');
    echo '<input type="text" name="wsb_user_api_key" value="' . esc_attr($option) . '" />';
  }
}

$wsbUserCheckPlugin = new WsbUserCheckPlugin();
