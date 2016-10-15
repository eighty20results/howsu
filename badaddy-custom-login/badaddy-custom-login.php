<?php
/*
  Plugin Name: BadAddy Login
  Description: Custom WP Login for Howsu..
  Version: 1.3
  Author: Andrew Walker
  Author URI: http://badaddy.uk
 */

/**
 * @since 1.2 - Updated background & text color (red/white) for login error message
 * @since 1.3 - Handle login & errors in this plugin
 * @since 1.4 - Use page slugs instead of page IDs (transitioning to being able to configure on an option page), remove unneeded code
 *
 */

function badaddy_verify_user( $user, $username, $password ) {

	/* page_id = 975 - /login/ */
	$login_page = add_query_arg( array( 'login' => 'empty' ), home_url( '/login/' ) );

	if (empty( $username) || empty($password)) {

		wp_safe_redirect( $login_page );
		exit;
	}
}

add_action( 'authenticate', 'badaddy_verify_user', 1, 3);  // hook failed login


function badaddy_auth_failure() {

	$login_page = add_query_arg( array( 'login' => 'failed' ), home_url( '/login/') );

	wp_safe_redirect( $login_page );
	exit;
}

add_action( 'wp_login_failed', 'badaddy_auth_failure', 10 );

function badaddy_redirect_to_custom() {

	$login_page = add_query_arg( array( 'login' => null ), home_url( '/login/' ) );
	$page_viewed = basename($_SERVER['REQUEST_URI']);

	if ('wp-login.php' == $page_viewed && 'GET' == $_SERVER['REQUEST_METHOD']) {
		wp_safe_redirect( $login_page );
		exit;
	}
}
add_action( 'init', 'badaddy_redirect_to_custom' );

// The callback function for the [cr] shortcode
function badaddy_login($atts)
{
	global $wpdb;
	global $current_user;
	global $pmpro_pages;

	//print_r($current_user);
	$pdb_row = $wpdb->get_row(
		$wpdb->prepare(
			"
			SELECT * 
			FROM xwdi_participants_database 
			WHERE user_id = %s 
			ORDER BY id DESC 
			LIMIT 1
			",
			$current_user->user_email
		)
	);

	if (is_user_logged_in()) {

		if (empty( $pdb_row ) ) {
			wp_redirect('/service-details/');
		} else {
			$url = add_query_arg( 'pid', $pdb_row->id, get_permalink( $pmpro_pages['account']) );
			wp_safe_redirect($url);
			exit();
		}
	} ?>

	<div style="margin-left: 20%; margin-right: 20%; min-width: 60% !important"> <?php

		$prompt = null;

		if( isset($_GET['login']) && !empty($_GET['login']) ){

			/*
			if ( 'empty' == $_GET['login'] && isset($_POST['pwd']) && empty( $_POST['pwd'] ) ) {
				$prompt = __("Sorry! You need to specify a password. Please try again.");
			}

			if ( 'empty' == $_GET['login'] && isset( $_POST['log'] ) && !is_email($_POST['log']) ) {
				$prompt = __( "Sorry! You need to use a valid email address.  Please try again." );
			}
			*/
			if ( 'failed' == $_GET['login'] || 'empty' == $_GET['login'] ) {
				$prompt = __( "Sorry! We experienced an authentication error.  Please try again." );
			}

			if ( !empty($prompt)) {
				?>
				<div
					style="text-align: center; padding: 5px; background-color: rgba(188, 24, 34, 0.5); color: white; font-size: 18px;"><?php echo $prompt; ?></div>
				<?php
			}
		}

		wp_login_form(
			array(
				'remember' => true,
				// 'redirect' => (is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
				'redirect' => site_url( $_SERVER['REQUEST_URI'] ),
				'form_id' => 'loginform',
				'id_email' => 'user_email',
				'id_password' => 'user_pass',
				'id_remember' => 'rememberme',
				'id_submit' => 'wp-submit',
				'label_username' => __('Email address'),
				'label_password' => __('Password'),
				'label_remember' => __('Remember Me'),
				'label_log_in' => __('Log In'),
				'value_username' => '',
				'value_remember' => false
			)
		);
		?>

		<a class="forgot-password" href="<?php echo wp_lostpassword_url(); ?>">
			<?php _e('Forgot your password?', 'personalize-login'); ?>
		</a>
	</div>
	<?php
}
add_shortcode('badaddy_login', 'badaddy_login');

function badaddy_logout() {
	wp_logout();
	wp_safe_redirect( site_url() );
	exit;
}

add_shortcode('badaddy_logout', 'badaddy_logout');
