define(['jquery', 'underscore', 'mustache', 'selectize', 'plugins/scrollfoo', 'views/base'],
function($, _, Mustache, Selectize, ScrollFoo, View) {
    'use strict';

    /**
     * Renders the timespan template
     *
     * @param {data} data Worksheet model
     */
    var TimespanSidebarView = View.extend({
        el: '[data-tornado-view="page-sidebar"]',
        template: '[data-tornado-template="timespan-sidebar"]',

        timespanApplyButton: '[data-timespan-apply-button]',

        spanInputEl: '[data-timespan-span]',
        intervalSelectEl: '[data-timespan-interval]',
        intervalSelect: null,

        onSubmitTimespanCallback: $.noop,

        // ScrollFoo instance
        scrollfoo: null,

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.timespan', this.timespanApplyButton, function(ev) {
                ev.preventDefault();

                _this.submitTimespan();
            });

            $(this.el).on('change.timespan', this.intervalSelectEl, function(ev) {
                _this.adjustSpanInput();
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.timespan');

            return this;
        },

        submitTimespan: function() {
            var interval = this.getIntervalValue();
            var max = this.getMaxSpanForInterval(interval);
            var span = Math.min(this.getSpanValue(), max);

            this.onSubmitTimespanCallback(span, interval);
        },

        onSubmitTimespan: function(callback) {
            this.onSubmitTimespanCallback = callback;
        },

        initIntervalSelect: function() {
            $(this.intervalSelectEl).selectize({
                items: [this.data.worksheet.interval]
            });
            this.intervalSelect = $(this.intervalSelectEl)[0].selectize;
            return this;
        },

        getIntervalValue: function() {
            return this.intervalSelect.getValue();
        },

        getSpanValue: function() {
            return parseInt($(this.spanInputEl).val(), 10);
        },

        /**
         * Adjusts the span input maximum and current value according to the current
         * interval selection.
         */
        adjustSpanInput: function() {
            var interval = this.getIntervalValue();
            var max = this.getMaxSpanForInterval(interval);
            var span = Math.min(this.getSpanValue(), max);

            $(this.spanInputEl).prop('max', max).val(span)

            return this;
        },

        /**
         * Gets a maximum possible span value for the given interval.
         *
         * @param  {String} interval Interval.
         *
         * @return {Number}
         */
        getMaxSpanForInterval: function(interval) {
            var max = 1;
            switch (interval) {
                case 'week':
                    max = 4;
                break;

                case 'day':
                    max = 14;
                break;

                case 'hour':
                    max = 24 * 7 * 2;
                break;

                case 'minute':
                default:
                    max = 60 * 24 * 2;
            }
            return max;
        },

        initScrollbar: function() {
            // Adding a small delay to allow the
            // csdl editor to be initialized first
            setTimeout(function() {
                this.scrollfoo = new ScrollFoo({
                    parentEl: '.scrollfoo__parent--timespan',
                    scrollerEl: '.scrollfoo__scroller--timespan',
                    visibleParentHeight: function() {
                        return window.innerHeight - $('.scrollfoo__parent--timespan').offset().top;
                    },
                    realParentHeight: function() {
                        return $('.scrollfoo__parent--timespan').outerHeight();
                    }
                });
            }.bind(this), 10);

            return this;
        },

        render: function() {
            var template = $(this.template).html();
            template = Mustache.render(template, this.data.worksheet);

            $(this.el).html(template);

            //this.menu.stopSpinner();

            this
                .initIntervalSelect()
                .adjustSpanInput()
                .initScrollbar()
                .finalizeView();
        }
    });

    return TimespanSidebarView;
});
