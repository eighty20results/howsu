<?php
/*
 * default template for the [pdb_record] shortcode for editing a record on the frontend
 *
 * this template uses a table to format the form
 * 
 * @version Participants Database 1.6
 */
?>
<div class="wrap <?php echo $this->wrap_class ?>">
	<?php
	/*
	 * as of version 1.6 this template can handle the display when no record is found
	 */

	// Show error messages/warnings
	$util = e20rUtils::get_instance();
	$util->display_notice();

	if ( ! empty( $this->participant_id ) ) :

		$this->print_errors();
		?>

		<?php
		// Set the submission (payment_ page & arguments
		// FIXME: If PMPro MMPU is installed, this needs to be adjusted to support multiple levels being selected during the checkout.
		$this->submission_page = add_query_arg( 'level', $_SESSION['pmpro_level_id'], $this->submission_page );

		if ( WP_DEBUG ) {
			error_log( "Using submission URL {$this->submission_page}" );
		}

		?>

		<?php // print the form header
		$this->print_form_head();
		wp_nonce_field( 'e20r_pdb_update', 'e20r-pdb-nonce' );
		?>

		<?php while ( $this->have_groups() ) : $this->the_group(); ?>
		<?php $this->group->print_title() ?>
		<?php $this->group->print_description() ?>

		<table class="form-table">

			<tbody class="field-group field-group-<?php echo $this->group->name ?>">

			<?php
			// step through the fields in the current group

			while ( $this->have_fields() ) : $this->the_field();
				?>

				<tr class="<?php $this->field->print_element_class() ?>">

					<th for="<?php $this->field->print_element_id() ?>"><?php $this->field->print_label() ?></th>
					<td>

						<?php $this->field->print_element_with_id(); ?>

						<?php if ( $this->field->has_help_text() ) : ?>
							<span class="helptext"><?php $this->field->print_help_text() ?></span>
						<?php endif ?>

					</td>

				</tr>

			<?php endwhile; // field loop ?>

			</tbody>

		</table>

	<?php endwhile; // group loop
		?>
		<table class="form-table">

			<tbody class="field-group field-group-submit">

			<tr>
				<th><h3><?php $this->print_save_changes_label() ?></h3></th>
				<td class="submit-buttons">
					<?php $this->print_submit_button( 'button-primary' ); // you can specify a class for the button, second parameter sets button text
					?>
				</td>
			</tr>

			</tbody>

		</table><!-- end group -->

		<?php $this->print_form_close() ?>

	<?php else : ?>

		<?php
		/*
		 * this part of the template is used if no record is found
		 */
		global $pmpro_pages;

		echo empty( Participants_Db::$plugin_options['no_record_error_message'] ) ? '' : '<p class="alert alert-error">' . Participants_Db::plugin_setting( 'no_record_error_message' ) . '</p>';

		wp_redirect( get_permalink( $pmpro_pages['levels'] ) );
		exit;

		// wp_redirect('/?page_id=428');
		?>

	<?php endif ?>

</div>
