define([
        'jquery',
        'underscore',
        'mustache',
        'plugins/scrollfoo',
        'hbs!templates/filters/filters-sidebar',
        'views/filters/filters-options',
        'views/base',
        'models/EventTrackerModel'
    ],
    function ($, _, Mustache, ScrollFoo, FiltersSideBarTpl, FiltersOptionsView, View, ETModel) {
        'use strict';

    /**
     * Renders the filters template
     *
     * @param {data} data Worksheet model
     */
    var FiltersSidebarView = View.extend({
        el: '[data-tornado-view="page-sidebar"]',
        template: FiltersSideBarTpl,

        filtersOptionsView: null,

        filtersApplyButton: '[data-filters-apply-button]',

        onSubmitFiltersCallback: $.noop,

        // ScrollFoo instance
        scrollfoo: null,

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.filters', this.filtersApplyButton, function(ev) {
                ev.preventDefault();

                _this.submitFilters();
            });

            $(this.el).on('change.filters', '.filters-select', function(ev) {
                // prevent errors from being called to early
                if (_this.scrollfoo) {
                    _this.scrollfoo.doCalculate();
                }
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.filters');

            return this;
        },

        submitFilters: function() {
            var filters = this.filtersOptionsView.getValues();
            ETModel.record('Appying filters', filters);
            this.onSubmitFiltersCallback(filters);
        },

        onSubmitFilters: function(callback) {
            this.onSubmitFiltersCallback = callback;
        },

        initScrollbar: function() {
            // Adding a small delay to allow the
            // csdl editor to be initialized first
            setTimeout(function() {
                this.scrollfoo = new ScrollFoo({
                    parentEl: '.scrollfoo__parent--filters',
                    scrollerEl: '.scrollfoo__scroller--filters',
                    visibleParentHeight: function() {
                        return window.innerHeight - $('.scrollfoo__parent--filters').offset().top;
                    },
                    realParentHeight: function() {
                        return $('.scrollfoo__parent--filters').outerHeight();
                    }
                });
            }.bind(this), 10);

            return this;
        },

        render: function () {
            $(this.el).html(this.template(this.data.worksheet));

            // Flatten the filters data
            var filters = this.data.worksheet.filters;
            filters.start = this.data.worksheet.start;
            filters.end = this.data.worksheet.end;

            this.filtersOptionsView = new FiltersOptionsView({
                analysis: this.data.worksheet.analysis_type,
                recording: this.data.recording,
                filters: filters,
                timeSpan: this.data.worksheet.span,
                timeInterval: this.data.worksheet.interval,
                targets: this.data.targets
            });

            this.filtersOptionsView.render();

            //this.menu.stopSpinner();

            this
                .initScrollbar()
                .finalizeView();
        }
    });

    return FiltersSidebarView;
});
