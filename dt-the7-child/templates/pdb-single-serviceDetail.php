<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 04/10/2015
 * Time: 12:27
 */

/*
 * template for displaying Customer Information
 *
 * single record template
 */

//print_r($_SESSION);

global $current_user;

if ( WP_DEBUG ) {
	error_log( "Loading serviceDetail data for user ID: {$current_user->ID} - {$_REQUEST['pdb']}" );
}

// Show error messages/warnings
$util = e20rUtils::get_instance();
$util->display_notice();

// get the template object for this record
$record = new PDb_Template( $this );

// Get TextIt Integration information
// FIXME: Redudnant. The service_level info should be included in the record??
// $ti        = e20rTextitIntegration::get_instance();
// $user_info = $ti->getUserRecord( $current_user->ID, true );

// $level = $ti->_process_text( $user_info->service_level );
if (empty($level) ) { $level = $record->record->service->fields->service_level->value; }

?>
<style>
	.edit {
		display: none !important
	}
</style>
<div class="wrap <?php echo $this->wrap_class ?>">
	<?php wp_nonce_field('e20r_pdb_update', 'e20r-pdb-nonce'); ?>
	<?php while ( $this->have_groups() ) : $this->the_group(); ?>

		<?php while ( $this->have_fields() ) : $this->the_field() ?>

			<?php if ( $this->group->name == "service" ) { ?>
				<table>
					<?php

					if ( $level == 'protector' && $this->field->name != "time_window_2" && $this->field->name != "time_window_3" ||
					     $level == 'weekend' && $this->field->name != "time_window_2" && $this->field->name != "time_window_3" ||
					     $level == 'guardian' && $this->field->name != "time_window_3" ||
					     $level == 'guardianangel'
					) { ?>
						<tr class="<?php echo Participants_Db::$css_prefix . $this->field->name ?>">
							<?php if ( $this->field->name !== "user_id" && $this->field->name !== "status" && $this->field->name !== "u_id" ) { ?>
								<td width="30%"
								    class="<?php echo $this->field->name ?> fieldTitle"><?php $this->field->print_label() ?></td>

								<td width="50%" rel="<?php echo $this->field->name ?>"
								    class="<?php echo $this->field->name; ?>_input"><?php $this->field->print_element() ?></td>
								<script> jQuery("[name=<?php echo $this->field->name; ?>]").attr('disabled', true);</script>
								<td width="20%">
									<?php if ( $this->field->name !== "service_level" && $this->field->name !== "user_id" && $this->field->name !== "status" && $this->field->name !== "u_id" ) { ?>
										<div class="<?php echo $this->field->name ?>_edit">
											<a id="edit" href="" name="<?php echo $this->field->name ?>"><i
													class="fa fa-pencil-square-o fa-2x"></i></a>
										</div>
										<div class="edit <?php echo $this->field->name ?>_save">
											<a type="submit" id="save" href=""
											   name="<?php echo $this->field->name ?>"><i
													class="fa fa-floppy-o fa-2x"></i></a>
										</div>
									<?php } ?>
								</td>
							<?php } ?>
						</tr>
					<?php } ?>
				</table>
			<?php } ?>
		<?php endwhile; // end of the fields loop ?>
	<?php endwhile; // end of the groups loop ?>
</div>
<?php //var_dump( $record->fields->service_number->value); ?>