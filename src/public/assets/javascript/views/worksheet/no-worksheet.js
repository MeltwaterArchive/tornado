define(['jquery', 'mustache', 'views/base'],
function($, Mustache, View) {
    'use strict';

    /**
     * Renders the `no worksheet` template
     */
    var NoWorksheetView = View.extend({
        el: '[data-tornado-view="page-content"]',
        template: '[data-tornado-template="no-worksheet"]',

        bindEvents: function() {
            return this;
        },

        unbindEvents: function() {
            return this;
        },

        render: function() {
            var template = $(this.template).html();
            template = Mustache.render(template);

            $(this.el).html(template);
            this.menu.hide();

            this.finalizeView();
        }
    });

    return NoWorksheetView;
});
