define(['jquery', 'underscore', 'promise'],
function($, _, Promise) {
    'use strict';

    var instance;

    if (instance) {
        return instance;
    }

    /**
     * Region collection
     *
     * @singleton
     */
    var RegionCollection = function() {
        this.endpoint = '/countries?include=regions';
        this.collection = {};
        this.fetchPromise = null;
    };

    /**
     * Get the Region collection.
     *
     * @return {Promise} Map of countries and their regions.
     */
    RegionCollection.prototype.get = function() {
        if (_.keys(this.collection).length) {
            return Promise.resolve(this.collection);
        }

        if (this.fetchPromise) {
            return this.fetchPromise;
        }

        this.fetchPromise = new Promise(function(resolve, reject) {
            $.ajax(this.endpoint, {
                type: 'GET',
                contentType: 'application/json'
            }).done(function(response) {
                var regionCollection = this.merge(response.data.countries).get();

                resolve(regionCollection);
            }.bind(this)).fail(function(error) {
                reject(new Error('! [Region Collection] ' + error.status + ': ' + error.statusText));
            });
        }.bind(this));

        return this.fetchPromise;
    };

    /**
     * Get all countries.
     *
     * @return {Promise} Array of countries.
     */
    RegionCollection.prototype.getCountries = function() {
        return this.get().then(function(countries) {
            return _.keys(countries);
        });
    };

    /**
     * Get all regions.
     *
     * @return {Promise} Array of regions.
     */
    RegionCollection.prototype.getRegions = function() {
        return this.get().then(function(countries) {
            var regions = [];
            _.each(countries, function(regs) {
                regions = regions.concat(regs);
            });
            return regions;
        });
    };

    /**
     * Add a country with regions to our collection
     *
     * @param  {String} country   Country name.
     * @param  {Array}  regions   Array of this country's regions.
     * @return {Object}           Region collection instance
     */
    RegionCollection.prototype.add = function(country, regions) {
        this.collection[country] = regions;
        return this;
    };

    /**
     * Merge an array of regions with our current region collection
     *
     * @param  {Object} countries  A map of countries and their regions.
     * @return {Object}            Region collection instance
     */
    RegionCollection.prototype.merge = function(countries) {
        _.each(countries, function(regions, country) {
            this.add(country, regions);
        }.bind(this));
        return this;
    };

    instance = new RegionCollection();

    return instance;
});
