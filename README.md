## User Validation for West Sound Brewers

In order to avoid invalid user registrations on Wordpress site, the registration process validates the user's email address against the Mailchimp list (current members of the club)

1. Settings require a mailchimp API Key and the Data Center for the mailchimp API
1. wp-admin/options.php needs the settings to be allowed
<code>
$allowed_options['wsb_user_options_group'] = array('wsb_mail_dc','wsb_user_api_key');
</code>
1. Some extra/invalid code needs to be cleaned up.
