define(['jquery', 'underscore', 'mustache', 'modallica', 'buzzkill', 'collections/chart', 'collections/workbook', 'views/base', 'views/worksheet/create-worksheet', 'charts/charts'],
function($, _, Mustache, Modallica, Buzzkill, ChartCollection, WorkbookCollection, View, CreateWorksheet, Trace) {
    'use strict';

    /**
     * Chart view constructor
     *
     * @param {Object} data    Chart Collection [Array], WorksheetId [Number]
     * @param {Object} options Chart options for Trace
     */
    var ChartView = View.extend({
        // The DOM element where the charts will be rendered
        el: '[data-tornado-view="page-content"]',

        // The template used to populate the charts
        template: '[data-tornado-template="chart"]',

        contextMenuItemEl: '[data-context-menu-item="explore"]',
        exploreDataAttr: 'data-explore',

        chartEl: '.chart-wrapper',
        chartNameEl: '.chart__title',
        chartIdAttribute: 'data-chart-id',

        actionEl: '[data-dropdown-action]',
        actionAttribute: 'data-dropdown-action',

        nameInputEl: '#chart-rename-name',
        nameFormEl: '[data-form="chart-rename"]',
        nameSubmitBtn: '[data-chart-rename-submit]',

        // Defaults for the intiializing the trace instances
        traceConfig: {
            settings: {
                chart: {
                    width: 0,
                    height: 0,
                    //margin: [40, 20, 20, 70],
                    colors: ['#3D5B94', '#3D5B94'],
                    rectpadding: 0.5
                },
                label: {
                    characterLimit: 9
                }
            }
        },

        // Display config (defaults)
        measure: 'unique_authors',
        sortBy: 'label',
        sortOrder: 'asc',
        displayMode: 'multiple',

        bindEvents: function() {
            var _this = this;

            $('body').on('click.chart', this.contextMenuItemEl, function(ev) {
                _this.doExplorationModal();
            });

            $('body').on('click.chart', this.actionEl, function(ev) {
                ev.preventDefault();

                var $el = $(this);
                var $chart = $el.closest(_this.chartEl);
                var action = $el.attr(_this.actionAttribute);

                switch (action) {
                    case 'rename':
                        _this.renameChart($chart);
                    break;

                    case 'delete':
                        _this.deleteChart($chart);
                    break;
                }
            });

            return this;
        },

        unbindEvents: function() {
            $('body').off('.chart');

            return this;
        },

        renameChart: function($chart) {
            var _this = this;
            var id = $chart.attr(this.chartIdAttribute);
            var chart = ChartCollection.getModel(id);

            // When Modallica shows up, listen for clicks on the confirmation button
            $(document).one('chart-rename-modal:ready.modallica', function() {
                $(_this.nameSubmitBtn).click(function(ev) {
                    ev.preventDefault();

                    var name = $(_this.nameInputEl).val();
                    _this.doRenameChart($chart, chart, name);
                });
            });

            Modallica.showFromData({
                title: 'Rename Chart',
                templateName: 'chart-rename-modal',
                chart: chart
            });
        },

        doRenameChart: function($chart, chart, newName) {
            var _this = this;

            $.ajax({
                url: '/api/project/' + this.data.projectId + '/chart/' + chart.id,
                type: 'PUT',
                data: JSON.stringify({
                    name: newName
                }),
                contentType: 'application/json'
            }).done(function(response) {
                chart.name = response.data.chart.name;

                $chart.find(_this.chartNameEl).html(chart.name);

                Modallica.hide();

            }).fail(function(error) {
                if (error.responseJSON) {
                    Buzzkill.form($(_this.nameFormEl), error.responseJSON.meta);
                    return;
                }

                throw new Error('! [Chart view ~ action: PUT] ' + error.status + ': ' + error.statusText);
            });
        },

        deleteChart: function($chart) {
            var id = $chart.attr(this.chartIdAttribute);

            // remove in frontend
            ChartCollection.remove(id);
            $chart.remove();

            // trigger an event so that the chart can also be deleted
            // from the parent worksheet view charts collection
            $(document).trigger('chartdeleted', [id]);

            // and also remove in the backend
            $.ajax({
                url: '/api/project/' + this.data.projectId + '/chart/' + id,
                type: 'DELETE'
            });
        },

        doExplorationModal: function() {
            var $chartBar = this.contextMenu.getOrigin();
            var exploreData = $.parseJSON($chartBar.attr(this.exploreDataAttr));
            var name = Object.keys(exploreData)[0];
            var worksheet = WorkbookCollection.getWorksheetById(this.data.worksheetId);

            CreateWorksheet.renderExplorationModal({
                worksheetId: this.data.worksheetId,
                name: name,
                explore: ('explore' in exploreData[name]) ? exploreData[name].explore : JSON.stringify(exploreData[name]),
                start: exploreData[name].start || worksheet.start,
                end: exploreData[name].end || worksheet.end
            });

            this.contextMenu.hide();
        },

        /**
         * Calculate the width and height of the chart
         */
        doCalculations: function($chart, chartData, chartObject) {
            this.traceConfig.settings.chart.width = $chart.width();

            if (chartObject.type == 'timeseries') {
                this.traceConfig.settings.chart.height = 399;
                this.traceConfig.settings.chart.colors = ['#ACCB94', '#3D5B94'];
            } else {
                var multiply = (this.displayMode === 'single') ? 1.7 : 1;
                this.traceConfig.settings.chart.height = chartData[Object.keys(chartData)[0]].length * 40 * multiply + 70;
            }
        },

        /**
         * Sort the chart data according to current sort settings.
         *
         * @param  {Object} chartData Data to be sorted.
         * @return {Object}           Sorted data.
         */
        sortData: function(chartData, chart) {
            var sortedData = {};

            switch (chart.type) {
                case 'tornado':
                    /* @type {Array} Maps keys of the data series in a new order, e.g. [5,2,3,1,4,0] */
                    var keyOrder = (this.sortBy === 'size')
                        ? this.sortTornadoDataBySize(chartData)
                        : this.sortTornadoDataByLabel(chartData);

                    if (this.sortOrder === 'desc') {
                        keyOrder.reverse();
                    }

                    // now sort the real data
                    _.each(chartData, function(data, key) {
                        // prepare sorted data for this data serie
                        if (sortedData[key] === undefined) {
                            sortedData[key] = [];
                        }

                        // sort the time series according the the new order
                        _.each(keyOrder, function(oldPosition) {
                            sortedData[key].push(data[oldPosition]);
                        });
                    });
                break;

                case 'histogram':
                    _.each(chartData, function(rows, key) {
                        var sortBy = (this.sortBy === 'size') ? 1 : 0;
                        sortedData[key] = _.sortBy(rows, sortBy);

                        if (this.sortOrder === 'desc') {
                            sortedData[key].reverse();
                        }
                    }.bind(this));
                break;

                default:
                    sortedData = chartData;
            }

            return sortedData;
        },

        /**
         * Sorts tornado data by labels and returns a new order for row keys in each data serie.
         *
         * @param  {Object} chartData Chart data.
         * @return {Array}
         */
        sortTornadoDataByLabel: function(chartData) {
            var keyOrder = [];

            _.each(chartData, function(data, key) {
                var labels = _.pluck(data, '0').sort();
                _.each(labels, function(label) {
                    keyOrder.push(_.findIndex(data, {0: label}));
                });
                return false; // we only need first series
            });

            return keyOrder;
        },

        /**
         * Sorts tornado data by row size and returns a new order for row keys in each data serie.
         *
         * @param  {Object} chartData Chart data.
         * @return {Array}
         */
        sortTornadoDataBySize: function(chartData) {
            // collect all sizes
            var sizes = {};

            _.each(chartData, function(data, key) {
                _.each(data, function(row, i) {
                    if (sizes[i] === undefined) {
                        sizes[i] = {
                            key: i,
                            size: 0
                        };
                    }

                    sizes[i].size += Math.abs(row[1]);
                });
            });

            // return the new order
            return _.pluck(_.sortBy(sizes, 'size'), 'key');
        },

        /**
         * Position the labels of the graph
         */
        setLabelsPosition: function($chart) {
            if ($chart.find('.trace-legend .label').length > 1) {
                var $firstLabel = $chart.find('.trace-legend .label:first-child');
                var $lastLabel = $chart.find('.trace-legend .label:last-child');
                var firstLabelLeftOffset = (this.traceConfig.settings.chart.width / 4) + ($firstLabel.width() / 2) + 20;
                var lastLabelLeftOffset = (this.traceConfig.settings.chart.width / 2) + ($lastLabel.width() / 2) + 35;

                $firstLabel.css('left', firstLabelLeftOffset);
                $lastLabel.css('left', lastLabelLeftOffset);

            // Timeseries charts only have 1 label
            } else {
                $chart
                    .find('.trace-legend .label')
                        .css({
                            width: this.traceConfig.settings.chart.width,
                            textAlign: 'center'
                        });
            }
        },

        /**
         * Generate the final config for the chart by
         * merging Trace's defaults with the user ones
         *
         * @return {Object} Trace config
         */
        getFinalTraceConfig: function($chart, chartData) {

            // assumption that the width of a letter is 5 pixels
            var letterWidth = 7,
                labels = [],
                characterLimit,
                minCharacterLimit = 10,
                chartWidth = this.traceConfig.settings.chart.width,
                labelLength,
                labelQuartile,
                config;

            // fetch all the labels out of the data
            Object.keys(chartData).forEach(function (series) {
                return chartData[series].forEach(function (item) {
                    if (labels.indexOf(item[0]) === -1) {
                        labels.push(item[0]);
                    }
                });
            });

            

            // when browsers get more awesome i'll be able to use Arrow functions
            labelLength = labels.map(function (label) {
                return label.length;
            });

            labelQuartile = (labelLength.length % 2 === 0) ? labelLength.slice( (labelLength.length/2) - 1, labelLength.length) : labelLength.slice(Math.ceil(labelLength.length / 2) - 1, labelLength.length);
            // work out the mediun of the quartile
            labelQuartile = (labelQuartile.length % 2 === 0) ? labelQuartile[(labelQuartile.length/2) - 1] + labelQuartile[(labelQuartile.length/2)] / 2 : labelQuartile[Math.floor(labelQuartile.length / 2)];
            // work out the limit, this uses an arbitary value of character pixel width
            characterLimit = Math.floor(Math.min(chartWidth / 3, labelQuartile * letterWidth) / letterWidth);
            // ensure it's not below the min
            characterLimit = characterLimit < minCharacterLimit ? minCharacterLimit : characterLimit;

            config = {
                div: $chart[0],
                data: chartData,

                // tooltip function
                tooltips: function() {
                    var args = arguments[0],
                        tooltip = args.label + ': ' + Math.abs(args.value);

                    if (this.data.hasBaseline) {
                        tooltip += ' (' + Math.abs(args.baseline) + ')';
                    }

                    return tooltip;
                }.bind(this),

                // y tick function
                yTickFormat: function(label) {
                    // forumla based on size of chart
                    // average length of the label
                    if (label.length > characterLimit) {
                        this.setAttribute('data-tooltip', label);
                        return label.substr(0, characterLimit - 3) + '...';
                    }
                    return label;
                },
                margin: [40, 20, 20, characterLimit * letterWidth]
            };

            return $.extend(true, {}, config, this.traceConfig.settings.chart);
        },

        /**
         * Initialize Trace and draw the charts
         */
        tracify: function() {
            _.each(this.data.charts, function(chart) {
                var $chart = $('[data-tornado-chart="' + chart.id + '"]');
                var chartData = $.parseJSON(chart.data);
                var finalTraceConfig;

                chartData = chartData[this.measure];
                chartData = this.sortData(chartData, chart);

                this.doCalculations($chart, chartData, chart);

                finalTraceConfig = this.getFinalTraceConfig($chart, chartData);

                new Trace[chart.type](finalTraceConfig);

                this.setLabelsPosition($chart);
            }.bind(this));

            return this;
        },

        /**
         * Renders the chart elements and appends them to the DOM.
         * Then initializes Trace to draw those charts.
         *
         * @param {String}  measure
         * @param {String}  sortBy
         * @param {String}  sortOrder
         * @param {String}  displayMode
         */
        render: function(measure, sortOrder, displayMode) {
            var chartTemplate = $(this.template).html();
            var chartsTemplate = '';
            this.measure = measure || this.measure;
            this.sortBy = sortOrder.split(':')[0] || this.sortBy;
            this.sortOrder = sortOrder.split(':')[1] || this.sortOrder;
            this.displayMode = displayMode;

            _.each(this.data.charts, function(chart) {
                chartsTemplate += Mustache.render(chartTemplate, chart || {});
            });

            $(this.el).html(chartsTemplate);

            this
                .tracify()
                .finalizeView();
        }
    });

    return ChartView;
});
