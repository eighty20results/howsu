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
 * default template for the [pdb_record] shortcode for editing a record on the frontend
 *
 * this template uses a table to format the form
 * 
 * @version Participants Database 1.6
 */
if ( !is_user_logged_in() ) {
	wp_redirect( wp_login_url() );
	exit();
}
// Show error messages/warnings
$util = e20rUtils::get_instance();
$util->display_notice();
?>

<div class="wrap <?php echo $this->wrap_class ?>">
  <?php
  wp_nonce_field('e20r_pdb_update', 'e20r-pdb-nonce');
  /*
   * as of version 1.6 this template can handle the display when no record is found
   *
   *
   */
  if (!empty($this->participant_id)) {
  ?>

  <?php // output any validation errors
 $this->print_errors(); ?>
  
  <?php // print the form header
  $this->print_form_head()
  ?>

  <?php while ($this->have_groups()) { $this->the_group(); ?>
    <?php $this->group->print_title() ?>
    <?php $this->group->print_description() ?>
    
    <table  class="form-table">

      <tbody class="field-group field-group-<?php echo $this->group->name ?>">

      <?php
      // step through the fields in the current group
      
        while ($this->have_fields()) { $this->the_field(); ?>
      
      <tr class="<?php $this->field->print_element_class() ?>">
      
        <th for="<?php $this->field->print_element_id() ?>"><?php $this->field->print_label() ?></th>
        <td>
          <?php $this->field->print_element_with_id(); ?>
              <?php if ($this->field->has_help_text()) { ?>
          <span class="helptext"><?php $this->field->print_help_text() ?></span>
          <?php } ?>
        </td>
      </tr>
      <?php } // field loop ?>
      </tbody>
    </table>
  <?php } // group loop ?>
    <table class="form-table">
    <tbody class="field-group field-group-submit">
      <tr>
        <th><h3><?php $this->print_save_changes_label() ?></h3></th>
        <td class="submit-buttons">
          <?php $this->print_submit_button('button-primary'); // you can specify a class for the button, second parameter sets button text ?>
        </td>
      </tr>
    </tbody>
    </table><!-- end group -->
  <?php $this->print_form_close() ?>
    <?php } else { ?>
    <?php 
    /*
     * this part of the template is used if no record is found
     */
    echo empty(Participants_Db::$plugin_options['no_record_error_message']) ? '' : '<p class="alert alert-error">' . Participants_Db::plugin_setting('no_record_error_message') . '</p>';
    global $pmpro_pages;
    wp_redirect( get_permalink( $pmpro_pages['levels'] ) );
    exit;
    // echo empty(Participants_Db::$plugin_options['no_record_error_message']) ? '' : '<p class="alert alert-error">' . Participants_Db::plugin_setting('no_record_error_message') . '</p>';
    ?>
    <?php } ?>
</div>

