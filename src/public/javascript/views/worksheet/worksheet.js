define([
    'jquery',
    'underscore',
    'mustache',
    'router',
    'moment',
    'selectize',
    'services/analyzer',
    'collections/workbook',
    'hbs!templates/worksheet/worksheet',
    'views/base',
    'views/chart/chart',
    'views/sample/sample',
    'views/worksheet/filter-builder',
    'views/worksheet/worksheet-pagination'
], function (
    $,
    _,
    Mustache,
    Router,
    moment,
    selectize,
    analyzer,
    WorkbookCollection,
    WorksheetTpl,
    View,
    ChartView,
    SampleView,
    FilterBuilder,
    Pagination
) {
    'use strict';

    /**
     * Renders the worksheet master template
     *
     * @param {data} data Worksheet model
     */
    var WorksheetView = View.extend({
        el: '[data-tornado-view="page-header"]',
        contentEl: '[data-tornado-view="page-content"]',
        footerEl: '[data-tornado-view="page-footer"]',

        template: '[data-tornado-template="worksheet"]',
        footerTemplate: '[data-tornado-template="worksheet-footer"]',

        hintToggleEl: '[data-worksheet-hint-toggle]',
        hintToggleAttribute: 'data-worksheet-hint-toggle',
        hintToggleActiveClass: 'worksheet__hint-button--active',
        hintEl: '[data-worksheet-hint]',
        hintAttribute: 'data-worksheet-hint',
        hintActiveClass: 'worksheet__hint--active',

        displayMode: 'multiple',
        displayModeEl: '[data-change-display-mode]',
        displayModeAttribute: 'data-change-display-mode',
        displayModeActiveEl: '[data-tornado-view="page-content"]',
        displayModeActiveAttribute: 'data-display-mode',

        outliersEl: '[data-show-outliers]',
        outliersActiveAttribute: 'data-show-outliers',

        measure: 'unique_authors',
        measureEl: '[data-worksheet-measure]',
        measureAttribute: 'data-worksheet-measure',
        measureActionEl: '.worksheet__actions--measures .worksheet__action',

        worksheetActionLinkEl: '.worksheet__action__link',
        worksheetActionActiveClass: 'worksheet__action--active',
        worksheetActionButtonActiveClass: 'worksheet__action__button--active',
        worksheetActionButtonRefresh: '[data-worksheet-refresh]',

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.worksheet', this.worksheetActionLinkEl, function() {
                if ($(_this.contentEl).find('[data-blocker="block"]').length > 0) {
                    return false;
                }
            });

            $(this.el).on('changePage.worksheetPagination', this.renderCharts.bind(this));

            $(this.el).on('click.worksheet', this.hintToggleEl, function(ev) {
                ev.preventDefault();

                var hint = $(this).attr(_this.hintToggleAttribute);
                var show = !$(this).hasClass(_this.hintToggleActiveClass);

                _this.toggleHint(hint, show);
            });

            $('#worksheet-measures').selectize({
                render: {
                    option: function (item, escape) {
                        var str =
                        '<div class="option">' +
                            '<div class="title">' + item.text + '</div>' +
                            '<div class="description">' + item.description + '</div>' +
                        '</div>';
                        return str;
                    }
                }
            });
            $('#worksheet-measures').on('change', this.onMeasureClick.bind(this));

            $(this.el).on('click.worksheet', this.displayModeEl, this.setDisplayMode.bind(this));

            $(this.el).on('click.worksheet', this.worksheetActionButtonRefresh, function(ev) {
                ev.preventDefault();

                _this.refreshWorksheetCharts();
            });

            $(document).on('chartdeleted.worksheet', function(ev, chartId) {
                var index = _.findIndex(_this.data.charts, {id: chartId});

                if (index !== -1) {
                    _this.data.charts.splice(index, 1);

                    // if removed a chart from a single view then refresh to show next chart
                    if (_this.displayMode === 'single') {
                        _this.displayPage(_this.currentPage);
                    }
                }
            });

            $(window).on('resize.worksheet', function() {
                if ($('[data-tornado-page="worksheet"]').length > 0) {
                    _this.redraw();
                }
            });

            $(document).on('analyzer.worksheet', function (e, worksheetId) {
                if (worksheetId === this.data.worksheet.id) {
                    this.data.worksheet.updated_at = Math.floor(Date.now()/1000);
                    this.renderFooter();
                }
            }.bind(this));

            this.pagination.bindEvents();
            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.worksheet');
            $(document).off('.worksheet');
            $(window).off('.worksheet');

            this.pagination.unbindEvents();

            return this;
        },

        toggleHint: function(name, show) {
            var _this = this;

            // update button state
            $(this.hintToggleEl).each(function() {
                var $this = $(this);

                if (show && $this.attr(_this.hintToggleAttribute) === name) {
                    $this.addClass(_this.hintToggleActiveClass);
                } else {
                    $this.removeClass(_this.hintToggleActiveClass);
                }
            });

            // update hint state
            $(this.hintEl).each(function() {
                var $this = $(this);

                if (show && $this.attr(_this.hintAttribute) === name) {
                    $this.addClass(_this.hintActiveClass);
                } else {
                    $this.removeClass(_this.hintActiveClass);
                }
            });
        },

        /**
         * Method called when clicking on a measure
         *
         * @param  {jQuert} $measure Measure $element
         */
        onMeasureClick: function($measure) {
            var measure = $('#worksheet-measures').val();

            setTimeout(function() {
                this.setMeasure($measure, measure);
            }.bind(this), 0);
        },

        /**
         * Sets the measure
         *
         * @param {String} measure  The measure name
         */
        setMeasure: function($measure, measure) {
            if (measure !== 'interactions' && measure !== 'unique_authors') {
                throw new Error('! [Worksheet Controller] - invalid measure "' + measure + '"');
            }

            this.measure = measure || 'unique_authors';

            this.renderCharts();
        },

        setOutliers: function(display) {
            var worksheetOptions = WorkbookCollection.getWorksheetOptions(this.data.worksheet.id);
            var displayOutliers = display || worksheetOptions.outliers;
            var newOutliersState = (displayOutliers) ? 'on' : 'off';

            $(this.outliersEl).attr(this.outliersActiveAttribute, newOutliersState);

            return this;
        },

        refreshWorksheetCharts: function() {
            var $worksheetActionButtonRefresh = $(this.worksheetActionButtonRefresh);

            var _clearLoaderAndSpinner = function() {
                this.loader.unload($worksheetActionButtonRefresh);
            }.bind(this);

            var _onSuccess = function(data) {
                this.data.charts = data.collection;
                this.renderCharts();

                _clearLoaderAndSpinner();
            }.bind(this);

            analyzer
                .analyze(this.data.worksheet)
                .then(_onSuccess, _clearLoaderAndSpinner);
        },

        renderCharts: function() {
            var worksheetOptions = WorkbookCollection.getWorksheetOptions(this.data.worksheet.id);
            var comparison = null;

            if (this.data.worksheet.baseline_dataset_id || this.data.worksheet.secondary_recording_id) {
                comparison = this.data.worksheet.baseline_dataset_id ? true : false;
            }


            var chartView = new ChartView({
                charts: this.pagination.paginateCharts(this.data.charts.filter(function (chart) {
                    // only render charts applicable to this worksheet
                    return chart.worksheet_id === this.data.worksheet.id;
                }.bind(this))),
                projectId: this.data.workbook.project_id,
                hasBaseline: (this.data.worksheet.baseline_dataset_id || this.data.worksheet.secondary_recording_id) ? true : false,
                worksheetId: this.data.worksheet.id,
                comparison: comparison
            });

            chartView.render(this.measure, worksheetOptions.sort, this.displayMode);

            return this;
        },

        renderSample: function () {
            var posts = this.data.posts.filter(function (post) {
                // only render posts applicable to this worksheet
                return post.recording_id === this.data.workbook.recording_id;
            }.bind(this));

            if(this.data.worksheet.analysis_type !== 'sample'){
                return this;
            }

            var sampleView = new SampleView({
                posts: posts,
                sample: true
            });

            sampleView.render();
            return this;
        },

        setDisplayMode: function(mode) {
            var _this = this;

            if (typeof mode === 'string') {
                this.displayMode = (mode === 'single') ? mode : 'multiple';
            } else {
                // toggle the display mode
                this.displayMode = this.displayMode === 'single' ? 'multiple' : 'single';
            }

            this.pagination.options.perPage = (this.displayMode === 'single') ? 1 : 10;

            // update the class
            $(this.displayModeEl)
                .removeClass('single')
                .removeClass('multiple');

            if (this.displayMode === 'single') {
                $(this.displayModeEl)
                    .addClass('multiple')
                    .attr('data-tooltip', 'Show multiple charts per page');
            } else {
                $(this.displayModeEl)
                    .addClass('single')
                    .attr('data-tooltip', 'Show one chart per page');
            }

            // Set the attribute on the worksheet (so CSS can be adjusted)
            $(this.displayModeActiveEl).attr(this.displayModeActiveAttribute, this.displayMode);

            // On mode change always go back to page 1
            this.pagination.displayPage(1, true);
        },

        renderFooter: function() {
            var template = $(this.footerTemplate).html();
            var data = this.data.worksheet;

            data.created = moment.unix(data.created_at).format('HH:mm A DD MMMM YYYY');
            data.updated = moment.unix(data.updated_at).format('HH:mm A DD MMMM YYYY');

            template = Mustache.render(template, data);

            $(this.footerEl).html(template);

            return this;
        },

        comparisonInfo: function(worksheet) {
            if (!worksheet.baseline_dataset_id && !worksheet.secondary_recording_id) {
                return false;
            }

            var comparisonInfo = worksheet.comparison === 'baseline' ? 'Baselined' : 'Compared';
            comparisonInfo += ' against ';

            if (worksheet.baseline_dataset_id) {
                comparisonInfo += ' DataSift curated comparison';
                if (this.data.baselineDataset) {
                    comparisonInfo += ' "' + this.data.baselineDataset.name + '"';
                }

                return comparisonInfo + '.';
            }

            comparisonInfo += ' recording';

            if (this.data.secondaryRecording) {
                comparisonInfo += ' "' + this.data.secondaryRecording.name + '"';
            }

            var filtersInfo = FilterBuilder.filtersInfo(worksheet.secondary_recording_filters);

            if (filtersInfo) {
                comparisonInfo += ' (' + filtersInfo.substring(0, filtersInfo.length - 1) + ')';
            }

            return comparisonInfo + '.';
        },

        redraw: _.debounce(function() {
            this.render();
        }, 250),

        render: function() {

            $(this.el).html(WorksheetTpl({
                sample: this.data.worksheet.chart_type === 'sample',
                worksheet: this.data.worksheet,
                filters: FilterBuilder.filtersInfo(this.data.worksheet.filters),
                comparison: this.comparisonInfo(this.data.worksheet)
            }));

            this.pagination = new Pagination({
                chartLength: this.data.charts.length
            });

            $(this.displayModeActiveEl).attr(this.displayModeActiveAttribute, '');
            //this.menu.show();

            this.pagination.render();

            // if it's the only chart change the display mode, this needs to
            // happen before it's rendered
            if (this.data.charts.length === 1) {
                this.setDisplayMode('single');
            }

            this.setOutliers()
                .renderCharts()
                //.renderSample()
                .renderFooter()
                .finalizeView();

        }
    });

    return WorksheetView;
});
