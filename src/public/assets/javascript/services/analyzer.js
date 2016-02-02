define([
    'jquery', 
    'promise', 
    'collections/chart', 
    'services/http/error-formatter'
], function($, Promise, ChartCollection, ErrorFormatter) {
    
    'use strict';

    /**
     * Analyzer API client that performs analysis on the given worksheet.
     *
     * @singleton
     */
    var Analyzer = function () {};

    /**
     * Analyze the given worksheet and return a collection of generated charts.
     *
     * @param  {WorksheetModel} worksheet Worksheet model.
     * @return {Promise}
     */
    Analyzer.prototype.analyze = function(worksheet) {
        var d = new Date(),
            data = {
            worksheet_id: parseInt(worksheet.id, 10),
            dimensions: worksheet.dimensions,
            chart_type: worksheet.chart_type || 'tornado',
            type: worksheet.analysis_type || 'freqDist',
            comparison: worksheet.comparison || 'compare',
            measurement: worksheet.measurement || 'unique_authors'
        };

        // these methods may not be included in the request,
        // but if they are they will be validated
        var optionals = [
            'secondary_recording_id',
            'secondary_recording_filters',
            'baseline_dataset_id',
            'filters',
            'span',
            'interval',
            'start',
            'end'
        ];

        _.each(optionals, function(prop) {
            if (worksheet[prop]) {
                data[prop] = worksheet[prop];
            }
        });

        // special case to deal with the timeframe
        // @todo this should be pulled out into a seperate model somewhere
        if (worksheet.filters.timeframe && worksheet.filters.timeframe !== 'custom') {
            // change the start/end time to suit the timeframe
            switch (worksheet.filters.timeframe) {
                case 'Last 24 Hours': {
                    data.start = Math.floor(d.getTime()/1000) - 3600 * 24;
                    data.end = Math.floor(d.getTime()/1000);
                    break;
                }
                case 'Last 7 Days': {
                    data.start = Math.floor(d.getTime()/1000) - 3600 * 24 * 7;
                    data.end = Math.floor(d.getTime()/1000);
                    break;
                }
                case 'Last 2 Weeks': {
                    data.start = Math.floor(d.getTime()/1000) - 3600 * 24 * 14;
                    data.end = Math.floor(d.getTime()/1000);
                    break;
                }
                case 'Last 4 Weeks': {
                    data.start = Math.floor(d.getTime()/1000) - 3600 * 24 * 28;
                    data.end = Math.floor(d.getTime()/1000);
                    break;
                }
            }
        }

        var numberFields = [
            'secondary_recording_id',
            'baseline_dataset_id',
            'span',
            'start',
            'end'
        ];

        _.each(numberFields, function(prop) {
            if (data[prop] !== undefined) {
                data[prop] = parseInt(data[prop], 10);
            }
        });

        // Trigger analyzer action - no matter if success or not, the only thing matter is user activity
        $(document).trigger('analyzer.project', [{
            workbookId: worksheet.workbook_id
        }]);

        return new Promise(function(resolve, reject) {
            $.ajax('/analyzer', {
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data)
            }).done(function(response) {
                if (response.data.length > 0) {
                    ChartCollection.replaceByWorksheetId(response.data[0].worksheet_id, response.data);
                }

                resolve(ChartCollection);
            }).fail(function(error) {
                ErrorFormatter.format(error, undefined, this.url);
                reject(error);
            });
        });
    };

    return new Analyzer();
});
