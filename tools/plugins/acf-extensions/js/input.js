;(function($) {

    // take whatever from the repeater
    var args = jQuery.extend({}, acf.fields.repeater);

    // change the type
    args.type = 'component_field';

    // assign the actions to run our custom one
    args.actions['ready'] = 'initialize_component';
    args.actions['append'] = 'initialize_component';

    // custom initialization to replace the data-event attributes
    args.initialize_component = function() {
        this.initialize();

        $('[data-event="add-row"]', this.$el).last().attr('data-event', 'add-component');

        var $repeater_table = this.$el.children('.acf-table').first();
        $('> tbody > .acf-row > .remove a[data-event="add-row"]', $repeater_table).attr('data-event', 'add-component');
        $('> tbody > .acf-row > .remove a[data-event="remove-row"]', $repeater_table).attr('data-event', 'remove-component');
    };

    // repeater rendered first inside component,
    // so we need to be explicit, to prevent double dipping
    // also remove collapes event
    args.events = {
        'click a[data-event="add-component"]'       : '_add',
        'click a[data-event="remove-component"]'    : '_remove',
        'mouseenter td.order'                       : '_mouseenter'
    },

    // and init it
    acf.fields.component_field = acf.field.extend(args);

})(jQuery);
