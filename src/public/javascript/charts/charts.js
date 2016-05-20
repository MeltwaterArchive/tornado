define([
	'charts/types/Tornado',
	'charts/types/Histogram',
	'charts/types/TimeSeries'
], function(Tornado, Histogram, TimeSeries) {
    'use strict';

    return {
        tornado: Tornado,
        histogram: Histogram,
        timeseries: TimeSeries
    };
});
