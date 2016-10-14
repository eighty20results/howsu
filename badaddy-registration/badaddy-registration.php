<?php
/*
  Plugin Name: BadAddy Registration
  Description: Custom WP Registration for Howsu..
  Version: 1.3.3
  Author: Andrew Walker example from (Tristan Slater w/ Agbonghama Collins)
  Author URI: http://badaddy.uk
 */

/**
 * Fixes:
 *
 * @since 1.1 - Added bypass of registration process if user is already logged in. (by Eighty / 20 Results)
 * @since 1.1 - Use filter to return the page ID for the next step in the registration process (by Eighty / 20 Results)
 * @since 1.3 - Split CSS & JS out of main plugin file ( Eighty / 20 Results )
 * @since 1.3 - Make registration form responsive. ( Eighty / 20 Results )
 * @since 1.3.1 - Include filter for email address as a valid username (including email addresses w/ + characters. ( Eighty / 20 Results )
 * @since 1.3.2 - Fix field sanitation and login URL for WP
 * @since 1.3.3 - Use page stub, not numeric page ID
 */


/////////////////
// PLUGIN CORE //
/////////////////

function cr( $fields, $errors) {

	// Check args and replace if necessary
	if (!is_array($fields))     $fields = array();
	if (!is_wp_error($errors))  $errors = new WP_Error;

	// Get chosen level
	$level = isset( $_REQUEST['level'] ) ? sanitize_text_field( $_REQUEST['level']) : null;

	// The "Service Details" page ID
	$next_page = apply_filters('howsu_service_details_page_stub', '/service-details/' );
	$url = add_query_arg( 'level', $level, site_url( $next_page ) );

	// Skip this step if the user is already logged in.
	if ( is_user_logged_in() ) {

		wp_redirect( $url );
		exit();
	}

	// Check for form submit
	if (isset($_POST['submit'])) {

		// Get fields from submitted form
		$fields = cr_get_fields();

		// Validate fields and produce errors
		if (cr_validate($fields, $errors)) {

			// If successful, register user
			$insertUser = wp_insert_user($fields);

			//var_dump($insertUser);

			if ( $insertUser ) {
				// get the username from the URL

				// get the user data (need the ID for login)
				$user = get_user_by( 'id', $insertUser );
				// if a user is returned, log them in
				if ( $user && ! user_can( $user->ID, 'manage_options' ) ) {
					wp_set_current_user( $user->ID, $insertUser );
					wp_set_auth_cookie( $user->ID );
					do_action( 'wp_login', $insertUser );
					wp_redirect( $url );
					exit();
				}
			}
			// And display a message
			echo 'Registration complete. Go to the <a href="' . wp_login_url() . '">login page</a>.';

			// Clear field data
			$fields = array();
		}
	}

	// Santitize fields
	cr_sanitize($fields);

	// Generate form
	cr_display_form($fields, $errors);
}

function cr_sanitize(&$fields) {

	$fields['user_login']   =  isset($fields['user_login'])  ? sanitize_user($fields['user_login']) : '';
	$fields['user_pass']    =  isset($fields['user_pass'])   ? esc_attr($fields['user_pass']) : '';
	$fields['user_email']   =  isset($fields['user_email'])  ? sanitize_email($fields['user_email']) : '';
}

