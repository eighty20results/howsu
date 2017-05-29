<?php
/**
 * Copyright (c) 2016-2017 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/*
 * default template for signup form
 *
 * If the signup form setting "Show Field Groups" is selected, the form fields
 * will be grouped by the field groups that have their "visible" attribute
 * checked in the manage database fields page.
 *
 * If the "Show Field Groups" setting is not checked, the fields will be shown
 * in the same order, but without the group titles.
 *
 * this template is a simple demonstration of what is possible it is set up to
 * output the form exactly as it was output before we brought in templates so the
 * upgrade will not affect existing installations
 *
 * for those unfamiliar with PHP, just remember that something like
 * <?php echo $field->title ?> just prints out the field's title. You can move it
 * around, but leave all the parts between the <> brackets as they are.
 *
 */

if ( !is_user_logged_in() ) {
	wp_redirect( wp_login_url() );
	exit();
}

//var_dump($_SESSION);
global $current_user, $wpdb;

// Show error messages/warnings
$util = e20rUtils::get_instance();
$util->display_notice();

?>
<div class="wrap <?php echo $this->wrap_class ?>">

	<?php // output any validation errors
	$this->print_errors(); ?>

	<?php $this->print_form_head(); // this must be included before any fields are output. hidden fields may be added here as an array argument to the function ?>

	<!-- TextIt Service info handling -->
	<?php

	if ( ! class_exists( 'e20rTextitIntegration' ) ) {
		if ( WP_DEBUG ) {
			error_log( "Couldn't find the TextIt Integration functionality??" );
		}

		return;
	}

	$ti         = e20rTextitIntegration::get_instance();
	$level_name = isset( $_REQUEST['level'] ) ? sanitize_text_field( $_REQUEST['level'] ) : null;
	$level_map  = $ti->membershipLevelMap();

	$i = 1;

	$user_record = $ti->getUserRecord( $current_user->ID );

	if ( empty( $level_name ) ) {
		$level      = pmpro_getMembershipLevelForUser();
		if ( !empty( $level ) ) {
			$level_name = $ti->_process_text( $level->name );
		} else {
			$level_name = $ti->_process_text('Free');
		}
	}

	if ( WP_DEBUG ) {
		error_log( "Using the following level: {$level_name}" );
	}

	if ( ! empty( $user_record ) ) {
		$one_time_fee = $user_record->onetimefee;
	}

	if ( WP_DEBUG ) {
		error_log( "Level Mappings: " . print_r( $level_map, true ) );
	}

	if ( ! empty( $level_name ) ) {
		$pmpro_level_id = $level_map[ $level_name ]['id'];

		$_SESSION['pmpro_level_id'] = $pmpro_level_id;
	} else {
		$pmpro_level_id = 0;
	}

	if ( WP_DEBUG ) {
		error_log( "Using PMPro Level: {$pmpro_level_id} for {$level_name}" );
	}
	?>
	<!-- TextIt Service info handling ends -->

	<table class="form-table pdb-signup">
		<?php wp_nonce_field('e20r_pdb_update', 'e20r-pdb-nonce'); ?>
		<?php while ( $this->have_groups() ) : $this->the_group(); ?>

			<tbody class="field-group field-group-<?php echo $this->group->name ?>">
			<?php if ( $this->group->printing_title() ) : // are we printing group titles and descriptions? ?>
				<tr class="signup-group">
					<td colspan="2">
						<?php $this->group->print_title() ?>
						<?php $this->group->print_description() ?>
					</td>
				</tr>
			<?php else : ?>
			<?php endif; // end group title/description row ?>
			<?php while ( $this->have_fields() ) : $this->the_field(); ?>
				<?php
				//var_dump($this->field->name);
				$element = $this->field->name;
				if ( $this->field->name == "time_window" || $this->field->name == "time_window_2" || $this->field->name == "time_window_3" ) {
					if ( $i <= $pmpro_level_id ) {
						$i ++; ?>
						<tr class="<?php $this->field->print_element_class() ?>">
						<th for="<?php $this->field->print_element_id() ?>"><?php $this->field->print_label(); // this function adds the required marker ?></th>
						<td>
						<?php if ( $this->field->has_help_text() ) : ?>
							<span class="helptext"
							      style="font-size: 15px;"><?php $this->field->print_help_text() ?></span>
						<?php endif ?>
						<?php $this->field->print_element_with_id();
					}
				} else { ?>
					<tr class="<?php $this->field->print_element_class() ?>">
					<th for="<?php $this->field->print_element_id() ?>"><?php $this->field->print_label(); // this function adds the required marker ?></th>
					<td><?php if ( $element == "service_number" ) { ?>
						<input id="countrycodeBox" type="text" value="+44" size="6" readonly/>
					<?php } ?>
					<?php $this->field->print_element_with_id();
					if ( $this->field->has_help_text() ) :?>
						<span class="helptext" style="font-size: 15px;"><?php $this->field->print_help_text() ?></span>
					<?php endif ?>
				<?php } ?>
				</td>
				</tr>
			<?php endwhile; // fields ?>

			</tbody>

		<?php endwhile; // groups ?>

		<tbody class="field-group field-group-submit">

		<tr>
			<td class="submit-buttons">

				<?php $this->print_submit_button( 'button-primary' ); // you can specify a class for the button ?>

			</td>
			<td class="submit-buttons">

				<?php $this->print_retrieve_link(); // this only prints if enabled in the settings ?>

			</td>
		</tr>

		</tbody>
	</table>

	<?php $this->print_form_close() ?>

</div>
<script type="text/javascript">
	jQuery(document).ready(function () {

		jQuery('[name="user_id"]').attr("value", "<?php esc_attr_e( $current_user->user_email ); ?>").focus();

		var servicelevel = "<?php echo isset( $level_map[ $level_name ]['name'] ) ? esc_attr( $level_map[ $level_name ]['name'] ) : null; ?>";

		jQuery('[name="service_level"]').attr("value", servicelevel).focus();
		jQuery('[name="service_type"]').focus();

		var userDetail = <?php echo json_encode( $user_record ); ?>;

		if (userDetail) {
			jQuery('[name="service_type"]').val(userDetail.service_type);
			jQuery('[name="service_number"]').val(userDetail.service_number);
			jQuery('[name="time_zone"]').val(userDetail.time_zone);
			jQuery('[name="time_window"]').val(userDetail.time_window);
			jQuery('[name="time_window_2"]').val(userDetail.time_window_2);
			jQuery('[name="time_window_3"]').val(userDetail.time_window_3);
		} else {
			console.log('No user record found');
		}
	});
</script>
