<?php
/*
Plugin Name: PMPro Customizations for How's U? Today
Plugin URI: https://eighty20results.com/paid-memberships-pro/do-it-for-me/
Description: Customizations for Paid Memberships Pro
Version: 1.1
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: https://eighty20results.com/thomas-sjolshagen/
*/

/*
	Member Numbers

	* Change the generate_member_number function if your member number needs to be in a certain format.
	* Member numbers are generated when users are registered or when the membership account page is accessed for the first time.
*/
//Generate member_number when a user is registered.
function howsu_generate_member_number($user_id)
{
	$member_number = get_user_meta($user_id, "member_number", true);

	//if no member number, create one
	if(empty($member_number))
	{
		global $wpdb;

		if ( class_exists( 'e20rTextitIntegration' ) ) {
			$class = e20rTextitIntegration::get_instance();
			$user_data = $class->getUserRecord( $user_id );
			$member_number = isset( $user_data->service_number ) && !empty( $user_data->service_number ) ? $user_data->service_number : null;
		}

		//this code generates a string 10 characters long of numbers and letters
		while(empty($member_number))
		{
			$scramble = md5(AUTH_KEY . current_time('timestamp') . $user_id . SECURE_AUTH_KEY);
			$member_number = substr($scramble, 0, 10);
			$check = $wpdb->get_var(
				$wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_value = %s LIMIT 1", $member_number )
			);

			if($check || is_numeric($member_number)) {
				$member_number = null;
			}
		}

		//save to user meta
		update_user_meta($user_id, "member_number", $member_number);

	}

	return $member_number;
}
add_action('user_register', 'howsu_generate_member_number');

//Show it on the membership account page.
function howsu_pmpro_account_bullets_bottom()
{
	if(is_user_logged_in())
	{
		global $current_user;

		//get member number
		$member_number = get_user_meta($current_user->ID, "member_number", true);

		//if no number, generate one
		if(empty($member_number)) {
			$member_number = generate_member_number( $current_user->ID );
		}

		//show it
		if(!empty($member_number))
		{
			?>
			<li><strong><?php _e("Member Number", "pmpro");?>:</strong> <?php echo $member_number?></li>
			<?php
		}
	}
}
add_action('pmpro_account_bullets_bottom', 'howsu_pmpro_account_bullets_bottom');
add_action('pmpro_invoice_bullets_bottom', 'howsu_pmpro_account_bullets_bottom');

//show member_number in confirmation emails
function howsu_pmpro_email_filter($email) {

	//only update admin confirmation emails
	if(strpos($email->template, "checkout") !== false)
	{
		if(!empty($email->data) && !empty($email->data['user_login']))
		{
			$user = get_user_by("login", $email->data['user_login']);
			if(!empty($user) && !empty($user->ID))
			{
				$member_number = get_user_meta($user->ID, "member_number", true);

				if(!empty($member_number))
					$email->body = str_replace("<p>Membership Level", "<p>Member Number:" . $member_number . "</p><p>Membership Level", $email->body);
			}
		}
	}

	return $email;
}
add_filter("pmpro_email_filter", "howsu_pmpro_email_filter", 30, 2);

