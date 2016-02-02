/*global require: false, define: false, window: false, requirejs: true, toString: true */
define(['d3', 'charts/base'],
function(d3, Trace) {
    'use strict';

    /**
     * # Time Series
     * Renders a line graph.
     *
     * ## Usage
     * `new Trace.timeseries(options)`
     *
     * ## Options
     * - `showx`: Show or hide the X axis, defaults to `true`
     * - `showy`: Show or hide the Y axis, defaults to `true`
     * - `showpoints`: Show the points on the line
     * - `interpolate`: Type of line interpolation (whether it's curved or straight) see https://github.com/mbostock/d3/wiki/SVG-Shapes#line_interpolate
     * - `brush`: This will enable drag selections of the line chart. This is a function which accepts a single element of the extent, the x0 and x1 positions of the brush box.
     *
     * @class  TimeSeries
     * @constructor
     *
     * @param {[type]} options [description]
     */
    var TimeSeries = function(options) {
        Trace.call(this);

        this._extend(this.options, {
            showx: true,
            showy: true,
            showpoints: false,
            interpolate: 'linear',
            gridlines: true,
            brush: true,
            xTickFormat: d3.time.format('%Y-%m-%d %H:%M')
        }, options);

        this.lines = {};
        this.areas = {};
        this.linePaths = {};
        this.areaPaths = {};
        this.points = {};
        this.series = [];
        this.legend = [];

        this._build();
    };

    /**
     * Extends the Trace base library
     * @extends {Trace}
     * @type {Trace}
     */
    TimeSeries.prototype = Object.create(Trace.prototype);
    TimeSeries.prototype.constructor = TimeSeries;

    /**
     * Calculate the domains/ranges for the line chart
     *
     * We use the getExtremes method to find the max and min for each of the dimensions, check
     * to see if its a date using `toString` if it is use a time scale otherwise use a linear
     * scale.
     *
     * @private
     */
    TimeSeries.prototype._calculate = function() {
        var maxX = this._getExtremes(this.options.data, 0, 'max');
        var minX = this._getExtremes(this.options.data, 0, 'min');
        var minY = this._getExtremes(this.options.data, 1, 'min');
        var maxY = this._getExtremes(this.options.data, 1, 'max');
        var margin = this.options.margin;
        var height = this.options.height;
        var width = this.options.width;

        minY = toString.call(minY) === '[object Date]' ? minY : minY > 0 ? 0 : minY;

        this.xfunc = toString.call(minX) === '[object Date]' ? d3.time.scale() : d3.scale.linear();
        this.xfunc.domain([minX, maxX]).range([0, width - margin[1] - margin[3]]);

        this.yfunc = toString.call(maxY) === '[object Date]' ? d3.time.scale() : d3.scale.linear();
        this.yfunc.domain([minY, maxY]).range([height - margin[0] - margin[2], 0]);
    };

    /**
     * Tick the graph when we get new data.
     *
     * We also have to tick the axis. If we have points, those need to be moved as well
     *
     * @private
     */
    TimeSeries.prototype._tick = function() {
        // recalculate everything
        this._calculate();

        Object.keys(this.linePaths).forEach(function(path) {
            this.linePaths[path].transition()
                .duration(100)
                .ease('linear')
                .attr('d', this.lines[path](this.options.data[path]));

            this.areaPaths[path].transition()
                .duration(100)
                .ease('linear')
                .attr('d', this.areas[path](this.options.data[path]));

            if (this.options.showpoints) {
                this.points[path].data(this.options.data[path])
                    .transition()
                    .duration(100)
                    .ease('linear')
                    .attr('cx', function(d, i) { return this.xfunc(d[0]); }.bind(this))
                    .attr('cy', function(d, i) { return this.yfunc(d[1]); }.bind(this));
            }

        }.bind(this));

        Trace.prototype._tick.call(this);
    };

    /**
     * Draw the chart. We draw each line and each axis
     *
     * @private
     */
    TimeSeries.prototype._draw = function() {
        var margin = this.options.margin;
        var height = this.options.height;
        var width = this.options.width;

        // build the SVG wrapper
        this.chart = d3.select(this.options.div)
            .append('svg')
            .attr('class', 'trace-linegraph')
            .attr('height', height)
            .attr('width', width)
            .attr('viewbox', '0 0 ' + width + ' ' + height)
            .attr('perserveAspectRatio', 'xMinYMid')
            .append('g')
            .attr('transform', 'translate(' + margin[3] + ',' + (margin[0]) + ')');

        // for each series build in the line
        this.series.forEach(function(s) {
            this.lines[s] = d3.svg.line()
                .x(function(value) {
                return value[0] ? this.xfunc(value[0]) : this.xfunc(0);
            }.bind(this))
            .y(function(value) {
                return value[1] ? this.yfunc(value[1]) : this.yfunc(0);
            }.bind(this))
            .interpolate(this.options.interpolate);

            this.areas[s] = d3.svg.area()
                .x(function(d) { return this.xfunc(d[0]); }.bind(this))
                .y0(height - margin[0] - margin[2])
                .y1(function(d) { return this.yfunc(d[1]); }.bind(this));
        }.bind(this));

        // now draw each of the lines
        Object.keys(this.lines).forEach(function(series, i) {
            var color = this.colors(i);

            this.linePaths[series] = this.chart.append('path')
                .attr('d', this.lines[series](this.options.data[series]))
                .attr('class', 'trace-' + series)
                .attr('stroke', color)
                .attr('stroke-width', '2px')
                .attr('fill', 'none');

            this.areaPaths[series] = this.chart.append('path')
                .datum(this.options.data[series])
                .attr('class', 'area')
                .attr('d', this.areas[series])
                .attr('fill', color)
                .attr('opacity', 0.2);

            // draw the points
            if (this.options.showpoints) {
                this.points[series] = this.chart.selectAll('.point')
                    .data(this.options.data[series])
                    .enter().append('circle')
                    .attr('fill', color)
                    .attr('class', 'trace-' + series)
                    .attr('cx', function(d, i) { return this.xfunc(d[0]); }.bind(this))
                    .attr('cy', function(d, i) { return this.yfunc(d[1]); }.bind(this))
                    .attr('r', function(d, i) { return 3; })
                    .on('mouseover', this._mouseover.bind(this))
                    .on('mouseout', this._mouseout.bind(this));
            }
        }.bind(this));

        // optional selecting of an area of the chart
        if (this.options.brush) {
            var brush = d3.svg.brush().x(this.xfunc);
            var gbrush;
            var mask;
            var extent;

            gbrush = this.chart.append('g')
                .attr('class', 'brush')
                .attr('fill', 'rgba(60,183,255, 0.29)')
                .call(brush)
                .call(brush.event);

            gbrush.selectAll('rect')
                .attr('height', this.options.height - this.options.margin[0] - this.options.margin[2]);

            mask = gbrush.selectAll('.extent')
                .attr('data-context-menu', 'explore:Explore in new worksheet');

            // Prevent d3 from intercepting the right clicks
            mask.on('mousedown', function() {
                if (d3.event.button === 2) {
                    d3.event.stopImmediatePropagation();
                }
            });

            brush.on('brushend', function() {
                if (!d3.event.sourceEvent) {
                    return;
                }

                var data = {};
                var exploreName;
                var _getTimestamp = function(date) {
                    return Math.round(new Date(date).getTime() / 1000);
                };

                extent = brush.extent();
                exploreName = extent[0] + ' - ' + extent[1];

                data[exploreName] = {};
                data[exploreName].explore = null;
                data[exploreName].start = _getTimestamp(extent[0]);
                data[exploreName].end = _getTimestamp(extent[1]);

                mask
                    .attr('data-explore', function() {
                        return JSON.stringify(data);
                    });
            }.bind(this));
        }
    };

    /**
     * Build the line graph
     *
     * @private
     *
     * @return {[type]} [description]
     */
    TimeSeries.prototype._build = function() {
        var newData = {};

        for (var i in this.options.data) {
            newData[i] = this.options.data[i].map(function(v) {
                v[0] = new Date(v[0] * 1000);

                return v;
            });
        }

        this.options.data = newData;
        this.series = Object.keys(this.options.data);
        this._calculate();
        this._draw();

        // call the parent method
        Trace.prototype._build.call(this);
    };

    return TimeSeries;
});
