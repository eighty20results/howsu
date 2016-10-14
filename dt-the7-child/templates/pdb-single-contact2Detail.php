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
// get the template object for this record

// Show error messages/warnings
$util = e20rUtils::get_instance();
$util->display_notice();

$record = new PDb_Template( $this );

$record->fields->service_number->value;

?>

<style>
	.edit {
		display: none !important
	}
</style>

<div class="wrap <?php echo $this->wrap_class ?>">
	<?php wp_nonce_field( 'e20r_pdb_update', 'e20r-pdb-nonce' ); ?>
	<?php while ( $this->have_groups() ) : $this->the_group(); ?>
		<?php while ( $this->have_fields() ) : $this->the_field() ?>
			<?php $empty_class = $this->get_empty_class( $this->field ); ?>
			<?php if ( $this->group->name == "contact_2" ) {
				?>
				<table>
					<tr class="<?php echo Participants_Db::$css_prefix . $this->field->name . ' ' . $empty_class ?>">

						<td width="30%"
						    class="<?php echo $this->field->name . ' ' . $empty_class ?>"><?php $this->field->print_label() ?></td>

						<td width="50%" name="<?php echo $this->field->name ?>"
						    class="<?php echo $this->field->name . '_input' ?>"><?php $this->field->print_value() ?></td>

						<td width="20%">
							<div class="<?php echo $this->field->name ?>_edit">
								<a id="edit" href="" name="<?php echo $this->field->name ?>"><i
										class="fa fa-pencil-square-o fa-2x"></i></a>
							</div>
							<div class="edit <?php echo $this->field->name ?>_save">
								<a type="submit" id="save" href="" name="<?php echo $this->field->name ?>"><i
										class="fa fa-floppy-o fa-2x"></i></a>
							</div>
						</td>

					</tr>
				</table>
			<?php } ?>
		<?php endwhile; // end of the fields loop ?>
	<?php endwhile; // end of the groups loop ?>
</div>
