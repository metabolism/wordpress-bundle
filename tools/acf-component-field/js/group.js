;(function($) {

    // append the overlay mockup
    $("#acf-field-group-locations .inside").append('<div class="acf-component-disabled-overlay"></div>');
    $("#acf-field-group-options .inside").append('<div class="acf-component-disabled-overlay"></div>');

    // put a white overlay when the component checkbox is checked.
    $('#is_acf_component_checkbox').on('change', function() {

        if($(this).is(":checked")) {
            $("#acf-field-group-locations, #acf-field-group-options").addClass('is-acf-component');
        } else {
            $("#acf-field-group-locations, #acf-field-group-options").removeClass('is-acf-component');
        }

    }).trigger('change');

})(jQuery);
