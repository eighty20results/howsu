jQuery(document).ready(function() {
    "use strict";
    jQuery('input[name=submit]').prop('disabled', true).css('background-color', 'gray');
});
jQuery('#emailInput').keyup(function() {
    "use strict";
    window.console.log(jQuery('#emailInput').val());
    jQuery('#loginInput').val(jQuery('#emailInput').val());
    window.console.log(jQuery('#loginInput').val());
});
jQuery('input[name=user_pass_reEnter]').keyup(function() {
    "use strict";
    var pass = jQuery('input[name=user_pass]').val();
    if(jQuery(this).val() !== pass) {
        jQuery(this).css('background-color', '#bc1822');
        jQuery('input[name=submit]').prop('disabled', true).css('background-color', 'gray');
    } else {
        jQuery(this).css('background-color', 'white');
        jQuery('input[name=submit]').prop('disabled', false).css('background-color', 'red');
    }
});