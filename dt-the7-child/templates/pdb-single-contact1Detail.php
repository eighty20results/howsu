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
if ( !is_user_logged_in() ) {
	wp_redirect( wp_login_url() );
	exit();
}
/*
 * template for displaying Customer Information
 *
 * single record template
 */

// Show error messages/warnings
$util = e20rUtils::get_instance();
$util->display_notice();

// get the template object for this record
$record = new PDb_Template( $this );

$record->fields->service_number->value;

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
				<?php $empty_class = $this->get_empty_class( $this->field ); ?>
				<?php if ( $this->group->name == "contact_1" ) { ?>
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
	<hr>
	<div class="wrap <?php echo $this->wrap_class ?>">
		<?php while ( $this->have_groups() ) : $this->the_group(); ?>
			<?php while ( $this->have_fields() ) : $this->the_field() ?>
				<?php if ( $this->group->name == "contact_2" ) { ?>
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
