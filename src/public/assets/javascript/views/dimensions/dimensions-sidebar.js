define(['jquery', 'underscore', 'mustache', 'plugins/scrollfoo', 'views/base'],
function($, _, Mustache, ScrollFoo, View) {
    'use strict';

    /**
     * Renders the dimensions sidebar template
     */
    var DimensionsSidebarView = View.extend({
        el: '[data-tornado-view="page-sidebar"]',
        template: '[data-tornado-template="dimensions-sidebar"]',

        // ScrollFoo instance
        scrollfoo: $.noop,

        bindEvents: function() {
            var _this = this;

            $(this.el).on('keyup.dimensions', '.dimensions-search', function(ev) {
                _this.refine($(this).val().toLowerCase());
                _this.scrollfoo.doCalculate();
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.dimensions', '.dimensions-search');

            return this;
        },

        /**
         * Refine the dimensions as we type in the input field
         *
         * @param  {String} value Input value
         */
        refine: function(value) {
            $('.dimensions > .dimension').each(function() {
                var $dimension = $(this);

                if ($dimension.text().toLowerCase().search(value) > -1) {
                    $dimension.show();
                } else {
                    $dimension.hide();
                }
            });
        },

        initializeScrollbar: function() {
            this.scrollfoo = new ScrollFoo({
                parentEl: '.scrollfoo__parent--dimensions',
                scrollerEl: '.scrollfoo__scroller--dimensions',
                visibleParentHeight: function() {
                    return window.innerHeight - $('.scrollfoo__parent--dimensions').offset().top;
                }.bind(this),
                realParentHeight: function() {
                    return $('.scrollfoo__parent--dimensions').outerHeight();
                }
            });
        },

        render: function() {
            var template = $(this.template).html();

            // filter out empty groups
            var groups = _.filter(this.data.dimensionsGroups, function(group) {
                return group.items.length > 0;
            });

            template = Mustache.render(template, {groups: groups});

            $(this.el).html(template);

            //this.menu.stopSpinner();

            setTimeout(function() {
                this.initializeScrollbar();
            }.bind(this), 10);

            this.finalizeView();
        }
    });

    return DimensionsSidebarView;
});
