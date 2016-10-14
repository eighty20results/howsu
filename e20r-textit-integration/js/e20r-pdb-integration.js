/**
 * Created by sjolshag on 9/30/16.
 */

(function () {
    "use strict";

    window.console.log("Loading PDB handler");

    var pdb = {
        init: function () {

            var self = this;

            // Fix settings for Country settings on service-detail page (if applicable)
            if (textIt.userDetail && textIt.userDetail.country) {
                jQuery('select[name="country_code"]').val(textIt.userDetail.country);
                jQuery.each(telcodes, function (field, value) {
                    if (value.Country === textIt.userDetail.country) {
                        //console.log(value.Dial + ' - ' + value.Country);
                        jQuery('#countrycodeBox').val('+' + value.Dial);
                    }
                });
            } else {
                jQuery('select[name="country_code"]').val('United Kingdom');
                jQuery('select[name="time_zone"]').val('(GMT+00:00) Greenwich Mean Time : Dublin  Etc/Greenwich');
            }

            if (textIt.userDetail && textIt.userDetail.service_type) {
                jQuery('input[name="service_type"]').each(function () {

                    var radio = jQuery(this);
                    if (radio.val() === textIt.userDetail.service_type) {
                        radio.prop('checked', true);
                    }
                });

                var sn = jQuery('input[name="service_number"]');
                var v = sn.val();

                if (v === '') {
                    sn.val(v);
                }
            }

            jQuery('#edit').on('click', function (e) {
                self.edit(e, this);
            });

            jQuery('#save').on('click', function (e) {

                self.save(e, this);
            });

            jQuery('[name="country_code"]').on('change', function () {
                var c = jQuery('[name="country_code"] option:selected').val();
                var code = '';

                jQuery.each(telcodes, function (field, value) {
                    if (value.Country === c) {
                        //console.log(value.Dial + ' - ' + value.Country);
                        jQuery('#countrycodeBox').val('+' + value.Dial);
                    }
                });
            });

            jQuery('[name="service_number"]').on('change', function () {
                var elem = jQuery(this);
                var c = elem.val();
                var hasZero = c.substr(0, 1);
                if (hasZero === '0') {
                    elem.val(c.substring(1));
                }
            });

            jQuery('input[type=submit]').on('click', function () {
                var c = jQuery('#countrycodeBox').val();
                var sn = jQuery('[name="service_number"]');
                var n = sn.val();
                if ( n && (n.substr(0, 1) !== '+') ) {
                    sn.val(c + n);
                }
            });

        },
        save: function (e, element) {

            e.preventDefault();

            var $nonce_val = jQuery('#e20r-pdb-nonce').val();
            var attrName = jQuery(element).attr('name');

            var box = "." + attrName + "_input";

            var record_id = textIt.userDetail.id;
            var $service_number = textIt.userDetail.service_number;

            var newVal = jQuery(box).text();
            var column = jQuery(box).attr('name');

            jQuery(box).attr('contenteditable', false);

            jQuery("div." + attrName + "_edit").removeClass("edit");
            jQuery("div." + attrName + "_save").addClass("edit");

            //console.log('VALUE: ' + newVal + ' - And Column to update: ' + column);

            jQuery.ajax({
                url: textIt.settings.ajaxurl,
                type: 'POST',
                timeout: textIt.settings.timeout,
                data: {
                    'action': 'e20r_pdb_update',
                    'nonce': $nonce_val,
                    'pdb': record_id,
                    'col': attrName, // jQuery(element).attr('name'),
                    'val': newVal,
                    'sn': $service_number
                },
                success: function (res) {

                    window.console.log("Response from server: ", res);

                    if (res.success) {

                        var $url = window.location.href + '?pdb=' + record_id;
                        window.console.log("Redirecting to: ", $url);

                        window.location = $url;
                    }
                }
            });
        },
        edit: function (e, element) {
            e.preventDefault();

            var attrName = jQuery(element).attr('name');
            var box = "." + attrName + "_input";

            window.console.log(box);
            jQuery(box).attr('contenteditable', true);

            jQuery("div." + attrName + "_save").removeClass("edit");
            jQuery("div." + attrName + "_edit").addClass("edit");

            jQuery(box).focus();

        }
    };

    pdb.init();
}(jQuery));

jQuery(document).ready(function () {
    "use strict";

    // var userDetail = textIt.userDetail;

    if (textIt.userDetail) {
        // Main
        jQuery('input[name="first_name"]').val(textIt.userDetail.first_name);
        jQuery('input[name="last_name"]').val(textIt.userDetail.last_name);
        jQuery('input[name="address"]').val(textIt.userDetail.address);
        jQuery('input[name="city"]').val(textIt.userDetail.city);
        jQuery('input[name="country"]').val(textIt.userDetail.country);
        jQuery('input[name="zip"]').val(textIt.userDetail.zip);
        jQuery('input[name="phone"]').val(textIt.userDetail.phone);
        jQuery('input[name="user_id"]').val(textIt.userDetail.user_id);
        jQuery('input[name="mailing_list"]').val(textIt.userDetail.mailing_list);

        // Contact 1
        jQuery('input[name="full_name_c1"]').val(textIt.userDetail.full_name_c1);
        jQuery('input[name="contact_number_c1"]').val(textIt.userDetail.contact_number_c1);
        jQuery('input[name="contact_number_2_c1"]').val(textIt.userDetail.contact_number_2_c1);
        jQuery('input[name="email_c1"]').val(textIt.userDetail.email_c1);
        jQuery('input[name="relationship_c1"]').val(textIt.userDetail.relationship_c1);

        // Contact 2
        jQuery('input[name="full_name_c2"]').val(textIt.userDetail.full_name_c2);
        jQuery('input[name="contact_number_c2"]').val(textIt.userDetail.contact_number_c2);
        jQuery('input[name="contact_number_2_c2"]').val(textIt.userDetail.contact_number_2_c2);
        jQuery('input[name="email_c2"]').val(textIt.userDetail.email_c2);
        jQuery('input[name="relationship_c2"]').val(textIt.userDetail.relationship_c2);

    } else {
        window.console.log('no userDetail stored');
    }
});