function cr_display_form($fields = array(), $errors = null) {

	// Check for wp error obj and see if it has any errors
	if (is_wp_error($errors) && count($errors->get_error_messages()) > 0) {

		// Display errors
		?><ul><?php
		foreach ($errors->get_error_messages() as $key => $val) {
			?><li>
			<?php echo $val; ?>
			</li><?php
		}
		?></ul><?php
	}

	// Disaply form

	?>
<form action="<?php $_SERVER['REQUEST_URI'] ?>" method="post" id="howsu-registration-form">
	<input id="loginInput" type="hidden" size="40" name="user_login" value="<?php echo (isset($fields['user_login']) ? $fields['user_login'] : '') ?>">
	<table class="howsu-registration-table">
		<tbody class="howsu-registration">
			<tr>
				<td colspan="2" bgcolor="#f6f6f6">
					<p>Great Choice! Let's create your online account where you can view and manage your subscription.  First let's start by creating your account login details</p>
				</td>
			</tr>
			<tr>
				<td>
					<label class="howsu-reg-label" for="email">Email <strong style="color: red; vertical-align: top; font-size: 12px;">*</strong></label>
				</td>
				<td>
					<input class="howsu-reg-field" id="emailInput" type="text" size="80" name="user_email" value="<?php echo (isset($fields['user_email']) ? $fields['user_email'] : '') ?>">
					<script>
					</script>
				</td>
			</tr>
			<tr>
				<td>
					<label class="howsu-reg-label" for="user_pass">Password <strong style="color: red; vertical-align: top; font-size: 12px;">*</strong></label>
				</td>
				<td>
					<input class="howsu-reg-field" type="password" size="80" name="user_pass">
				</td>
			</tr>
			<tr>
				<td>
					<label class="howsu-reg-label" for="user_pass_reEnter">Confirm Password <strong style="color: red; vertical-align: top; font-size: 12px;">*</strong></label>
				</td>
				<td>
					<input class="howsu-reg-field" type="password" size="80" name="user_pass_reEnter">
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="submit" name="submit" value="Register" disabled>
				</td>
			</tr>
		</tbody>
	</table>

	</form><?php
}

function cr_get_fields() {
	return array(
		'user_login'   =>  isset($_POST['user_login'])   ?  sanitize_text_field( $_POST['user_login'] )  :  '',
		'user_pass'    =>  isset($_POST['user_pass'])    ?  esc_attr( $_POST['user_pass'] )   :  '',
		'user_email'   =>  isset($_POST['user_email'])   ?  sanitize_email( $_POST['user_email'] )       :  ''
	);
}

function cr_validate(&$fields, &$errors) {

	// Make sure there is a proper wp error obj
	// If not, make one
	if (!is_wp_error($errors))  $errors = new WP_Error;

	// Validate form data

	if (empty($fields['user_login']) || empty($fields['user_pass']) || empty($fields['user_email'])) {
		$errors->add('field', 'Required form field is missing');
	}

	if (strlen($fields['user_login']) < 4) {
		$errors->add('username_length', 'Username too short. At least 4 characters is required');
	}

	if (username_exists($fields['user_login']))
		$errors->add('user_name', 'Sorry, that username already exists!');

	if (!validate_username($fields['user_login'])) {
		$errors->add('username_invalid', 'Sorry, the username you entered is not valid');
	}

	if (!is_email($fields['user_email'])) {
		$errors->add('username_invalid', 'Sorry, the username you entered is not valid');
	}

	if (email_exists($fields['user_email']))
		$errors->add('user_name', 'Sorry, that username already exists!');



	if (strlen($fields['user_pass']) < 5) {
		$errors->add('user_pass', 'Password length must be greater than 5');
	}

	// If errors were produced, fail
	if (count($errors->get_error_messages()) > 0) {
		return false;
	}

	// Else, success!
	return true;
}

/**
 * Filter which will permit a valid email address to be used as a username.
 *
 * @param bool      $is_valid
 * @param string    $username
 *
 * @return bool
 */
function cr_allow_email( $is_valid, $username ) {

	if ( false == $is_valid ) {
		return filter_var($username, FILTER_VALIDATE_EMAIL);
	}

	return $is_valid;
}

add_filter('validate_username', 'cr_allow_email', 10, 2);

///////////////
// SHORTCODE //
///////////////

// The callback function for the [cr] shortcode
function cr_cb() {
	$fields = array();
	$errors = new WP_Error();

	// Buffer output
	ob_start();

	// Custom registration, go!
	cr($fields, $errors);

	// Return buffer
	return ob_get_clean();
}
add_shortcode('badaddy_registration', 'cr_cb');

function cr_enqueue() {
	wp_enqueue_style('badaddy-registration', plugin_dir_url(__FILE__) . 'css/badaddy.css', array( 'style' ), '1.1' );
	wp_enqueue_script('badaddy-registration', plugin_dir_url(__FILE__) . 'javascript/badaddy-registration.js', array( 'jquery' ), '1.1', true );
}
add_action('wp_enqueue_scripts', 'cr_enqueue', 15 );