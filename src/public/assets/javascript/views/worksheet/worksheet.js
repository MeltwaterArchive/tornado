define(['jquery', 'underscore', 'mustache', 'router', 'services/analyzer', 'collections/workbook', 'views/base', 'views/chart/chart', 'views/worksheet/worksheet-pagination'],
function($, _, Mustache, Router, analyzer, WorkbookCollection, View, ChartView, Pagination) {
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

            $(this.el).on('click.worksheet', this.measureEl, function(ev) {
                ev.preventDefault();

                _this.onMeasureClick($(this));
            });

            $(this.el).on('click.worksheet', this.displayModeEl, function(ev) {
                ev.preventDefault();

                // Ignore click if active
                if ($(this).hasClass(_this.worksheetActionButtonActiveClass) ||
                    $(_this.contentEl).find('[data-blocker="block"]').length > 0) {
                    return;
                }

                _this.setDisplayMode($(this).attr(_this.displayModeAttribute));
            });

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
            var measure = $measure.attr(this.measureAttribute);

            // Don't allow clicking on active measurements again
            if ($measure.hasClass(this.worksheetActionActiveClass)) {
                return false;
            }

            $(this.measureActionEl).removeClass(this.worksheetActionActiveClass);
            $measure.addClass(this.worksheetActionActiveClass);

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
            var chartView = new ChartView({
                charts: this.pagination.paginateCharts(this.data.charts.filter(function (chart) {
                    // only render charts applicable to this worksheet
                    return chart.worksheet_id === this.data.worksheet.id;
                }.bind(this))),
                projectId: this.data.workbook.project_id,
                hasBaseline: (this.data.worksheet.baseline_dataset_id || this.data.worksheet.secondary_recording_id) ? true : false,
                worksheetId: this.data.worksheet.id
            });

            chartView.render(this.measure, worksheetOptions.sort, this.displayMode);

            return this;
        },

        setDisplayMode: function(mode) {
            var _this = this;

            // Make sure only these two supported
            this.displayMode = (mode === 'single') ? mode : 'multiple';
            this.pagination.options.perPage = (this.displayMode === 'single') ? 1 : 10;

            // Update active class on appropriate elements
            $(this.displayModeEl).each(function() {
                if ($(this).attr(_this.displayModeAttribute) === _this.displayMode) {
                    $(this).addClass(_this.worksheetActionButtonActiveClass);
                } else {
                    $(this).removeClass(_this.worksheetActionButtonActiveClass);
                }
            });

            // Set the attribute on the worksheet (so CSS can be adjusted)
            $(this.displayModeActiveEl).attr(this.displayModeActiveAttribute, this.displayMode);

            // On mode change always go back to page 1
            this.pagination.displayPage(1, true);
        },

        renderFooter: function() {
            var template = $(this.footerTemplate).html();
            var data = this.data.worksheet;

            if (Date.prototype.toLocaleTimeString) {
                if (data.created_at) {
                    var date = new Date(data.created_at * 1000);
                    data.created = date.toLocaleTimeString() + ' ' + date.toLocaleDateString();
                }

                if (data.updated_at) {
                    var date = new Date(data.updated_at * 1000);
                    data.updated = date.toLocaleTimeString() + ' ' + date.toLocaleDateString();
                }
            }

            template = Mustache.render(template, data);

            $(this.footerEl).html(template);

            return this;
        },

        filtersInfo: function(filters) {
            var sets = [];

            _.each(['country', 'region', 'gender', 'age'], function(key) {
                if (!_.isArray(filters[key]) || !filters[key].length) {
                    return;
                }

                var values = _.map(filters[key], _.capitalize);
                var lastValue = values.length > 1 ? values.pop() : null;
                var str = values.join(', ');
                sets.push(lastValue ? str + ' and ' + lastValue : str);
            });

            if (filters.keywords && filters.keywords.length) {
                sets.push('that contains any of the words "' + filters.keywords.join('", "') + '"');
            }

            if (filters.links && filters.links.length) {
                sets.push('linking to "' + filters.links.join('", "') + '"');
            }

            var filtersInfo = sets.join(', ');

            if (filters.csdl && filters.csdl.length) {
                filtersInfo += (filtersInfo.length) ? ' and' : '';
                filtersInfo += ' custom CSDL';
            }

            var start = filters.start ? new Date(filters.start * 1000) : null;
            var end = filters.end ? new Date(filters.end * 1000) : null;
            var startDate = start ? start.toLocaleDateString() + ' ' + start.toLocaleTimeString() : null;
            var endDate = end ? end.toLocaleDateString() + ' ' + start.toLocaleTimeString() : null;

            if (start && end) {
                filtersInfo += ' posted between ' + startDate + ' and ' + endDate;
            } else if (start) {
                filtersInfo += ' posted since ' + startDate;
            } else if (end) {
                filtersInfo += ' posted before ' + endDate;
            }

            return filtersInfo.length ? _.capitalize(filtersInfo.trim()) + '.' : false;
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

            var filtersInfo = this.filtersInfo(worksheet.secondary_recording_filters);

            if (filtersInfo) {
                comparisonInfo += ' (' + filtersInfo.substring(0, filtersInfo.length - 1) + ')';
            }

            return comparisonInfo + '.';
        },

        redraw: _.debounce(function() {
            this.render();
        }, 250),

        render: function() {
            var template = Mustache.render($(this.template).html(), {
                worksheet: this.data.worksheet,
                filters: this.filtersInfo(this.data.worksheet.filters),
                comparison: this.comparisonInfo(this.data.worksheet)
            });

            this.pagination = new Pagination({
                chartLength: this.data.charts.length
            });

            $(this.el).html(template);

            $(this.displayModeActiveEl).attr(this.displayModeActiveAttribute, '');
            //this.menu.show();

            this.pagination.render();

            // if it's the only chart change the display mode, this needs to 
            // happen before it's rendered
            if (this.data.charts.length === 1) {
                this.setDisplayMode('single');
            }

            this
                .setOutliers()
                .renderCharts()
                .renderFooter()
                .finalizeView();

        }
    });

    return WorksheetView;
});
