define(['d3', 'charts/base'],
function(d3, Trace) {
    'use strict';

    /**
     * # Tornado
     * Renders a bar chart, or a stacked bar chart depending on the number of series supplied
     *
     * This is highly influanced by (NVD3 Multi Bar Horizontal)[https://github.com/novus/nvd3/blob/master/src/models/multiBarHorizontal.js]
     * however our chart will only ever accept positive numbers.
     *
     * ## Usage
     * `new Trace.tornado(options)`
     *
     * ## Options
     * - `showx`: Show or hide the X axis, defaults to `true`
     * - `showy`: Show or hide the Y axis, defualts to `true`
     * - `rectpadding`: The padding the main rect has to appear thinner, compared to the baseline rect
     *
     * @class  BarChart
     * @constructor
     *
     * @param {[type]} options [description]
     */
    var Tornado = function(options) {
        Trace.call(this);

        this._extend(this.options, {
            showx: true,
            showy: true,
            rectpadding: 0.5,
            gridlines: true
        }, options);

        this._build();
    };

    /**
     * Extends the Trace base library
     * @extends {Trace}
     * @type {Trace}
     */
    Tornado.prototype = Object.create(Trace.prototype);
    Tornado.prototype.constructor = Tornado;

    /**
     * Calculate the x and y functions
     *
     * We use the d3.layout.stack() and modify the data to be in the correct format
     *
     * @private
     */
    Tornado.prototype._calculate = function() {
        var margin = this.options.margin;
        var height = this.options.height;
        var width = this.options.width;
        var keys = Object.keys(this.options.data);

        var max = [];
        var min = [];

        for (var i in this.options.data) {
            max[i] = 0;
            min[i] = 0;
            for (var j in this.options.data[i]) {
                max[i] = Math.max(
                    max[i],
                    this.options.data[i][j][1] - this.options.data[i][j][2]
                );
                min[i] = Math.min(
                    min[i],
                    this.options.data[i][j][1] - this.options.data[i][j][2]
                );
            }
        }

        // convert the data into the correct format
        this.mappedData = keys.map(function(k, i) {
            return {
                key: k,
                values: this.options.data[k].map(function(v) {
                    var meta = (v.length > 3) ? v[3] : {};
                    var dist = v[1] - v[2];
                    return {
                        label: v[0],
                        /**
                        * HACK!!!
                        *
                        * Force the value to be negative, this will force the rect to render in the
                        * negative segement, we are then going to make sure the values are always
                        * abs everywhere else.
                        */
                        value: i === 0 ? Math.abs(v[1]) * -1 : Math.abs(v[1]),
                        baseline: i === 0 ? Math.abs(v[2]) * -1 : Math.abs(v[2]),
                        tooltip: ('tooltip' in meta) ? meta.tooltip : false,
                        explore: ('explore' in meta) ? meta.explore : false,
                        baselineClass: (dist == min[k]) ? 'chart__underindexed' : (dist == max[k]) ? 'chart__overindexed' : ''
                    };
                })
            };
        }.bind(this));

        // create a stack layout, define how we are getting our y value
        this.mappedData = d3.layout.stack()
            .offset('zero')
            .values(function(d) { return d.values; })
            .y(function(d) {
                return d.value;
            })
            (this.mappedData);

        // give each item a reference to it's series
        this.mappedData.forEach(function(series, i) {
            series.values.forEach(function(point) {
                point.series = i;
            });
        });

        // determine the values we are going to use for the transform
        this.mappedData[0].values.map(function(d, i) {
            var posBase = 0;
            var negBase = 0;
            var posBaseLineBase = 0;
            var negBaseLineBase = 0;

            this.mappedData.map(function(d) {
                var f = d.values[i];
                f.size = Math.abs(f.y);
                f.bsize = Math.abs(f.baseline);

                if (f.y < 0) {
                    f.y1 = negBase - f.size;
                    f.b1 = negBaseLineBase - f.bsize;
                    negBase = negBase - f.size;
                    negBaseLineBase = negBaseLineBase - f.bsize;
                } else {
                    f.y1 = posBase;
                    f.b1 = posBaseLineBase;
                    posBase = posBase + f.size;
                    posBaseLineBase = posBaseLineBase + f.bsize;
                }
            });
        }.bind(this));

        // flatten the data
        var seriesData = this.mappedData.map(function(d, i) {
            return d.values.map(function(d) {
                return {
                    x: d.label,
                    y: d.value,
                    y0: d.y0,
                    y1: d.y1,
                    b: d.baseline,
                    b0: d.y0,
                    b1: d.b1
                };
            });
        });

        this.xfunc = d3.scale.ordinal().domain(d3.merge(seriesData).map(function(d) {
            return d.x;
        })).rangeRoundBands([0, height - margin[0] - margin[2]], 0.1);

        this.yfunc = d3.scale.linear().domain(d3.extent(d3.merge(seriesData).map(function(d) {
            if (Math.abs(d.y) > Math.abs(d.b)) {
                return d.y > 0 ? d.y1 + d.y : d.y1;
            } else {
                return d.b > 0 ? d.b1 + d.b : d.b1;
            }
        })));

        this.yfunc.range([0, width - margin[1] - margin[3]]);
        this.y0 = d3.scale.linear().domain(this.yfunc.domain()).range([this.yfunc(0), this.yfunc(0)]);

        this.options.tooltips = typeof this.options.tooltips === 'function'
            ? this.options.tooltips
            : function(vals) {
                var tooltip = vals.tooltip;

                if (tooltip == false) {
                    tooltip = vals.label + ': ' + vals.value + ' (' + vals.baseline + ')';
                }

                return tooltip;
            };
    };


    /**
     * Draw the chart
     */
    Tornado.prototype._draw = function() {
        var margin = this.options.margin;
        var height = this.options.height;
        var width = this.options.width;
        var rectPadding = this.options.rectpadding;

        // build the SVG wrapper
        this.chart = d3.select(this.options.div)
            .data([this.mappedData])
            .append('svg')
            .attr('class', 'trace-likert')
            .attr('width', width)
            .attr('height', height)
            .attr('viewbox', '0 0 ' + width + ' ' + height)
            .attr('perserveAspectRatio', 'xMinYMid')
            .append('g')
                .attr('transform', 'translate(' + margin[3] + ',' + (margin[0]) + ')');

        // wrap each of the series into a group
        this.group = this.chart.selectAll('g.trace-likertgroup')
            .data(function(d) { return d; }, function(d, i) { return i; })
        .enter()
            .append('g')
                .attr('class', function(d, i) { return 'trace-likertgroup trace-likertgroup--' + i; })
                .style('fill', function(d, i) { return this.colors(i); }.bind(this));

        // build a baseline rect
        this.baselinerect = this.group.selectAll('rect.y2')
            .data(function(d) {
                return d.values;
            })
        .enter()
            .append('g')
                .attr('transform', function(d, i, j) {
                    return 'translate(' + this.yfunc(d.b1) + ',' + this.xfunc(d.label) + ')';
                }.bind(this))
                .append('rect')
                    .attr('class', function(d) {
                        return 'y2 trace-likert-bar trace-likert-bar--baseline ' + d.baselineClass;
                    })
                    .attr('width', function(d, i) {
                        return Math.abs(this.yfunc(d.baseline + d.y0) - this.yfunc(d.y0));
                    }.bind(this))
                    .attr('height', this.xfunc.rangeBand())
                    .attr('data-tooltip', function(d) {
                        return d.label + ': ' + d.value + ' (' + d.baseline + ')';
                    }.bind(this));

        // build a rect in each
        this.rect = this.group.selectAll('rect.y1')
            .data(function(d) {
                return d.values;
            })
        .enter()
            .append('g')
                .attr('transform', function(d, i, j) {
                    return 'translate(' + this.yfunc(d.y1) + ',' + (this.xfunc(d.label) + (this.xfunc.rangeBand() * rectPadding) / 2) + ')';
                }.bind(this))
                .append('rect')
                    .attr('class', 'y1 trace-likert-bar trace-likert-bar--main')
                    .attr('width', function(d, i) {
                        return Math.abs(this.yfunc(d.value + d.y0) - this.yfunc(d.y0));
                    }.bind(this))
                    .attr('height', this.xfunc.rangeBand() - (this.xfunc.rangeBand() * rectPadding))
                    .attr('data-context-menu', 'explore:Explore in new worksheet')
                    .attr('data-tooltip', function(d) {
                        return d.label + ': ' + d.value + ' (' + d.baseline + ')';
                    }.bind(this))
                    .attr('data-explore', function(d) {
                        return JSON.stringify(d.explore);
                    });

        // draw a vertical line that separates the 2 series
        this.line = this.chart.selectAll('.trace-likert')
            .data([this.mappedData])
        .enter()
            .append('line')
            .attr('y1', 0)
            .attr('y2', height - (margin[0] + margin[1]))
            .attr('x1', this.yfunc(0))
            .attr('x2', this.yfunc(0))
            .style({
                stroke: '#fff', 'stroke-width': 4
            });

        // lets flip the axis so we can use our parents renderer
        var tempy = this.yfunc;
        var tempx = this.xfunc;
        this.yfunc = tempx;
        this.xfunc = tempy;

        // override the tickFormatter to use our function
        var tempxFormatter = this.options.xTickFormat;
        this.options.xTickFormat = function(d) {
            // turn it positive
            d = Math.abs(d);

            return tempxFormatter(d);
        };

        // render the axis and legend
        Trace.prototype._build.call(this);
    };

    /**
     * Build the Tornado chart
     *
     * @private
     */
    Tornado.prototype._build = function() {
        this._calculate();
        this._draw();
    };

    return Tornado;
});
