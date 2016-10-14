<?php
// File Security Check
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Set the content width based on the theme's design and stylesheet.
 *
 * @since 1.0.0
 */
if ( ! isset( $content_width ) ) {
	$content_width = 1200; /* pixels */
}

/**
 * Initialize theme.
 *
 * @since 1.0.0
 */
require( trailingslashit( get_template_directory() ) . 'inc/init.php' );

/**
 * Remove the admin bar (access to backend) if the user is NOT an admin
 */
function e20r_remove_admin_bar() {

	if (!current_user_can('manage_options') && !is_admin()) {
		show_admin_bar(false);
	}
}

add_action('after_setup_theme', 'e20r_remove_admin_bar');

if ( function_exists( 'yoast_breadcrumb' ) ) {
	function yoastBreadcrumbs() {
		// The start/end html is theme-specific.
		return yoast_breadcrumb( '<div class="wf-td"><div class="breadcrumbs text-normal" id="breadcrumbs">', '</div></div>', false );
	}

	// Override the theme's function for making breadcrumbs.
	// This is for 'The7' theme, the name of the function is theme-specific.
	add_filter( 'presscore_get_breadcrumbs', 'yoastBreadcrumbs', 1 );
}
