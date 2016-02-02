define(['jquery', 'mustache', 'views/base'],
function($, Mustache, View) {
    'use strict';

    /**
     * Renders the `no workbooks` template
     */
    var NoWorkbooksView = View.extend({
        el: '[data-tornado-view="page-content"]',
        template: '[data-tornado-template="no-workbooks"]',

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

            this.finalizeView();
        }
    });

    return NoWorkbooksView;
});
