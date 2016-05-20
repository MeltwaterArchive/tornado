define(['jquery', 'underscore', 'mustache', 'selectize', 'plugins/scrollfoo', 'views/filters/filters-options', 'views/base'],
function($, _, Mustache, selectize, ScrollFoo, FiltersOptionsView, View) {
    'use strict';

    /**
     * Renders the comparison template
     *
     * @param {data} data
     */
    var ComparisonSidebarView = View.extend({
        el: '[data-tornado-view="page-sidebar"]',
        template: '[data-tornado-template="comparison-sidebar"]',
        itemTemplate: '[data-tornado-template="comparison-sidebar-item"]',

        filtersOptionsView: null,

        comparisonApplyButton: '[data-comparison-apply-button]',
        onSubmitComparisonCallback: $.noop,

        comparisonMethodEl: '[name="comparison_method"]',
        comparisonMethodSelect: null,
        comparisonOptionsActiveClass: 'comparison-options--active',

        recordingEl: '[data-comparison-data-list="recordings"]',
        datasetEl: '[data-comparison-data-list="datasets"]',
        recordingSelect: null,
        datasetSelect: null,

        scrollfoo: null,

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.comparison', this.comparisonApplyButton, function(ev) {
                ev.preventDefault();

                _this.applyComparison();
            });

            $(this.el).on('change.comparison', this.comparisonMethodEl, function() {
                _this.changeMethod($(this).val());
            });

            $(this.el).on('change.comparison', '.filters-select', function() {
                // prevent errors from being called too early
                if (_this.scrollfoo) {
                    _this.scrollfoo.doCalculate();
                }
            });

            $(this.el).on('change.comparison', this.recordingEl, function() {
                var recordingId = $(this).val();
                var recording = _.find(_this.data.recordings, {id: recordingId});
                _this.filtersOptionsView.data.recording = recording;
                _this.filtersOptionsView.render();
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.comparison');

            return this;
        },

        changeMethod: function(method) {
            $(this.el).find('[data-comparison-options]').removeClass(this.comparisonOptionsActiveClass)
                .filter('[data-comparison-options="' + method + '"]').addClass(this.comparisonOptionsActiveClass);

            // need to recalculate the scroller
            if (this.scrollfoo) {
                this.scrollfoo.doCalculate();
            }
        },

        /**
         * Called when hitting the apply button
         */
        applyComparison: function() {
            var method = $(this.comparisonMethodSelect).val();

            switch (method) {
                case 'none':
                    this.submitComparison('compare', null, null);
                break;

                case 'dataset':
                    var datasetId = $(this.datasetEl).val() || 0;
                    this.submitComparison('baseline', null, parseInt(datasetId, 10));
                break;

                case 'recording':
                    var recordingId = $(this.recordingEl).val() || 0;
                    var mode = $(this.el).find('[name="comparison"]:checked').val();
                    var filterValues = this.filtersOptionsView.getValues();
                    var filter = {
                        keywords: filterValues.keywords || [],
                        links: filterValues.links || [],
                        start: filterValues.start || null,
                        end: filterValues.end || null,
                        gender: filterValues.gender || [],
                        country: filterValues.country || [],
                        region: filterValues.region || [],
                        age: filterValues.age || [],
                        csdl: filterValues.csdl || ''
                    };
                    this.submitComparison(mode, parseInt(recordingId, 10), null, filter);
                break;
            }
        },

        onSubmitComparison: function(callback) {
            this.onSubmitComparisonCallback = callback;
        },

        submitComparison: function(comparison, recordingId, datasetId, filter) {
            this.onSubmitComparisonCallback(comparison, recordingId, datasetId, filter);
        },

        initScrollbar: function() {
            // Adding a small delay to allow the
            // csdl editor to be initialized first
            setTimeout(function() {
                this.scrollfoo = new ScrollFoo({
                    parentEl: '.scrollfoo__parent--comparison',
                    scrollerEl: '.scrollfoo__scroller--comparison',
                    visibleParentHeight: function() {
                        return window.innerHeight - $('.scrollfoo__parent--comparison').offset().top;
                    },
                    realParentHeight: function() {
                        return $('.scrollfoo__parent--comparison').outerHeight();
                    }
                });
            }.bind(this), 10);

            return this;
        },

        renderRecordings: function(template) {
            var recordingsHtml = '';

            _.each(this.data.recordings, function(recording) {
                if (this.data.disabled_recording !== recording.id) {
                    recordingsHtml += Mustache.render(template, {
                        id: recording.id,
                        name: recording.name,
                        selected: (parseInt(this.data.selected_recording, 10) === parseInt(recording.id, 10))
                    });
                }
            }.bind(this));

            $(this.recordingEl).append(recordingsHtml);

            return this;
        },

        renderDatasets: function(template) {
            var datasetsHtml = '';

            _.each(this.data.datasets, function(dataset) {
                datasetsHtml += Mustache.render(template, {
                    id: dataset.id,
                    name: dataset.name,
                    selected: (parseInt(this.data.selected_dataset, 10) === parseInt(dataset.id, 10))
                });
            }.bind(this));

            $(this.datasetEl).append(datasetsHtml);

            return this;
        },

        /**
         * Choosing the right options when rendering the view
         *
         * @return {Object} View instance
         */
        selectOptions: function() {
            var method = 'none';
            if (this.data.selected_recording) {
                method = 'recording';
            } else if (this.data.selected_dataset) {
                method = 'dataset';
            }

            $(this.comparisonMethodEl).find('[value="' + method + '"]').prop('selected', true);
            this.changeMethod(method);

            $(this.el).find('[name="comparison"][value="' + this.data.comparison + '"]').prop('checked', true);
            return this;
        },

        render: function() {
            var template = $(this.template).html();
            var itemTemplate = $(this.itemTemplate).html();

            template = Mustache.render(template, this.data);
            $(this.el).html(template);

            var filters = _.extend({}, {
                keywords: [],
                links: [],
                start: null,
                end: null,
                gender: [],
                country: [],
                region: [],
                age: [],
                csdl: ''
            }, this.data.secondary_recording_filters);

            this.filtersOptionsView = new FiltersOptionsView({
                analysis: this.data.analysis_type,
                recording: this.data.recording,
                filters: filters,
                timeSpan: this.data.span,
                timeInterval: this.data.interval,
                targets: this.data.targets
            });

            this.filtersOptionsView.render();

            this
                .renderRecordings(itemTemplate)
                .renderDatasets(itemTemplate)
                .selectOptions();

            this.comparisonMethodSelect = $(this.comparisonMethodEl).selectize();

            this.recordingSelect = $(this.recordingEl).selectize({
                allowEmptyOption: true
            });

            this.datasetSelect = $(this.datasetEl).selectize({
                allowEmptyOption: true
            });

            this
                .initScrollbar()
                .finalizeView();

            return this;
        }
    });

    return ComparisonSidebarView;
});
