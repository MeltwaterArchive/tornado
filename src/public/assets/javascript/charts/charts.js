define(function(require) {
    'use strict';

    return {
        tornado: require('./types/Tornado'),
        histogram: require('./types/Histogram'),
        timeseries: require('./types/TimeSeries')
    };
});
