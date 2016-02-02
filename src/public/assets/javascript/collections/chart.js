define(['jquery', 'underscore'], function($, _) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    var ChartCollection = function(data) {
        this.collection = [];
    };

    ChartCollection.prototype.get = function() {
        Promise.resolve(this.collection);
    };

    ChartCollection.prototype.getModel = function(chartId) {
        return _.findWhere(this.collection, {
            id: chartId
        });
    };

    ChartCollection.prototype.getByWorksheetId = function(worksheetId) {
        return _.where(this.collection, {
            worksheet_id: worksheetId
        });
    };

    /**
     * Add a chart to our collection
     *
     * @param  {Object} chart   A chart object
     * @return {Object}         Chart collection instance
     */
    ChartCollection.prototype.add = function(chart) {
        this.collection.push(chart);

        return this;
    };

    /**
     * Merge an array of chart with our current chart collection
     *
     * @param  {Array}  chart   An array of chart objects
     * @return {Object}         Chart collection instance
     */
    ChartCollection.prototype.merge = function(charts) {
        this.collection = this.collection.concat(charts);

        return this;
    };

    /**
     * Replaces a set of charts, from a specific worksheetID, with new ones
     *
     * @param  {Number} worksheetId Worksheet ID
     * @param  {Array}  charts      An array of charts
     * @return {Object}             Chart collection instance
     */
    ChartCollection.prototype.replaceByWorksheetId = function(worksheetId, charts) {

        var worksheetCharts = _.where(this.collection, {        
            worksheet_id: worksheetId      
        });

        this.collection = _.difference(this.collection, worksheetCharts);
        this.merge(charts);
        return this;
    };

    /**
     * Remove a single chart from the collection
     * @param  {Number} chartId     Chart id
     * @return {Object}             Chart collection instance
     */
    ChartCollection.prototype.remove = function(chartId) {
        this.collection = _.without(this.collection, this.getModel(chartId));

        return this;
    };

    instance = new ChartCollection();

    return instance;
});
